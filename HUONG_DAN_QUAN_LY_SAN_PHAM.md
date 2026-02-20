# 📋 HƯỚNG DẪN CÀI ĐẶT TÍNH NĂNG QUẢN LÝ SẢN PHẨM CÓ HÌNH ẢNH

## ✅ Các tính năng đã hoàn thành:

### 1️⃣ Thêm sản phẩm (0.5 điểm)
- ✅ **Phân loại đúng**: Có dropdown chọn danh mục (category)
- ✅ **Upload hình**: Cho phép chọn file ảnh (JPG, PNG, GIF - max 2MB)
- ✅ **Preview trước khi thêm**: Hiển thị hình ảnh ngay khi chọn file

### 2️⃣ Sửa sản phẩm (0.5 điểm)
- ✅ **Hiển thị đúng thông tin**: Tất cả thông tin hiện tại được load đầy đủ
- ✅ **Hiển thị phân loại**: Dropdown category hiển thị đúng danh mục hiện tại
- ✅ **Hiển thị hình**: Hình ảnh hiện tại được hiển thị rõ ràng
- ✅ **Thay đổi hình**: Cho phép upload hình mới, preview ngay lập tức

### 3️⃣ Xóa sản phẩm (0.75 điểm)
- ✅ **Kiểm tra đã bán**: Tự động check trong bảng `order_items`
- ✅ **Đã bán → Ẩn**: Nếu sản phẩm đã được bán → chỉ set `status='inactive'` (soft delete)
- ✅ **Chưa bán → Xóa**: Nếu chưa bán → confirm và xóa hẳn khỏi database (hard delete)
- ✅ **Xóa file hình**: Tự động xóa file ảnh khi xóa sản phẩm hoàn toàn

---

## 🔧 CÀI ĐẶT:

### Bước 1: Cập nhật Database

Chạy script SQL sau trong phpMyAdmin hoặc MySQL Workbench:

```sql
-- Thêm cột img vào bảng products
ALTER TABLE products 
ADD COLUMN img VARCHAR(255) NULL COMMENT 'Đường dẫn hình ảnh sản phẩm' 
AFTER name;

-- Cập nhật giá trị mặc định cho sản phẩm cũ
UPDATE products 
SET img = 'default-product.jpg' 
WHERE img IS NULL OR img = '';
```

**Hoặc** chạy file: `database/backup/update_products_add_img.sql`

### Bước 2: Tạo hình ảnh mặc định

1. Tìm hoặc tạo một hình ảnh placeholder (khuyến nghị: 400x400px)
2. Đặt tên là `default-product.jpg`
3. Upload vào: `assets/uploads/products/default-product.jpg`

**Gợi ý**: Tìm "product placeholder image" trên Google Images

### Bước 3: Import lại database hoàn chỉnh (Tùy chọn)

Nếu muốn import toàn bộ database mới:

```bash
mysql -u root -p gym_management < database/gym_management.sql
```

---

## 🧪 KIỂM TRA TÍNH NĂNG:

### ✅ Test Thêm Sản Phẩm:
1. Vào: `admin/products.php`
2. Click "Thêm Sản Phẩm"
3. Chọn danh mục từ dropdown
4. Click "Chọn file" → chọn ảnh
5. **→ Ảnh hiện ngay lập tức** (preview)
6. Điền thông tin và lưu
7. **→ Sản phẩm mới có hình ảnh trong danh sách**

### ✅ Test Sửa Sản Phẩm:
1. Click nút "Sửa" (icon bút)
2. **→ Hình ảnh hiện tại hiển thị**
3. **→ Danh mục đã chọn đúng**
4. Thay đổi hình: chọn file mới
5. **→ Hình preview thay đổi ngay**
6. Click "Cập nhật"
7. **→ Hình mới được lưu, hình cũ bị xóa**

### ✅ Test Xóa Sản Phẩm:

**Case 1: Sản phẩm ĐÃ BÁN**
1. Tạo đơn hàng chứa sản phẩm A
2. Thử xóa sản phẩm A
3. **→ Thông báo: "Sản phẩm đã được bán... chuyển sang Inactive"**
4. **→ Sản phẩm vẫn còn, chỉ ẩn đi**

**Case 2: Sản phẩm CHƯA BÁN**
1. Tạo sản phẩm mới B (không bán)
2. Thử xóa sản phẩm B
3. Click OK khi confirm
4. **→ Thông báo: "Đã xóa sản phẩm HOÀN TOÀN"**
5. **→ Sản phẩm bị xóa khỏi database**
6. **→ File hình ảnh cũng bị xóa**

---

## 📁 CẤU TRÚC FILE ĐÃ THAY ĐỔI:

```
DoAn/
├── database/
│   ├── gym_management.sql                    [CẬP NHẬT - Thêm cột img]
│   └── backup/
│       └── update_products_add_img.sql       [MỚI - Script ALTER TABLE]
│
├── admin/
│   ├── products.php                          [CẬP NHẬT - Thêm upload + preview]
│   ├── product_edit.php                      [CẬP NHẬT - Hiển thị hình + edit]
│   └── process/
│       ├── product_add.php                   [CẬP NHẬT - Xử lý upload]
│       ├── product_edit_process.php          [CẬP NHẬT - Update hình]
│       └── product_delete.php                [CẬP NHẬT - Smart delete]
│
└── assets/uploads/products/
    ├── README.md                             [MỚI - Hướng dẫn]
    └── default-product.jpg                   [CẦN TẠO - Hình mặc định]
```

---

## 🎯 ĐIỂM CỘNG KHI DEMO:

### Thêm sản phẩm (0.5đ):
- ✅ Chọn đúng danh mục từ dropdown
- ✅ Upload hình thành công
- ✅ **HIGHLIGHT**: Preview hình ngay khi chọn file

### Sửa sản phẩm (0.5đ):
- ✅ Form load đúng tất cả thông tin
- ✅ **HIGHLIGHT**: Dropdown category đúng giá trị
- ✅ **HIGHLIGHT**: Hình hiện tại hiển thị rõ ràng
- ✅ Thay đổi hình và preview mới

### Xóa sản phẩm (0.75đ):
- ✅ **HIGHLIGHT**: Check trong order_items tự động
- ✅ **HIGHLIGHT**: Đã bán → chỉ ẩn (thông báo rõ ràng)
- ✅ **HIGHLIGHT**: Chưa bán → xóa hẳn (confirm trước)
- ✅ Xóa file hình ảnh khi xóa hoàn toàn

---

## ⚠️ LƯU Ý:

1. **Phải tạo thư mục**: `assets/uploads/products/` (đã tự động tạo khi upload)
2. **Phải có file**: `default-product.jpg` trong thư mục trên
3. **Quyền thư mục**: chmod 777 cho `assets/uploads/` (trên Linux/Mac)
4. **Kiểm tra**: 
   - `upload_max_filesize = 2M` trong `php.ini`
   - `post_max_size = 8M` trong `php.ini`

---

## 📞 HỖ TRỢ:

Nếu gặp lỗi:
- **Không upload được**: Check quyền thư mục và php.ini
- **Hình không hiển thị**: Check đường dẫn `../assets/uploads/products/`
- **Xóa không được**: Check foreign key constraints

---

**Chúc bạn làm đồ án tốt! 🎉**
