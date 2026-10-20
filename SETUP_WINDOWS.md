# Cài đặt & chạy project trên Windows (không dùng Docker)

Cách này cài PHP, Composer, MySQL **trực tiếp lên Windows**, không qua Docker.

## 1. Cài Laragon (khuyên dùng — có sẵn PHP + MySQL + Composer)

[Laragon](https://laragon.org/download/) là công cụ phổ biến cho dev PHP trên Windows, cài 1 lần có đủ PHP, MySQL, Composer, Apache/Nginx, không cần cấu hình gì thêm.

1. Tải **Laragon Full** (bản Full đã kèm sẵn PHP/MySQL/Composer): https://laragon.org/download/
2. Cài đặt (Next liên tục là xong).
3. Mở Laragon, bấm **Start All** — nó sẽ bật Apache/Nginx + MySQL.
4. Kiểm tra PHP/Composer có sẵn: mở **Laragon → Terminal** (hoặc "Cmder" trong menu Laragon), gõ:
   ```
   php -v
   composer -V
   ```

> Nếu không muốn dùng Laragon, có thể cài riêng lẻ: PHP tại https://windows.php.net/download/ (bản Thread Safe, >= 8.2), Composer tại https://getcomposer.org/download/, MySQL tại https://dev.mysql.com/downloads/installer/. Phần hướng dẫn bên dưới vẫn áp dụng, chỉ khác bước khởi động MySQL/PHP.

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

## 4. Tạo database MySQL

Mở **Laragon → Menu → MySQL → HeidiSQL** (hoặc phpMyAdmin, hoặc bất kỳ tool GUI nào bạn quen: HeidiSQL/TablePlus/MySQL Workbench), kết nối:

| Thông số | Giá trị mặc định Laragon |
|---|---|
| Host | `127.0.0.1` |
| Port | `3306` |
| User | `root` |
| Password | *(để trống — Laragon mặc định không đặt password root)* |

Tạo database mới tên `laravel` (hoặc tên tuỳ bạn, miễn khớp với `.env` ở bước sau).

## 5. Cấu hình `.env` kết nối MySQL

Mở file `api/.env`, sửa phần DB:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

(Nếu không dùng Laragon mà tự cài MySQL riêng, điền đúng user/password bạn đã đặt lúc cài MySQL.)

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

1. Mở Laragon, bấm **Start All** (bật MySQL).
2. Trong `api/`: `php artisan serve`
3. Trong `storefront/`: `npm run dev`
4. Mở trình duyệt: `http://localhost:5180`

## Sự cố thường gặp

- **`php` hoặc `composer` không nhận lệnh**: PHP/Composer của Laragon chưa được thêm vào PATH — mở lại terminal từ trong Laragon (nó tự set PATH), hoặc tự thêm đường dẫn `C:\laragon\bin\php\php-x.x.x` vào biến môi trường PATH của Windows.
- **`php artisan migrate` báo lỗi kết nối DB**: kiểm tra MySQL đã "Start" trong Laragon chưa, và `.env` đã đúng host/port/user/password/database chưa, rồi chạy `php artisan config:clear`.
- **Thiếu extension PHP** (ví dụ lỗi "pdo_mysql" hoặc "mbstring"): Laragon → Menu → PHP → php.ini, mở file, bỏ dấu `;` trước dòng `extension=pdo_mysql`, `extension=mbstring`, `extension=fileinfo`, lưu lại rồi khởi động lại Apache/Nginx trong Laragon.
- **Port 8000/5180/3306 đã bị chiếm**: đổi port tương ứng (`php artisan serve --port=8001`, hoặc sửa `storefront/vite.config.js`, hoặc đổi port MySQL trong cấu hình Laragon).
