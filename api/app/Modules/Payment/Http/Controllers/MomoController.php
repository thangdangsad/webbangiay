<?php

namespace App\Modules\Payment\Http\Controllers;

use App\Modules\Notification\Notifier;
use App\Modules\Order\Models\Order;
use App\Modules\Payment\Services\MomoService;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MomoController
{
    public function __construct(private MomoService $momo) {}

    public function create(Request $r)
    {
        $v = $r->validate(['order_code' => 'required|string', 'phone' => 'nullable|string']);
        $order = Order::where('code', $v['order_code'])->firstOrFail();
        $user = Auth::guard('customer')->user();
        if (! ($user && $order->user_id === $user->id) && Notifier::normalizePhone((string) ($v['phone'] ?? '')) !== $order->phone) {
            throw ApiException::notFound();
        }

        return ['data' => $this->momo->create($order)];
    }

    /** IPN server-to-server từ MoMo; trả 204 nếu xử lý xong. */
    public function ipn(Request $r)
    {
        return $this->momo->handleIpn($r->all()) ? response()->noContent() : response()->json(['message' => 'invalid signature'], 400);
    }

    /** Khách quay lại — chỉ để điều hướng, KHÔNG chốt đơn. */
    public function back(Request $r)
    {
        $code = preg_replace('/-\d+$/', '', (string) $r->query('orderId'));

        return redirect(config('shoe.frontends.storefront').'/dat-hang-thanh-cong/'.$code);
    }

    /** Trang MoMo giả lập (chỉ khi MOMO_FAKE=true). */
    public function fakePage(string $momoOrderId, Request $r)
    {
        abort_unless(config('shoe.momo.fake'), 404);
        $payment = \App\Modules\Payment\Models\Payment::where('momo_order_id', $momoOrderId)->firstOrFail();
        $code = preg_replace('/-\d+$/', '', $momoOrderId);
        if ($act = $r->query('action')) {
            $this->momo->fakeIpn($momoOrderId, $act === 'success');

            return redirect(config('shoe.frontends.storefront').'/dat-hang-thanh-cong/'.$code.'?phone='.urlencode((string) $payment->order->phone));
        }
        $amount = number_format($payment->amount, 0, ',', '.');
        $html = <<<HTML
<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>MoMo (giả lập)</title>
<body style="font-family:system-ui;background:#fdf2f8;display:grid;place-items:center;min-height:100vh;margin:0">
<div style="background:#fff;border-radius:16px;padding:32px;max-width:360px;width:90%;box-shadow:0 8px 30px #0001;text-align:center">
<div style="background:#a50064;color:#fff;border-radius:12px;padding:10px;font-weight:700">MoMo · Cổng thanh toán giả lập</div>
<p style="color:#666">Đơn hàng <b>{$code}</b></p><h1 style="margin:8px 0">{$amount}đ</h1>
<a href="?action=success" style="display:block;background:#a50064;color:#fff;padding:12px;border-radius:999px;text-decoration:none;font-weight:700;margin-top:18px">Xác nhận thanh toán</a>
<a href="?action=fail" style="display:block;color:#a50064;padding:12px;text-decoration:none;margin-top:6px">Hủy giao dịch</a></div></body></html>
HTML;

        return response($html);
    }
}
