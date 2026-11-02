# Thiết kế Backend – Website bán giày

> Phiên bản 1.1 · 02/11/2026 · Đi kèm `01-BA-pages.md`
> **Stack:** Laravel (REST API) · MySQL 8 · Redis · Vue 3 (2 ứng dụng frontend tách riêng)

---

## 1. Kiến trúc tổng thể

### 1.1 Mô hình: REST API tách biệt + 2 ứng dụng Vue 3

Backend **chỉ cung cấp REST API (JSON)**, không render giao diện (không Blade, không Inertia). Storefront (**Nuxt 3**) và Admin (**Vue 3 SPA**) là hai ứng dụng độc lập, giao tiếp với backend hoàn toàn qua API. Backend kiểu **modular monolith**: vận hành đơn giản, dễ tách service sau này.

```
┌───────────────────┐      ┌───────────────────┐
│ Storefront        │      │ Admin             │
│ Nuxt 3 (SSR)      │      │ Vue 3 + Vite      │
│ localhost:3000    │      │ localhost:5173    │
└─────────┬─────────┘      └─────────┬─────────┘
          │  HTTP(S) · REST/JSON · Authorization: Bearer <access JWT>
          ▼                          ▼
┌─────────────────────────────────────────────┐
│  Nginx (khi deploy) · CORS · rate-limit       │
└──────────────────────┬──────────────────────┘
                       ▼
┌─────────────────────────────────────────────┐
│    Laravel API (PHP-FPM) – modular monolith   │
│ auth · catalog · inventory · cart · order     │
│ payment(MoMo) · shipping · promotion          │
│ customer · cms · report · notification        │
└───────┬─────────────┬─────────────┬─────────┘
        ▼             ▼             ▼
     MySQL 8     Redis (3 vai trò)   Local storage (ảnh/logo)
                 ├─ auth  (JWT session, refresh token)
                 ├─ cache (+ khóa, rate-limit)
                 └─ queue ─► Worker (Horizon) + Scheduler
```

| Repo | Công nghệ | Chạy local (hiện tại) | Khi deploy sau này |
|---|---|---|---|
| `shoe-api` | Laravel + MySQL + Redis | `http://localhost:8000` | `api.<domain>` |
| `shoe-storefront` | **Nuxt 3** (SSR cho trang công khai) | `http://localhost:3000` | `<domain>` |
| `shoe-admin` | Vue 3 + Vite (SPA) | `http://localhost:5173` | `admin.<domain>` |

Dự án chạy trên **localhost**: ba cổng khác nhau là **cùng site nhưng khác origin** (trình duyệt tính site theo tên miền, không tính cổng) nên vẫn phải cấu hình CORS có `credentials`, còn cookie `SameSite=Strict` vẫn được gửi bình thường. Chi tiết ở §6.2. Nếu muốn bỏ CORS khi dev, có thể dùng proxy `/api` của Nuxt (`routeRules`/`nitro.devProxy`) và Vite trỏ sang `:8000`. Khi deploy thật, dùng subdomain cùng tên miền gốc để giữ nguyên cách làm này.

### 1.2 Công nghệ

| Lớp | Lựa chọn | Ghi chú |
|---|---|---|
| Backend | **Laravel 11+ / PHP 8.3** (PHP-FPM, tùy chọn Octane) | API-only: `routes/api.php`, API Resources, Form Requests |
| CSDL | **MySQL 8.0** (InnoDB, `utf8mb4`) | giao dịch, JSON column, FULLTEXT |
| Redis | **Redis 7** | auth (phiên + refresh token), cache, khóa, rate-limit, hàng đợi, giỏ guest |
| Xác thực | **JWT access token + refresh token lưu Redis** | `lcobucci/jwt` (hoặc `firebase/php-jwt`) + `TokenService` tự viết, chi tiết §6 |
| Phân quyền | Gate/Policy + `spatie/laravel-permission` | RBAC theo `resource:action` |
| Hàng đợi/Lịch | Laravel Queue (Redis) + **Horizon**, Laravel Scheduler | mail/SMS, hủy đơn quá hạn, đối soát, báo cáo |
| Tìm kiếm | MySQL FULLTEXT (ngram) → Meilisearch qua Laravel Scout khi lớn | |
| Lưu file (ảnh, logo) | **Local disk** – Laravel Storage (`public` disk), thư mục `storage/app/public` | upload qua API multipart; đổi sang S3/R2 chỉ cần đổi `FILESYSTEM_DISK` (Flysystem) |
| Tài liệu API | OpenAPI 3 (Scribe hoặc L5-Swagger) | FE dùng để sinh type |
| Frontend | **Storefront: Nuxt 3** (SSR, Pinia, `$fetch`) · **Admin: Vue 3** + Vite + Vue Router + Pinia + Axios | 2 app riêng, cùng gọi REST API; Nuxt cho SEO trang công khai |
| Kiểm thử | Pest/PHPUnit, Larastan (PHPStan), Laravel Pint | FE: Vitest, ESLint |
| CI/CD | GitHub Actions + Docker | |

### 1.3 Cấu trúc module (Laravel)

```
app/
├── Modules/
│   ├── Auth/          TokenService, RedisSessionStore, Guards, Controllers
│   ├── User/          Customer, Staff, Address
│   ├── Catalog/       Product, Variant, Category, Brand, Attribute
│   ├── Inventory/     Inventory, Movement, StockReceipt
│   ├── Cart/          giỏ MySQL (đã đăng nhập) + giỏ guest (Redis)
│   ├── Promotion/     Coupon, Promotion, Banner
│   ├── Order/         Order, OrderStateMachine, Return
│   ├── Payment/       MomoClient, MomoIpnHandler, Refund
│   ├── Shipping/      ShippingRate, Shipment
│   ├── Cms/           Page
│   ├── Report/        truy vấn thống kê
│   └── Notification/  Mail, SMS
│        (mỗi module: Http/Controllers · Http/Requests · Http/Resources ·
│         Models · Services · Policies · Events · Jobs · routes.php)
├── Support/           ApiResponse, Exception Handler, Enums, helper
config/ · database/migrations · database/seeders · routes/api.php · tests/
```

Quy tắc: module gọi nhau qua **Service** (không truy vấn chéo bảng của module khác; riêng Report được phép đọc để thống kê). Controller mỏng – nghiệp vụ nằm trong Service.

### 1.4 Lưu trữ hình ảnh và logo (local)

