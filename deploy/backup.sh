#!/usr/bin/env bash
# Sao lưu hằng ngày (cron): dump MySQL (single-transaction) + nén thư mục ảnh. Giữ 14 bản.
set -euo pipefail
DEST="${BACKUP_DIR:-/var/backups/solehouse}"; STAMP=$(date +%Y%m%d-%H%M)
mkdir -p "$DEST"
docker compose -f "$(dirname "$0")/docker-compose.prod.yml" exec -T mysql sh -c 'mysqldump --single-transaction --routines -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"' | gzip > "$DEST/db-$STAMP.sql.gz"
docker compose -f "$(dirname "$0")/docker-compose.prod.yml" exec -T app tar czf - -C /var/www/html/storage/app public > "$DEST/images-$STAMP.tar.gz"
ls -1t "$DEST"/db-*.sql.gz | tail -n +15 | xargs -r rm -f
ls -1t "$DEST"/images-*.tar.gz | tail -n +15 | xargs -r rm -f
echo "OK $STAMP"
