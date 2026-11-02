<?php

namespace App\Modules\Order\Services;

use App\Modules\Cart\Services\CartService;
use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Notification\Notifier;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Payment\Models\Payment;
use App\Modules\Promotion\Models\CouponRedemption;
use App\Modules\Promotion\Services\CouponService;
use App\Modules\Shipping\Services\ShippingService;
use App\Modules\User\Models\User;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OrderService
{
    public function __construct(
        private CartService $carts, private InventoryService $inventory, private ShippingService $shipping,
        private CouponService $coupons, private OrderStateMachine $machine,
    ) {}

    public static function generateCode(): string
    {
        $day = now('Asia/Ho_Chi_Minh')->format('ymd');
        $key = "order:seq:$day";
        $redis = Redis::connection('cache');
        $n = $redis->incr($key);
        if ($n === 1) {
            $existing = Order::where('code', 'like', "SH-$day-%")->count();
            if ($existing > 0) {
                $redis->set($key, $existing + 1);
                $n = $existing + 1;
            }
            $redis->expire($key, 172800);
        }

        return sprintf('SH-%s-%04d', $day, $n);
    }

    /** Checkout: toàn bộ tính tiền ở server. */
    public function checkout(array $in, ?User $user, ?string $cartToken, string $idempotencyKey): Order
    {
        if ($existing = Order::where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        $lock = Cache::lock('lock:checkout:'.($user?->id ?? $cartToken ?? $idempotencyKey), 10);
        if (! $lock->get()) {
            throw ApiException::conflict('CHECKOUT_IN_PROGRESS', 'Đơn hàng đang được xử lý, vui lòng chờ');
        }

        try {
            $cart = $this->carts->view($user, $cartToken);
            if (! $cart['items']) {
                throw new ApiException('CART_EMPTY', 'Giỏ hàng trống', 422);
            }
            if ($bad = collect($cart['items'])->firstWhere('issue')) {
                throw ApiException::conflict('OUT_OF_STOCK', "{$bad['name']} (size {$bad['size']}) không đủ hàng", ['variant_id' => $bad['variant_id']]);
            }

            $method = $in['payment_method'];
            $enabled = Settings::get("payment.{$method}_enabled");
            if (! $enabled) {
                throw ApiException::invalid('Phương thức thanh toán này hiện không khả dụng');
            }

            $ship = $this->shipping->quote($in['address']['province_code'], $cart['weight_g'], $cart['subtotal']);
            $fee = ($cart['coupon']['free_ship'] ?? false) ? 0 : $ship['fee'];
            $grand = $cart['subtotal'] - $cart['discount_total'] + $fee;

            if ($method === 'momo' && $grand > Settings::get('payment.momo_max_amount')) {
                throw ApiException::invalid('Đơn hàng vượt hạn mức thanh toán MoMo, vui lòng chọn COD');
            }

            $order = DB::transaction(function () use ($in, $user, $cart, $fee, $grand, $method, $idempotencyKey) {
                $qty = [];
                foreach ($cart['items'] as $i) {
                    $qty[$i['variant_id']] = ($qty[$i['variant_id']] ?? 0) + $i['qty'];
                }

                $order = Order::create([
                    'code' => self::generateCode(), 'user_id' => $user?->id,
                    'customer_name' => $in['receiver']['name'], 'phone' => Notifier::normalizePhone($in['receiver']['phone']),
                    'email' => $in['receiver']['email'] ?? $user?->email,
                    'ship_address' => $in['address'], 'status' => 'pending', 'payment_status' => 'unpaid', 'payment_method' => $method,
                    'subtotal' => $cart['subtotal'], 'discount_total' => $cart['discount_total'], 'shipping_fee' => $fee, 'grand_total' => $grand,
                    'coupon_code' => $cart['coupon']['code'] ?? null, 'note' => $in['note'] ?? null,
                    'idempotency_key' => $idempotencyKey, 'placed_at' => now(),
                ]);

                $this->inventory->reserve($qty, $order->id);

                foreach ($cart['items'] as $i) {
                    $img = null;
                    if ($path = $i['image']['url'] ?? null) {
                        $rel = ltrim(parse_url($path, PHP_URL_PATH), '/');
                        $rel = Str::after($rel, 'storage/');
                        if (Storage::disk('public')->exists($rel)) {
                            $img = "orders/{$order->id}/".Str::uuid().'.'.pathinfo($rel, PATHINFO_EXTENSION);
                            Storage::disk('public')->copy($rel, $img);
                        }
                    }
                    OrderItem::create([
                        'order_id' => $order->id, 'variant_id' => $i['variant_id'], 'product_id' => $i['product_id'],
                        'product_name' => $i['name'], 'sku' => $i['sku'], 'color' => $i['color'], 'size' => $i['size'],
                        'image_path' => $img, 'unit_price' => $i['unit_price'], 'qty' => $i['qty'], 'line_total' => $i['line_total'],
                    ]);
                }

                if ($c = $cart['coupon']) {
                    $coupon = $this->coupons->find($c['code']);
                    CouponRedemption::create(['coupon_id' => $coupon->id, 'user_id' => $user?->id, 'order_id' => $order->id, 'amount' => $c['discount']]);
                }
                Payment::create([
                    'order_id' => $order->id, 'method' => $method, 'amount' => $grand, 'status' => 'pending',
                    'provider' => $method === 'momo' ? 'momo' : null,
                    'expires_at' => $method === 'momo' ? now()->addMinutes((int) Settings::get('order.momo_expire_minutes', 15)) : null,
                ]);
                OrderStatusHistory::create(['order_id' => $order->id, 'from_status' => null, 'to_status' => 'pending', 'actor_type' => $user ? 'customer' : 'system', 'actor_id' => $user?->id, 'note' => 'Khách đặt hàng']);

                return $order;
            }, 3);
        } finally {
            $lock->release();
        }

        $this->carts->clear($user, $cartToken);
        if ($order->email) {
            Notifier::mail($order->email, "Đặt hàng thành công {$order->code}", "Cảm ơn bạn đã đặt hàng. Mã đơn: {$order->code}. Tổng tiền: ".number_format($order->grand_total, 0, ',', '.').'đ');
        }
        Notifier::staff(['admin', 'manager', 'cs'], "Có đơn mới {$order->code}", "Đơn {$order->code} · {$order->customer_name} · ".number_format($order->grand_total, 0, ',', '.').'đ · '.strtoupper($order->payment_method));

        return $order->load('items', 'payments');
    }

    /** Khách tự hủy khi chưa vượt "Đã xác nhận"; sau đó phải qua CSKH. */
    public function customerCancel(Order $order, User $user, ?string $reason): Order
    {
        if ($order->user_id !== $user->id) {
            throw ApiException::notFound();
        }
        if (! in_array($order->status, ['pending', 'confirmed'], true)) {
            throw ApiException::conflict('CANNOT_CANCEL', 'Đơn hàng đã được xử lý, vui lòng liên hệ CSKH để hủy');
        }

        return $this->machine->transition($order, 'cancelled', 'customer', $user->id, $reason ?: 'Khách hủy đơn');
    }

    public function timeline(Order $order): array
    {
        return $order->history->map(fn ($h) => [
            'status' => $h->to_status, 'actor_type' => $h->actor_type, 'note' => $h->note, 'at' => $h->created_at,
        ])->values()->all();
    }
}
