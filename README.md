# SoleHouse – Website bán giày

Thiết kế: `docs/docs/01-BA-pages.md` (BA) · `docs/docs/02-BE-design.md` (Backend) · mockup tĩnh: `docs/mockup/index.html`.

| Thư mục | Công nghệ | Cổng |
|---|---|---|
| `api/` | Laravel (REST `/api/v1`) · MySQL 8 · Redis 7 · JWT + refresh token trong Redis | 8000 |
| `storefront/` | Nuxt 3 (SSR trang công khai) + Pinia | 3000 |
| `admin-panel/` | Vue 3 + Vite + Pinia + Axios (SPA) | 5173 |
| `legacy/` | Code PHP cũ (chỉ tham khảo) | – |

## Chạy nhanh bằng `dev.sh` (khuyên dùng)

Một lệnh ở thư mục gốc để chạy cả backend và 2 frontend:

```bash
./dev.sh            # chạy tất cả; Ctrl+C để dừng API + 2 frontend
./dev.sh --fresh    # xóa sạch DB rồi migrate + seed lại
./dev.sh --reset-db # XÓA dữ liệu MySQL (api/data_mysql) rồi tạo lại — dùng khi lỗi 'Access denied for user'
./dev.sh --stop     # dừng cả MySQL và Redis (Docker)
```

**Yêu cầu:** Docker, Node.js + npm. PHP không bắt buộc: nếu máy không có PHP 8.3+ và Composer thì script tự build rồi dùng image Docker `shoe-php-dev` (từ `deploy/Dockerfile.dev`; đổi bằng biến môi trường `DOCKER_PHP_IMAGE`). Chạy trên Linux/macOS (bash) và Windows qua **WSL2** — hướng dẫn cài đặt từng bước: [`setup-window.md`](setup-window.md).

**Script làm gì, theo thứ tự**
1. Kiểm tra cổng 8000, 3000, 5173 còn trống; nếu bị chiếm thì báo lỗi và dừng (không chạy chồng).
2. Tạo `api/.env` từ `.env.example` nếu chưa có.
3. Khởi động MySQL + Redis bằng Docker (dữ liệu MySQL ở `api/data_mysql`) và chờ sẵn sàng.
4. `composer install` nếu chưa có `vendor`, sinh `APP_KEY`, `storage:link`, `migrate`. Chỉ seed dữ liệu mẫu khi DB còn trống (hoặc khi dùng `--fresh`).
5. Chạy API `:8000` kèm `queue:work` (gửi email) và `schedule:work` (hủy đơn MoMo quá hạn, đối soát, tổng hợp doanh thu…).
6. Nếu đã build dữ liệu crawl (`data-migration/build.py`) và DB chưa có sản phẩm thật thì tự nhập `import_products.sql` (xem `data-migration/README.md`). Sản phẩm demo ảnh vẽ chỉ tạo khi `SEED_DEMO_PRODUCTS=true`.
7. `npm install` nếu cần, rồi chạy Storefront `:3000` và Admin `:5173`.
8. Chờ cả ba phản hồi, in địa chỉ và tài khoản mẫu.

**Khi dừng:** Ctrl+C tắt API, queue, scheduler và 2 frontend (kể cả container PHP). MySQL/Redis vẫn chạy nền để lần sau lên nhanh; tắt bằng `./dev.sh --stop`.

**Log** từng phần nằm trong `.logs/` (`api.log`, `queue.log`, `schedule.log`, `storefront.log`, `admin.log`, `migrate.log`, `docker.log`, `composer.log`, `npm-*.log`) — đã gitignore. Khi một bước lỗi, script báo tên file log cần xem.

## Chạy từng phần thủ công

```bash
# 1. Hạ tầng (MySQL lưu ở api/data_mysql, Redis 3 DB: 0 cache · 1 auth · 2 queue)
cd api && docker compose up -d

# 2. API (cần PHP >= 8.3 + Composer, hoặc chạy qua Sail/Docker)
composer install
php artisan storage:link
php artisan migrate:fresh --seed
php artisan serve                 # :8000
php artisan queue:work            # mail/SMS (tùy chọn)
php artisan schedule:work         # tự hủy đơn MoMo quá hạn, dọn tmp, cảnh báo tồn thấp

# 3. Storefront và Admin
cd storefront  && npm install && npm run dev     # http://localhost:3000
cd admin-panel && npm install && npm run dev     # http://localhost:5173
```

