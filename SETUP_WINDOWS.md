# Cài đặt & chạy project trên Windows

PHP + Composer chạy **trực tiếp trên Windows** (không qua Docker). MySQL và
Redis chạy trong **Docker Desktop** (chỉ 2 service hạ tầng, không có container
app Laravel) — xem mục 4.

## 1. Cài PHP + Composer

Cách nhanh nhất là dùng [Laragon](https://laragon.org/download/) (chỉ để lấy PHP + Composer có sẵn, phần MySQL đi kèm Laragon thì bỏ qua, không cần Start — MySQL thật chạy trong Docker, xem mục 4):

1. Tải **Laragon Full**: https://laragon.org/download/
2. Cài đặt (Next liên tục là xong).
3. Mở **Laragon → Terminal** (hoặc "Cmder" trong menu Laragon) để có PHP/Composer trong PATH, gõ kiểm tra:
   ```
   php -v
   composer -V
   ```
4. Không cần bấm "Start All" / không cần khởi động MySQL của Laragon — MySQL thật của project chạy trong Docker (mục 4), không phải MySQL đi kèm Laragon.

> Nếu không muốn dùng Laragon, có thể cài riêng lẻ: PHP tại https://windows.php.net/download/ (bản Thread Safe, >= 8.2), Composer tại https://getcomposer.org/download/. Không cần cài MySQL ở bước này.

## 2. Lấy code về

Mở **Laragon → Terminal**, hoặc Git Bash / PowerShell bình thường:

```bash
cd C:\laragon\www
git clone <url-repo-cua-ban> webbangiay
cd webbangiay\api
```

(Nếu dùng Laragon, để code trong `C:\laragon\www\` thì Laragon tự tạo domain kiểu `webbangiay.test` luôn, tiện hơn.)

## 3. Cài dependency Laravel

```bash
composer install
copy .env.example .env
php artisan key:generate
```

## 4. Database + Redis: Docker Desktop (chỉ 2 service, không có container app)

MySQL và Redis chạy local qua Docker — không cần cài MySQL/Redis trực tiếp
lên Windows. Cần [Docker Desktop](https://www.docker.com/products/docker-desktop/)
đã cài và đang chạy.

```bash
cd api
docker compose up -d mysql redis
```

Lệnh này dùng sẵn `api/compose.yaml` (Laravel Sail) nhưng chỉ khởi động 2
service `mysql` và `redis`, publish port ra host (`3306`, `6379`) — **không**
build/chạy container app Laravel (PHP vẫn chạy trực tiếp trên Windows, xem
mục 7). Dùng `docker compose ps` để kiểm tra 2 container đang `Up (healthy)`,
và `docker compose down` khi muốn tắt.

## 5. Cấu hình `.env` kết nối database + Redis

Mở file `api/.env` (copy từ `.env.example` nếu chưa có) — giá trị mặc định
trong `.env.example` đã đúng cho setup này (`DB_HOST=127.0.0.1`,
`REDIS_HOST=127.0.0.1`, `REDIS_CLIENT=predis`), không cần sửa gì thêm:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

`REDIS_CLIENT=predis` là client Redis thuần PHP — không cần cài/build
extension `phpredis`, tiện cho Windows. Access token (login) và refresh
token đều lưu trong Redis (không lưu DB) — xem
`api/app/Services/Auth/TokenBroker.php`.

Sau khi sửa `.env` (nếu có), chạy `php artisan config:clear` để Laravel đọc lại config mới.

## 6. Chạy migration + test

```bash
php artisan migrate
php artisan test
```

## 7. Chạy backend

```bash
php artisan serve
```

API chạy ở: **http://localhost:8000**

(Nếu dùng Laragon và để code trong `www\`, bạn cũng có thể truy cập qua domain tự động `http://webbangiay.test` mà không cần chạy `artisan serve` — Laragon tự cấu hình virtual host qua Apache/Nginx có sẵn. Dùng `artisan serve` vẫn đơn giản và chắc chắn hoạt động hơn nếu mới làm quen.)

## 8. Chạy frontend storefront (Vue)

Cần cài **Node.js** (tải tại https://nodejs.org/, bản LTS), sau đó:

```bash
cd ..\storefront
npm install
npm run dev
```

Mặc định chạy ở `http://localhost:5180`. Nếu port bị trùng, đổi trong `storefront/vite.config.js`, rồi sửa khớp lại trong `api/.env` (`SANCTUM_STATEFUL_DOMAINS`, `FRONTEND_STOREFRONT_URL`) và `api/config/cors.php` (`allowed_origins`), sau đó chạy `php artisan config:clear`.

## 9. Thứ tự khởi động mỗi lần làm việc

1. Mở Docker Desktop (nếu chưa chạy), rồi trong `api/`: `docker compose up -d mysql redis` (bỏ qua nếu 2 container còn đang chạy từ lần trước).
2. Trong `api/`: `php artisan serve`
3. Trong `storefront/`: `npm run dev`
4. Mở trình duyệt: `http://localhost:5180`

## Sự cố thường gặp

- **`php` hoặc `composer` không nhận lệnh**: PHP/Composer của Laragon chưa được thêm vào PATH — mở lại terminal từ trong Laragon (nó tự set PATH), hoặc tự thêm đường dẫn `C:\laragon\bin\php\php-x.x.x` vào biến môi trường PATH của Windows.
- **`php artisan migrate`/`serve` báo lỗi kết nối DB** (ví dụ "SQLSTATE[HY000] [2002] Connection refused"): MySQL trong Docker chưa chạy — `docker compose up -d mysql redis` trong `api/`, đợi vài giây rồi thử lại. Kiểm tra `docker compose ps` thấy `mysql`/`redis` là `Up (healthy)`.
- **Lỗi kết nối Redis** (login báo lỗi 500, hoặc session không giữ được): kiểm tra Redis đã chạy (`docker compose ps`) và `.env` có `REDIS_HOST=127.0.0.1`, `REDIS_CLIENT=predis`.
- **Thiếu extension PHP** (ví dụ lỗi "pdo_mysql" hoặc "mbstring"): Laragon → Menu → PHP → php.ini, mở file, bỏ dấu `;` trước dòng `extension=pdo_mysql`, `extension=mbstring`, `extension=fileinfo`, lưu lại rồi khởi động lại Apache/Nginx trong Laragon.
- **Port 8000/5180 đã bị chiếm**: đổi port tương ứng (`php artisan serve --port=8001`, hoặc sửa `storefront/vite.config.js`).
