<?php

namespace App\Modules\Payment\Services;

use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Payment\Models\Payment;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * MoMo captureWallet. Chốt đơn CHỈ dựa trên IPN đã xác thực chữ ký.
 * MOMO_FAKE=true: giả lập cổng MoMo (trang thanh toán giả do API phục vụ) để chạy local không cần merchant.
 */
class MomoService
{
    private function cfg(string $k): string
    {
        return (string) config("shoe.momo.$k");
    }

    public function sign(array $fields, array $keys): string
    {
        sort($keys);
        $raw = collect($keys)->map(fn ($k) => "$k=".($fields[$k] ?? ''))->implode('&');

        return hash_hmac('sha256', $raw, $this->cfg('secret_key'));
    }

    /** Tạo giao dịch cho đơn chưa thanh toán → pay_url, qr_code_url, deeplink. */
    public function create(Order $order): array
    {
        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid' || in_array($order->status, ['cancelled', 'returned'], true)) {
            throw ApiException::conflict('NOT_PAYABLE', 'Đơn hàng không thể thanh toán MoMo');
        }
        $payment = $order->payments()->where('method', 'momo')->orderByDesc('id')->first();
        $attempt = $order->payments()->where('method', 'momo')->count();
        if ($payment && $payment->status !== 'pending') {
            $payment = null;
        }
        $momoOrderId = "{$order->code}-{$attempt}";
        $requestId = $momoOrderId.'-'.bin2hex(random_bytes(4));
        $minutes = (int) Settings::get('order.momo_expire_minutes', 15);

        $payment = $payment ?? new Payment(['order_id' => $order->id, 'method' => 'momo', 'provider' => 'momo']);
        $payment->fill([
            'amount' => $order->grand_total, 'status' => 'pending', 'momo_order_id' => $momoOrderId, 'momo_request_id' => $requestId,
            'expires_at' => now()->addMinutes($minutes),
        ])->save();
        $order->update(['payment_status' => 'pending']);

        $redirect = config('shoe.frontends.storefront').'/dat-hang-thanh-cong/'.$order->code.'?phone='.urlencode((string) $order->phone);
        $ipn = rtrim(config('app.url'), '/').'/api/v1/payments/momo/ipn';

        if (config('shoe.momo.fake')) {
            $pay = rtrim(config('app.url'), '/')."/api/v1/payments/momo/fake/$momoOrderId";

            return ['pay_url' => $pay, 'qr_code_url' => $pay, 'deeplink' => $pay, 'expires_at' => $payment->expires_at, 'fake' => true];
        }

        $body = [
            'partnerCode' => $this->cfg('partner_code'), 'accessKey' => $this->cfg('access_key'), 'requestId' => $requestId,
            'amount' => (string) $order->grand_total, 'orderId' => $momoOrderId, 'orderInfo' => "Thanh toan don hang {$order->code}",
            'redirectUrl' => $redirect, 'ipnUrl' => $ipn, 'requestType' => 'captureWallet', 'extraData' => '', 'lang' => 'vi',
        ];
        $body['signature'] = $this->sign($body, ['accessKey', 'amount', 'extraData', 'ipnUrl', 'orderId', 'orderInfo', 'partnerCode', 'redirectUrl', 'requestId', 'requestType']);
        $res = Http::timeout(15)->post($this->cfg('endpoint').'/v2/gateway/api/create', $body)->json();
        if (($res['resultCode'] ?? -1) !== 0) {
            Log::warning('MoMo create failed', ['res' => $res]);
            throw new ApiException('MOMO_ERROR', $res['message'] ?? 'Không tạo được giao dịch MoMo', 502);
        }

