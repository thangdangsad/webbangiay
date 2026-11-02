<?php

namespace App\Modules\Order\Services;

use App\Modules\Order\Models\Order;

class OrderPresenter
{
    public const STATUS_LABELS = [
        'pending' => 'Chờ xác nhận', 'confirmed' => 'Đã xác nhận', 'packing' => 'Đang đóng gói', 'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành', 'cancelled' => 'Đã hủy', 'returned' => 'Đổi trả',
    ];

    public function summary(Order $o): array
    {
        return [
            'id' => $o->id, 'code' => $o->code, 'status' => $o->status, 'status_label' => self::STATUS_LABELS[$o->status] ?? $o->status,
            'payment_status' => $o->payment_status, 'payment_method' => $o->payment_method,
            'customer_name' => $o->customer_name, 'phone' => $o->phone,
            'grand_total' => (int) $o->grand_total, 'item_count' => $o->relationLoaded('items') ? (int) $o->items->sum('qty') : null,
            'placed_at' => $o->placed_at?->toIso8601String(),
            'first_item' => $o->relationLoaded('items') && $o->items->first() ? $this->item($o->items->first()) : null,
        ];
    }

    public function item($i): array
    {
        return [
            'id' => $i->id, 'variant_id' => $i->variant_id, 'product_id' => $i->product_id, 'name' => $i->product_name, 'sku' => $i->sku,
            'color' => $i->color, 'size' => $i->size, 'image' => $i->image_path ? ['url' => storage_url($i->image_path), 'sm' => storage_url(image_variant($i->image_path, 'sm'))] : null,
            'unit_price' => (int) $i->unit_price, 'qty' => (int) $i->qty, 'line_total' => (int) $i->line_total,
        ];
    }

    public function detail(Order $o, bool $admin = false): array
    {
        $o->loadMissing('items', 'history', 'payments', 'shipment', 'returns');
        $payment = $o->payments->sortByDesc('id')->first();
        $data = $this->summary($o) + [
            'email' => $o->email, 'ship_address' => $o->ship_address, 'note' => $o->note,
            'subtotal' => (int) $o->subtotal, 'discount_total' => (int) $o->discount_total, 'shipping_fee' => (int) $o->shipping_fee,
            'coupon_code' => $o->coupon_code,
            'items' => $o->items->map(fn ($i) => $this->item($i))->values(),
            'timeline' => $o->history->map(fn ($h) => ['status' => $h->to_status, 'label' => self::STATUS_LABELS[$h->to_status] ?? $h->to_status, 'actor_type' => $h->actor_type, 'note' => $h->note, 'at' => $h->created_at])->values(),
            'payment' => $payment ? ['method' => $payment->method, 'status' => $payment->status, 'amount' => (int) $payment->amount, 'paid_at' => $payment->paid_at, 'expires_at' => $payment->expires_at] : null,
            'shipment' => $o->shipment ? ['carrier' => $o->shipment->carrier, 'tracking_code' => $o->shipment->tracking_code, 'status' => $o->shipment->status, 'shipped_at' => $o->shipment->shipped_at, 'delivered_at' => $o->shipment->delivered_at] : null,
            'can_cancel' => in_array($o->status, ['pending', 'confirmed'], true),
            'can_return' => $o->status === 'completed' && $o->completed_at && $o->completed_at->gt(now()->subDays((int) \App\Support\Settings::get('order.return_days', 7))) && $o->returns->whereNotIn('status', ['rejected'])->isEmpty(),
            'returns' => $o->returns->map(fn ($r) => ['code' => $r->code, 'status' => $r->status, 'refund_amount' => (int) $r->refund_amount])->values(),
        ];
        if ($admin) {
            $data += [
                'user_id' => $o->user_id, 'internal_note' => $o->internal_note, 'allowed_next' => OrderStateMachine::allowedNext($o->status),
                'payments' => $o->payments->map(fn ($p) => ['id' => $p->id, 'method' => $p->method, 'status' => $p->status, 'amount' => (int) $p->amount, 'provider_txn_id' => $p->provider_txn_id, 'result_code' => $p->result_code, 'paid_at' => $p->paid_at, 'momo_order_id' => $p->momo_order_id])->values(),
            ];
        }

        return ['data' => $data]['data'];
    }
}
