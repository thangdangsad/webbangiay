<?php

namespace App\Modules\Cart\Services;

use App\Modules\Cart\Models\Cart;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Services\PricingService;
use App\Modules\Promotion\Services\CouponService;
use App\Modules\User\Models\User;
use App\Support\ApiException;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

/** Giỏ đã đăng nhập lưu MySQL, giỏ guest lưu Redis `cart:guest:{token}` (HASH, TTL 30 ngày). */
class CartService
{
    private const GUEST_TTL = 30 * 86400;

    public function __construct(private PricingService $pricing, private CouponService $coupons) {}

    private function r()
    {
        return Redis::connection('cache');
    }

    private function gkey(string $token): string
    {
        return "cart:guest:$token";
    }

    public static function newToken(): string
    {
        return (string) Str::uuid();
    }

    /** @return array<int,int> variant_id => qty */
    public function lines(?User $user, ?string $token): array
    {
        if ($user) {
            $cart = Cart::with('items')->where('user_id', $user->id)->first();

            return $cart ? $cart->items->pluck('qty', 'variant_id')->map(fn ($q) => (int) $q)->all() : [];
        }
        if (! $token) {
            return [];
        }
        $h = $this->r()->hgetall($this->gkey($token));
        unset($h['_coupon']);

        return array_map('intval', $h);
    }

    public function couponCode(?User $user, ?string $token): ?string
    {
        if ($user) {
            return Cart::where('user_id', $user->id)->value('coupon_code');
        }

        return $token ? ($this->r()->hget($this->gkey($token), '_coupon') ?: null) : null;
    }

    public function setQty(?User $user, ?string $token, int $variantId, int $qty, bool $additive = false): void
    {
        $variant = ProductVariant::with('inventory', 'product')->find($variantId);
        if (! $variant || ! $variant->is_active || $variant->product?->status !== 'active') {
            throw ApiException::notFound('Sản phẩm không còn bán');
        }
        $current = $this->lines($user, $token)[$variantId] ?? 0;
        $target = $additive ? $current + $qty : $qty;
        $available = $variant->inventory ? max(0, $variant->inventory->on_hand - $variant->inventory->reserved) : 0;
        if ($target > $available) {
            throw ApiException::conflict('OUT_OF_STOCK', $available > 0 ? "Chỉ còn $available sản phẩm trong kho" : 'Sản phẩm đã hết hàng', ['available' => $available]);
        }

        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            if ($target <= 0) {
                $cart->items()->where('variant_id', $variantId)->delete();
            } else {
                $cart->items()->updateOrCreate(['variant_id' => $variantId], ['qty' => $target]);
            }

            return;
        }
        $key = $this->gkey($token);
        $target <= 0 ? $this->r()->hdel($key, $variantId) : $this->r()->hset($key, $variantId, $target);
        $this->r()->expire($key, self::GUEST_TTL);
    }

    public function remove(?User $user, ?string $token, int $variantId): void
    {
        $this->setQty($user, $token, $variantId, 0);
    }

    public function setCoupon(?User $user, ?string $token, ?string $code): void
    {
        if ($user) {
            Cart::firstOrCreate(['user_id' => $user->id])->update(['coupon_code' => $code]);
        } elseif ($token) {
            $code === null ? $this->r()->hdel($this->gkey($token), '_coupon') : $this->r()->hset($this->gkey($token), '_coupon', $code);
            $this->r()->expire($this->gkey($token), self::GUEST_TTL);
        }
    }

    public function clear(?User $user, ?string $token): void
    {
        if ($user) {
            Cart::where('user_id', $user->id)->each(function ($c) {
                $c->items()->delete();
                $c->update(['coupon_code' => null]);
            });
        } elseif ($token) {
            $this->r()->del($this->gkey($token));
        }
    }

    public function mergeGuestCart(User $user, string $token): void
    {
        foreach ($this->lines(null, $token) as $variantId => $qty) {
            try {
                $this->setQty($user, null, $variantId, $qty, additive: true);
            } catch (ApiException) {
                // bỏ qua dòng hết hàng
            }
        }
        if (($code = $this->couponCode(null, $token)) && ! $this->couponCode($user, null)) {
            $this->setCoupon($user, null, $code);
        }
        $this->clear(null, $token);
    }

    /** Dựng giỏ đầy đủ: giá/tồn tính lại ở server, cảnh báo dòng lỗi. */
    public function view(?User $user, ?string $token): array
    {
        $lines = $this->lines($user, $token);
        $variants = ProductVariant::with(['product.images', 'color', 'size', 'inventory'])->whereIn('id', array_keys($lines))->get()->keyBy('id');

        $items = [];
        $subtotal = 0;
        $weight = 0;
        $warnings = [];
        foreach ($lines as $variantId => $qty) {
            $v = $variants[$variantId] ?? null;
            if (! $v || ! $v->is_active || $v->product?->status !== 'active') {
                $this->setQty($user, $token, $variantId, 0); // dòng không còn bán → gỡ
                $warnings[] = ['variant_id' => $variantId, 'code' => 'REMOVED', 'message' => 'Một sản phẩm không còn được bán nên đã bị gỡ khỏi giỏ'];

                continue;
            }
            $p = $v->product;
            $pr = $this->pricing->price($v, $p);
            $available = $v->inventory ? max(0, $v->inventory->on_hand - $v->inventory->reserved) : 0;
            $issue = $available <= 0 ? 'out_of_stock' : ($qty > $available ? 'insufficient' : null);
            if ($issue) {
                $warnings[] = ['variant_id' => $variantId, 'code' => strtoupper($issue), 'message' => "{$p->name} (size {$v->size->label}) ".($issue === 'out_of_stock' ? 'đã hết hàng' : "chỉ còn $available sản phẩm")];
            }
            $img = $p->images->firstWhere('color_id', $v->color_id) ?? $p->images->firstWhere('is_primary', true) ?? $p->images->first();
            $line = $pr['price'] * $qty;
            $subtotal += $issue === 'out_of_stock' ? 0 : $line;
            $weight += $v->weight_g * $qty;
            $items[] = [
                'variant_id' => $v->id, 'product_id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'sku' => $v->sku,
                'color' => $v->color->name, 'color_hex' => $v->color->hex, 'size' => $v->size->label,
                'image' => $img ? $img->urls() : null,
                'unit_price' => $pr['price'], 'compare_at_price' => $pr['compare_at_price'], 'qty' => $qty,
                'line_total' => $line, 'available' => $available, 'issue' => $issue,
            ];
        }

        $discount = 0;
        $coupon = null;
        if ($code = $this->couponCode($user, $token)) {
            try {
                $e = $this->coupons->evaluate($code, $subtotal, $user?->id);
                $discount = $e['discount'];
                $coupon = ['code' => $e['coupon']->code, 'type' => $e['coupon']->type, 'discount' => $discount, 'free_ship' => $e['free_ship']];
            } catch (ApiException $ex) {
                $this->setCoupon($user, $token, null);
                $warnings[] = ['code' => 'COUPON_INVALID', 'message' => $ex->getMessage()];
            }
        }

        return [
            'items' => $items, 'count' => array_sum(array_column($items, 'qty')), 'weight_g' => $weight,
            'subtotal' => $subtotal, 'discount_total' => $discount, 'coupon' => $coupon,
            'total' => max(0, $subtotal - $discount), 'warnings' => $warnings,
        ];
    }
}
