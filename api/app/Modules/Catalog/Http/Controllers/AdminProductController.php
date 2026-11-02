<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Services\ImageStore;
use App\Modules\Catalog\Services\ProductService;
use App\Support\ApiException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminProductController
{
    public function __construct(private ProductService $products, private ImageStore $images) {}

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255', 'slug' => 'nullable|string|max:255', 'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id', 'gender' => 'nullable|in:men,women,unisex,kids',
            'description' => 'nullable|string', 'material' => 'nullable|string|max:255', 'status' => 'nullable|in:draft,active,hidden',
            'seo_title' => 'nullable|string|max:255', 'seo_description' => 'nullable|string|max:255',
            'variants' => 'sometimes|array', 'variants.*.id' => 'nullable|integer', 'variants.*.sku' => 'required|string|max:64',
            'variants.*.color_id' => 'required|exists:colors,id', 'variants.*.size_id' => 'required|exists:sizes,id',
            'variants.*.price' => 'required|integer|min:0', 'variants.*.compare_at_price' => 'nullable|integer|min:0',
            'variants.*.barcode' => 'nullable|string|max:64', 'variants.*.weight_g' => 'nullable|integer|min:0',
            'variants.*.is_active' => 'nullable|boolean', 'variants.*.stock' => 'nullable|integer|min:0',
            'images' => 'sometimes|array', 'images.*.path' => 'required|string', 'images.*.color_id' => 'nullable|exists:colors,id',
            'images.*.alt' => 'nullable|string|max:255', 'images.*.is_primary' => 'nullable|boolean',
        ];
    }

    private function row(Product $p): array
    {
        $img = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
        $stock = $p->variants->sum(fn ($v) => $v->inventory?->on_hand ?? 0);
        $avail = $p->variants->sum(fn ($v) => $v->inventory ? max(0, $v->inventory->on_hand - $v->inventory->reserved) : 0);

        return [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'status' => $p->status, 'gender' => $p->gender,
            'brand' => $p->brand?->name, 'category' => $p->category?->name, 'base_price' => (int) $p->base_price,
            'image' => $img ? $img->urls() : null, 'variant_count' => $p->variants->count(), 'on_hand' => $stock, 'available' => $avail,
            'sku' => $p->variants->first()?->sku, 'created_at' => $p->created_at,
        ];
    }

    public function index(Request $r)
    {
        $q = Product::with(['brand:id,name', 'category:id,name', 'images', 'variants.inventory'])->orderByDesc('id');
        if ($s = trim((string) $r->query('q'))) {
            $q->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhereHas('variants', fn ($v) => $v->where('sku', 'like', "%$s%")));
        }
        foreach (['status', 'brand_id', 'category_id'] as $f) {
            if ($r->filled($f)) {
                $q->where($f, $r->query($f));
            }
        }
        if ($r->boolean('low_stock')) {
            $q->whereHas('variants.inventory', fn ($i) => $i->whereColumn('on_hand', '<=', 'low_stock_threshold'));
        }
        $page = $q->paginate(min(100, (int) $r->query('limit', 20)));

        return ['data' => $page->getCollection()->map(fn ($p) => $this->row($p))->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => $page->perPage(), 'total' => $page->total()]];
    }

    public function show(int $id)
    {
        $p = Product::with(['variants.inventory', 'variants.color', 'variants.size', 'images'])->findOrFail($id);

        return ['data' => [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'brand_id' => $p->brand_id, 'category_id' => $p->category_id, 'gender' => $p->gender,
            'description' => $p->description, 'material' => $p->material, 'status' => $p->status, 'base_price' => (int) $p->base_price,
            'seo_title' => $p->seo_title, 'seo_description' => $p->seo_description,
            'images' => $p->images->map(fn ($i) => ['id' => $i->id, 'path' => $i->path, 'color_id' => $i->color_id, 'alt' => $i->alt, 'is_primary' => (bool) $i->is_primary] + $i->urls())->values(),
            'variants' => $p->variants->map(fn ($v) => [
                'id' => $v->id, 'sku' => $v->sku, 'color_id' => $v->color_id, 'color' => $v->color->name, 'size_id' => $v->size_id, 'size' => $v->size->label,
                'price' => (int) $v->price, 'compare_at_price' => $v->compare_at_price, 'barcode' => $v->barcode, 'weight_g' => $v->weight_g, 'is_active' => (bool) $v->is_active,
                'on_hand' => $v->inventory?->on_hand ?? 0, 'reserved' => $v->inventory?->reserved ?? 0,
            ])->values(),
        ]];
    }

    public function store(Request $r)
    {
        $p = $this->products->save(null, $r->validate($this->rules()), $r->user('staff')->id);

        return response()->json($this->show($p->id), 201);
    }

    public function update(Request $r, int $id)
    {
        $p = $this->products->save(Product::findOrFail($id), $r->validate($this->rules()), $r->user('staff')->id);

        return $this->show($p->id);
    }

    public function destroy(int $id)
    {
        $this->products->delete(Product::findOrFail($id));

        return response()->noContent();
    }

    public function status(Request $r, int $id)
    {
        $v = $r->validate(['status' => ['required', Rule::in(['draft', 'active', 'hidden'])]]);
        $p = Product::findOrFail($id);
        $p->update($v);
        $this->products->flush();
        app(\App\Modules\Catalog\Services\SearchIndexer::class)->sync($p);

        return ['data' => ['id' => $p->id, 'status' => $p->status]];
    }

    public function bulk(Request $r)
    {
        $v = $r->validate(['ids' => 'required|array|min:1', 'ids.*' => 'integer', 'action' => 'required|in:show,hide,delete']);
        foreach (Product::whereIn('id', $v['ids'])->get() as $p) {
            match ($v['action']) {
                'show' => $p->update(['status' => 'active']),
                'hide' => $p->update(['status' => 'hidden']),
                'delete' => $this->products->delete($p),
            };
        }
        $this->products->flush();

        return ['data' => ['affected' => count($v['ids'])]];
    }

    public function syncVariants(Request $r, int $id)
    {
        $v = $r->validate(['variants' => 'required|array'] + collect($this->rules())->filter(fn ($x, $k) => str_starts_with($k, 'variants.'))->all());
        $p = Product::findOrFail($id);
        \Illuminate\Support\Facades\DB::transaction(function () use ($p, $v, $r) {
            $this->products->syncVariants($p, $v['variants'], $r->user('staff')->id);
            $p->update(['base_price' => (int) ($p->variants()->where('is_active', true)->min('price') ?? 0)]);
        });
        $this->products->flush();

        return $this->show($id);
    }

    public function export()
    {
        $rows = Product::with(['brand', 'category', 'variants.color', 'variants.size', 'variants.inventory'])->get();
        $out = fopen('php://temp', 'w+');
        fputcsv($out, ['product', 'slug', 'brand', 'category', 'gender', 'status', 'sku', 'color', 'size', 'price', 'compare_at_price', 'stock']);
        foreach ($rows as $p) {
            foreach ($p->variants as $v) {
                fputcsv($out, [$p->name, $p->slug, $p->brand?->name, $p->category?->name, $p->gender, $p->status, $v->sku, $v->color->name, $v->size->label, $v->price, $v->compare_at_price, $v->inventory?->on_hand ?? 0]);
            }
        }
        rewind($out);

        return response("\xEF\xBB\xBF".stream_get_contents($out), 200, ['Content-Type' => 'text/csv; charset=utf-8', 'Content-Disposition' => 'attachment; filename="products.csv"']);
    }

    /** Nhập CSV: cập nhật giá / tồn theo SKU (cột sku, price, compare_at_price, stock). */
    public function import(Request $r)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);
        $h = fopen($r->file('file')->getRealPath(), 'r');
        $head = array_map(fn ($x) => strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', $x))), fgetcsv($h) ?: []);
        if (! in_array('sku', $head, true)) {
            throw ApiException::invalid('File CSV phải có cột sku');
        }
        $updated = 0;
        $errors = [];
        $inv = app(\App\Modules\Inventory\Services\InventoryService::class);
        while (($row = fgetcsv($h)) !== false) {
            $d = array_combine($head, array_pad($row, count($head), null));
            $v = \App\Modules\Catalog\Models\ProductVariant::where('sku', $d['sku'])->first();
            if (! $v) {
                $errors[] = "SKU {$d['sku']} không tồn tại";

                continue;
            }
            if (isset($d['price']) && $d['price'] !== '') {
                $v->price = (int) $d['price'];
            }
            if (array_key_exists('compare_at_price', $d)) {
                $v->compare_at_price = $d['compare_at_price'] !== '' ? (int) $d['compare_at_price'] : null;
            }
            $v->save();
            if (isset($d['stock']) && $d['stock'] !== '') {
                $delta = (int) $d['stock'] - (int) ($v->inventory?->on_hand ?? 0);
                if ($delta !== 0) {
                    try {
                        $inv->adjust($v->id, $delta, 'Nhập CSV', request()->user('staff')->id);
                    } catch (ApiException $e) {
                        $errors[] = "SKU {$d['sku']}: ".$e->getMessage();
                    }
                }
            }
            $updated++;
        }
        $this->products->flush();

        return ['data' => ['updated' => $updated, 'errors' => $errors]];
    }

    public function upload(Request $r)
    {
        $v = $r->validate(['file' => 'required|file|mimes:jpg,jpeg,png,webp|max:5120', 'type' => 'required|in:product,brand,banner,logo,return']);

        return response()->json(['data' => $this->images->storeTmp($r->file('file'), $v['type'])], 201);
    }

    public function deleteUpload(Request $r)
    {
        $v = $r->validate(['path' => 'required|string']);
        if (str_starts_with($v['path'], 'tmp/')) {
            $this->images->delete($v['path']);
        }

        return response()->noContent();
    }
}
