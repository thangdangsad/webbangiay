<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Services\InventoryService;
use App\Support\ApiException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(private ImageStore $images, private InventoryService $inventory) {}

    public static function sanitizeHtml(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }
        $clean = strip_tags($html, '<p><br><ul><ol><li><strong><b><em><i><u><h2><h3><h4><a><table><thead><tbody><tr><th><td>');
        $clean = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);

        return preg_replace('/(href|src)\s*=\s*(["\'])\s*javascript:[^"\']*\2/i', '$1=$2#$2', $clean);
    }

    public function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = Str::slug($base) ?: 'san-pham';
        $try = $slug;
        $i = 2;
        while (Product::withTrashed()->where('slug', $try)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $try = $slug.'-'.$i++;
        }

        return $try;
    }

    public function save(?Product $product, array $d, ?int $staffId): Product
    {
        $product = DB::transaction(function () use ($product, $d, $staffId) {
            $isNew = ! $product;
            $product ??= new Product();
            $product->fill([
                'name' => $d['name'], 'brand_id' => $d['brand_id'] ?? null, 'category_id' => $d['category_id'] ?? null,
                'gender' => $d['gender'] ?? 'unisex', 'description' => self::sanitizeHtml($d['description'] ?? null),
                'material' => $d['material'] ?? null, 'status' => $d['status'] ?? 'draft',
                'seo_title' => $d['seo_title'] ?? null, 'seo_description' => $d['seo_description'] ?? null,
            ]);
            $product->slug = $this->uniqueSlug($d['slug'] ?? $d['name'], $product->id);
            $product->save();

            if (isset($d['variants'])) {
                $this->syncVariants($product, $d['variants'], $staffId);
            }
            if (isset($d['images'])) {
                $this->syncImages($product, $d['images']);
            }
            $product->base_price = (int) ($product->variants()->where('is_active', true)->min('price') ?? 0);
            if ($product->status === 'active' && ! $product->variants()->where('is_active', true)->exists()) {
                throw ApiException::invalid('Sản phẩm đang bán phải có ít nhất một biến thể');
            }
            $product->save();

            return $product;
        });
        $this->flush();
        app(SearchIndexer::class)->sync($product);

        return $product->load('variants.inventory', 'images');
    }

    public function syncVariants(Product $product, array $rows, ?int $staffId): void
    {
        $skus = array_column($rows, 'sku');
        if (count($skus) !== count(array_unique($skus))) {
            throw ApiException::invalid('Mã SKU bị trùng trong danh sách biến thể');
        }
        $keep = [];
        foreach ($rows as $row) {
            $variant = null;
            if (! empty($row['id'])) {
                $variant = $product->variants()->find($row['id']);
            }
            $variant ??= $product->variants()->where('color_id', $row['color_id'])->where('size_id', $row['size_id'])->first();
            $dup = ProductVariant::where('sku', $row['sku'])->when($variant, fn ($q) => $q->where('id', '!=', $variant->id))->exists();
            if ($dup) {
                throw ApiException::invalid("SKU {$row['sku']} đã tồn tại");
            }
            $isNew = ! $variant;
            $variant ??= new ProductVariant(['product_id' => $product->id]);
            $variant->fill([
                'sku' => $row['sku'], 'color_id' => $row['color_id'], 'size_id' => $row['size_id'], 'price' => $row['price'],
                'compare_at_price' => $row['compare_at_price'] ?? null, 'barcode' => $row['barcode'] ?? null,
                'weight_g' => $row['weight_g'] ?? 800, 'is_active' => $row['is_active'] ?? true,
            ])->save();
            $this->inventory->ensureRow($variant->id);
            if ($isNew && ! empty($row['stock'])) {
                $this->inventory->restock([$variant->id => (int) $row['stock']], 'receipt', 'Tồn đầu kỳ khi tạo sản phẩm', 'product', $product->id, $staffId);
            }
            $keep[] = $variant->id;
        }
        foreach ($product->variants()->whereNotIn('id', $keep)->get() as $old) {
            if (DB::table('order_items')->where('variant_id', $old->id)->exists()) {
                $old->update(['is_active' => false]); // đã phát sinh đơn: chỉ ẩn
            } else {
                $old->delete();
            }
        }
    }

    public function syncImages(Product $product, array $rows): void
    {
        $keep = [];
        foreach (array_values($rows) as $i => $row) {
            $path = $this->images->commit($row['path'], "products/{$product->id}");
            $img = ProductImage::where('product_id', $product->id)->where('path', $path)->first() ?? new ProductImage(['product_id' => $product->id]);
            $meta = [];
            if (! $img->exists && ($disk = \Illuminate\Support\Facades\Storage::disk('public'))->exists($path)) {
                $size = @getimagesizefromstring($disk->get($path));
                $meta = ['width' => $size[0] ?? null, 'height' => $size[1] ?? null, 'size_bytes' => $disk->size($path)];
            }
            $img->fill(['path' => $path, 'color_id' => $row['color_id'] ?? null, 'alt' => $row['alt'] ?? null, 'sort_order' => $i, 'is_primary' => (bool) ($row['is_primary'] ?? $i === 0)] + $meta)->save();
            $keep[] = $img->id;
        }
        foreach (ProductImage::where('product_id', $product->id)->whereNotIn('id', $keep)->get() as $gone) {
            $this->images->delete($gone->path);
            $gone->delete();
        }
        $first = ProductImage::where('product_id', $product->id)->where('is_primary', true)->first()
            ?? ProductImage::where('product_id', $product->id)->orderBy('sort_order')->first();
        if ($first) {
            ProductImage::where('product_id', $product->id)->update(['is_primary' => false]);
            $first->update(['is_primary' => true]);
        }
    }

    /** Sản phẩm đã có đơn chỉ ẩn, không xóa cứng. */
    public function delete(Product $product): void
    {
        if (DB::table('order_items')->where('product_id', $product->id)->exists()) {
            $product->update(['status' => 'hidden']);
        } else {
            $product->delete();
        }
        $this->flush();
        app(SearchIndexer::class)->sync($product);
    }

    public function flush(): void
    {
        Cache::tags(['catalog', 'home'])->flush();
    }
}
