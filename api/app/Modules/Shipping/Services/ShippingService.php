<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Shipping\Models\ShippingRate;
use App\Support\Settings;

class ShippingService
{
    /** @return array{fee:int,free:bool,zone:?string,method:string,carrier:string,eta:string} */
    public function quote(string $provinceCode, int $weightG, int $subtotal): array
    {
        $rates = ShippingRate::query()->orderBy('min_weight')->get();
        $rate = $rates->first(fn ($r) => in_array($provinceCode, $r->province_codes ?? [], true) && $weightG >= $r->min_weight && $weightG <= $r->max_weight)
            ?? $rates->first(fn ($r) => empty($r->province_codes) && $weightG >= $r->min_weight && $weightG <= $r->max_weight);

        $fee = $rate ? (int) $rate->fee : (int) Settings::get('shipping.default_fee');
        $threshold = $rate?->free_threshold ?? Settings::get('shipping.free_threshold');
        $free = $threshold && $subtotal >= $threshold;

        return [
            'fee' => $free ? 0 : $fee, 'free' => (bool) $free, 'zone' => $rate?->zone, 'method' => 'standard',
            'carrier' => Settings::get('shipping.carrier'), 'eta' => '2-4 ngày',
        ];
    }
}