Do chưa dùng dịch vụ lưu trữ đám mây (S3/R2), toàn bộ ảnh sản phẩm, logo thương hiệu, banner, logo/favicon cửa hàng và ảnh đổi trả được **lưu ngay trong thư mục dự án Laravel** bằng *Laravel Filesystem* với disk `public`. Mã nguồn không phụ thuộc nơi lưu: sau này chỉ đổi biến môi trường `FILESYSTEM_DISK=s3` là chuyển sang đám mây.

**Cấu hình**
- `.env`: `FILESYSTEM_DISK=public`, `APP_URL=http://localhost:8000`.
- Chạy một lần `php artisan storage:link` để tạo liên kết `public/storage → storage/app/public`; ảnh truy cập qua `http://localhost:8000/storage/...`.
- Nuxt/Vue hiển thị ảnh bằng `url` do API trả về (không tự ghép đường dẫn); khai báo origin này trong `nuxt.config` (`image.domains` nếu dùng `@nuxt/image`).

**Cấu trúc thư mục**

```
shoe-api/
└── storage/app/public/
    ├── products/{product_id}/
    │   ├── {uuid}.webp            ảnh gốc đã chuẩn hóa (tối đa 1600 px)
    │   ├── {uuid}_md.webp         800 px  (trang chi tiết)
    │   └── {uuid}_sm.webp         300 px  (thẻ sản phẩm, giỏ hàng)
    ├── brands/{brand_id}/logo.webp
    ├── banners/{uuid}_desktop.webp · {uuid}_mobile.webp
    ├── settings/logo.webp · favicon.png
    ├── returns/{return_id}/{uuid}.webp
    └── tmp/                        tệp mới tải lên, chưa gắn vào bản ghi (dọn sau 24 giờ)
```

**Quy tắc**
1. CSDL chỉ lưu **đường dẫn tương đối** (`path`), không lưu URL đầy đủ; API Resource dùng `Storage::url($path)` để sinh `url` (đổi domain/disk không phải sửa dữ liệu).
2. Upload qua `POST /admin/uploads` (multipart/form-data) bằng guard `staff`: validate `image`, `mimes:jpg,jpeg,png,webp`, `max:5120` (KB), kích thước tối thiểu 400×400 với ảnh sản phẩm.
3. Sau khi nhận: đổi tên theo UUID, xoay đúng chiều theo EXIF rồi xóa metadata, chuyển sang **WebP** và sinh các kích thước `_md`, `_sm` (thư viện Intervention Image, chạy bằng Queue nếu nhiều ảnh).
4. Tệp mới nằm ở `tmp/`; khi admin bấm *Lưu sản phẩm* thì di chuyển vào thư mục đích và ghi `product_images`. Lệnh theo lịch `files:cleanup-tmp` xóa tệp ở `tmp/` quá 24 giờ.
5. Xóa ảnh khỏi sản phẩm thì xóa cả tệp và các bản resize; ảnh trong `order_items.image_path` là **ảnh snapshot** nên khi đặt hàng được sao chép sang `orders/{order_id}/` để đơn cũ không mất ảnh khi sản phẩm bị xóa/đổi ảnh.
6. Không cho thực thi script trong thư mục ảnh (Nginx/Apache chặn `*.php`); không dùng tên tệp do người dùng gửi.
7. Header `Cache-Control: public, max-age=31536000, immutable` cho ảnh (tên tệp là UUID nên không đổi nội dung).
8. Sao lưu thư mục `storage/app/public` cùng CSDL; không commit vào Git.

---

## 2. Mô hình dữ liệu (MySQL 8)

### 2.1 ERD (rút gọn)

```
users ──< addresses
users ──< orders ──< order_items >── product_variants >── products >── categories
                │                         │                    └──── brands
                ├──< order_status_history └──< inventory_movements
                ├──< payments
                ├──< shipments
                └──< returns ──< return_items
users ──< carts ──< cart_items >── product_variants
users ──< wishlists >── products
products ──< product_images ;  products ──< product_reviews
coupons ──< coupon_redemptions >── orders
staff_users >── roles ──< role_permissions >── permissions
audit_logs
```

### 2.2 Bảng chính

> **Quy ước MySQL:** InnoDB, `utf8mb4` + collation `utf8mb4_unicode_ci` (không phân biệt hoa/thường); PK `bigint unsigned` tự tăng; tiền lưu **số nguyên VND** (`bigint unsigned`), không dùng float; thời gian lưu **UTC** (`timestamp`), FE hiển thị theo `Asia/Ho_Chi_Minh`; enum lưu dạng `varchar` + PHP Enum (Laravel casting) để thêm giá trị không phải `ALTER`; cột JSON dùng kiểu `json`; xóa mềm bằng `deleted_at`; ràng buộc `CHECK` (MySQL ≥ 8.0.16); mọi bảng có `created_at`, `updated_at`.

**users** (khách hàng)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| email | varchar(190) unique null | collation `_ci` nên không phân biệt hoa/thường; unique cho phép nhiều NULL |
| phone | varchar(15) unique null | chuẩn hóa +84 |
| password_hash | varchar(255) null | argon2id; null nếu đăng nhập social |
| full_name | varchar(120) | |
| gender, birthday | | tùy chọn |
| status | enum(`active`,`blocked`) | |
| email_verified_at | timestamp null | |

**staff_users** · `id, email, password_hash, full_name, role_id, status, last_login_at, two_factor_secret`
**roles / permissions / role_permissions** – RBAC theo `resource:action` (vd `product:write`).

**addresses** · `id, user_id, receiver_name, phone, province_code, district_code, ward_code, street, is_default`

**brands** · `id, name, slug unique, logo_path, is_active`
**categories** · `id, parent_id null, name, slug unique, sort_order, is_active`

**products**

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| name | varchar(255) | |
| slug | varchar(255) unique | |
| brand_id, category_id | FK | |
| gender | enum(`men`,`women`,`unisex`,`kids`) | |
| description | text | HTML đã sanitize |
| material | varchar(255) | |
| base_price | bigint | giá niêm yết thấp nhất (denormalize để lọc) |
| status | enum(`draft`,`active`,`hidden`) | |
| seo_title, seo_description | | |
| deleted_at | timestamp null | soft delete |

**attributes**: `colors(id, name, hex)`, `sizes(id, system enum(EU,US,UK,CM), label, sort_order)`

