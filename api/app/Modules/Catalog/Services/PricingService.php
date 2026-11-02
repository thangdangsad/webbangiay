<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Promotion\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/** Giá bán = giá khuyến mãi đang hiệu lực (nếu có) hoặc giá niêm yết của biến thể. */
class PricingService
{
    private ?Collection $promos = null;

    /** @return Collection<int,array{type:string,value:int,name:string,products:array,categories:array,brands:array}> */
    public function activePromotions(): Collection
    {
        return $this->promos ??= collect(Cache::tags(['catalog'])->remember('promotions:active', 60, function () {
            return Promotion::with('targets')->where('is_active', true)
                ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
                ->get()->map(fn ($p) => [
                    'id' => $p->id, 'name' => $p->name, 'type' => $p->discount_type, 'value' => (int) $p->value, 'ends_at' => $p->ends_at?->toIso8601String(),
                    'products' => $p->targets->pluck('product_id')->filter()->values()->all(),
                    'categories' => $p->targets->pluck('category_id')->filter()->values()->all(),
                    'brands' => $p->targets->pluck('brand_id')->filter()->values()->all(),
                ])->all();
        }));
    }

    /** Khuyến mãi tốt nhất áp cho sản phẩm với giá niêm yết $price. */
    public function bestPromotion(Product $product, int $price): ?array
    {
        $best = null;
        $bestDiscount = 0;
        foreach ($this->activePromotions() as $p) {
            $hit = in_array($product->id, $p['products'], true)
                || ($product->category_id && in_array($product->category_id, $p['categories'], true))
                || ($product->brand_id && in_array($product->brand_id, $p['brands'], true));
            if (! $hit) {
                continue;
            }
            $d = $p['type'] === 'percent' ? intdiv($price * min(100, $p['value']), 100) : $p['value'];
            if ($d > $bestDiscount) {
                $bestDiscount = min($d, $price);
                $best = $p + ['discount' => $bestDiscount];
            }
        }

        return $best;
    }

    /** @return array{price:int,compare_at_price:?int,promotion:?string} */
    public function price(ProductVariant $v, ?Product $product = null): array
    {
        $product ??= $v->product;
        $list = (int) $v->price;
        $promo = $this->bestPromotion($product, $list);
        if ($promo) {
            return ['price' => $list - $promo['discount'], 'compare_at_price' => $list, 'promotion' => $promo['name']];
        }

        return ['price' => $list, 'compare_at_price' => $v->compare_at_price && $v->compare_at_price > $list ? (int) $v->compare_at_price : null, 'promotion' => null];
    }

    public function productIdsOnPromotion(): array
    {
        $ids = collect();
        foreach ($this->activePromotions() as $p) {
            $q = Product::query()->where('status', 'active');
            $q->where(function ($w) use ($p) {
                $w->whereIn('id', $p['products'] ?: [0])
                    ->orWhereIn('category_id', $p['categories'] ?: [0])
                    ->orWhereIn('brand_id', $p['brands'] ?: [0]);
            });
            $ids = $ids->merge($q->pluck('id'));
        }

        return $ids->unique()->values()->all();
    }
}
