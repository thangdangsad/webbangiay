<?php

namespace App\Modules\Order\Services;

use App\Modules\Inventory\Services\InventoryService;
use App\Modules\Order\Models\Order;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\ReturnImage;
use App\Modules\Order\Models\ReturnItem;
use App\Modules\Order\Models\ReturnRequest;
use App\Modules\Payment\Services\MomoService;
use App\Modules\User\Models\User;
use App\Support\ApiException;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** requested → approved/rejected → received → refunded */
class ReturnService
{
    public function __construct(private InventoryService $inventory, private MomoService $momo, private OrderStateMachine $machine) {}

    public function request(Order $order, User $user, string $reason, array $items, array $imagePaths = []): ReturnRequest
    {
        if ($order->user_id !== $user->id) {
            throw ApiException::notFound();
        }
        if ($order->status !== 'completed' || ! $order->completed_at) {
            throw ApiException::conflict('NOT_RETURNABLE', 'Chỉ đơn đã hoàn thành mới được yêu cầu đổi/trả');
        }
        $days = (int) Settings::get('order.return_days', 7);
        if ($order->completed_at->lt(now()->subDays($days))) {
            throw ApiException::conflict('RETURN_EXPIRED', "Đã quá thời hạn đổi/trả $days ngày");
        }
        if ($order->returns()->whereNotIn('status', ['rejected'])->exists()) {
            throw ApiException::conflict('RETURN_EXISTS', 'Đơn hàng đã có yêu cầu đổi/trả');
        }

        return DB::transaction(function () use ($order, $reason, $items, $imagePaths) {
            $refund = 0;
            $ret = ReturnRequest::create(['code' => 'RT-'.strtoupper(Str::random(8)), 'order_id' => $order->id, 'reason' => $reason, 'status' => 'requested']);
            foreach ($items as $row) {
                $oi = OrderItem::where('order_id', $order->id)->find($row['order_item_id']);
                if (! $oi || $row['qty'] < 1 || $row['qty'] > $oi->qty) {
                    throw ApiException::invalid('Sản phẩm hoặc số lượng đổi/trả không hợp lệ');
                }
                ReturnItem::create(['return_id' => $ret->id, 'order_item_id' => $oi->id, 'qty' => $row['qty'], 'restock' => true]);
                $refund += $oi->unit_price * $row['qty'];
            }
            $ret->update(['refund_amount' => $refund]);
            foreach ($imagePaths as $path) {
                if (! str_starts_with($path, 'tmp/') || str_contains($path, '..')) {
                    continue;
                }
                $dest = "returns/{$ret->id}/".basename($path);
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->move($path, $dest);
                    ReturnImage::create(['return_id' => $ret->id, 'path' => $dest]);
                }
            }

            \App\Modules\Notification\Notifier::staff(['admin', 'manager', 'cs'], "Yêu cầu đổi/trả mới {$ret->code}", "Đơn {$order->code}: {$reason}");

            return $ret;
        });
    }

    private function expect(ReturnRequest $r, string $status): void
    {
        if ($r->status !== $status) {
            throw ApiException::conflict('INVALID_RETURN_STATE', "Yêu cầu đang ở trạng thái '{$r->status}'");
        }
    }

    public function approve(ReturnRequest $r, int $staffId, ?string $note): ReturnRequest
    {
        $this->expect($r, 'requested');
        $r->update(['status' => 'approved', 'staff_id' => $staffId, 'staff_note' => $note]);

        return $r;
    }

    public function reject(ReturnRequest $r, int $staffId, ?string $note): ReturnRequest
    {
        $this->expect($r, 'requested');
        $r->update(['status' => 'rejected', 'staff_id' => $staffId, 'staff_note' => $note]);

        return $r;
    }

    /** Kho nhận hàng; $restock = [return_item_id => bool] (false = hàng lỗi, hủy). */
    public function receive(ReturnRequest $r, int $staffId, array $restock = []): ReturnRequest
    {
        $this->expect($r, 'approved');

        return DB::transaction(function () use ($r, $staffId, $restock) {
            $r->load('items.orderItem');
            $qty = [];
            foreach ($r->items as $it) {
                $flag = $restock[$it->id] ?? $it->restock;
                $it->update(['restock' => (bool) $flag]);
                if ($flag && $it->orderItem->variant_id) {
                    $qty[$it->orderItem->variant_id] = ($qty[$it->orderItem->variant_id] ?? 0) + $it->qty;
                }
            }
            if ($qty) {
                $this->inventory->restock($qty, 'return', 'Nhập lại hàng đổi trả '.$r->code, 'return', $r->id, $staffId);
            }
            $r->update(['status' => 'received', 'staff_id' => $staffId]);

            return $r;
        });
    }

    public function refund(ReturnRequest $r, int $staffId, ?int $amount = null): ReturnRequest
    {
        $this->expect($r, 'received');

        return DB::transaction(function () use ($r, $staffId, $amount) {
            $order = Order::with('payments')->lockForUpdate()->find($r->order_id);
            $amount = min($amount ?? $r->refund_amount, $order->grand_total);
            $paid = $order->payments->firstWhere('status', 'paid');
            if ($paid && $paid->method === 'momo') {
                $this->momo->refund($order, $amount, 'Hoàn tiền đổi trả '.$r->code);
            }
            $order->payment_status = $amount >= $order->grand_total ? 'refunded' : 'partial_refund';
            $order->save();
            $r->update(['status' => 'refunded', 'refund_amount' => $amount, 'staff_id' => $staffId]);
            $this->machine->transition($order, 'returned', 'staff', $staffId, 'Hoàn tất đổi trả '.$r->code);

            return $r;
        });
    }
}