**product_variants** (SKU = Màu × Size)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| product_id | FK | |
| sku | varchar(64) unique | |
| color_id, size_id | FK | unique(product_id, color_id, size_id) |
| price | bigint | giá bán |
| compare_at_price | bigint null | giá gạch |
| barcode | varchar(64) null | |
| weight_g | int | để tính ship |
| is_active | bool | |

**product_images** · `id, product_id, color_id null, path, alt, sort_order, is_primary, width, height, size_bytes` (`path` là đường dẫn tương đối dưới `public` disk, vd `products/128/0f3a....webp`)

**inventory** (1 dòng / biến thể – v1 một kho)

| Cột | Kiểu | Ghi chú |
|---|---|---|
| variant_id | PK/FK | |
| on_hand | int ≥ 0 | tồn thực tế |
| reserved | int ≥ 0 | đang giữ cho đơn chưa giao |
| low_stock_threshold | int | mặc định 5 |
| version | int | optimistic lock |

Khả dụng = `on_hand − reserved` (CHECK `reserved <= on_hand`).

**inventory_movements** (append-only) · `id, variant_id, type enum(receipt, sale, reserve, release, return, adjust), qty_delta, reason, ref_type, ref_id, staff_id, created_at`

**stock_receipts / stock_receipt_items** · phiếu nhập: `supplier, note, staff_id` + `variant_id, qty, unit_cost`.

**carts / cart_items** (giỏ của khách đã đăng nhập, lưu MySQL) · `carts(id, user_id unique, coupon_code)`; `cart_items(cart_id, variant_id, qty)` unique(cart_id, variant_id).
**Giỏ guest** lưu Redis `cart:guest:{cart_token}` (HASH variant_id → qty, TTL 30 ngày); `cart_token` là UUID do server cấp, FE gửi qua header `X-Cart-Token`; khi đăng nhập thì gộp vào giỏ MySQL.

**coupons** · `id, code unique, type enum(percent, fixed, free_ship), value, min_order, max_discount, usage_limit, per_user_limit, starts_at, ends_at, is_active`
**coupon_redemptions** · `id, coupon_id, user_id null, order_id, amount`
**promotions** · `id, name, discount_type, value, starts_at, ends_at` + `promotion_targets(promotion_id, product_id | category_id | brand_id)`
**banners** · `id, title, image_path, mobile_image_path, link, position, sort_order, starts_at, ends_at, is_active`

**orders**

| Cột | Kiểu | Ghi chú |
|---|---|---|
| id | bigint PK | |
| code | varchar(20) unique | `SH-261102-0001` |
| user_id | FK null | null = guest |
| customer_name, phone, email | | snapshot |
| ship_address | json | snapshot địa chỉ |
| status | enum | xem §4 |
| payment_status | enum(`unpaid`,`pending`,`paid`,`refunded`,`partial_refund`) | |
| payment_method | enum(`cod`,`momo`) | |
| subtotal, discount_total, shipping_fee, grand_total | bigint | |
| coupon_code | varchar | |
| note, internal_note | text | |
| idempotency_key | varchar unique | chống đặt trùng |
| placed_at, cancelled_at, completed_at | timestamp null | |

**order_items** · `id, order_id, variant_id, product_name, sku, color, size, image_path, unit_price, qty, line_total` (**snapshot** – không phụ thuộc thay đổi sau này)
**order_status_history** · `id, order_id, from_status, to_status, actor_type(customer|staff|system), actor_id, note, created_at`
**payments** · `id, order_id, method(cod|momo), amount, status(pending|paid|failed|refunded), provider('momo'), momo_order_id, momo_request_id, provider_txn_id (transId), result_code, raw_payload json, expires_at, paid_at`
**shipments** · `id, order_id, carrier, tracking_code, fee, status, shipped_at, delivered_at`
**shipping_rates** · `id, zone (tỉnh/nhóm tỉnh), min_weight, max_weight, fee, free_threshold`
**returns / return_items** · `id, order_id, reason, status(requested, approved, rejected, received, refunded), refund_amount, staff_id` + `order_item_id, qty, restock bool` + `return_images(return_id, path)`
**product_reviews** · `id, product_id, user_id, order_item_id, rating, content, status(pending, approved)`
**wishlists** · `user_id, product_id` PK kép
**cms_pages** · `id, slug, title, content, is_published`
**audit_logs** · `id, staff_id, action, entity, entity_id, before json, after json, ip, created_at`

### 2.3 Index quan trọng

- `products(slug)`, `products(status, category_id, brand_id)`, `FULLTEXT(name) WITH PARSER ngram` (tìm tiếng Việt có dấu)
- `product_variants(product_id)`, `product_variants(sku)`
- `orders(code)`, `orders(user_id, placed_at DESC)`, `orders(status, placed_at DESC)`, `orders(phone)`
- `inventory_movements(variant_id, created_at DESC)`
- `coupons(code)`

### 2.4 Dữ liệu lưu trong Redis (không nằm trong MySQL)

| Nhóm | Khóa | Ghi chú |
|---|---|---|
| Phiên đăng nhập, refresh token | `auth:*` | xem §6.4 |
| Giỏ guest | `cart:guest:{token}` | TTL 30 ngày |
| Token đặt lại mật khẩu / xác thực email | `pwd_reset:{sha256}`, `verify_email:{sha256}` | chỉ lưu hash, TTL 30 phút / 24 giờ |
| Rate-limit, khóa đăng nhập sai | `rl:*`, `auth:rl:*` | Laravel RateLimiter |
| Khóa phân tán | `lock:checkout:*`, `lock:momo:ipn:*` | `Cache::lock()` |
| Cache | `cache:*` (tags `catalog`, `home`) | xóa khi admin cập nhật |
| Hàng đợi | `queues:*` | Horizon |

Laravel **không dùng** bảng `sessions` (API stateless).

---

## 3. Thiết kế API (REST, `/api/v1`)

### 3.1 Quy ước chung

- JSON, `snake_case`. Thời gian ISO 8601 UTC. Tiền: số nguyên VND.
- Phân trang: `?page=1&limit=20` → `{ data: [], meta: { page, limit, total } }`.
- Lỗi thống nhất:
```json
{ "error": { "code": "OUT_OF_STOCK", "message": "Size 42 đã hết hàng", "details": [] } }
```
- Mã HTTP: 200/201/204, 400 (validate), 401, 403, 404, 409 (xung đột tồn kho/trạng thái), 422, 429.
- Header `Idempotency-Key` cho `POST /orders`.
- Storefront: `/api/v1/...` (guard `customer`) · Admin: `/api/v1/admin/...` (guard `staff` + RBAC).
- **Stateless:** gửi `Authorization: Bearer <access_token>`; không dùng session/cookie của Laravel (trừ cookie refresh token ở nhóm `/auth/*`).
- **CORS:** chỉ cho phép đúng origin của FE, khai báo qua env `CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173`, `Access-Control-Allow-Credentials: true`, không dùng `*`.
- Giỏ guest: header `X-Cart-Token`.
- Rate-limit theo nhóm route (`throttle:api`, `throttle:login`…) bằng Redis.
- Dùng Laravel **API Resource** để định dạng đầu ra; Form Request để validate.

