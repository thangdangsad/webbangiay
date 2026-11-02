# Cài đặt môi trường dev trên Windows

Hướng dẫn cài các công cụ để chạy SoleHouse (API Laravel + Storefront Nuxt + Admin Vue) trên Windows 10/11.

> **Khuyến nghị: dùng WSL2 (Ubuntu).** Script `dev.sh` là bash, dùng `setsid`, `/dev/tcp`… nên chạy chuẩn trong WSL2; **không hỗ trợ Git Bash** (thiếu `setsid`, và Git Bash tự đổi đường dẫn `/app` khi gọi Docker). Nếu không muốn dùng WSL, xem [Phần 8 – chạy thủ công bằng PowerShell](#8-không-dùng-wsl-chạy-thủ-công-bằng-powershell).

## Tổng quan cần cài

| Công cụ | Dùng để | Bắt buộc |
|---|---|---|
| WSL2 + Ubuntu | môi trường chạy `dev.sh` | Có (cách khuyến nghị) |
| Docker Desktop | MySQL, Redis (và PHP nếu máy không có PHP) | Có |
| Git | clone / commit code | Có |
| Node.js 22 qua **nvm** | chạy Storefront (Nuxt) và Admin (Vite) | Có |
| PHP 8.3+ & Composer | chạy API | **Không** – `dev.sh` tự build image PHP bằng Docker |
| Python 3 + Pillow | chỉ để chạy `data-migration/build.py` | Không |
| VS Code (+ extension WSL) | soạn code | Nên có |

Yêu cầu máy: Windows 10 22H2 trở lên hoặc Windows 11, RAM ≥ 8 GB (khuyến nghị 16 GB), ~15 GB trống, bật **Virtualization (VT-x/SVM)** trong BIOS.

---

## 1. Cài WSL2 + Ubuntu

Mở **PowerShell (Run as administrator)**:

```powershell
wsl --install -d Ubuntu-24.04
```

Khởi động lại máy khi được yêu cầu, mở "Ubuntu" từ Start Menu và tạo user/password Linux. Kiểm tra:

```powershell
wsl -l -v          # Ubuntu-24.04 ... VERSION 2
```

Nếu đang là VERSION 1: `wsl --set-version Ubuntu-24.04 2`.

Cập nhật gói trong Ubuntu:

```bash
sudo apt update && sudo apt -y upgrade
sudo apt -y install git curl unzip build-essential
```

### (Tùy chọn) giới hạn RAM cho WSL
Tạo `C:\Users\<tên bạn>\.wslconfig`:

```ini
[wsl2]
memory=6GB
processors=4
```
Rồi `wsl --shutdown` để áp dụng.

## 2. Cài Docker Desktop

1. Tải: https://www.docker.com/products/docker-desktop/ (hoặc `winget install -e --id Docker.DockerDesktop`).
2. Cài đặt, chọn **Use WSL 2 instead of Hyper-V**. Khởi động lại nếu được yêu cầu.
3. Mở Docker Desktop → **Settings → Resources → WSL Integration** → bật *Enable integration with my default WSL distro* và bật cho **Ubuntu-24.04** → *Apply & restart*.
4. Kiểm tra **trong Ubuntu (WSL)**:

```bash
docker version
docker compose version
docker run --rm hello-world
```

> Docker Desktop phải **đang chạy** (biểu tượng cá voi ở khay hệ thống) mỗi khi dùng `dev.sh`. Cá nhân/doanh nghiệp nhỏ dùng miễn phí; doanh nghiệp lớn cần xem giấy phép của Docker.

## 3. Cài Git

Trong **Ubuntu (WSL)** đã cài ở bước 1. Cấu hình:

```bash
git config --global user.name  "Tên của bạn"
git config --global user.email "ban@example.com"
git config --global core.autocrlf input      # luôn lưu LF, tránh lỗi script
```

(Nếu còn dùng Git trên Windows: `winget install -e --id Git.Git`, chọn *Checkout as-is, commit Unix-style line endings*.)

## 4. Cài Node.js bằng nvm

### Cách A – trong WSL (khuyến nghị, đi cùng `dev.sh`)

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.1/install.sh | bash
# đóng và mở lại terminal Ubuntu, rồi:
nvm install 22
nvm use 22
nvm alias default 22
node -v && npm -v          # v22.x và 10.x
```

> Đừng dùng Node cài trên Windows từ trong WSL (đường dẫn `/mnt/c/...` sẽ chậm và dễ lỗi). Nếu `which node` trả về đường dẫn bắt đầu bằng `/mnt/c`, hãy cài nvm trong WSL như trên.

### Cách B – nvm-windows (nếu chạy Node trực tiếp trên Windows)

```powershell
winget install -e --id CoreyButler.NVMforWindows
# mở PowerShell mới:
nvm install 22
nvm use 22
node -v
```

## 5. PHP & Composer (không bắt buộc)

`dev.sh` tự dùng PHP trong Docker: lần chạy đầu nó build image `shoe-php-dev` từ `deploy/Dockerfile.dev` (PHP 8.3 + các extension gd/pdo_mysql/…, Composer), mất khoảng 1–2 phút, các lần sau dùng lại.

Nếu muốn cài PHP thật trong WSL (nhanh hơn một chút):

```bash
sudo apt -y install software-properties-common
sudo add-apt-repository -y ppa:ondrej/php && sudo apt update
sudo apt -y install php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl php8.3-zip php8.3-mysql php8.3-gd php8.3-bcmath php8.3-intl
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
php -v && composer -V
```
Khi có `php` và `composer` trong PATH, `dev.sh` tự chuyển sang dùng bản cài sẵn.

## 6. Python + Pillow (chỉ khi nhập dữ liệu crawl)

```bash
sudo apt -y install python3 python3-pip python3-venv
python3 -m venv ~/.venvs/shoe && source ~/.venvs/shoe/bin/activate
pip install pillow
```
Xem `data-migration/README.md` để chạy `build.py`.

## 7. Lấy code và chạy

**Clone vào ổ đĩa của Linux (`~/`), KHÔNG clone vào `/mnt/c/...`** – truy cập file qua `/mnt/c` chậm hơn nhiều lần (npm install, MySQL, Nuxt) và dễ lỗi quyền file.

```bash
cd ~
git clone <url-repo-của-bạn> webbangiay
cd webbangiay
chmod +x dev.sh
./dev.sh
```

Lần đầu mất vài phút (build image PHP, `composer install`, `npm install`, migrate + seed). Khi thấy khung địa chỉ:

| | Địa chỉ |
|---|---|
| Storefront | http://localhost:3000 |
| Admin | http://localhost:5173 — `admin@solehouse.vn` / `Admin@123` |
| API | http://localhost:8000/api/v1 |

Mở trực tiếp các địa chỉ này bằng trình duyệt **trên Windows** (WSL2 tự chuyển tiếp `localhost`). Luôn dùng `localhost`, đừng dùng `127.0.0.1` (cookie đăng nhập sẽ không gửi được).

Dừng: **Ctrl+C**. Tắt luôn MySQL/Redis: `./dev.sh --stop`. Reset sạch dữ liệu: `./dev.sh --fresh`.

Soạn code bằng VS Code: cài extension **WSL**, rồi trong Ubuntu gõ `code .` tại thư mục dự án.

## 8. Không dùng WSL: chạy thủ công bằng PowerShell

Cần: Docker Desktop, Git for Windows, nvm-windows (Node 22), và **PHP 8.3 + Composer** cài trên Windows (dễ nhất là [Laragon Full](https://laragon.org/download/): có sẵn PHP + Composer; **không** bật MySQL của Laragon vì MySQL thật chạy bằng Docker). Trong `php.ini` của Laragon bật các extension: `gd`, `pdo_mysql`, `mbstring`, `intl`, `zip`, `fileinfo`, `bcmath`.

Mở 4 cửa sổ PowerShell:

```powershell
# Cửa sổ 1 – hạ tầng + API
cd C:\dev\webbangiay\api
docker compose up -d mysql redis
composer install
copy .env.example .env        # nếu chưa có .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve --port=8000

# Cửa sổ 2 – hàng đợi email + lịch
cd C:\dev\webbangiay\api ; php artisan queue:work
# (thêm một cửa sổ nữa) php artisan schedule:work

# Cửa sổ 3 – Storefront
cd C:\dev\webbangiay\storefront ; npm install ; npm run dev

# Cửa sổ 4 – Admin
cd C:\dev\webbangiay\admin-panel ; npm install ; npm run dev
```

Clone vào đường dẫn ngắn như `C:\dev\` (tránh giới hạn 260 ký tự của Windows khi `npm install`). Nếu `git clone` làm file `.sh` bị CRLF thì repo đã có `.gitattributes` ép LF; có thể chạy `git add --renormalize .`.

## 9. Kiểm tra nhanh sau khi cài

```bash
docker run --rm hello-world        # Docker chạy được
docker compose version             # v2.x
node -v                            # v22.x
git --version
curl -s localhost:8000/up          # sau khi ./dev.sh: API trả 200
```

## 10. Lỗi thường gặp

| Triệu chứng | Cách xử lý |
|---|---|
| `bash: ./dev.sh: /bin/bash^M: bad interpreter` | File bị CRLF. Chạy `sed -i 's/\r$//' dev.sh` (hoặc `dos2unix dev.sh`), và đặt `git config --global core.autocrlf input`. |
| `Cannot connect to the Docker daemon` / `docker: command not found` trong WSL | Mở Docker Desktop và đợi nó chạy xong; kiểm tra bước bật *WSL Integration* cho Ubuntu (Phần 2). |
| `Cổng 8000/3000/5173 đang bị chiếm` | Tìm tiến trình: PowerShell `netstat -ano \| findstr :8000` rồi `taskkill /PID <pid> /F`; hoặc trong WSL `ss -ltnp \| grep 8000`. Tắt dev server cũ. |
| MySQL không lên, báo cổng 3306 bận | Máy đang chạy MySQL/XAMPP/Laragon MySQL. Tắt nó, hoặc đặt `FORWARD_DB_PORT=3307` trong `api/.env` **và** `DB_PORT=3307`. Tương tự Redis: `FORWARD_REDIS_PORT` + `REDIS_PORT`. |
| `npm install` rất chậm hoặc lỗi quyền | Dự án đang nằm trong `/mnt/c/...`. Chuyển vào `~/` của WSL. |
| `EACCES` khi `npm install -g` | Dùng nvm (Phần 4) thay vì cài Node bằng `apt`. |
| Build image PHP lỗi mạng | Chạy lại `./dev.sh`; xem `.logs/docker-build.log`. Có proxy công ty thì cấu hình proxy trong Docker Desktop → Settings → Resources → Proxies. |
| Đăng nhập xong bị văng ra / không giữ phiên | Đang truy cập bằng `127.0.0.1` hoặc IP. Dùng `http://localhost:3000` và `http://localhost:5173`. |
| Ảnh sản phẩm không hiện | Chạy `php artisan storage:link` (trong `api/`); với dữ liệu crawl, chạy `data-migration/build.py` để sinh ảnh. |
| WSL ngốn RAM | Đặt giới hạn trong `.wslconfig` (Phần 1), `wsl --shutdown`. |
| Đồng hồ lệch sau khi máy ngủ → JWT báo hết hạn | `wsl --shutdown` rồi mở lại WSL (đồng bộ lại giờ). |

## 11. Gỡ cài đặt / dọn dẹp

```bash
./dev.sh --stop                                  # dừng MySQL + Redis
cd api && docker compose down -v                 # xóa luôn volume Redis (dữ liệu MySQL nằm ở api/data_mysql)
docker image rm shoe-php-dev                     # xóa image PHP dev
docker system prune                              # dọn image/container thừa
```
