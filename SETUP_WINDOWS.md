# Cài đặt & chạy project trên Windows (không dùng Docker)

Cách này cài PHP, Composer, MySQL **trực tiếp lên Windows**, không qua Docker.

## 1. Cài PHP + Composer

Project **không dùng MySQL cài local** — database thật chạy trên **TiDB Cloud** (xem mục 4), nên bạn chỉ cần cài PHP + Composer, không cần Laragon hay MySQL Installer.

Cách nhanh nhất là dùng [Laragon](https://laragon.org/download/) (chỉ để lấy PHP + Composer có sẵn, phần MySQL đi kèm Laragon thì bỏ qua, không cần Start):

1. Tải **Laragon Full**: https://laragon.org/download/
2. Cài đặt (Next liên tục là xong).
3. Mở **Laragon → Terminal** (hoặc "Cmder" trong menu Laragon) để có PHP/Composer trong PATH, gõ kiểm tra:
   ```
   php -v
   composer -V
   ```
4. Không cần bấm "Start All" / không cần khởi động MySQL của Laragon — project kết nối thẳng tới database trên cloud.

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

## 4. Database: TiDB Cloud (không cần tạo/cài gì local)

Database dùng chung cho cả team đã có sẵn trên **TiDB Cloud** (MySQL-compatible, serverless) — không cần cài MySQL, không cần tạo database mới, chỉ cần config `.env` để kết nối tới.

Xin thông tin kết nối (host/user/password) từ người quản lý project (không ghi trực tiếp trong file này vì đây là file commit lên git). Nếu muốn xem bằng GUI, có thể dùng HeidiSQL/TablePlus/DBeaver/IntelliJ Database tool, kết nối qua driver MySQL tới host TiDB Cloud, bật SSL.

## 5. Cấu hình `.env` kết nối database

Mở file `api/.env` (copy từ `.env.example` nếu chưa có), sửa phần DB theo thông tin TiDB Cloud được cấp:

```env
DB_CONNECTION=mysql
DB_HOST=gateway01.ap-southeast-1.prod.aws.tidbcloud.com
DB_PORT=4000
DB_DATABASE=<tên-database-được-cấp>
DB_USERNAME=<username-được-cấp>
DB_PASSWORD=<password-được-cấp>
```

TiDB Cloud yêu cầu kết nối qua **TLS/SSL**, cần chỉ Laravel tới file CA cert gốc hệ thống. Trên Windows (dùng Laragon), thêm dòng:

```env
MYSQL_ATTR_SSL_CA=C:\laragon\bin\php\<phiên-bản-php>\extras\ssl\cacert.pem
```

(Nếu không có sẵn file CA cert, tải bộ CA bundle của Mozilla tại https://curl.se/ca/cacert.pem, lưu vào máy rồi chỉ đường dẫn tới đó.)

Sau khi sửa `.env`, chạy `php artisan config:clear` để Laravel đọc lại config mới.

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

1. Trong `api/`: `php artisan serve` (không cần bật MySQL local — DB đã chạy sẵn trên TiDB Cloud).
2. Trong `storefront/`: `npm run dev`
3. Mở trình duyệt: `http://localhost:5180`

## Sự cố thường gặp

- **`php` hoặc `composer` không nhận lệnh**: PHP/Composer của Laragon chưa được thêm vào PATH — mở lại terminal từ trong Laragon (nó tự set PATH), hoặc tự thêm đường dẫn `C:\laragon\bin\php\php-x.x.x` vào biến môi trường PATH của Windows.
- **`php artisan migrate`/`serve` báo lỗi kết nối DB** (ví dụ "SQLSTATE[HY000] [2002]" hoặc lỗi SSL): kiểm tra `.env` đã đúng `DB_HOST`/`DB_PORT`/`DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` theo thông tin được cấp chưa, đường dẫn `MYSQL_ATTR_SSL_CA` có tồn tại trên máy không, rồi chạy `php artisan config:clear`. Cũng kiểm tra mạng/firewall công ty có chặn kết nối ra ngoài cổng `4000` không — TiDB Cloud yêu cầu kết nối Internet, không chạy được offline.
- **Thiếu extension PHP** (ví dụ lỗi "pdo_mysql" hoặc "mbstring"): Laragon → Menu → PHP → php.ini, mở file, bỏ dấu `;` trước dòng `extension=pdo_mysql`, `extension=mbstring`, `extension=fileinfo`, lưu lại rồi khởi động lại Apache/Nginx trong Laragon.
- **Port 8000/5180 đã bị chiếm**: đổi port tương ứng (`php artisan serve --port=8001`, hoặc sửa `storefront/vite.config.js`).
