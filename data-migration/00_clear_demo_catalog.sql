-- TÙY CHỌN: xóa các sản phẩm DEMO do seeder tạo (id < 1000). Chạy TRƯỚC import_products.sql nếu muốn chỉ còn dữ liệu thật.
-- Ảnh demo trong storage/app/public/products/1..16 không bị xóa bởi SQL (xóa tay nếu cần).
START TRANSACTION;
DELETE FROM inventory_movements WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id < 1000);
DELETE FROM products WHERE id < 1000;   -- cascade: biến thể, ảnh, tồn kho, wishlist, đánh giá
COMMIT;