### 3.2 Storefront API

| Method | Endpoint | Mô tả |
|---|---|---|
| POST | `/auth/register` | Đăng ký |
| POST | `/auth/login` | Đăng nhập → body `{ access_token, token_type, expires_in }` + cookie `rt_customer` (refresh token, HttpOnly) |
| POST | `/auth/refresh` | Đọc refresh token từ cookie → cấp access token mới + xoay refresh token |
| POST | `/auth/logout` | Thu hồi phiên hiện tại |
| POST | `/auth/logout-all` | Thu hồi mọi phiên của tài khoản |
| GET/DELETE | `/me/sessions[/:sid]` | Danh sách thiết bị đăng nhập / đăng xuất từng thiết bị |
| POST | `/auth/forgot-password` · `/auth/reset-password` | |
| GET | `/me` · PATCH `/me` · PUT `/me/password` | Hồ sơ |
| GET/POST/PUT/DELETE | `/me/addresses[/:id]` | Sổ địa chỉ |
| GET/PUT/DELETE | `/me/wishlist[/:productId]` | Yêu thích |
| GET | `/home` | Banner + khối sản phẩm trang chủ |
| GET | `/categories` · `/brands` | |
| GET | `/products` | Lọc: `q, category, brand, gender, size, color, price_min, price_max, on_sale, sort, page` |
| GET | `/products/:slug` | Chi tiết + biến thể + tồn khả dụng |
| GET | `/products/:slug/reviews` · POST | Đánh giá |
| GET | `/cart` | Lấy giỏ (user đăng nhập hoặc header `X-Cart-Token`) |
| POST | `/cart/items` | `{ variant_id, qty }` |
| PATCH/DELETE | `/cart/items/:variantId` | |
| POST/DELETE | `/cart/coupon` | Áp / gỡ mã |
| POST | `/shipping/quote` | Tính phí ship theo địa chỉ + giỏ |
| GET | `/payment-methods` | |
| POST | `/orders` | Tạo đơn từ giỏ (checkout) |
| GET | `/me/orders` · `/me/orders/:code` | Lịch sử đơn |
| POST | `/me/orders/:code/cancel` | Hủy đơn |
| POST | `/me/orders/:code/returns` | Yêu cầu đổi/trả |
| GET | `/orders/track?code=&phone=` | Tra cứu guest |
| POST | `/payments/momo/create` | Tạo giao dịch MoMo cho đơn `unpaid` → trả `pay_url`, `qr_code_url`, `deeplink` |
| POST | `/payments/momo/ipn` | IPN từ MoMo (server-to-server, xác thực chữ ký HMAC SHA256) |
| GET | `/payments/momo/return` | Redirect khách quay lại sau thanh toán (chỉ hiển thị, **không** dùng để chốt đơn) |
| GET | `/orders/:code/payment-status` | Frontend polling trạng thái thanh toán |
| GET | `/pages/:slug` | Trang tĩnh |
| GET | `/locations/provinces` … | Tỉnh/Quận/Phường |

**Ví dụ – Tạo đơn**

```http
POST /api/v1/orders
Idempotency-Key: 7d9f...
{
  "receiver": { "name": "Nguyễn Văn A", "phone": "0901234567", "email": "a@mail.com" },
  "address": { "province_code": "01", "district_code": "005", "ward_code": "00165", "street": "12 Trần Duy Hưng" },
  "shipping_method": "standard",
  "payment_method": "cod",
  "note": "Gọi trước khi giao"
}
```
```json
201 { "data": { "code": "SH-261102-0001", "status": "pending",
  "grand_total": 2890000, "payment_status": "unpaid" } }
```

### 3.3 Admin API (`/admin`)

| Nhóm | Endpoint |
|---|---|
| Auth | `POST /admin/auth/login` → `/admin/auth/2fa/verify` · `/admin/auth/refresh` · `/admin/auth/logout` · `/admin/auth/logout-all` · `GET /admin/me` (kèm danh sách quyền) |
| Dashboard | `GET /admin/dashboard/summary?range=7d` |
| Sản phẩm | `GET/POST /admin/products` · `GET/PUT/DELETE /admin/products/:id` · `PATCH /admin/products/:id/status` · `POST /admin/products/bulk` · `POST /admin/products/import` · `GET /admin/products/export` |
| Biến thể | `PUT /admin/products/:id/variants` (đồng bộ cả bảng SKU) |
| Ảnh / logo | `POST /admin/uploads` (multipart: `file`, `type = product|brand|banner|logo|return`) → trả `{ path, url }`; `DELETE /admin/uploads` xóa tệp mồ côi; ảnh sản phẩm gắn vào sản phẩm khi lưu `PUT /admin/products/:id` |
| Danh mục/Thương hiệu/Thuộc tính | CRUD `/admin/categories`, `/brands`, `/colors`, `/sizes` |
| Kho | `GET /admin/inventory`, `POST /admin/inventory/receipts`, `POST /admin/inventory/adjust`, `GET /admin/inventory/movements` |
| Đơn hàng | `GET /admin/orders` · `GET /admin/orders/:id` · `POST /admin/orders/:id/transition` `{ to, note }` · `POST /admin/orders/:id/cancel` · `PATCH /admin/orders/:id/internal-note` · `POST /admin/orders/:id/shipment` · `POST /admin/orders/:id/payments` · `POST /admin/orders/:id/payments/momo/query` (truy vấn lại giao dịch) · `POST /admin/orders/:id/payments/momo/refund` · `GET /admin/orders/:id/print` |
| Đổi trả | `GET /admin/returns` · `POST /admin/returns/:id/approve|reject|receive|refund` |
| Khách hàng | `GET /admin/customers` · `GET /admin/customers/:id` · `PATCH /admin/customers/:id/status` |
| Marketing | CRUD `/admin/coupons`, `/promotions`, `/banners` |
| Báo cáo | `GET /admin/reports/revenue|top-products|inventory|returns` (+ `?format=csv`) |
| CMS | CRUD `/admin/pages` |
| Cài đặt | `GET/PUT /admin/settings`, CRUD `/admin/shipping-rates`, `/admin/staff`, `/admin/roles` |
| Audit | `GET /admin/audit-logs` |

