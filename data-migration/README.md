# Nhập dữ liệu sản phẩm đã crawl

Nguồn: `Scraping/data/raw_shoes_*.csv` + `images_shoes/` (Biti's, Sneaker Buzz).

```bash
pip install pillow                                   # chỉ cần Pillow
python3 build.py --src ~/Documents/thangdv/Scraping/data   # làm sạch + xử lý ảnh + sinh SQL
# (tùy chọn) xóa 16 sản phẩm demo của seeder:
mysql ... < 00_clear_demo_catalog.sql
mysql -uroot -p --default-character-set=utf8mb4 shoe < import_products.sql
php artisan cache:clear   # nếu dùng Meilisearch: php artisan search:reindex
```
Docker: `docker exec -i api-mysql-1 mysql -uroot -ppassword --default-character-set=utf8mb4 shoe < import_products.sql`

- `import_products.sql` chạy lại được nhiều lần (xóa và nhập lại sản phẩm id ≥ 1000, brand/màu/size/danh mục dùng "thêm nếu chưa có").
- Ảnh ghi vào `api/storage/app/public/products/{id}/` (WebP gốc ≤1600px + `_md` 800px + `_sm` 300px) — không commit, tạo lại bằng `build.py`.
- `out/cleaning_report.txt`: thống kê, dòng bị loại, bảng chuẩn hóa màu.
- `--stock N` đổi tồn kho giả định (mặc định 10); `--skip-images` chỉ sinh SQL.

## Tự động nhập khi chạy `./dev.sh`
Sau khi đã chạy `build.py` (có `import_products.sql` và ảnh trong `api/storage/app/public/products/1000...`), `./dev.sh` / `./dev.sh --fresh` tự nhập sản phẩm thật nếu DB chưa có (id ≥ 1000). Seeder **không** tạo sản phẩm demo (ảnh vẽ) nữa, trừ khi đặt `SEED_DEMO_PRODUCTS=true` trong `api/.env`.