Dùng thống nhất `localhost` (không trộn `127.0.0.1`) vì cookie refresh token là host-only.

## Tài khoản seed

| Vai trò | Email | Mật khẩu |
|---|---|---|
| Admin | admin@solehouse.vn | Admin@123 |
| Quản lý | manager@solehouse.vn | Manager@123 |
| Kho | kho@solehouse.vn | Kho@12345 |
| CSKH | cskh@solehouse.vn | Cskh@12345 |
| Khách | khach@solehouse.vn | Khach@123 |

Mã giảm giá mẫu: `WELCOME100`, `SALE10`, `FREESHIP`.

## Cấu hình (api/.env)
| Nhóm | Biến | Ghi chú |
|---|---|---|
| Email (thay SMS) | `MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_*` | Mặc định `log` (mail ghi vào `storage/logs`). Gmail: host `smtp.gmail.com`, port 587, dùng App Password. Email gửi cho khách (xác nhận đơn, đổi trạng thái, quên mật khẩu) và nhân viên (đơn mới, đổi trả, tồn thấp, lệch MoMo). |
| Đăng nhập Google | `GOOGLE_CLIENT_ID` | Tạo OAuth client loại *Web* trong Google Cloud Console, thêm `http://localhost:3000` vào *Authorized JavaScript origins*. Để trống = ẩn nút Google. |
| MoMo | `MOMO_PARTNER_CODE`, `MOMO_ACCESS_KEY`, `MOMO_SECRET_KEY`, `MOMO_ENDPOINT`, `MOMO_FAKE` | Mặc định là khóa sandbox công khai của MoMo + `MOMO_FAKE=true` (trang MoMo giả lập, IPN vẫn ký/xác thực thật). Chạy thật: điền thông tin merchant, `MOMO_ENDPOINT=https://payment.momo.vn` (sandbox: `https://test-payment.momo.vn`), `MOMO_FAKE=false`, và `APP_URL` phải là địa chỉ công khai để MoMo gọi IPN `POST {APP_URL}/api/v1/payments/momo/ipn`. Đối soát: `php artisan payments:reconcile` (tự chạy 01:30 hằng ngày). |
| Tìm kiếm | `SEARCH_DRIVER=mysql\|meilisearch`, `MEILISEARCH_HOST`, `MEILISEARCH_KEY` | Meilisearch tùy chọn: `docker compose --profile search up -d meilisearch`, đặt `SEARCH_DRIVER=meilisearch`, `php artisan search:reindex` (có chịu lỗi gõ sai; Meili lỗi tự quay về MySQL). |
| Bảo mật | `AUTH_COOKIE_SECURE`, `STAFF_2FA_ENFORCED`, `CORS_ALLOWED_ORIGINS` | Bật `AUTH_COOKIE_SECURE=true` khi chạy HTTPS. |

Vận chuyển đang **giả lập** (`MockCarrier`: khi chuyển "Đang giao" tự sinh mã vận đơn `MOCKyymmdd…`); thay bằng GHN/GHTK khi có tài khoản.

## Vận hành
- Hàng đợi: `php artisan horizon` (bảng điều khiển `http://localhost:8000/horizon`, chỉ môi trường local) hoặc `php artisan queue:work`.
- Lịch: `php artisan schedule:work` — hủy đơn MoMo quá hạn (mỗi phút), tổng hợp doanh thu `reports:summarize` (00:10), đối soát MoMo (01:30), dọn ảnh tạm, cảnh báo tồn thấp.
- Tài liệu API (OpenAPI 3): `php artisan docs:openapi` → `docs/openapi.json` (đã sinh sẵn).
- Triển khai tham khảo: `deploy/` (Dockerfile, nginx, docker-compose.prod.yml, backup.sh). CI: `.github/workflows/ci.yml`.

## Kiểm thử
- API: `cd api && php artisan test` (DB `shoe_test` + Redis prefix riêng; tạo DB: `CREATE DATABASE shoe_test`), `vendor/bin/pint --test`, `vendor/bin/phpstan analyse`.
- Admin / Storefront: `npm test` (Vitest), admin có `npm run lint`.
- E2E trình duyệt (cần API + 2 FE đang chạy): `cd e2e && npm i && npm run install-browser && npm test`.