---

## 4. Vòng đời đơn hàng (state machine)

```
            ┌──────────────► cancelled
            │                    ▲
 pending ──► confirmed ──► packing ──► shipping ──► completed ──► (returned)
 (Chờ XN)   (Đã XN)      (Đóng gói)   (Đang giao)  (Hoàn thành)   (Đổi trả)
```

| Từ | Sang | Ai được phép | Tác động kho / tiền |
|---|---|---|---|
| pending | confirmed | CSKH, Manager, hệ thống (khi đã thanh toán) | – |
| pending, confirmed | cancelled | Khách, CSKH, Manager | Trả `reserved` |
| confirmed | packing | Kho | – |
| packing | shipping | Kho | Tạo shipment, **trừ `on_hand` & `reserved`** |
| shipping | completed | Hệ thống (carrier webhook) / Manager | COD → ghi nhận đã thu |
| shipping | cancelled (giao thất bại) | Manager | Nhập lại kho |
| completed | returned | CSKH/Manager (qua return) | Nhập kho nếu `restock`, hoàn tiền |

Mọi chuyển trạng thái: kiểm tra hợp lệ trong service, ghi `order_status_history`, phát sự kiện `order.status_changed` → gửi thông báo.

---

## 5. Luồng xử lý quan trọng

### 5.1 Đặt hàng (checkout) – chống bán quá tồn

```
1. Validate giỏ + địa chỉ + coupon
2. DB::transaction(..., attempts: 3)   // InnoDB, tự retry khi deadlock
3.   Với mỗi variant (sắp xếp theo id để tránh deadlock):
        UPDATE inventory
        SET reserved = reserved + :qty, version = version + 1
        WHERE variant_id = :id AND (on_hand - reserved) >= :qty
        → 0 dòng bị ảnh hưởng ⇒ rollback, trả 409 OUT_OF_STOCK
4.   Tạo orders + order_items (snapshot giá, tên, size, màu)
5.   Ghi coupon_redemptions (nếu có), inventory_movements(type=reserve)
6.   Tạo payments (pending/unpaid theo phương thức)
7. COMMIT (kết thúc transaction)
8. Xóa giỏ; đẩy job gửi email/SMS xác nhận
9. Nếu thanh toán online: trả payment_url
```

- **Idempotency:** `idempotency_key` unique – gọi lại cùng key trả về đơn cũ; thêm `Cache::lock('lock:checkout:{user|cart_token}', 10)` (Redis) để chặn bấm đặt hàng hai lần.
- Laravel: `DB::table('inventory')->where('variant_id', $id)->whereRaw('on_hand - reserved >= ?', [$qty])->update([...])` trả về số dòng bị ảnh hưởng (0 ⇒ hết hàng); luôn xử lý variant theo thứ tự `id` để tránh deadlock.
- **Tính tiền luôn ở server**, bỏ qua mọi giá do client gửi.

### 5.2 Thanh toán online – MoMo

Tích hợp **MoMo Payment Gateway** (`captureWallet` – ví MoMo / quét QR). `partnerCode`, `accessKey`, `secretKey` lưu ở secret manager/biến môi trường, **không** đưa ra frontend. Test bằng sandbox của MoMo, production dùng endpoint chính thức.

```
Khách        Storefront        Backend                    MoMo
  │  Đặt hàng (momo) │              │                         │
  │─────────────────►│ POST /orders │ tạo đơn (unpaid) + giữ kho
  │                  │ POST /payments/momo/create             │
  │                  │─────────────►│ tạo request + ký HMAC   │
  │                  │              │ POST /v2/gateway/api/create
  │                  │              │────────────────────────►│
  │                  │◄─ pay_url / qr / deeplink ─────────────│
  │◄─ chuyển sang MoMo, xác nhận thanh toán ──────────────────│
  │                  │              │◄── IPN (resultCode, transId, signature)
  │                  │              │ verify chữ ký + số tiền → payments=paid
  │◄─ redirect về trang thành công (polling payment-status) ───│
```

Quy tắc:
1. **Chốt đơn chỉ dựa trên IPN** đã xác thực chữ ký và khớp `amount`, `orderId`; trang redirect của khách chỉ để hiển thị.
2. `orderId` gửi MoMo = mã đơn + hậu tố lần thử (vd `SH-261102-0001-1`) để cho thanh toán lại; `requestId` duy nhất mỗi lần gọi.
3. IPN idempotent: `transId` unique + `Cache::lock('lock:momo:ipn:{orderId}', 10)` để không xử lý song song; nhận trùng thì bỏ qua. Route IPN nằm trong `routes/api.php` (stateless nên không vướng CSRF), trả HTTP 204 (đối chiếu tài liệu MoMo hiện hành).
4. `resultCode = 0` → `paid`, đơn tự chuyển `pending → confirmed`; mã khác → `failed`, đơn vẫn `unpaid`, khách thử lại hoặc đổi sang COD.
5. Đơn MoMo `unpaid` quá **15 phút** (`expires_at`): command theo lịch (Laravel Scheduler, mỗi phút) tự hủy và trả `reserved`; trước khi hủy gọi API query MoMo để tránh hủy nhầm đơn đã thanh toán.
6. Hoàn tiền (hủy/đổi trả sau khi đã trả): gọi API refund MoMo theo `transId`; cập nhật `payments.status = refunded`, `orders.payment_status = refunded | partial_refund`.
7. Đối soát hằng ngày giữa `payments` và báo cáo giao dịch MoMo, cảnh báo khi lệch.
8. Vượt hạn mức giao dịch của merchant → ẩn MoMo, chỉ cho COD.

### 5.3 Nhập kho / điều chỉnh
Mỗi thay đổi tồn **bắt buộc** sinh `inventory_movements` trong cùng transaction; điều chỉnh yêu cầu lý do.

### 5.4 Đổi trả
`requested → approved/rejected → received → refunded`; khi `received` và `restock = true` → cộng `on_hand`, ghi movement `return`.

---

## 6. Xác thực & phân quyền (JWT + Redis)

### 6.1 Nguyên tắc

