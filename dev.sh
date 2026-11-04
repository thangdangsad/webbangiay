#!/usr/bin/env bash
# Chạy toàn bộ môi trường dev bằng một lệnh:  ./dev.sh        (Ctrl+C để dừng tất cả)
#   Hạ tầng (Docker): MySQL + Redis → API Laravel :8000 (+ queue + scheduler) → Storefront :3000 → Admin :5173
# Tùy chọn:  ./dev.sh --fresh   xóa sạch DB rồi migrate + seed lại
#            ./dev.sh --stop    dừng cả hạ tầng Docker (MySQL, Redis)
#            ./dev.sh --reset-db  XÓA dữ liệu MySQL (api/data_mysql) để khởi tạo lại user/mật khẩu theo api/.env (sửa lỗi 'Access denied for user')
set -uo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API="$ROOT/api"; LOGS="$ROOT/.logs"; mkdir -p "$LOGS"
FRESH=0; RESET_DB=0
for a in "$@"; do case "$a" in --fresh) FRESH=1 ;; --reset-db) RESET_DB=1; FRESH=1 ;; --stop) (cd "$API" && docker compose down); exit 0 ;; esac; done

c_ok()  { printf '\033[32m✔ %s\033[0m\n' "$*"; }
c_inf() { printf '\033[36m➜ %s\033[0m\n' "$*"; }
c_err() { printf '\033[31m✘ %s\033[0m\n' "$*" >&2; }
need()  { command -v "$1" >/dev/null 2>&1 || { c_err "Thiếu '$1' — hãy cài trước."; exit 1; }; }

need docker; need node; need npm

# PHP: dùng bản cài sẵn nếu có (>= 8.3 + composer), nếu không chạy qua Docker (tự build image shoe-php-dev từ deploy/Dockerfile.dev)
if command -v php >/dev/null 2>&1 && command -v composer >/dev/null 2>&1; then
  PHP_MODE=local
  php_run() { (cd "$API" && "$@"); }
else
  PHP_MODE=docker
  DOCKER_PHP_IMAGE="${DOCKER_PHP_IMAGE:-shoe-php-dev}"
  if ! docker image inspect "$DOCKER_PHP_IMAGE" >/dev/null 2>&1; then
    if [ "$DOCKER_PHP_IMAGE" = shoe-php-dev ]; then
      c_inf "Build image PHP cho dev (lần đầu, vài phút)..."
      docker build -t shoe-php-dev -f "$ROOT/deploy/Dockerfile.dev" "$ROOT/deploy" >"$LOGS/docker-build.log" 2>&1 || { c_err "Build image lỗi, xem .logs/docker-build.log"; exit 1; }
    else
      c_err "Không thấy image '$DOCKER_PHP_IMAGE'."; exit 1
    fi
  fi
  php_run() { docker run --rm -i --network host -u "$(id -u):$(id -g)" -e HOME=/tmp -e COMPOSER_HOME=/tmp/composer -v "$API:/app" -w /app --entrypoint "$1" "$DOCKER_PHP_IMAGE" "${@:2}"; }
fi
c_inf "PHP chạy ở chế độ: $PHP_MODE"

PIDS=(); DOCKER_NAMES=()
cleanup() {
  trap - INT TERM EXIT
  echo; c_inf "Đang dừng các tiến trình..."
  for p in "${PIDS[@]:-}"; do [ -n "$p" ] && kill -- -"$p" 2>/dev/null || kill "$p" 2>/dev/null; done
  for n in "${DOCKER_NAMES[@]:-}"; do [ -n "$n" ] && docker rm -f "$n" >/dev/null 2>&1; done
  c_ok "Đã dừng (MySQL/Redis vẫn chạy nền; dừng bằng ./dev.sh --stop)"
  exit 0
}
trap cleanup INT TERM EXIT

# bắt đầu 1 tiến trình nền trong nhóm riêng, log ra .logs/<tên>.log
spawn() { local name="$1"; shift; setsid "$@" >"$LOGS/$name.log" 2>&1 & PIDS+=("$!"); }

