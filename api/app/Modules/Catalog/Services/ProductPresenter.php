<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use Illuminate\Support\Collection;

class ProductPresenter
{
    public function __construct(private PricingService $pricing) {}

    public function relations(): array
    {
        return ['brand:id,name,slug', 'category:id,name,slug', 'images', 'variants.color', 'variants.size', 'variants.inventory'];
    }

    private function variantRows(Product $p): Collection
    {
        return $p->variants->where('is_active', true)->map(function ($v) use ($p) {
            $pr = $this->pricing->price($v, $p);
            $inv = $v->inventory;

            return [
                'id' => $v->id, 'sku' => $v->sku, 'color_id' => $v->color_id, 'size_id' => $v->size_id,
                'size' => $v->size?->label, 'size_system' => $v->size?->system, 'size_order' => $v->size?->sort_order,
                'price' => $pr['price'], 'compare_at_price' => $pr['compare_at_price'], 'promotion' => $pr['promotion'],
                'available' => $inv ? max(0, $inv->on_hand - $inv->reserved) : 0,
            ];
        })->values();
    }

    public function card(Product $p): array
    {
        $variants = $this->variantRows($p);
        $cheapest = $variants->sortBy('price')->first();
        $inStock = $variants->contains(fn ($v) => $v['available'] > 0);
        $primary = $p->images->firstWhere('is_primary', true) ?? $p->images->first();
        $second = $p->images->where('id', '!=', $primary?->id)->first();
        $price = $cheapest['price'] ?? (int) $p->base_price;
        $compare = $cheapest['compare_at_price'] ?? null;

        $labels = [];
        if ($compare) {
            $labels[] = 'sale';
        }
        if ($p->created_at && $p->created_at->gt(now()->subDays(30))) {
            $labels[] = 'new';
        }
        if (! $inStock) {
            $labels[] = 'out_of_stock';
        }

        return [
            'id' => $p->id, 'name' => $p->name, 'slug' => $p->slug,
            'brand' => $p->brand ? ['id' => $p->brand->id, 'name' => $p->brand->name, 'slug' => $p->brand->slug] : null,
            'price' => $price, 'compare_at_price' => $compare,
            'discount_percent' => $compare ? (int) round(($compare - $price) * 100 / $compare) : 0,
            'image' => $primary ? $primary->urls() : null,
            'hover_image' => $second ? $second->urls() : null,
            'colors' => $p->variants->pluck('color')->filter()->unique('id')->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'hex' => $c->hex])->values(),
            'labels' => $labels, 'in_stock' => $inStock,
        ];
    }

    public function detail(Product $p): array
    {
        $variants = $this->variantRows($p);
        $rating = $p->reviews_avg_rating ?? null;

        return $this->card($p) + [
            'description' => $p->description, 'material' => $p->material, 'gender' => $p->gender,
            'category' => $p->category ? ['id' => $p->category->id, 'name' => $p->category->name, 'slug' => $p->category->slug] : null,
            'seo_title' => $p->seo_title ?: $p->name, 'seo_description' => $p->seo_description,
            'images' => $p->images->map(fn ($i) => ['id' => $i->id, 'color_id' => $i->color_id, 'alt' => $i->alt ?: $p->name, 'is_primary' => (bool) $i->is_primary] + $i->urls())->values(),
            'sizes' => $variants->unique('size_id')->sortBy('size_order')->map(fn ($v) => ['id' => $v['size_id'], 'label' => $v['size'], 'system' => $v['size_system']])->values(),
            'variants' => $variants->map(fn ($v) => collect($v)->except(['size_order'])->all())->values(),
            'rating' => ['avg' => $rating ? round((float) $rating, 1) : null, 'count' => (int) ($p->reviews_count ?? 0)],
            'sold_count' => (int) $p->sold_count,
        ];
    }
}