- API **stateless**: không dùng session Laravel. Hai guard riêng: `customer` và `staff` (khác `aud`, khác secret, khác namespace Redis).
- **Access token:** JWT ngắn hạn, gửi qua header `Authorization: Bearer`.
- **Refresh token:** chuỗi ngẫu nhiên (opaque) 256-bit, **chỉ lưu hash SHA-256 trong Redis**, **xoay vòng mỗi lần dùng**.
- **Redis là nguồn sự thật của phiên:** thu hồi được ngay lập tức dù JWT chưa hết hạn.

### 6.2 Thông số

| | Khách (`customer`) | Nhân viên (`staff`) |
|---|---|---|
| Access token | 15 phút | 10 phút |
| Refresh token | 30 ngày (trượt), tối đa 90 ngày kể từ lúc đăng nhập | 12 giờ tuyệt đối, idle timeout 2 giờ |
| Thuật toán | HS256, secret riêng mỗi guard (`kid` để xoay khóa) | như trái |
| Số phiên đồng thời | tối đa 5 thiết bị | tối đa 2 |
| Xác thực 2 lớp | tùy chọn | TOTP bắt buộc với Admin/Manager |
| Cookie refresh token | `rt_customer` · `Path=/api/v1/auth` | `rt_staff` · `Path=/api/v1/admin/auth` |

Vượt số phiên tối đa → thu hồi phiên cũ nhất. Có thể chuyển sang RS256 nếu sau này có service khác cần verify token.

**Chạy localhost (dự án cá nhân):** `localhost:3000` (Nuxt), `localhost:5173` (Admin) và `localhost:8000` (API) là cùng site nhưng khác origin, nên:
1. Vẫn bắt buộc cấu hình CORS với `credentials`; FE gọi API với `withCredentials: true` (Axios) / `credentials: 'include'` (`$fetch`).
2. Cookie `SameSite=Strict` vẫn được gửi, vì request cùng site.
3. Cookie là *host-only* (không đặt `Domain`) và **không phân biệt cổng**, nên phải đặt tên và `Path` riêng (`rt_customer`, `rt_staff`) để hai app không đè cookie của nhau.
4. Cờ `Secure` điều khiển bằng env `AUTH_COOKIE_SECURE` (tắt khi chạy `http://`; Chrome coi localhost là secure context còn Safari thì không).
5. Dùng thống nhất `localhost`, không trộn với `127.0.0.1` (là host khác nên cookie không được gửi).

### 6.3 Claims của access token

```json
{ "iss": "http://localhost:8000", "aud": "customer", "sub": "1024",
  "sid": "01JC8Z3K...", "jti": "b7f1c2e0...", "iat": 1762030000, "exp": 1762030900 }
```

Không đưa danh sách quyền vào token. Quyền của nhân viên đọc từ Redis `perm:staff:{id}` (TTL 5 phút, xóa khi đổi vai trò) nên thay đổi quyền có hiệu lực nhanh.

### 6.4 Cấu trúc khóa Redis (instance/DB `auth`)

| Khóa | Kiểu | Nội dung | TTL |
|---|---|---|---|
| `auth:{aud}:sess:{sid}` | HASH | `user_id, device, ip, user_agent, created_at, last_used_at, refresh_hash, prev_refresh_hash, rotated_at, absolute_exp` | = thời hạn refresh |
| `auth:{aud}:rt:{sha256(refresh)}` | STRING | `sid` (tra cứu phiên từ refresh token) | = thời hạn refresh |
| `auth:{aud}:used:{sha256}` | STRING | `sid` của refresh token đã bị thay (dùng để phát hiện tái sử dụng) | = thời hạn refresh |
| `auth:{aud}:user:{user_id}:sids` | SET | các `sid` đang hoạt động | theo phiên dài nhất |
| `auth:{aud}:deny:{jti}` | STRING | `1` – chặn một access token cụ thể (khi logout) | = thời gian còn lại của access token |
| `auth:{aud}:revoked_before:{user_id}` | STRING | timestamp; access token có `iat` nhỏ hơn bị từ chối | 90 ngày |
| `auth:rl:login:{ip}:{identifier}` | STRING | đếm đăng nhập sai | 15 phút |
| `perm:staff:{user_id}` | SET | danh sách `resource:action` | 5 phút |

### 6.5 Luồng xử lý

**Đăng nhập**
1. Kiểm tra rate-limit → xác minh mật khẩu (argon2id) → (nhân viên) xác minh TOTP.
2. Tạo `sid`, sinh refresh token ngẫu nhiên; ghi Redis bằng pipeline: `sess`, `rt`, `sids`.
3. Ký access JWT (`sid`, `jti`). Trả `{ access_token, token_type: "Bearer", expires_in: 900 }`; refresh token trả qua cookie:
   `Set-Cookie: rt_customer=<token>; HttpOnly; SameSite=Strict; Path=/api/v1/auth; Max-Age=...` (thêm `Secure` khi chạy HTTPS; nhân viên dùng `rt_staff`, `Path=/api/v1/admin/auth`)

**Mỗi request cần đăng nhập** (middleware `auth:customer` / `auth:staff`)
1. Verify chữ ký, `exp`, `iss`, `aud`.
2. Một lượt pipeline Redis (~1 ms): phiên `sess:{sid}` còn tồn tại? `deny:{jti}` không có? `iat` ≥ `revoked_before:{sub}`? Sai một điều kiện → 401.
3. Nạp user, (nhân viên) kiểm tra quyền qua Gate/Policy.

**Làm mới token** `POST /auth/refresh` – thực thi bằng **Lua script** để atomic
1. Lấy refresh token từ cookie → `sha = sha256(token)` → `GET rt:{sha}` → `sid`. Không có và cũng không nằm trong `used:{sha}` → 401 `REFRESH_INVALID`.
2. Nếu `sha == sess.refresh_hash` (token hiện hành): sinh refresh mới, ghi `rt:{new}`, đặt `prev_refresh_hash = sha`, chuyển `sha` sang `used:{sha}`, cập nhật `sess`, cấp access token mới. Với phiên khách: gia hạn trượt nhưng không vượt `absolute_exp`.
3. Nếu `sha == sess.prev_refresh_hash` và còn trong **cửa sổ ân hạn 10 giây** (nhiều tab/request song song): chỉ cấp access token mới, **không** xoay refresh.
4. Nếu `sha` nằm trong `used:{sha}` ngoài cửa sổ ân hạn → **phát hiện tái sử dụng (nghi bị đánh cắp)**: xóa toàn bộ phiên `sid`, ghi log bảo mật, trả 401 `REFRESH_REUSED`, gửi email cảnh báo cho khách.