port_busy() { (exec 3<>"/dev/tcp/127.0.0.1/$1") 2>/dev/null && { exec 3>&- 3<&-; return 0; } || return 1; }
for p in 8000 3000 5173; do port_busy $p && { c_err "Cổng $p đang bị chiếm — hãy tắt tiến trình đang chạy (hoặc ./dev.sh sau khi dừng bản cũ)."; trap - EXIT; exit 1; }; done

# 1. Hạ tầng
[ -f "$API/.env" ] || { cp "$API/.env.example" "$API/.env"; c_inf "Đã tạo api/.env từ .env.example"; NEW_ENV=1; }
# đọc thông tin DB từ api/.env (mặc định khớp compose.yaml)
env_get() { grep -E "^$1=" "$API/.env" | head -1 | cut -d= -f2- | tr -d '"\r'; }
DB_ROOT_PASS="$(env_get DB_PASSWORD)"; DB_ROOT_PASS="${DB_ROOT_PASS:-password}"; DB_NAME="$(env_get DB_DATABASE)"; DB_NAME="${DB_NAME:-shoe}"
if [ "$RESET_DB" = 1 ]; then
  c_inf "Reset MySQL: xóa api/data_mysql..."
  (cd "$API" && docker compose down) >"$LOGS/docker.log" 2>&1
  docker run --rm -v "$API:/work" alpine sh -c 'rm -rf /work/data_mysql' >>"$LOGS/docker.log" 2>&1
fi
c_inf "Khởi động MySQL + Redis (Docker)..."
(cd "$API" && docker compose up -d mysql redis) >"$LOGS/docker.log" 2>&1 || { c_err "docker compose lỗi, xem .logs/docker.log"; trap - EXIT; exit 1; }
for i in $(seq 1 60); do
  (cd "$API" && docker compose exec -T mysql mysqladmin ping -h127.0.0.1 -uroot -p"$DB_ROOT_PASS" --silent >/dev/null 2>&1) && break
  [ "$i" = 60 ] && { c_err "MySQL không sẵn sàng"; trap - EXIT; exit 1; }; sleep 2
done
c_ok "MySQL + Redis sẵn sàng"

# 2. API
if [ ! -d "$API/vendor" ]; then c_inf "composer install..."; php_run composer install --no-interaction >"$LOGS/composer.log" 2>&1 || { c_err "composer lỗi, xem .logs/composer.log"; trap - EXIT; exit 1; }; fi
grep -q '^APP_KEY=.\+' "$API/.env" || php_run php artisan key:generate --force >/dev/null
[ -e "$API/public/storage" ] || php_run php artisan storage:link >/dev/null 2>&1
if [ "$FRESH" = 1 ]; then c_inf "migrate:fresh --seed..."; php_run php artisan migrate:fresh --seed --force >"$LOGS/migrate.log" 2>&1 || { c_err "migrate lỗi, xem .logs/migrate.log"; trap - EXIT; exit 1; }
else
  php_run php artisan migrate --force >"$LOGS/migrate.log" 2>&1 || {
    if grep -q "Access denied" "$LOGS/migrate.log"; then c_err "MySQL từ chối user/mật khẩu trong api/.env (dữ liệu MySQL cũ được tạo với thông tin khác). Chạy:  ./dev.sh --reset-db   (xóa dữ liệu MySQL rồi tạo lại)"
    else c_err "migrate lỗi, xem .logs/migrate.log"; fi
    trap - EXIT; exit 1; }
  ROLES=$(php_run php artisan tinker --execute='echo \App\Modules\User\Models\Role::count();' 2>/dev/null | tr -dc '0-9')
  if [ "${ROLES:-0}" = 0 ]; then c_inf "DB trống → seed dữ liệu mẫu..."; php_run php artisan db:seed --force >>"$LOGS/migrate.log" 2>&1 || { c_err "seed lỗi"; trap - EXIT; exit 1; }; fi
