<?php

namespace App\Modules\Order\Http\Controllers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Notification\Notifier;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Services\OrderPresenter;
use App\Modules\Order\Services\OrderService;
use App\Modules\Order\Services\ReturnService;
use App\Modules\Payment\Services\MomoService;
use App\Modules\Shipping\Services\ShippingService;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StoreOrderController
{
    public function __construct(
        private OrderService $orders, private OrderPresenter $presenter, private CartService $carts,
        private ShippingService $shipping, private ReturnService $returns,
    ) {}

    public function paymentMethods()
    {
        return ['data' => [
            ['code' => 'cod', 'name' => 'Thanh toán khi nhận hàng (COD)', 'enabled' => (bool) Settings::get('payment.cod_enabled')],
            ['code' => 'momo', 'name' => 'Ví MoMo', 'enabled' => (bool) Settings::get('payment.momo_enabled'), 'max_amount' => (int) Settings::get('payment.momo_max_amount')],
        ]];
    }

    public function quote(Request $r)
    {
        $v = $r->validate(['province_code' => 'required|string']);
        $user = Auth::guard('customer')->user();
        $cart = $this->carts->view($user, $user ? null : $r->header('X-Cart-Token'));
        $q = $this->shipping->quote($v['province_code'], max(1, $cart['weight_g']), $cart['subtotal']);
        if ($cart['coupon']['free_ship'] ?? false) {
            $q['fee'] = 0;
            $q['free'] = true;
        }

        return ['data' => $q + ['subtotal' => $cart['subtotal'], 'discount_total' => $cart['discount_total'], 'grand_total' => $cart['subtotal'] - $cart['discount_total'] + $q['fee']]];
    }

    public function store(Request $r)
    {
        $v = $r->validate([
            'receiver.name' => 'required|string|max:120',
            'receiver.phone' => ['required', 'regex:/^(0|\+84)\d{9}$/'],
            'receiver.email' => 'nullable|email|max:190',
            'address.province_code' => 'required|string|max:10', 'address.district_code' => 'required|string|max:10',
            'address.ward_code' => 'required|string|max:10', 'address.street' => 'required|string|max:255',
            'address.province_name' => 'nullable|string', 'address.district_name' => 'nullable|string', 'address.ward_name' => 'nullable|string',
            'shipping_method' => 'nullable|in:standard', 'payment_method' => 'required|in:cod,momo', 'note' => 'nullable|string|max:500',
        ], ['receiver.phone.regex' => 'Số điện thoại không hợp lệ']);
        $key = $r->header('Idempotency-Key') ?: throw ApiException::invalid('Thiếu header Idempotency-Key');

        $user = Auth::guard('customer')->user();
        $order = $this->orders->checkout($v, $user, $user ? null : $r->header('X-Cart-Token'), $key);

        return response()->json(['data' => $this->presenter->summary($order->loadMissing('items'))], 201);
    }

    public function myOrders(Request $r)
    {
        $q = Order::with('items')->where('user_id', $r->user('customer')->id)->orderByDesc('placed_at');
        if ($s = $r->query('status')) {
            $q->where('status', $s);
        }
        $page = $q->paginate(min(30, (int) $r->query('limit', 10)));

        return ['data' => $page->getCollection()->map(fn ($o) => $this->presenter->summary($o))->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }

    private function mine(Request $r, string $code): Order
    {
        return Order::where('code', $code)->where('user_id', $r->user('customer')->id)->firstOrFail();
    }

    public function myOrder(Request $r, string $code)
    {
        return ['data' => $this->presenter->detail($this->mine($r, $code))];
    }

    public function cancel(Request $r, string $code)
    {
        $order = $this->orders->customerCancel($this->mine($r, $code), $r->user('customer'), $r->input('reason'));

        return ['data' => $this->presenter->detail($order)];
    }

    public function requestReturn(Request $r, string $code)
    {
        $v = $r->validate([
            'reason' => 'required|string|max:1000', 'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer', 'items.*.qty' => 'required|integer|min:1',
            'images' => 'nullable|array|max:5', 'images.*' => 'string',
        ]);
        $ret = $this->returns->request($this->mine($r, $code), $r->user('customer'), $v['reason'], $v['items'], $v['images'] ?? []);

        return response()->json(['data' => ['code' => $ret->code, 'status' => $ret->status, 'refund_amount' => (int) $ret->refund_amount]], 201);
    }

    /** Tra cứu cho guest: mã đơn + SĐT. */
    public function track(Request $r)
    {
        $v = $r->validate(['code' => 'required|string', 'phone' => 'required|string']);
        $order = Order::where('code', strtoupper(trim($v['code'])))->where('phone', Notifier::normalizePhone($v['phone']))->first();
        if (! $order) {
            throw ApiException::notFound('Không tìm thấy đơn hàng với thông tin đã nhập');
        }

        return ['data' => $this->presenter->detail($order)];
    }

    /** Cho trang "đặt hàng thành công" (guest không đăng nhập vẫn xem được nhờ mã đơn + phone). */
    public function publicOrder(Request $r, string $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        $user = Auth::guard('customer')->user();
        if (! ($user && $order->user_id === $user->id) && Notifier::normalizePhone((string) $r->query('phone')) !== $order->phone) {
            throw ApiException::notFound();
        }

        return ['data' => $this->presenter->detail($order)];
    }

    public function paymentStatus(Request $r, string $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        $user = Auth::guard('customer')->user();
        if (! ($user && $order->user_id === $user->id) && Notifier::normalizePhone((string) $r->query('phone')) !== $order->phone) {
            throw ApiException::notFound();
        }
        $payment = $order->payments()->orderByDesc('id')->first();

        return ['data' => ['order_status' => $order->status, 'payment_status' => $order->payment_status, 'payment' => $payment?->status, 'expires_at' => $payment?->expires_at]];
    }

    /** Đơn MoMo chưa thanh toán → chuyển sang COD (xác nhận chờ CSKH). */
    public function switchCod(Request $r, string $code)
    {
        $order = Order::where('code', $code)->firstOrFail();
        $user = Auth::guard('customer')->user();
        if (! ($user && $order->user_id === $user->id) && Notifier::normalizePhone((string) $r->input('phone')) !== $order->phone) {
            throw ApiException::notFound();
        }
        if ($order->payment_method !== 'momo' || $order->payment_status === 'paid' || $order->status !== 'pending') {
            throw ApiException::conflict('NOT_SWITCHABLE', 'Đơn hàng không thể đổi sang COD');
        }
        if (! Settings::get('payment.cod_enabled')) {
            throw ApiException::invalid('Thanh toán COD hiện không khả dụng');
        }
        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->payments()->create(['method' => 'cod', 'amount' => $order->grand_total, 'status' => 'pending']);
            $order->update(['payment_method' => 'cod', 'payment_status' => 'unpaid']);
        });

        return ['data' => $this->presenter->detail($order->refresh())];
    }

    /** Khách tải ảnh minh chứng đổi/trả (tệp tạm, gắn vào yêu cầu khi gửi). */
    public function uploadReturnImage(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120']);

        return response()->json(['data' => app(\App\Modules\Catalog\Services\ImageStore::class)->storeTmp($r->file('file'), 'return')], 201);
    }
}
