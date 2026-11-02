<?php

namespace App\Modules\Catalog\Http\Controllers;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Size;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Promotion\Models\Banner;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Models\Promotion;
use App\Modules\Promotion\Models\PromotionTarget;
use App\Modules\Shipping\Models\ShippingRate;
use App\Support\ApiException;
use Illuminate\Validation\Rule;

class ResourceRegistry
{
    public static function get(string $name): array
    {
        return self::all()[$name] ?? abort(404);
    }

    public static function all(): array
    {
        return [
            'brands' => [
                'model' => Brand::class, 'search' => ['name'], 'slug' => true, 'flush' => true, 'order' => [['name', 'asc']],
                'images' => ['logo_path' => 'logo'], 'dir' => 'brands',
                'rules' => fn ($m) => ['name' => 'required|string|max:120', 'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($m?->id)], 'logo_path' => 'nullable|string', 'is_active' => 'boolean'],
            ],
            'categories' => [
                'model' => Category::class, 'search' => ['name'], 'slug' => true, 'flush' => true, 'order' => [['sort_order', 'asc'], ['id', 'asc']],
                'rules' => fn ($m) => ['name' => 'required|string|max:120', 'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($m?->id)],
                    'parent_id' => ['nullable', 'exists:categories,id', Rule::notIn([$m?->id])], 'sort_order' => 'integer|min:0', 'is_active' => 'boolean'],
                'guard' => function ($m) {
                    if (Category::where('parent_id', $m->id)->exists()) {
                        throw ApiException::conflict('IN_USE', 'Danh mục còn danh mục con');
                    }
                },
            ],
            'colors' => [
                'model' => Color::class, 'search' => ['name'], 'flush' => true, 'order' => [['name', 'asc']],
                'rules' => fn ($m) => ['name' => 'required|string|max:60', 'hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/']],
            ],
            'sizes' => [
                'model' => Size::class, 'flush' => true, 'filters' => ['system'], 'order' => [['system', 'asc'], ['sort_order', 'asc']],
                'rules' => fn ($m) => ['system' => 'required|in:EU,US,UK,CM', 'label' => 'required|string|max:20', 'sort_order' => 'integer|min:0'],
            ],
            'coupons' => [
                'model' => Coupon::class, 'search' => ['code'], 'upper' => ['code'], 'filters' => ['type', 'is_active'],
                'rules' => fn ($m) => [
                    'code' => ['required', 'string', 'max:40', Rule::unique('coupons', 'code')->ignore($m?->id)], 'type' => 'required|in:percent,fixed,free_ship',
                    'value' => 'required|integer|min:0', 'min_order' => 'integer|min:0', 'max_discount' => 'nullable|integer|min:0',
                    'usage_limit' => 'nullable|integer|min:1', 'per_user_limit' => 'nullable|integer|min:1',
                    'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean',
                ],
                'present' => fn ($m, $a) => $a + ['used' => \App\Modules\Promotion\Models\CouponRedemption::where('coupon_id', $m->id)->count()],
            ],
            'promotions' => [
                'model' => Promotion::class, 'search' => ['name'], 'with' => ['targets'], 'flush' => true,
                'rules' => fn ($m) => [
                    'name' => 'required|string|max:255', 'discount_type' => 'required|in:percent,fixed', 'value' => 'required|integer|min:1',
                    'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date|after_or_equal:starts_at', 'is_active' => 'boolean',
                    'targets' => 'array', 'targets.*.product_id' => 'nullable|integer', 'targets.*.category_id' => 'nullable|integer', 'targets.*.brand_id' => 'nullable|integer',
                ],
                'strip' => ['targets'],
                'after' => function ($m, $r) {
                    if ($r->has('targets')) {
                        $m->targets()->delete();
                        foreach ($r->input('targets', []) as $t) {
                            PromotionTarget::create(['promotion_id' => $m->id, 'product_id' => $t['product_id'] ?? null, 'category_id' => $t['category_id'] ?? null, 'brand_id' => $t['brand_id'] ?? null]);
                        }
                    }
                },
            ],
            'banners' => [
                'model' => Banner::class, 'search' => ['title'], 'flush' => true, 'order' => [['sort_order', 'asc']],
                'images' => ['image_path' => 'image', 'mobile_image_path' => 'mobile_image'], 'dir' => 'banners',
                'rules' => fn ($m) => ['title' => 'required|string|max:255', 'image_path' => 'nullable|string', 'mobile_image_path' => 'nullable|string', 'link' => 'nullable|string|max:255',
                    'position' => 'required|in:hero,promo,popup', 'sort_order' => 'integer|min:0', 'starts_at' => 'nullable|date', 'ends_at' => 'nullable|date', 'is_active' => 'boolean'],
            ],
            'pages' => [
                'model' => CmsPage::class, 'sanitize' => ['content'], 'search' => ['title', 'slug'], 'flush' => false, 'order' => [['title', 'asc']],
                'rules' => fn ($m) => ['slug' => ['required', 'string', 'max:255', Rule::unique('cms_pages', 'slug')->ignore($m?->id)], 'title' => 'required|string|max:255', 'content' => 'nullable|string', 'is_published' => 'boolean'],
            ],
            'shipping-rates' => [
                'model' => ShippingRate::class, 'search' => ['zone'], 'order' => [['id', 'asc']],
                'rules' => fn ($m) => ['zone' => 'required|string|max:80', 'province_codes' => 'nullable|array', 'province_codes.*' => 'string', 'min_weight' => 'integer|min:0', 'max_weight' => 'integer|min:0',
                    'fee' => 'required|integer|min:0', 'free_threshold' => 'nullable|integer|min:0'],
            ],
        ];
    }
}
