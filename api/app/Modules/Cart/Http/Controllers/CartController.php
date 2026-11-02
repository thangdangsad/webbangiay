<?php

namespace App\Modules\Cart\Http\Controllers;

use App\Modules\Cart\Services\CartService;
use App\Modules\Promotion\Services\CouponService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartController
{
    public function __construct(private CartService $carts, private CouponService $coupons) {}

    private function ctx(Request $r, bool $create = false): array
    {
        $user = Auth::guard('customer')->user();
        $token = $r->header('X-Cart-Token');
        if (! $user && ! $token && $create) {
            $token = CartService::newToken();
        }

        return [$user, $user ? null : $token];
    }

    private function respond(Request $r, $user, $token)
    {
        $res = response()->json(['data' => $this->carts->view($user, $token) + ['cart_token' => $token]]);
        if ($token) {
            $res->header('X-Cart-Token', $token);
        }

        return $res;
    }

    public function show(Request $r)
    {
        [$u, $t] = $this->ctx($r);

        return $this->respond($r, $u, $t);
    }

    public function add(Request $r)
    {
        $v = $r->validate(['variant_id' => 'required|integer', 'qty' => 'required|integer|min:1|max:20']);
        [$u, $t] = $this->ctx($r, true);
        $this->carts->setQty($u, $t, $v['variant_id'], $v['qty'], additive: true);

        return $this->respond($r, $u, $t);
    }

    public function update(Request $r, int $variantId)
    {
        $v = $r->validate(['qty' => 'required|integer|min:0|max:20']);
        [$u, $t] = $this->ctx($r, true);
        $this->carts->setQty($u, $t, $variantId, $v['qty']);

        return $this->respond($r, $u, $t);
    }

    public function remove(Request $r, int $variantId)
    {
        [$u, $t] = $this->ctx($r);
        $this->carts->remove($u, $t, $variantId);

        return $this->respond($r, $u, $t);
    }

    public function applyCoupon(Request $r)
    {
        $v = $r->validate(['code' => 'required|string|max:40']);
        [$u, $t] = $this->ctx($r, true);
        $view = $this->carts->view($u, $t);
        $this->coupons->evaluate($v['code'], $view['subtotal'], $u?->id); // ném COUPON_INVALID nếu sai
        $this->carts->setCoupon($u, $t, strtoupper(trim($v['code'])));

        return $this->respond($r, $u, $t);
    }

    public function removeCoupon(Request $r)
    {
        [$u, $t] = $this->ctx($r);
        $this->carts->setCoupon($u, $t, null);

        return $this->respond($r, $u, $t);
    }
}
