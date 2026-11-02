<?php

namespace App\Modules\Order\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Notification\Notifier;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderStatusHistory;
use App\Modules\Payment\Services\MomoService;
use App\Modules\Promotion\Models\CouponRedemption;
use App\Modules\Shipping\Models\Shipment;
use App\Modules\Shipping\Services\MockCarrier;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/** pending → confirmed → packing → shipping → completed ; nhánh cancelled / returned. */
class OrderStateMachine
{
    public const TRANSITIONS = [
        'pending' => ['confirmed', 'cancelled'],
        'confirmed' => ['packing', 'cancelled'],
        'packing' => ['shipping'],
        'shipping' => ['completed', 'cancelled'],
        'completed' => ['returned'],
    ];

    /** Quyền staff cần có cho từng trạng thái đích. */
    public const PERMISSION = [
        'confirmed' => 'order:confirm', 'cancelled' => 'order:cancel', 'packing' => 'order:fulfil',
        'shipping' => 'order:fulfil', 'completed' => 'order:complete', 'returned' => 'return:process',
    ];

    public function __construct(private InventoryService $inventory, private MomoService $momo) {}

    public static function allowedNext(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    public function transition(Order $order, string $to, string $actorType, ?int $actorId = null, ?string $note = null, array $extra = []): Order
    {
        return DB::transaction(function () use ($order, $to, $actorType, $actorId, $note, $extra) {
            $order = Order::with('items')->lockForUpdate()->findOrFail($order->id);
            $from = $order->status;
            if (! in_array($to, self::allowedNext($from), true)) {
                throw ApiException::conflict('INVALID_TRANSITION', "Không thể chuyển đơn từ '$from' sang '$to'");
            }
            $qty = $order->items->whereNotNull('variant_id')->groupBy('variant_id')->map->sum('qty')->all();

            switch ($to) {
                case 'cancelled':
                    if ($from === 'shipping') {
                        $this->inventory->restock($qty, 'return', 'Giao thất bại, nhập lại kho', 'order', $order->id, $actorType === 'staff' ? $actorId : null);
                    } else {
                        $this->inventory->release($qty, $order->id);
                    }
                    CouponRedemption::where('order_id', $order->id)->delete();
                    $order->cancelled_at = now();
                    $this->cancelPayments($order);
                    break;
                case 'shipping':
                    $this->inventory->ship($qty, $order->id);
                    $mock = app(MockCarrier::class)->createShipment($order);
                    Shipment::firstOrCreate(['order_id' => $order->id], [
                        'carrier' => $extra['carrier'] ?? $mock['carrier'], 'tracking_code' => $extra['tracking_code'] ?? $mock['tracking_code'],
                        'fee' => $order->shipping_fee, 'status' => 'shipping', 'shipped_at' => now(),
                    ]);
                    break;
                case 'completed':
                    $order->completed_at = now();
                    if ($order->payment_method === 'cod' && $order->payment_status !== 'paid') {
                        $order->payment_status = 'paid';
                        $order->payments()->where('method', 'cod')->update(['status' => 'paid', 'paid_at' => now()]);
                    }
                    Shipment::where('order_id', $order->id)->update(['status' => 'delivered', 'delivered_at' => now()]);
                    foreach ($order->items->groupBy('product_id') as $pid => $rows) {
                        if ($pid) {
                            DB::table('products')->where('id', $pid)->increment('sold_count', $rows->sum('qty'));
                        }
                    }
                    break;
            }

            $order->status = $to;
            $order->save();
            OrderStatusHistory::create([
                'order_id' => $order->id, 'from_status' => $from, 'to_status' => $to,
                'actor_type' => $actorType, 'actor_id' => $actorId, 'note' => $note,
            ]);
            $this->notify($order, $to);

            return $order->refresh();
        }, 3);
    }

    /** Hủy các giao dịch đang chờ; đã thanh toán MoMo thì hoàn tiền. */
    private function cancelPayments(Order $order): void
    {
        foreach ($order->payments()->get() as $p) {
            if ($p->status === 'pending') {
                $p->update(['status' => 'failed']);
            } elseif ($p->status === 'paid' && $p->method === 'momo') {
                $this->momo->refund($order, $p->amount, 'Hủy đơn '.$order->code);
            }
        }
    }

    private function notify(Order $order, string $to): void
    {
        $labels = ['confirmed' => 'đã được xác nhận', 'shipping' => 'đang được giao', 'completed' => 'đã hoàn thành', 'cancelled' => 'đã bị hủy'];
        if (isset($labels[$to])) {
            if ($order->email) {
                Notifier::mail($order->email, "Đơn hàng {$order->code} {$labels[$to]}", "Đơn hàng {$order->code} {$labels[$to]}.");
            }
        }
    }
}
