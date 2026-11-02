<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** Bộ lọc danh sách sản phẩm cho storefront (S02/S02b/S02c). */
class ProductSearch
{
    public function __construct(private PricingService $pricing, private ProductPresenter $presenter) {}

    public function query(Request $r): Builder
    {
        $q = Product::query()->where('status', 'active');

        if ($term = trim((string) $r->query('q'))) {
            $ids = SearchIndexer::enabled() ? app(SearchIndexer::class)->search($term) : null;
            if ($ids !== null) {
                $q->whereIn('id', $ids ?: [0]);
            } else {
                $q->where(function ($w) use ($term) {
                $w->whereRaw('MATCH(name) AGAINST (? IN BOOLEAN MODE)', [$term])
                    ->orWhere('name', 'like', "%$term%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', "%$term%"));
                });
            }
        }
        if ($slug = $r->query('category')) {
            $cat = Category::where('slug', $slug)->first();
            $ids = $cat ? array_merge([$cat->id], Category::where('parent_id', $cat->id)->pluck('id')->all()) : [0];
            $q->whereIn('category_id', $ids);
        }
        if ($brands = $this->csv($r->query('brand'))) {
            $q->whereHas('brand', fn ($b) => $b->whereIn('slug', $brands));
        }
        if ($genders = $this->csv($r->query('gender'))) {
            $q->whereIn('gender', array_merge($genders, in_array('men', $genders) || in_array('women', $genders) ? ['unisex'] : []));
        }
        $sizes = $this->csv($r->query('size'));
        $colors = $this->csv($r->query('color'));
        if ($sizes || $colors) {
            $q->whereHas('variants', function ($v) use ($sizes, $colors) {
                $v->where('is_active', true);
                if ($sizes) {
                    $v->whereIn('size_id', $sizes)->whereHas('inventory', fn ($i) => $i->whereRaw('on_hand - reserved > 0'));
                }
                if ($colors) {
                    $v->whereIn('color_id', $colors);
                }
            });
        }
        if ($r->filled('price_min')) {
            $q->where('base_price', '>=', (int) $r->query('price_min'));
        }
        if ($r->filled('price_max')) {
            $q->where('base_price', '<=', (int) $r->query('price_max'));
        }
        if ($r->boolean('on_sale')) {
            $promoIds = $this->pricing->productIdsOnPromotion();
            $q->where(fn ($w) => $w->whereIn('id', $promoIds ?: [0])
                ->orWhereHas('variants', fn ($v) => $v->whereColumn('compare_at_price', '>', 'price')));
        }

        return match ($r->query('sort')) {
            'price_asc' => $q->orderBy('base_price'),
            'price_desc' => $q->orderByDesc('base_price'),
            'best_seller' => $q->orderByDesc('sold_count'),
            default => $q->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    public function paginate(Request $r): array
    {
        $limit = min(48, max(1, (int) $r->query('limit', 12)));
        $page = $this->query($r)->with($this->presenter->relations())->paginate($limit);

        return [
            'data' => $page->getCollection()->map(fn ($p) => $this->presenter->card($p))->values(),
            'meta' => ['page' => $page->currentPage(), 'limit' => $limit, 'total' => $page->total()],
        ];
    }

    private function csv(mixed $v): array
    {
        return array_values(array_filter(is_array($v) ? $v : explode(',', (string) $v), fn ($x) => $x !== '' && $x !== null));
    }
}