        return ['pay_url' => $res['payUrl'] ?? null, 'qr_code_url' => $res['qrCodeUrl'] ?? null, 'deeplink' => $res['deeplink'] ?? null, 'expires_at' => $payment->expires_at];
    }

    private const IPN_KEYS = ['accessKey', 'amount', 'extraData', 'message', 'orderId', 'orderInfo', 'orderType', 'partnerCode', 'payType', 'requestId', 'responseTime', 'resultCode', 'transId'];

    public function verifyIpn(array $p): bool
    {
        $fields = $p + ['accessKey' => $this->cfg('access_key')];

        return hash_equals($this->sign($fields, self::IPN_KEYS), (string) ($p['signature'] ?? ''));
    }

    /** Xử lý IPN — idempotent. @return bool true nếu hợp lệ */
    public function handleIpn(array $p): bool
    {
        if (! $this->verifyIpn($p)) {
            Log::warning('MoMo IPN signature mismatch', ['orderId' => $p['orderId'] ?? null]);

            return false;
        }
        $lock = Cache::lock('lock:momo:ipn:'.$p['orderId'], 10);
        if (! $lock->get()) {
            return true;
        }
        try {
            DB::transaction(function () use ($p) {
                $payment = Payment::where('momo_order_id', $p['orderId'])->lockForUpdate()->first();
                if (! $payment) {
                    return;
                }
                if ($payment->status === 'paid' || ($payment->provider_txn_id && $payment->provider_txn_id === (string) ($p['transId'] ?? ''))) {
                    return;
                }
                if ((int) $p['amount'] !== (int) $payment->amount) {
                    Log::alert('MoMo IPN amount mismatch', $p);

                    return;
                }
                $order = Order::lockForUpdate()->find($payment->order_id);
                $payment->raw_payload = $p;
                $payment->result_code = (int) $p['resultCode'];
                if ((int) $p['resultCode'] === 0) {
                    $payment->fill(['status' => 'paid', 'provider_txn_id' => (string) $p['transId'], 'paid_at' => now()])->save();
                    $order->payment_status = 'paid';
                    $order->save();
                    if ($order->status === 'pending') {
                        $order->status = 'confirmed';
                        $order->save();
                        OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => 'pending', 'to_status' => 'confirmed', 'actor_type' => 'system', 'note' => 'Đã thanh toán MoMo']);
                    }
                } else {
                    $payment->update(['status' => 'failed']);
                    $order->update(['payment_status' => 'unpaid']);
                }
            });
        } finally {
            $lock->release();
        }

        return true;
    }

    /** Giả lập IPN từ cổng MoMo giả. */
    public function fakeIpn(string $momoOrderId, bool $success): ?Payment
    {
        $payment = Payment::where('momo_order_id', $momoOrderId)->firstOrFail();
        $p = [
            'partnerCode' => $this->cfg('partner_code'), 'orderId' => $momoOrderId, 'requestId' => (string) $payment->momo_request_id,
            'amount' => (string) $payment->amount, 'orderInfo' => 'fake', 'orderType' => 'momo_wallet', 'transId' => (string) random_int(1000000000, 9999999999),
            'resultCode' => $success ? '0' : '1006', 'message' => $success ? 'Successful.' : 'Giao dich bi tu choi', 'payType' => 'qr',
            'responseTime' => (string) (now()->getTimestamp() * 1000), 'extraData' => '',
        ];
        $p['signature'] = $this->sign($p + ['accessKey' => $this->cfg('access_key')], self::IPN_KEYS);
        $this->handleIpn($p);

        return $payment->refresh();
    }

    /** Truy vấn lại giao dịch (admin / trước khi tự hủy đơn quá hạn). @return array{paid:bool,raw:?array} */
    public function query(Order $order): array
    {
        $payment = $order->payments()->where('method', 'momo')->orderByDesc('id')->first();
        if (! $payment || ! $payment->momo_order_id) {
            return ['paid' => false, 'raw' => null];
        }
        if (config('shoe.momo.fake')) {
            return ['paid' => $payment->status === 'paid', 'raw' => ['fake' => true, 'status' => $payment->status]];
        }
        $body = ['partnerCode' => $this->cfg('partner_code'), 'accessKey' => $this->cfg('access_key'), 'requestId' => $payment->momo_request_id.'-q'.time(), 'orderId' => $payment->momo_order_id, 'lang' => 'vi'];
        $body['signature'] = $this->sign($body, ['accessKey', 'orderId', 'partnerCode', 'requestId']);
        $res = Http::timeout(15)->post($this->cfg('endpoint').'/v2/gateway/api/query', $body)->json();
        if ((int) ($res['resultCode'] ?? -1) === 0 && ! empty($res['transId'])) {
            $this->handleIpn($this->asIpn($res, $payment));
        }

        return ['paid' => (int) ($res['resultCode'] ?? -1) === 0, 'raw' => $res];
    }

    private function asIpn(array $res, Payment $payment): array
    {
        $p = [
            'partnerCode' => $this->cfg('partner_code'), 'orderId' => $res['orderId'], 'requestId' => $res['requestId'] ?? '', 'amount' => (string) $res['amount'],
            'orderInfo' => $res['orderInfo'] ?? '', 'orderType' => 'momo_wallet', 'transId' => (string) $res['transId'], 'resultCode' => (string) $res['resultCode'],
            'message' => $res['message'] ?? '', 'payType' => $res['payType'] ?? '', 'responseTime' => (string) ($res['responseTime'] ?? ''), 'extraData' => $res['extraData'] ?? '',
        ];
        $p['signature'] = $this->sign($p + ['accessKey' => $this->cfg('access_key')], self::IPN_KEYS);

        return $p;
    }

    /** Hoàn tiền theo transId. Ném lỗi nếu MoMo từ chối. */
    public function refund(Order $order, int $amount, string $description): void
    {
        $payment = $order->payments()->where('method', 'momo')->where('status', 'paid')->orderByDesc('id')->first();
        if (! $payment) {
            return;
        }
        if (! config('shoe.momo.fake')) {
            $body = [
                'partnerCode' => $this->cfg('partner_code'), 'accessKey' => $this->cfg('access_key'), 'orderId' => $payment->momo_order_id.'-R'.time(),
                'requestId' => $payment->momo_request_id.'-R'.time(), 'amount' => (string) $amount, 'transId' => (string) $payment->provider_txn_id,
                'lang' => 'vi', 'description' => $description,
            ];
            $body['signature'] = $this->sign($body, ['accessKey', 'amount', 'description', 'orderId', 'partnerCode', 'requestId', 'transId']);
            $res = Http::timeout(15)->post($this->cfg('endpoint').'/v2/gateway/api/refund', $body)->json();
            if ((int) ($res['resultCode'] ?? -1) !== 0) {
                throw new ApiException('MOMO_REFUND_FAILED', $res['message'] ?? 'MoMo từ chối hoàn tiền', 502);
            }
        }
        $full = $amount >= $payment->amount;
        $payment->update(['status' => $full ? 'refunded' : 'paid']);
        $order->update(['payment_status' => $full ? 'refunded' : 'partial_refund']);
    }
}
