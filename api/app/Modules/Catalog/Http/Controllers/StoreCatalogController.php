<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Size;
use App\Modules\Catalog\Services\PricingService;
use App\Modules\Catalog\Services\ProductPresenter;
use App\Modules\Catalog\Services\ProductSearch;
use App\Modules\Order\Models\OrderItem;
use App\Modules\Order\Models\Review;
use App\Modules\Promotion\Models\Banner;
use App\Support\ApiException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StoreCatalogController
{
    public function __construct(private ProductPresenter $presenter, private ProductSearch $search, private PricingService $pricing) {}

    /** Cache chỉ lưu mảng thuần (Laravel không unserialize object mặc định). */
    private function cached(array $tags, string $key, int $ttl, \Closure $fn): array
    {
        return Cache::tags($tags)->remember($key, $ttl, fn () => json_decode(json_encode($fn()), true));
    }

    public function home()
    {
        return $this->cached(['catalog', 'home'], 'home', 120, function () {
            $rel = $this->presenter->relations();
            $inStock = fn ($q) => $q->where('status', 'active')->whereHas('variants.inventory', fn ($i) => $i->whereRaw('on_hand - reserved > 0'));
            $banners = Banner::where('is_active', true)->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))->orderBy('sort_order')->get()
                ->map(fn ($b) => ['id' => $b->id, 'title' => $b->title, 'position' => $b->position, 'link' => $b->link, 'image' => storage_url($b->image_path), 'mobile_image' => storage_url($b->mobile_image_path)]);
            $flash = collect($this->pricing->activePromotions())->sortBy('ends_at')->first();

            return ['data' => [
                'banners' => $banners,
                'categories' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')->get(['id', 'name', 'slug']),
                'new_arrivals' => $inStock(Product::query())->with($rel)->orderByDesc('created_at')->limit(8)->get()->map(fn ($p) => $this->presenter->card($p)),
                'best_sellers' => $inStock(Product::query())->with($rel)->orderByDesc('sold_count')->limit(8)->get()->map(fn ($p) => $this->presenter->card($p)),
                'brands' => Brand::where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug', 'logo_path'])->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'slug' => $b->slug, 'logo' => storage_url($b->logo_path)]),
                'flash_sale' => $flash ? ['name' => $flash['name'], 'ends_at' => $flash['ends_at'], 'type' => $flash['type'], 'value' => $flash['value']] : null,
            ]];
        });
    }

    public function categories()
    {
        return $this->cached(['catalog'], 'categories', 300, fn () => ['data' => Category::whereNull('parent_id')->where('is_active', true)->orderBy('sort_order')
            ->with(['children' => fn ($q) => $q->where('is_active', true)])->get()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'children' => $c->children->map(fn ($x) => ['id' => $x->id, 'name' => $x->name, 'slug' => $x->slug])->values()])]);
    }

    public function brands()
    {
        return $this->cached(['catalog'], 'brands', 300, fn () => ['data' => Brand::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'slug' => $b->slug, 'logo' => storage_url($b->logo_path)])]);
    }

    /** Màu & size cho bộ lọc / hướng dẫn size. */
    public function attributes()
    {
        return ['data' => [
            'colors' => Color::orderBy('name')->get(['id', 'name', 'hex']),
            'sizes' => Size::orderBy('system')->orderBy('sort_order')->get(['id', 'system', 'label']),
        ]];
    }

    public function index(Request $r)
    {
        return $this->search->paginate($r);
    }

    public function show(string $slug)
    {
        $p = Product::query()->where('slug', $slug)->whereIn('status', ['active'])
            ->with($this->presenter->relations())->withCount('reviews')->withAvg('reviews', 'rating')->firstOrFail();
        $related = Product::where('status', 'active')->where('id', '!=', $p->id)
            ->where(fn ($q) => $q->where('category_id', $p->category_id)->orWhere('brand_id', $p->brand_id))
            ->with($this->presenter->relations())->orderByDesc('sold_count')->limit(4)->get();

        return ['data' => $this->presenter->detail($p) + ['related' => $related->map(fn ($x) => $this->presenter->card($x))->values()]];
    }

    public function reviews(string $slug, Request $r)
    {
        $p = Product::where('slug', $slug)->firstOrFail();
        $page = Review::with('user:id,full_name')->where('product_id', $p->id)->where('status', 'approved')->orderByDesc('id')->paginate(10);

        return ['data' => $page->getCollection()->map(fn ($x) => [
            'id' => $x->id, 'rating' => $x->rating, 'content' => $x->content, 'author' => $x->user->full_name, 'at' => $x->created_at,
        ])->values(), 'meta' => ['page' => $page->currentPage(), 'limit' => 10, 'total' => $page->total()]];
    }

    public function storeReview(string $slug, Request $r)
    {
        $user = Auth::guard('customer')->user() ?? throw ApiException::unauthorized();
        $p = Product::where('slug', $slug)->firstOrFail();
        $v = $r->validate(['rating' => 'required|integer|between:1,5', 'content' => 'nullable|string|max:2000']);
        $item = OrderItem::where('product_id', $p->id)->whereHas('order', fn ($o) => $o->where('user_id', $user->id)->where('status', 'completed'))->first();
        if (! $item) {
            throw ApiException::forbidden('Chỉ khách đã mua sản phẩm mới được đánh giá');
        }
        if (Review::where('product_id', $p->id)->where('user_id', $user->id)->exists()) {
            throw ApiException::conflict('REVIEW_EXISTS', 'Bạn đã đánh giá sản phẩm này');
        }
        Review::create($v + ['product_id' => $p->id, 'user_id' => $user->id, 'order_item_id' => $item->id, 'status' => 'pending']);

        return response()->json(['data' => ['ok' => true, 'message' => 'Đánh giá của bạn sẽ hiển thị sau khi được duyệt']], 201);
    }
}
