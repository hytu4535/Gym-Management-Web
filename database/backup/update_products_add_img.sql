-- ===================================================
-- Script cập nhật cấu trúc bảng products
-- Thêm cột img để lưu hình ảnh sản phẩm
-- ===================================================

-- Thêm cột img vào bảng products
ALTER TABLE products 
ADD COLUMN img VARCHAR(255) NULL COMMENT 'Đường dẫn hình ảnh sản phẩm' 
AFTER name;

-- Cập nhật giá trị mặc định cho các sản phẩm cũ
UPDATE products 
SET img = 'default-product.jpg' 
WHERE img IS NULL OR img = '';

-- Kiểm tra kết quả
SELECT id, name, img, selling_price FROM products;
