# Cài đặt & chạy project trên Windows

Project này chạy Laravel (`api/`) qua Docker (Laravel Sail) — **không cần cài PHP/Composer/MySQL trực tiếp lên Windows**, chỉ cần Docker. MySQL cũng chạy trong container, bạn kết nối vào nó từ các tool Windows bình thường (HeidiSQL, TablePlus, DBeaver, MySQL Workbench...) qua `localhost:3306`.

## 1. Cài Docker Desktop (bắt buộc)

1. Tải và cài **Docker Desktop for Windows**: https://www.docker.com/products/docker-desktop/
2. Khi cài, chọn dùng **WSL 2 backend** (mặc định ở bản mới). Nếu máy chưa có WSL2, Docker Desktop sẽ tự nhắc cài — đồng ý theo hướng dẫn, có thể cần khởi động lại máy.
3. Mở Docker Desktop, đợi nó chạy xong (icon cá voi ở khay hệ thống chuyển sang trạng thái "running").
4. Kiểm tra trong **PowerShell**:
   ```powershell
   docker --version
   docker compose version
   ```

## 2. Cài WSL2 + Ubuntu (khuyến nghị, để code chạy nhanh)

Chạy Sail từ ổ đĩa Windows (`C:\...`) hoạt động được nhưng **chậm hơn đáng kể** so với chạy trong WSL2. Nên clone code vào trong WSL2:

```powershell
wsl --install -d Ubuntu
```

Sau khi cài xong, mở app **Ubuntu** từ Start Menu, tạo user/password Linux khi được hỏi. Từ đây, mọi lệnh bên dưới chạy **trong cửa sổ Ubuntu (WSL2)**, không phải PowerShell/CMD.

Trong Docker Desktop: **Settings → Resources → WSL Integration**, bật toggle cho bản phân phối Ubuntu bạn vừa cài.

## 3. Lấy code về

Trong cửa sổ Ubuntu (WSL2):

```bash
cd ~
git clone <url-repo-cua-ban> webbangiay
cd webbangiay
```

(Nếu đã có code sẵn trên Windows, copy nguyên thư mục vào trong WSL2, ví dụ vào `~/webbangiay`, đừng để trong `/mnt/c/...` vì chạy chậm.)

## 4. Chạy backend Laravel (api/)

```bash
cd ~/webbangiay/api
./vendor/bin/sail up -d
```

Lần đầu chạy sẽ hơi lâu vì Docker tải image. Khi xong:

```bash
./vendor/bin/sail artisan migrate
./vendor/bin/sail artisan test   # kiểm tra mọi thứ chạy đúng
```

API chạy ở: **http://localhost:8000**

Các lệnh hay dùng:
```bash
./vendor/bin/sail up -d        # bật containers
./vendor/bin/sail stop         # tắt containers
./vendor/bin/sail artisan ...  # chạy lệnh artisan
./vendor/bin/sail composer ... # chạy composer
./vendor/bin/sail npm ...      # chạy npm (bên trong container, không dùng cho storefront/)
```

## 5. Kết nối MySQL từ Windows (HeidiSQL / TablePlus / DBeaver / Workbench...)

MySQL chạy trong container nhưng có mở cổng ra ngoài máy, nên dùng tool GUI quen thuộc trên Windows kết nối như MySQL bình thường:

| Thông số | Giá trị |
|---|---|
| Host | `127.0.0.1` (hoặc `localhost`) |
| Port | `3306` |
| User | `sail` (hoặc `root`) |
| Password | `password` |
| Database | `laravel` |

> Các giá trị này lấy từ `api/.env` (`DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`). Nếu bạn đổi trong `.env`, nhớ `./vendor/bin/sail artisan config:clear` rồi `./vendor/bin/sail up -d` lại.

Nếu đang mở tool GUI **trên Windows** (không phải trong WSL2), vẫn dùng `127.0.0.1:3306` bình thường — Docker Desktop tự forward port từ WSL2 ra Windows.

## 6. Chạy frontend storefront (Vue)

Chạy **trực tiếp bằng Node**, không qua Docker:

```bash
cd ~/webbangiay/storefront
npm install
npm run dev
```

Mặc định chạy ở `http://localhost:5180` (xem `storefront/vite.config.js` nếu cần đổi port — nhớ đổi khớp luôn trong `api/.env` ở `SANCTUM_STATEFUL_DOMAINS`/`FRONTEND_STOREFRONT_URL` và `api/config/cors.php`, rồi `sail artisan config:clear`).

## 7. Thứ tự khởi động mỗi lần làm việc

```bash
cd ~/webbangiay/api && ./vendor/bin/sail up -d
cd ~/webbangiay/storefront && npm run dev
```

Mở trình duyệt: `http://localhost:5180`.

## Sự cố thường gặp

- **Port 3306/8000/5180 đã bị chiếm**: đổi `FORWARD_DB_PORT`/`APP_PORT` trong `api/.env`, hoặc đổi `PORT` trong `storefront/vite.config.js` (nhớ cập nhật CORS/Sanctum tương ứng).
- **Docker Desktop báo "WSL2 not installed"**: chạy `wsl --install` trong PowerShell (chạy với quyền Administrator), khởi động lại máy.
- **Chạy `sail` rất chậm**: kiểm tra code có đang nằm trong `/mnt/c/...` không — nếu có, chuyển vào thư mục home của WSL2 (`~/`) thay vì ổ Windows.
- **`./vendor/bin/sail` báo "permission denied"**: chạy `chmod +x vendor/bin/sail` trong thư mục `api/`.