fi
# Nhập dữ liệu sản phẩm thật (crawl) nếu đã build (data-migration/build.py) và DB chưa có
SQL_IMPORT="$ROOT/data-migration/import_products.sql"
if [ -f "$SQL_IMPORT" ]; then
  REAL=$(cd "$API" && docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASS" -N "$DB_NAME" -e "SELECT COUNT(*) FROM products WHERE id >= 1000" 2>/dev/null | tr -dc '0-9')
  if [ "${REAL:-0}" = 0 ]; then
    if [ -d "$API/storage/app/public/products/1000" ]; then
      c_inf "Nhập dữ liệu sản phẩm thật (data-migration/import_products.sql)..."
      (cd "$API" && docker compose exec -T mysql mysql -uroot -p"$DB_ROOT_PASS" --default-character-set=utf8mb4 "$DB_NAME" < "$SQL_IMPORT") >"$LOGS/import-products.log" 2>&1 \
        && c_ok "Đã nhập sản phẩm" || c_err "Nhập sản phẩm lỗi, xem .logs/import-products.log"
    else
      c_inf "Có import_products.sql nhưng chưa có ảnh → chạy: python3 data-migration/build.py --src <thư mục Scraping/data>"
    fi
  fi
else
  c_inf "Chưa có dữ liệu sản phẩm: chạy data-migration/build.py (xem data-migration/README.md) hoặc đặt SEED_DEMO_PRODUCTS=true rồi ./dev.sh --fresh"
fi
c_ok "Database sẵn sàng"

c_inf "Khởi động API :8000 (+ queue, scheduler)..."
if [ "$PHP_MODE" = local ]; then
  spawn api bash -c "cd '$API' && exec php artisan serve --host=0.0.0.0 --port=8000"
  spawn queue bash -c "cd '$API' && exec php artisan queue:work --tries=3 --sleep=2"
  spawn schedule bash -c "cd '$API' && exec php artisan schedule:work"
else
  docker_bg() { local n="shoe-$1"; shift; DOCKER_NAMES+=("$n"); docker rm -f "$n" >/dev/null 2>&1
    spawn "${n#shoe-}" docker run --rm --name "$n" --network host -u "$(id -u):$(id -g)" -e HOME=/tmp -v "$API:/app" -w /app --entrypoint php "$DOCKER_PHP_IMAGE" "$@"; }
  docker_bg api artisan serve --host=0.0.0.0 --port=8000
  docker_bg queue artisan queue:work --tries=3 --sleep=2
  docker_bg schedule artisan schedule:work
fi

# 3. Frontend
for fe in storefront admin-panel; do
  [ -d "$ROOT/$fe/node_modules" ] || { c_inf "npm install ($fe)..."; (cd "$ROOT/$fe" && npm install >"$LOGS/npm-$fe.log" 2>&1) || { c_err "npm install lỗi ($fe), xem .logs/npm-$fe.log"; trap - EXIT; exit 1; }; }
done
c_inf "Khởi động Storefront :3000 và Admin :5173..."
spawn storefront bash -c "cd '$ROOT/storefront' && exec npx nuxt dev --port 3000"
spawn admin bash -c "cd '$ROOT/admin-panel' && exec npx vite --port 5173 --host localhost"

# 4. Chờ sẵn sàng
wait_http() { for i in $(seq 1 90); do curl -s -o /dev/null "$1" && return 0; sleep 1; done; return 1; }
for t in "API|http://localhost:8000/up" "Storefront|http://localhost:3000/" "Admin|http://localhost:5173/"; do
  if wait_http "${t#*|}"; then c_ok "${t%%|*} → ${t#*|}"; else c_err "${t%%|*} chưa lên sau 90s — xem .logs/"; fi
done

cat <<MSG

  ┌──────────────────────────────────────────────────────────────┐
  │  Storefront   http://localhost:3000                          │
  │  Admin        http://localhost:5173   admin@solehouse.vn / Admin@123 │
  │  API          http://localhost:8000/api/v1                   │
  │  Khách mẫu    khach@solehouse.vn / Khach@123                 │
  │  Log          $LOGS/*.log                                    │
  └──────────────────────────────────────────────────────────────┘
  Nhấn Ctrl+C để dừng API + 2 frontend.

MSG
wait
