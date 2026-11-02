<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\InventoryMovement;
use App\Modules\Inventory\Models\StockReceipt;
use App\Modules\Inventory\Models\StockReceiptItem;
use App\Modules\Inventory\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminInventoryController
{
    public function __construct(private InventoryService $inventory) {}

    public function index(Request $r)
    {
        $q = ProductVariant::query()->with(['product:id,name,slug', 'color', 'size', 'inventory'])
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->leftJoin('inventory', 'inventory.variant_id', '=', 'product_variants.id')
            ->whereNull('products.deleted_at')
            ->select('product_variants.*')->orderBy('products.name')->orderBy('product_variants.id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('product_variants.sku', 'like', "%$s%")->orWhere('products.name', 'like', "%$s%"));
        }
        if ($r->boolean('low')) {
            $q->whereRaw('inventory.on_hand - inventory.reserved <= inventory.low_stock_threshold');
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 30)));

        return ['data' => $page->getCollection()->map(fn ($v) => [
            'variant_id' => $v->id, 'sku' => $v->sku, 'product_id' => $v->product_id, 'product_name' => $v->product->name,
            'color' => $v->color->name, 'size' => $v->size->label, 'price' => (int) $v->price,
            'on_hand' => $v->inventory?->on_hand ?? 0, 'reserved' => $v->inventory?->reserved ?? 0,
            'available' => max(0, ($v->inventory?->on_hand ?? 0) - ($v->inventory?->reserved ?? 0)),
            'low_stock_threshold' => $v->inventory?->low_stock_threshold ?? 5,
            'low' => ($v->inventory?->on_hand ?? 0) - ($v->inventory?->reserved ?? 0) <= ($v->inventory?->low_stock_threshold ?? 5),
        ])->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }

    public function threshold(Request $r, int $variantId)
    {
        $v = $r->validate(['low_stock_threshold' => 'required|integer|min:0']);
        $this->inventory->ensureRow($variantId);
        DB::table('inventory')->where('variant_id', $variantId)->update($v + ['updated_at' => now()]);

        return ['data' => $v];
    }

    public function receipt(Request $r)
    {
        $v = $r->validate([
            'supplier' => 'nullable|string|max:255', 'note' => 'nullable|string', 'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|exists:product_variants,id', 'items.*.qty' => 'required|integer|min:1', 'items.*.unit_cost' => 'nullable|integer|min:0',
        ]);
        $staff = $r->user('staff');
        $receipt = DB::transaction(function () use ($v, $staff) {
            $rc = StockReceipt::create(['code' => 'PN-'.now()->format('ymd').'-'.strtoupper(\Illuminate\Support\Str::random(4)), 'supplier' => $v['supplier'] ?? null, 'note' => $v['note'] ?? null, 'staff_id' => $staff->id]);
            $qty = [];
            foreach ($v['items'] as $i) {
                StockReceiptItem::create(['stock_receipt_id' => $rc->id, 'variant_id' => $i['variant_id'], 'qty' => $i['qty'], 'unit_cost' => $i['unit_cost'] ?? 0]);
                $this->inventory->ensureRow($i['variant_id']);
                $qty[$i['variant_id']] = ($qty[$i['variant_id']] ?? 0) + $i['qty'];
            }
            $this->inventory->restock($qty, 'receipt', 'Phiếu nhập '.$rc->code, 'receipt', $rc->id, $staff->id);

            return $rc;
        });
        \Illuminate\Support\Facades\Cache::tags(['catalog', 'home'])->flush();

        return response()->json(['data' => ['id' => $receipt->id, 'code' => $receipt->code]], 201);
    }

    public function receipts(Request $r)
    {
        $page = StockReceipt::withCount('items')->withSum('items', 'qty')->orderByDesc('id')->paginate(20);

        return ['data' => $page->getCollection()->map(fn ($x) => ['id' => $x->id, 'code' => $x->code, 'supplier' => $x->supplier, 'note' => $x->note, 'items' => $x->items_count, 'total_qty' => (int) $x->items_sum_qty, 'created_at' => $x->created_at])->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => 20, 'total' => $page->total()]];
    }

    public function adjust(Request $r)
    {
        $v = $r->validate(['variant_id' => 'required|exists:product_variants,id', 'delta' => 'required|integer', 'reason' => 'required|string|max:255']);
        $this->inventory->ensureRow($v['variant_id']);
        DB::transaction(fn () => $this->inventory->adjust($v['variant_id'], $v['delta'], $v['reason'], $r->user('staff')->id));
        \Illuminate\Support\Facades\Cache::tags(['catalog', 'home'])->flush();

        return ['data' => ['ok' => true]];
    }

    public function movements(Request $r)
    {
        $q = InventoryMovement::with(['variant.product:id,name', 'variant.color', 'variant.size'])->orderByDesc('id');
        if ($r->filled('variant_id')) {
            $q->where('variant_id', $r->query('variant_id'));
        }
        if ($r->filled('type')) {
            $q->where('type', $r->query('type'));
        }
        if ($s = $r->query('q')) {
            $q->whereHas('variant', fn ($v) => $v->where('sku', 'like', "%$s%")->orWhereHas('product', fn ($p) => $p->where('name', 'like', "%$s%")));
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 30)));
        $staff = \App\Modules\User\Models\StaffUser::whereIn('id', $page->getCollection()->pluck('staff_id')->filter())->pluck('full_name', 'id');

        return ['data' => $page->getCollection()->map(fn ($m) => [
            'id' => $m->id, 'type' => $m->type, 'qty_delta' => $m->qty_delta, 'reason' => $m->reason, 'ref_type' => $m->ref_type, 'ref_id' => $m->ref_id,
            'sku' => $m->variant?->sku, 'product_name' => $m->variant?->product?->name, 'color' => $m->variant?->color?->name, 'size' => $m->variant?->size?->label,
            'staff' => $m->staff_id ? ($staff[$m->staff_id] ?? '#'.$m->staff_id) : 'Hệ thống', 'created_at' => $m->created_at,
        ])->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }
}
