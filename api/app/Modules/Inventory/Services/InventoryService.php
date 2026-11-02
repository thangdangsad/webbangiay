<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\Models\InventoryMovement;
use App\Support\ApiException;
use Illuminate\Support\Facades\DB;

/** Mọi thay đổi tồn kho đều sinh inventory_movements trong cùng transaction. */
class InventoryService
{
    public function move(int $variantId, string $type, int $delta, ?string $reason = null, ?string $refType = null, ?int $refId = null, ?int $staffId = null): void
    {
        InventoryMovement::create([
            'variant_id' => $variantId, 'type' => $type, 'qty_delta' => $delta, 'reason' => $reason,
            'ref_type' => $refType, 'ref_id' => $refId, 'staff_id' => $staffId,
        ]);
    }

    /** Giữ hàng cho đơn mới. Variant xử lý theo thứ tự id để tránh deadlock. @param array<int,int> $qtyByVariant */
    public function reserve(array $qtyByVariant, ?int $orderId = null): void
    {
        ksort($qtyByVariant);
        foreach ($qtyByVariant as $variantId => $qty) {
            $n = DB::table('inventory')->where('variant_id', $variantId)
                ->whereRaw('on_hand - reserved >= ?', [$qty])
                ->update(['reserved' => DB::raw("reserved + $qty"), 'version' => DB::raw('version + 1'), 'updated_at' => now()]);
            if ($n === 0) {
                $label = DB::table('product_variants as v')->join('sizes as s', 's.id', '=', 'v.size_id')->where('v.id', $variantId)->value('s.label');
                throw ApiException::conflict('OUT_OF_STOCK', "Size {$label} đã hết hàng hoặc không đủ số lượng", ['variant_id' => $variantId]);
            }
            $this->move($variantId, 'reserve', $qty, null, 'order', $orderId);
        }
    }

    public function release(array $qtyByVariant, ?int $orderId = null, string $reason = 'Hủy đơn'): void
    {
        ksort($qtyByVariant);
        foreach ($qtyByVariant as $variantId => $qty) {
            DB::table('inventory')->where('variant_id', $variantId)
                ->update(['reserved' => DB::raw("GREATEST(reserved - $qty, 0)"), 'version' => DB::raw('version + 1'), 'updated_at' => now()]);
            $this->move($variantId, 'release', -$qty, $reason, 'order', $orderId);
        }
    }

    /** Xuất kho khi giao: trừ on_hand và reserved. */
    public function ship(array $qtyByVariant, int $orderId): void
    {
        ksort($qtyByVariant);
        foreach ($qtyByVariant as $variantId => $qty) {
            DB::table('inventory')->where('variant_id', $variantId)
                ->update(['on_hand' => DB::raw("on_hand - $qty"), 'reserved' => DB::raw("reserved - $qty"), 'version' => DB::raw('version + 1'), 'updated_at' => now()]);
            $this->move($variantId, 'sale', -$qty, 'Xuất kho giao hàng', 'order', $orderId);
        }
    }

    /** Nhập lại kho (giao thất bại / đổi trả còn nguyên). */
    public function restock(array $qtyByVariant, string $type, string $reason, string $refType, int $refId, ?int $staffId = null): void
    {
        ksort($qtyByVariant);
        foreach ($qtyByVariant as $variantId => $qty) {
            DB::table('inventory')->where('variant_id', $variantId)
                ->update(['on_hand' => DB::raw("on_hand + $qty"), 'version' => DB::raw('version + 1'), 'updated_at' => now()]);
            $this->move($variantId, $type, $qty, $reason, $refType, $refId, $staffId);
        }
    }

    public function adjust(int $variantId, int $delta, string $reason, ?int $staffId): void
    {
        if ($delta === 0) {
            throw ApiException::invalid('Số lượng điều chỉnh phải khác 0');
        }
        $n = DB::table('inventory')->where('variant_id', $variantId)
            ->whereRaw('on_hand + ? >= reserved', [$delta])
            ->update(['on_hand' => DB::raw("on_hand + ($delta)"), 'version' => DB::raw('version + 1'), 'updated_at' => now()]);
        if ($n === 0) {
            throw ApiException::conflict('INVALID_ADJUST', 'Tồn kho sau điều chỉnh không được nhỏ hơn số lượng đang giữ');
        }
        $this->move($variantId, 'adjust', $delta, $reason, 'adjust', null, $staffId);
    }

    public function ensureRow(int $variantId): void
    {
        DB::table('inventory')->insertOrIgnore(['variant_id' => $variantId, 'on_hand' => 0, 'reserved' => 0, 'low_stock_threshold' => 5, 'version' => 0, 'created_at' => now(), 'updated_at' => now()]);
    }
}
