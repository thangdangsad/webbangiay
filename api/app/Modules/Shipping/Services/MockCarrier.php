<?php

namespace App\Modules\Shipping\Services;

use App\Modules\Order\Models\Order;

/**
 * Hãng vận chuyển giả lập (chưa tích hợp GHN/GHTK/Viettel Post).
 * Khi cần tích hợp thật: viết class cùng chữ ký createShipment() và đổi binding.
 */
class MockCarrier
{
    /** @return array{carrier:string,tracking_code:string} */
    public function createShipment(Order $order): array
    {
        return ['carrier' => 'Giao hàng mô phỏng', 'tracking_code' => 'MOCK'.now()->format('ymd').strtoupper(substr(md5($order->code), 0, 6))];
    }
}