**Đăng xuất:** xóa `sess`, `rt`, khỏi `sids`; đặt `deny:{jti}`; xóa cookie.
**Đăng xuất mọi thiết bị / đổi mật khẩu / reset mật khẩu / khóa tài khoản:** xóa toàn bộ `sid` trong `user:{id}:sids` và đặt `revoked_before = now` (một khóa duy nhất vô hiệu mọi access token cũ).

Mã lỗi để FE xử lý: `TOKEN_EXPIRED` (nên gọi refresh), `TOKEN_INVALID`, `SESSION_REVOKED`, `REFRESH_INVALID`, `REFRESH_REUSED` (phải đăng nhập lại).

### 6.6 Phía Nuxt 3 (storefront) và Vue 3 (admin)

- Access token giữ trong **bộ nhớ (Pinia)**, không lưu `localStorage`/`sessionStorage` để giảm rủi ro bị XSS đánh cắp. Refresh token nằm trong cookie `HttpOnly` nên JS không đọc được.
- Axios interceptor: nhận 401 `TOKEN_EXPIRED` → gọi `/auth/refresh` (`withCredentials: true`) **một lần duy nhất** (single-flight), xếp hàng các request đang chờ rồi gửi lại; refresh lỗi → xóa state và chuyển về trang đăng nhập.
- Khi tải lại trang (F5): gọi `/auth/refresh` để lấy lại access token trước khi render các route cần đăng nhập.
- **Storefront Nuxt 3:**
  - SSR chỉ cho **trang công khai** (trang chủ, danh sách, chi tiết sản phẩm, hướng dẫn size, chính sách) – các trang này gọi API không cần đăng nhập.
  - Trang cần đăng nhập hoặc giỏ hàng (`/tai-khoan/**`, `/gio-hang`, `/thanh-toan/**`) cấu hình `routeRules: { ssr: false }` (render phía trình duyệt), vì cookie refresh token thuộc origin của API, server Nuxt không thể gọi API thay người dùng.
  - Cấu hình runtime: `NUXT_PUBLIC_API_BASE=http://localhost:8000/api/v1` (trình duyệt) và `NUXT_API_BASE_SERVER` (SSR gọi API).
  - Access token giữ trong Pinia (chỉ phía client); plugin `$api` bọc `$fetch`: `onRequest` gắn `Authorization: Bearer`, `onResponseError` xử lý `TOKEN_EXPIRED` bằng refresh single-flight.
  - `X-Cart-Token` của giỏ guest lưu ở phía client (cookie/localStorage), không dùng khi SSR.
- **Admin Vue 3:** SPA thuần (Vite), không cần SSR.
- Vue Router / Nuxt middleware guard chỉ phục vụ trải nghiệm; **quyền thật do BE quyết định**. Admin ẩn menu theo danh sách quyền từ `GET /admin/me`.

### 6.7 Vận hành Redis cho xác thực

- **Chạy local:** một Redis duy nhất là đủ, tách theo số DB (`0` cache, `1` auth, `2` queue). **Production** mới cần tách instance như dưới đây.
- Dùng **instance/DB Redis riêng cho `auth`** (`REDIS_AUTH_*`) với `maxmemory-policy noeviction`, tách khỏi Redis cache (`allkeys-lru`) và queue, để không bị xóa khóa khi đầy bộ nhớ hoặc khi `cache:clear`.
- Bật AOF `everysec` + replica/Sentinel. Nếu mất dữ liệu Redis auth thì toàn bộ người dùng phải đăng nhập lại: chấp nhận được nhưng phải có cảnh báo giám sát.
- Dung lượng ước tính ~1 KB/phiên → 100.000 phiên ≈ 100 MB.

### 6.8 Phân quyền (RBAC) & mật khẩu

- `permission = resource:action` (`product:read`, `product:write`, `order:transition`, `inventory:adjust`, `report:read`, `staff:manage`…) gắn vào role; kiểm tra bằng Gate/Policy hoặc middleware `can:`. Có thể dùng `spatie/laravel-permission` (lưu MySQL) kèm cache Redis.
- Mật khẩu: **argon2id** (`HASH_DRIVER=argon2id`); khóa tạm sau 5 lần sai (đếm bằng Redis); rate-limit theo IP + tài khoản.
- Mọi thao tác ghi của admin ghi `audit_logs` (before/after).

---

## 7. Bảo mật

| Rủi ro | Biện pháp |
|---|---|
| SQL injection | Eloquent / Query Builder (prepared statement), không nối chuỗi SQL |
| XSS | Sanitize HTML mô tả (HTMLPurifier), CSP, Vue escape mặc định; access token chỉ giữ trong bộ nhớ |
| CSRF | API dùng Bearer header nên không bị CSRF; riêng `/auth/refresh` và `/auth/logout` dùng cookie → `SameSite=Strict` + kiểm tra header `Origin` + header tùy chỉnh `X-Requested-With` |
| CORS | Chỉ cho phép đúng origin FE, bật credentials, không dùng `*` |
| Đánh cắp token | TTL ngắn, refresh xoay vòng, phát hiện tái sử dụng, thu hồi tức thời qua Redis, chỉ lưu hash refresh token |
| Lộ dữ liệu cá nhân | Ẩn SĐT/email theo quyền, API Resource chỉ trả các trường cần thiết, mã hóa at-rest ở lớp lưu trữ |
| Brute-force/scrape | `RateLimiter` (Redis) theo IP và tài khoản, CAPTCHA khi bất thường |
| Mass assignment | `$fillable` tường minh, dùng Form Request |
| Giả mạo giá/tồn | Tính toán hoàn toàn ở server |
| Upload độc hại | Validate MIME thật (`mimes:jpg,jpeg,png,webp`), tối đa 5 MB, giới hạn kích thước ảnh; đổi tên bằng UUID; xử lý lại ảnh bằng thư viện ảnh (loại bỏ metadata, mã nhúng); thư mục `storage` không cho thực thi PHP |
| IPN MoMo giả | Xác thực chữ ký HMAC SHA256, so khớp số tiền & orderId |
| Cấu hình | `.env` không commit, `APP_DEBUG=false` ở production, secret quản lý bằng secret manager |
| Tuân thủ | Không lưu dữ liệu thẻ; chính sách bảo mật & xóa dữ liệu theo yêu cầu |

