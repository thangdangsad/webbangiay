<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Color;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductImage;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Catalog\Models\Size;
use App\Modules\Catalog\Services\ProductService;
use App\Modules\Cms\Models\CmsPage;
use App\Modules\Promotion\Models\Banner;
use App\Modules\Promotion\Models\Coupon;
use App\Modules\Promotion\Models\Promotion;
use App\Modules\Promotion\Models\PromotionTarget;
use App\Modules\Shipping\Models\ShippingRate;
use App\Modules\User\Models\Permission;
use App\Modules\User\Models\Role;
use App\Modules\User\Models\StaffUser;
use App\Modules\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->rbac();
        $this->catalog();
        $this->marketing();
        $this->content();
        $this->shipping();
    }

    private function rbac(): void
    {
        $matrix = [
            'admin' => ['Admin', ['*']],
            'manager' => ['Quản lý', ['dashboard:read', 'report:read', 'product:read', 'product:write', 'inventory:read', 'inventory:write', 'order:read', 'order:confirm', 'order:cancel', 'order:fulfil', 'order:complete', 'payment:manage', 'return:read', 'return:process', 'return:restock', 'customer:read', 'customer:write', 'marketing:manage', 'content:manage', 'audit:read']],
            'warehouse' => ['Nhân viên kho', ['product:read', 'inventory:read', 'inventory:write', 'order:read', 'order:fulfil', 'return:read', 'return:restock']],
            'cs' => ['CSKH', ['product:read', 'order:read', 'order:confirm', 'order:cancel', 'return:read', 'return:process', 'customer:read', 'customer:write']],
        ];
        $all = ['dashboard:read', 'report:read', 'product:read', 'product:write', 'inventory:read', 'inventory:write', 'order:read', 'order:confirm', 'order:cancel', 'order:fulfil', 'order:complete', 'payment:manage', 'return:read', 'return:process', 'return:restock', 'customer:read', 'customer:write', 'marketing:manage', 'content:manage', 'settings:manage', 'staff:manage', 'audit:read'];
        foreach ($all as $code) {
            Permission::firstOrCreate(['code' => $code]);
        }
        foreach ($matrix as $code => [$name, $perms]) {
            $role = Role::firstOrCreate(['code' => $code], ['name' => $name]);
            $role->permissions()->sync(Permission::whereIn('code', $perms === ['*'] ? $all : $perms)->pluck('id'));
        }
        $staff = [
            ['admin@solehouse.vn', 'Chủ shop', 'admin', 'Admin@123'], ['manager@solehouse.vn', 'Quản lý Lan', 'manager', 'Manager@123'],
            ['kho@solehouse.vn', 'Kho Minh', 'warehouse', 'Kho@12345'], ['cskh@solehouse.vn', 'CSKH Hoa', 'cs', 'Cskh@12345'],
        ];
        foreach ($staff as [$email, $name, $role, $pass]) {
            StaffUser::firstOrCreate(['email' => $email], ['full_name' => $name, 'role_id' => Role::where('code', $role)->value('id'), 'password_hash' => Hash::make($pass)]);
        }
        User::firstOrCreate(['email' => 'khach@solehouse.vn'], ['full_name' => 'Nguyễn Văn A', 'phone' => '+84901234567', 'password_hash' => Hash::make('Khach@123'), 'email_verified_at' => now()]);
    }

    private function catalog(): void
    {
        $brands = ['Nike', 'Adidas', 'Asics', 'Vans', 'Converse', 'Reebok', 'On', 'New Balance'];
        foreach ($brands as $b) {
            Brand::firstOrCreate(['slug' => Str::slug($b)], ['name' => $b]);
        }
        $cats = ['Sneaker' => ['Sneaker cổ thấp', 'Sneaker cổ cao'], 'Chạy bộ' => [], 'Giày da' => [], 'Sandal' => []];
        $i = 0;
        foreach ($cats as $name => $children) {
            $parent = Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $i++]);
            foreach ($children as $j => $c) {
                Category::firstOrCreate(['slug' => Str::slug($c)], ['name' => $c, 'parent_id' => $parent->id, 'sort_order' => $j]);
            }
        }
        $colors = ['Đen' => '#111111', 'Trắng' => '#f5f5f5', 'Đỏ' => '#e8472b', 'Xanh dương' => '#2563eb', 'Xám' => '#9ca3af', 'Be' => '#d9c7a3'];
        foreach ($colors as $n => $hex) {
            Color::firstOrCreate(['name' => $n], ['hex' => $hex]);
        }
        foreach (range(35, 46) as $k => $eu) {
            Size::firstOrCreate(['system' => 'EU', 'label' => (string) $eu], ['sort_order' => $k]);
        }

        if (Product::exists()) {
            return;
        }
        $svc = app(ProductService::class);
        $catalog = [
            ['Air Runner Pro 2', 'Nike', 'chay-bo', 'men', 3490000, 2890000, ['Đen', 'Đỏ', 'Xanh dương']],
            ['Ultra Boost Lite', 'Adidas', 'chay-bo', 'unisex', 3250000, null, ['Đen', 'Trắng', 'Xám']],
            ['Classic Leather 90', 'Reebok', 'sneaker-co-thap', 'unisex', 1790000, null, ['Trắng', 'Đen']],
            ['Court Vision Low', 'Nike', 'sneaker-co-thap', 'women', 2450000, 1990000, ['Trắng', 'Be']],
            ['Cloud Trail X', 'On', 'chay-bo', 'men', 4100000, null, ['Xám', 'Xanh dương']],
            ['Old Skool Vans', 'Vans', 'sneaker-co-thap', 'unisex', 1650000, null, ['Đen', 'Trắng', 'Đỏ']],
            ['Chuck 70 High', 'Converse', 'sneaker-co-cao', 'unisex', 2250000, null, ['Đen', 'Be', 'Đỏ']],
            ['Gel Kayano 30', 'Asics', 'chay-bo', 'men', 4890000, 4290000, ['Xanh dương', 'Đen']],
            ['Gel Nimbus 25', 'Asics', 'chay-bo', 'women', 4590000, null, ['Trắng', 'Xám']],
            ['574 Core', 'New Balance', 'sneaker-co-thap', 'unisex', 2390000, null, ['Xám', 'Be']],
            ['Stan Smith', 'Adidas', 'sneaker-co-thap', 'unisex', 2600000, 2190000, ['Trắng']],
            ['Air Force 1 \'07', 'Nike', 'sneaker-co-thap', 'unisex', 3200000, null, ['Trắng', 'Đen']],
            ['Sk8-Hi', 'Vans', 'sneaker-co-cao', 'unisex', 1950000, null, ['Đen', 'Trắng']],
            ['Oxford Da Bò Cổ Điển', 'Reebok', 'giay-da', 'men', 2790000, null, ['Đen', 'Be']],
            ['Sandal Quai Ngang Comfort', 'Adidas', 'sandal', 'unisex', 790000, null, ['Đen', 'Xanh dương']],
            ['Fresh Foam 1080', 'New Balance', 'chay-bo', 'women', 4290000, 3790000, ['Xám', 'Đỏ']],
        ];
        $colorMap = Color::pluck('id', 'name');
        $sizes = Size::where('system', 'EU')->orderBy('sort_order')->get();
        foreach ($catalog as $n => [$name, $brand, $cat, $gender, $list, $sale, $cols]) {
            $variants = [];
            foreach ($cols as $c) {
                foreach ($sizes->filter(fn ($s) => (int) $s->label >= ($gender === 'women' ? 35 : 38) && (int) $s->label <= ($gender === 'women' ? 41 : 45)) as $s) {
                    $variants[] = [
                        'sku' => strtoupper(Str::slug($name, '').'-'.Str::slug($c, '').'-'.$s->label), 'color_id' => $colorMap[$c], 'size_id' => $s->id,
                        'price' => $list, 'compare_at_price' => null, 'weight_g' => 850, 'stock' => ($n + (int) $s->label) % 7 === 0 ? 0 : 5 + (($n * 3 + (int) $s->label) % 14),
                    ];
                }
            }
            if ($sale) {
                $variants = array_map(fn ($v) => $v + ['_sale' => true], $variants);
            }
            $variants = array_map(function ($v) use ($sale, $list) {
                if ($sale) {
                    $v['price'] = $sale;
                    $v['compare_at_price'] = $list;
                }
                unset($v['_sale']);

                return $v;
            }, $variants);
            $images = [];
            foreach ($cols as $k => $c) {
                $images[] = ['path' => $this->makeImage($name, Color::where('name', $c)->value('hex'), $n, $k), 'color_id' => $colorMap[$c], 'alt' => "$name màu $c", 'is_primary' => $k === 0];
                $images[] = ['path' => $this->makeImage($name, Color::where('name', $c)->value('hex'), $n, $k, true), 'color_id' => $colorMap[$c], 'alt' => "$name màu $c (nghiêng)"];
            }
            $p = $svc->save(null, [
                'name' => $name, 'brand_id' => Brand::where('name', $brand)->value('id'), 'category_id' => Category::where('slug', $cat)->value('id'),
                'gender' => $gender, 'status' => 'active', 'material' => 'Vải lưới thoáng khí, đế cao su', 'variants' => $variants, 'images' => $images,
                'description' => "<p><strong>$name</strong> từ $brand — thiết kế êm ái, bền bỉ, phù hợp đi hằng ngày lẫn vận động.</p><ul><li>Đế ngoài cao su chống trơn</li><li>Lót giày êm, thoáng khí</li><li>Bảo hành keo đế 6 tháng</li></ul>",
            ], null);
            DB::table('products')->where('id', $p->id)->update(['sold_count' => (16 - $n) * 7, 'created_at' => now()->subDays($n * 3)]);
        }
    }

    /** Ảnh giày minh họa (GD) → tmp/ để ProductService chuyển vào products/{id}. */
    private function makeImage(string $name, string $hex, int $seed, int $variant, bool $angled = false): string
    {
        $mgr = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        $size = 900;
        $im = imagecreatetruecolor($size, $size);
        $bgs = [[255, 232, 225], [230, 239, 255], [241, 241, 238], [233, 247, 238], [255, 243, 214], [236, 236, 244], [251, 233, 240], [228, 244, 246]];
        $bg = $bgs[($seed + $variant) % count($bgs)];
        imagefill($im, 0, 0, imagecolorallocate($im, ...$bg));
        sscanf($hex, '#%02x%02x%02x', $r, $g, $b);
        $upper = imagecolorallocate($im, $r, $g, $b);
        $dark = imagecolorallocate($im, max(0, $r - 50), max(0, $g - 50), max(0, $b - 50));
        $sole = imagecolorallocate($im, 245, 245, 245);
        $shadow = imagecolorallocatealpha($im, 0, 0, 0, 100);
        $off = $angled ? 40 : 0;
        imagefilledellipse($im, 450, 650, 640, 60, $shadow);
        imagefilledpolygon($im, [130, 560 - $off / 2, 190, 330 + $off, 330, 300 + $off, 420, 400, 560, 430, 700, 470, 780, 560, 780, 600, 130, 600], $upper);
        imagefilledpolygon($im, [130, 560, 180, 380 + $off, 260, 360 + $off, 250, 560], $dark);
        imagefilledpolygon($im, [420, 400, 480, 380, 540, 430, 470, 440], $dark);
        imagefilledrectangle($im, 110, 590, 800, 640, $sole);
        imagefilledellipse($im, 110, 615, 50, 50, $sole);
        imagefilledellipse($im, 800, 615, 50, 50, $sole);
        imagesetthickness($im, 6);
        imageline($im, 340, 380 + $off, 480, 470, imagecolorallocate($im, 255, 255, 255));
        imageline($im, 380, 360 + $off, 520, 450, imagecolorallocate($im, 255, 255, 255));
        $tmp = tempnam(sys_get_temp_dir(), 'shoe').'.png';
        imagepng($im, $tmp);
        $path = 'tmp/'.Str::uuid().'.webp';
        $disk = Storage::disk('public');
        $disk->put($path, (string) $mgr->read($tmp)->toWebp(85));
        foreach (['md' => 600, 'sm' => 300] as $sfx => $px) {
            $disk->put(image_variant($path, $sfx), (string) $mgr->read($tmp)->scaleDown($px, $px)->toWebp(82));
        }
        unlink($tmp);

        return $path;
    }

    private function marketing(): void
    {
        Coupon::firstOrCreate(['code' => 'WELCOME100'], ['type' => 'fixed', 'value' => 100000, 'min_order' => 1000000, 'per_user_limit' => 1, 'usage_limit' => 500, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(6)]);
        Coupon::firstOrCreate(['code' => 'SALE10'], ['type' => 'percent', 'value' => 10, 'min_order' => 500000, 'max_discount' => 300000, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3)]);
        Coupon::firstOrCreate(['code' => 'FREESHIP'], ['type' => 'free_ship', 'value' => 0, 'min_order' => 300000, 'starts_at' => now()->subDay(), 'ends_at' => now()->addMonths(3)]);
        if (! Promotion::exists()) {
            $p = Promotion::create(['name' => 'Flash sale cuối tuần', 'discount_type' => 'percent', 'value' => 15, 'starts_at' => now()->subHour(), 'ends_at' => now()->addDays(2)]);
            PromotionTarget::create(['promotion_id' => $p->id, 'brand_id' => Brand::where('slug', 'vans')->value('id')]);
        }
        if (! Banner::exists()) {
            Banner::create(['title' => 'Bộ sưu tập Thu Đông 2026', 'position' => 'hero', 'link' => '/san-pham', 'sort_order' => 1]);
            Banner::create(['title' => 'Flash sale giảm đến 40%', 'position' => 'promo', 'link' => '/khuyen-mai', 'sort_order' => 2]);
        }
    }

    private function content(): void
    {
        $pages = [
            'chinh-sach-doi-tra' => ['Chính sách đổi trả', '<h2>Đổi trả trong 7 ngày</h2><p>Giày chưa qua sử dụng, còn nguyên hộp và tem mác. Đổi size miễn phí lần đầu.</p><ul><li>Liên hệ CSKH hoặc gửi yêu cầu trong mục "Đơn hàng"</li><li>Hoàn tiền trong 3–5 ngày làm việc sau khi nhận lại hàng</li></ul>'],
            'chinh-sach-bao-hanh' => ['Chính sách bảo hành', '<p>Bảo hành keo đế 6 tháng với lỗi do nhà sản xuất.</p>'],
            'chinh-sach-van-chuyen' => ['Chính sách vận chuyển', '<p>Giao toàn quốc 2–4 ngày. Miễn phí vận chuyển cho đơn từ 1.000.000đ. Được kiểm tra hàng khi nhận.</p>'],
            'chinh-sach-bao-mat' => ['Chính sách bảo mật', '<p>SoleHouse cam kết bảo mật thông tin khách hàng và không lưu dữ liệu thẻ thanh toán.</p>'],
            'gioi-thieu' => ['Giới thiệu', '<p>SoleHouse — cửa hàng giày chính hãng, giao nhanh, đổi size dễ dàng.</p>'],
            'huong-dan-chon-size' => ['Hướng dẫn chọn size', '<h2>Cách đo chân</h2><p>Đặt chân lên tờ giấy, đánh dấu gót và ngón dài nhất, đo khoảng cách (cm) rồi đối chiếu bảng size.</p><table><thead><tr><th>EU</th><th>US</th><th>UK</th><th>CM</th></tr></thead><tbody><tr><td>38</td><td>6</td><td>5</td><td>24.0</td></tr><tr><td>39</td><td>6.5</td><td>5.5</td><td>24.5</td></tr><tr><td>40</td><td>7.5</td><td>6.5</td><td>25.0</td></tr><tr><td>41</td><td>8</td><td>7</td><td>25.5</td></tr><tr><td>42</td><td>8.5</td><td>7.5</td><td>26.0</td></tr><tr><td>43</td><td>9.5</td><td>8.5</td><td>27.0</td></tr><tr><td>44</td><td>10</td><td>9</td><td>27.5</td></tr></tbody></table>'],
        ];
        foreach ($pages as $slug => [$title, $content]) {
            CmsPage::firstOrCreate(['slug' => $slug], ['title' => $title, 'content' => $content]);
        }
    }

    private function shipping(): void
    {
        if (ShippingRate::exists()) {
            return;
        }
        ShippingRate::create(['zone' => 'Nội thành Hà Nội & TP.HCM', 'province_codes' => ['01', '79'], 'fee' => 20000, 'free_threshold' => 1000000]);
        ShippingRate::create(['zone' => 'Toàn quốc', 'province_codes' => null, 'fee' => 35000, 'free_threshold' => 1000000]);
    }
}
