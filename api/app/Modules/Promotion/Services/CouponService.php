<?php

namespace App\Modules\Promotion\Services;

use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Models\CouponRedemption;
use App\Support\ApiException;

class CouponService
{
    public function find(string $code): Coupon
    {
        $c = Coupon::where('code', strtoupper(trim($code)))->first();
        if (! $c) {
            throw new ApiException('COUPON_INVALID', 'Mã giảm giá không tồn tại', 422);
        }

        return $c;
    }

    /** Kiểm tra & tính giảm giá. @return array{coupon:Coupon,discount:int,free_ship:bool} */
    public function evaluate(string $code, int $subtotal, ?int $userId): array
    {
        $c = $this->find($code);
        $fail = fn (string $m) => new ApiException('COUPON_INVALID', $m, 422);

        if (! $c->is_active) {
            throw $fail('Mã giảm giá đã bị vô hiệu');
        }
        if ($c->starts_at && $c->starts_at->isFuture()) {
            throw $fail('Mã giảm giá chưa đến thời gian áp dụng');
        }
        if ($c->ends_at && $c->ends_at->isPast()) {
            throw $fail('Mã giảm giá đã hết hạn');
        }
        if ($subtotal < $c->min_order) {
            throw $fail('Đơn hàng tối thiểu '.number_format($c->min_order, 0, ',', '.').'đ để dùng mã này');
        }
        if ($c->usage_limit !== null && CouponRedemption::where('coupon_id', $c->id)->count() >= $c->usage_limit) {
            throw $fail('Mã giảm giá đã hết lượt sử dụng');
        }
        if ($c->per_user_limit !== null && $userId && CouponRedemption::where('coupon_id', $c->id)->where('user_id', $userId)->count() >= $c->per_user_limit) {
            throw $fail('Bạn đã sử dụng hết lượt của mã này');
        }

        $discount = match ($c->type) {
            'percent' => intdiv($subtotal * min(100, (int) $c->value), 100),
            'fixed' => (int) $c->value,
            default => 0,
        };
        if ($c->max_discount) {
            $discount = min($discount, (int) $c->max_discount);
        }

        return ['coupon' => $c, 'discount' => min($discount, $subtotal), 'free_ship' => $c->type === 'free_ship'];
    }
}