---

## 8. Hiệu năng & mở rộng

- **SEO storefront (Nuxt 3):** SSR cho trang công khai, `useSeoMeta` + JSON-LD `schema.org/Product`, sitemap (`@nuxtjs/sitemap`), cache trang bằng `routeRules` (ví dụ `swr`) cho danh sách/chi tiết sản phẩm; BE chỉ cần trả `seo_title`, `seo_description`, ảnh, giá và tồn qua API.
- Cache Redis (`Cache::tags`): danh mục, thương hiệu, trang chủ, chi tiết sản phẩm (TTL ngắn, xóa theo tag khi admin cập nhật).
- Eloquent: dùng `with()` tránh N+1, `cursorPaginate()` cho danh sách lớn, chỉ `select` cột cần.
- MySQL: index theo §2.3, `EXPLAIN` các truy vấn lọc sản phẩm; khi tải tăng thì thêm read replica cho báo cáo.
- Production: `config:cache`, `route:cache`, OPcache; cân nhắc Laravel Octane.
- Ảnh: sinh nhiều kích thước WebP khi upload (§1.4), trình duyệt cache bằng header `Cache-Control`; khi deploy có thể đặt CDN phía trước thư mục `/storage`.
- Báo cáo nặng: chạy bằng job nền / bảng tổng hợp theo ngày (`daily_sales_summary`).
- Có thể tách `payment`, `notification`, `report` thành service riêng khi cần.

---

## 9. Thông báo (Notification)

| Sự kiện | Kênh | Người nhận |
|---|---|---|
| Đặt hàng thành công | Email (+SMS) | Khách |
| Đơn xác nhận / đang giao / hoàn thành | Email, SMS | Khách |
| Có đơn mới | Email / Telegram/Zalo | Nhân viên |
| Tồn kho thấp | Email | Manager, Kho |
| Yêu cầu đổi trả | Email | CSKH |
| Quên mật khẩu / xác thực email | Email | Khách |

Dùng Laravel Notification + Mail/SMS channel, gửi qua Queue (Horizon) có retry/backoff và ghi log.

---

## 10. Triển khai & vận hành

- Môi trường: `dev`, `staging`, `production`; cấu hình qua `.env` / secret manager.
- **Chạy local (hiện tại):** MySQL + Redis bằng Docker Compose (hoặc Laravel Sail); `php artisan serve` (:8000), `php artisan horizon`, `php artisan schedule:work`; Nuxt `npm run dev` (:3000); Admin `npm run dev` (:5173).
- **Khi deploy:** Docker Compose (v1): `nginx`, `php-fpm`, `mysql`, `redis-auth`, `redis-cache` (kiêm queue), `horizon` (worker), `scheduler` (`php artisan schedule:work`). FE build tĩnh, phục vụ qua Nginx/CDN.
- Migration: Laravel migrations + seeders (danh mục, size EU 35–46, vai trò & quyền mặc định, tài khoản admin đầu tiên).
- Sao lưu thư mục ảnh `storage/app/public` cùng với CSDL (rsync/zip hằng ngày); không commit thư mục này vào Git (`.gitignore`), chỉ giữ tệp `.gitkeep`.
- Sao lưu MySQL: dump hằng ngày (`mysqldump --single-transaction` hoặc XtraBackup) + binlog để khôi phục theo thời điểm; kiểm thử khôi phục định kỳ.
- CI/CD: API – Pint, Larastan, Pest; FE – ESLint, Vitest, build; triển khai tự động lên staging, duyệt tay lên production.
- Giám sát: Sentry (lỗi), Horizon (hàng đợi), Laravel Pulse hoặc Prometheus/Grafana (latency, tỉ lệ lỗi), cảnh báo khi tồn kho thấp, job thất bại, Redis auth gần đầy hoặc mất kết nối.

---

## 11. Kiểm thử

| Loại | Phạm vi trọng tâm |
|---|---|
| Unit (Pest) | Tính giá, coupon, phí ship, state machine đơn, `TokenService` |
| Feature/Integration | Checkout + tồn kho, IPN MoMo, đổi trả (dùng MySQL + Redis thật trong CI) |
| Auth | Đăng nhập, refresh xoay vòng, cửa sổ ân hạn, phát hiện tái sử dụng, logout/logout-all, `revoked_before`, hết hạn tuyệt đối |
| Concurrency | 2 người cùng mua sản phẩm cuối cùng → chỉ 1 thành công; 2 request refresh song song |
| E2E (Playwright) | Duyệt → giỏ → thanh toán → admin xử lý đơn |
| Bảo mật | OWASP Top 10, phân quyền theo vai trò, kiểm tra CORS/cookie |

---

## 12. Lộ trình gợi ý

| Giai đoạn | Nội dung | Thời gian ước tính |
|---|---|---|
| 1 | Auth (JWT + refresh token Redis), catalog, biến thể, ảnh, tồn kho cơ bản, Admin sản phẩm | 3–4 tuần |
| 2 | Giỏ hàng, checkout COD, đơn hàng, Admin đơn | 3–4 tuần |
| 3 | Thanh toán MoMo (sandbox → production), vận chuyển, coupon/khuyến mãi, email/SMS | 2–3 tuần |
| 4 | Đổi trả, báo cáo, CMS, phân quyền chi tiết, tối ưu & SEO | 2–3 tuần |

---

## 13. Câu hỏi cần chốt với chủ shop

1. Bán 1 kho hay nhiều chi nhánh? Có bán tại cửa hàng (POS) không?
2. Thanh toán online đã chọn **MoMo**; cần chốt: đã có tài khoản merchant MoMo chưa, có cần hoàn tiền tự động qua API không, và đơn vị vận chuyển muốn tích hợp (GHN, GHTK, Viettel Post…)?
3. Chính sách đổi trả cụ thể (số ngày, ai chịu phí ship, đổi size miễn phí?).
4. Có cần đánh giá sản phẩm, điểm thưởng, flash sale ngay từ v1?
5. Cần xuất hóa đơn điện tử (VAT) không?
6. Dự kiến số lượng sản phẩm và đơn/ngày để chọn hạ tầng phù hợp.
7. ✅ **Đã chốt:** storefront dùng **Nuxt 3 (SSR)**.
8. ✅ **Đã chốt:** dự án cá nhân, **chạy localhost** (API `:8000`, Nuxt `:3000`, Admin `:5173`) – xem §1.1 và §6.2.
