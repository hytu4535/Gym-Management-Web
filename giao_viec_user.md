# PHÂN CHIA CÔNG VIỆC - TRANG NGƯỜI DÙNG (CLIENT)
## Nhóm 5 người - Gym Management System
### Cấu trúc theo Module giống Admin

---

## 📁 FILES CHUNG (Layout & Trang chủ)
### Đã tạo sẵn:
- `client/index.php` - Trang chủ
- `client/layout/header.php` - Menu navigation
- `client/layout/footer.php` - Footer
- `client/assets/css/custom-shop.css` - CSS tùy chỉnh

### Files tĩnh đã có sẵn (cần động hóa):
- `client/about.php` - Giới thiệu
- `client/blog.php` - Blog
- `client/contact.php` - Liên hệ
- `client/gallery.php` - Thư viện ảnh
- `client/bmi-calculator.php` - Tính BMI

---

## MODULE 1 – Login & Tài khoản (BẢO)

### Chức năng User cần làm:
✅ Đăng ký tài khoản (có AJAX) - **0.5 điểm**
✅ Đăng nhập / Đăng xuất
✅ Hồ sơ cá nhân
✅ Đổi mật khẩu
✅ Quản lý địa chỉ giao hàng

### Files đã tạo:
```
client/
├── register.php ✅
├── login.php ✅
├── logout.php ✅
├── profile.php ✅
├── addresses.php ✅
└── ajax/
    ├── register-process.php ✅
    └── login-process.php ✅
```

### Công việc cần làm:

**1. register.php - Đăng ký tài khoản**
```php
// Form đăng ký với AJAX/Fetch
// - Họ tên, email, SĐT, username, password, ngày sinh, giới tính
// - Validate: password phải giống nhau, email hợp lệ
// - Gửi bằng Fetch API
```

**2. ajax/register-process.php**
```php
// Xử lý đăng ký
// 1. Validate dữ liệu đầu vào
// 2. Check username/email đã tồn tại chưa
// 3. Hash password: password_hash($password, PASSWORD_DEFAULT)
// 4. INSERT INTO members (username, email, password_hash, full_name, phone, birth_date, gender)
// 5. Return JSON: {success: true, message: "Đăng ký thành công!"}
```

**3. login.php - Đăng nhập**
```php
// Form đăng nhập với AJAX
// - Username/Email
// - Password
// - Remember me checkbox
// - Gửi bằng Fetch API
```

**4. ajax/login-process.php**
```php
// Xử lý đăng nhập
// 1. Query: SELECT * FROM members WHERE (username=? OR email=?) AND status='active'
// 2. Verify password: password_verify($password, $row['password_hash'])
// 3. Set session: $_SESSION['user_id'], $_SESSION['username'], $_SESSION['full_name'], $_SESSION['email']
// 4. Remember me: setcookie('remember_token', ...)
// 5. Return JSON: {success: true, redirect: 'index.php'}
```

**5. profile.php - Hồ sơ cá nhân**
```php
// Hiển thị thông tin người dùng
// - Avatar, họ tên, email, SĐT, ngày sinh, giới tính
// - Form cập nhật thông tin
// - Form đổi mật khẩu (cần nhập mật khẩu cũ)
// - Sidebar: Profile, Gói tập, Đơn hàng, Địa chỉ, Đăng xuất
```

**6. addresses.php - Quản lý địa chỉ**
```php
// Danh sách địa chỉ giao hàng
// - Hiển thị tất cả địa chỉ đã lưu
// - Đánh dấu địa chỉ mặc định
// - Nút: Thêm mới, Sửa, Xóa, Set default
// - Modal form thêm/sửa địa chỉ (AJAX)
```

**7. ajax/address-add.php**
```php
// Thêm địa chỉ mới
// 1. Validate: recipient_name, phone, address, city, district
// 2. Nếu set default: UPDATE addresses SET is_default=0 WHERE member_id=?
// 3. INSERT INTO addresses (member_id, recipient_name, phone, address, city, district, is_default)
// 4. Return JSON
```

### Bảng liên quan:
- `members` - Thông tin tài khoản
- `addresses` - Địa chỉ giao hàng

---

## MODULE 2 – Hội viên & Sức khỏe & Hạng (HUY)

### Chức năng User cần làm:
✅ Xem chỉ số BMI của bản thân
✅ Xem hạng hội viên hiện tại
✅ Xem quyền lợi theo hạng
✅ Xem gói tập đang sử dụng
✅ Danh sách lớp tập (training schedules)
✅ Trang chủ & Header/Footer

### Files đã tạo:
```
client/
├── index.php ✅ (cần update)
├── my-bmi.php ❌ (cần tạo)
├── my-tier.php ❌ (cần tạo)
├── my-packages.php ✅
└── classes.php ✅ (cần động hóa)
```

### Công việc cần làm:

**1. File CẦN TẠO: my-bmi.php**
```php
// Xem lịch sử đo BMI
// - Bảng: Ngày đo, Chiều cao, Cân nặng, BMI, Phân loại (Gầy/Bình thường/Thừa cân/Béo phì)
// - Biểu đồ: Line chart thể hiện BMI theo thời gian
// - Form thêm số đo mới (nếu có quyền)
// Query: SELECT * FROM bmi_measurements WHERE member_id=? ORDER BY measurement_date DESC
```

**2. File CẦN TẠO: my-tier.php**
```php
// Xem hạng hội viên
// - Hạng hiện tại: Bronze/Silver/Gold/Platinum
// - Điểm tích lũy hiện tại
// - Quyền lợi của hạng: 
//   + Giảm giá gói tập
//   + Ưu đãi dịch vụ
//   + Ưu đãi sản phẩm
// - Xem các ưu đãi đã sử dụng
// Query: 
// SELECT m.*, mt.tier_name, mt.discount_percentage, mt.min_spending
// FROM members m 
// JOIN member_tiers mt ON m.tier_id = mt.tier_id 
// WHERE m.member_id=?
```

**3. classes.php - Động hóa lớp tập**
```php
// Thay hardcode bằng query database
$sql = "SELECT ts.*, t.trainer_name, t.specialization
        FROM training_schedules ts
        LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
        WHERE ts.status = 'active'
        ORDER BY ts.schedule_time";
// Hiển thị: Tên lớp, Thời gian, HLV, Sức chứa, Đã đăng ký
```

**4. index.php - Trang chủ**
```php
// Đã có sẵn, cần update:
// 1. Load 4-6 sản phẩm bán chạy
// 2. Hiển thị banner ưu đãi
// 3. Hiển thị lớp tập nổi bật
// 4. Stats: Số HLV, Số lớp tập, Số thành viên
```

**5. layout/header.php**
```php
// Đã có sẵn, cần kiểm tra:
// - session_start() ở đầu file
// - Cart badge với số lượng
// - User menu: Profile, Gói tập, BMI, Hạng, Đơn hàng, Đăng xuất
// - Menu: Trang chủ | Sản phẩm | Gói tập | Lớp tập | Dịch vụ | HLV | Liên hệ
```

### Bảng liên quan:
- `members` - Thông tin hội viên
- `bmi_measurements` - Số đo BMI
- `member_tiers` - Hạng hội viên
- `member_packages` - Gói tập đã đăng ký
- `training_schedules` - Lịch tập
- `trainers` - Huấn luyện viên

---

## MODULE 3 – Dịch vụ & Dinh dưỡng & HLV (KIÊN)

### Chức năng User cần làm:
✅ Xem danh sách dịch vụ
✅ Đăng ký dịch vụ
✅ Xem kế hoạch dinh dưỡng được gán
✅ Xem danh sách HLV
✅ Xem lịch tập của mình

### Files đã tạo:
```
client/
├── services.php ✅ (cần động hóa)
├── service-register.php ❌ (cần tạo)
├── my-services.php ❌ (cần tạo)
├── my-nutrition.php ❌ (cần tạo)
├── trainers.php ✅ (cần động hóa)
├── my-schedules.php ❌ (cần tạo)
└── ajax/
    └── service-register-process.php ❌ (cần tạo)
```

### Công việc cần làm:

**1. services.php - Động hóa danh sách dịch vụ**
```php
// Thay hardcode bằng query
$sql = "SELECT * FROM services WHERE status='active' ORDER BY service_name";
// Hiển thị: Tên dịch vụ, Giá, Mô tả
// Nút: "Đăng ký dịch vụ" -> service-register.php?id=xxx
```

**2. File CẦN TẠO: service-register.php**
```php
// Form đăng ký dịch vụ
// - Hiển thị thông tin dịch vụ đã chọn
// - Chọn ngày bắt đầu
// - Chọn số buổi (nếu có)
// - Ghi chú
// - Nút "Xác nhận đăng ký"
// Submit AJAX đến service-register-process.php
```

**3. File CẦN TẠO: ajax/service-register-process.php**
```php
// Xử lý đăng ký dịch vụ
// 1. Check đăng nhập
// 2. Validate service_id, start_date
// 3. INSERT INTO member_services (member_id, service_id, start_date, status='pending', note)
// 4. Tạo notification cho admin
// 5. Return JSON

```

**4. File CẦN TẠO: my-services.php**
```php
// Xem dịch vụ đã đăng ký
// Query: SELECT ms.*, s.service_name, s.description, s.price
//        FROM member_services ms
//        JOIN services s ON ms.service_id = s.service_id
//        WHERE ms.member_id=?
// Hiển thị: Dịch vụ, Ngày đăng ký, Trạng thái, Ghi chú
```

**5. File CẦN TẠO: my-nutrition.php**
```php
// Xem kế hoạch dinh dưỡng được HLV gán
// Query: SELECT mnp.*, np.plan_name, np.description, np.calories_target
//        FROM member_nutrition_plans mnp
//        JOIN nutrition_plans np ON mnp.plan_id = np.plan_id
//        WHERE mnp.member_id=?
// - Hiển thị thực đơn hàng ngày
// - Danh sách món ăn trong kế hoạch
// Query chi tiết: SELECT npi.*, ni.item_name, ni.calories
//                  FROM nutrition_plan_items npi
//                  JOIN nutrition_items ni ON npi.item_id = ni.item_id
//                  WHERE npi.plan_id=?
```

**6. trainers.php - Động hóa HLV**
```php
// Thay hardcode
$sql = "SELECT * FROM trainers WHERE status='active' ORDER BY trainer_name";
// Hiển thị: Ảnh, Tên, Chuyên môn, Kinh nghiệm, Mô tả
```

**7. File CẦN TẠO: my-schedules.php**
```php
// Xem lịch tập của mình
// - Lịch theo tuần/tháng
// - Hiển thị: Thứ, Giờ, Lớp học, HLV, Phòng
// Query: SELECT ts.*, t.trainer_name
//        FROM training_schedules ts
//        JOIN trainers t ON ts.trainer_id = t.trainer_id
//        WHERE ts.status='active'
//        ORDER BY ts.schedule_time
```

### Bảng liên quan:
- `services` - Dịch vụ
- `member_services` - Dịch vụ đã đăng ký
- `nutrition_plans` - Kế hoạch dinh dưỡng
- `nutrition_items` - Món ăn
- `nutrition_plan_items` - Chi tiết thực đơn
- `member_nutrition_plans` - KH dinh dưỡng được gán
- `trainers` - Huấn luyện viên
- `training_schedules` - Lịch tập

---

## MODULE 4 – Bán hàng & Đơn hàng & Gói tập (Ý)

### Chức năng User cần làm:
✅ Giỏ hàng - **0.25 điểm**
✅ Mua hàng (checkout) - **0.5 điểm**
✅ Xem lịch sử đơn - **0.5 điểm**
✅ Xem gói tập
✅ Đăng ký gói tập

### Files đã tạo:
```
client/
├── products.php ✅
├── product-detail.php ✅
├── cart.php ✅
├── checkout.php ✅
├── checkout-process.php ✅
├── invoice.php ✅
├── order-history.php ✅
├── order-detail.php ✅
├── packages.php ✅ (cần động hóa)
├── package-register.php ✅
├── my-packages.php ✅
└── ajax/
    ├── cart-add.php ✅
    ├── cart-update.php ✅
    ├── cart-remove.php ✅
    └── package-register-process.php ✅
```

### Công việc cần làm:

**1. products.php - Danh sách sản phẩm** - **0.5 điểm**
```php
// Hiển thị menu chức năng theo danh mục
$sql = "SELECT * FROM products p
        JOIN categories c ON p.category_id = c.category_id
        WHERE p.status='active'
        ORDER BY p.product_name";
// Sidebar: Filter theo category, price range
// Pagination: 12 sản phẩm/trang
// AJAX load khi đổi page
```

**2. product-detail.php - Chi tiết sản phẩm** - **0.5 điểm**
```php
// Hiển thị chi tiết đầy đủ
$sql = "SELECT * FROM products WHERE product_id=?";
// - Image gallery (nhiều ảnh)
// - Tên, giá, mô tả, thông số kỹ thuật
// - Số lượng còn
// - Nút "Thêm vào giỏ hàng" (AJAX)
// - Sản phẩm liên quan
```

**3. cart.php - Giỏ hàng** - **0.25 điểm**
```php
// Query giỏ hàng
$sql = "SELECT c.*, p.product_name, p.price, p.image
        FROM carts c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.member_id=?";
// - Bảng: Ảnh, Tên, Giá, Số lượng, Thành tiền
// - Nút +/-: AJAX cập nhật số lượng
// - Nút X: AJAX xóa sản phẩm
// - Tổng tiền
// - Nút "Thanh toán"
```

**4. ajax/cart-add.php**
```php
// Thêm vào giỏ (AJAX)
// 1. Check login: if(!isset($_SESSION['user_id'])) return error
// 2. Check product exists
// 3. Check already in cart:
//    - YES: UPDATE carts SET quantity=quantity+1
//    - NO: INSERT INTO carts (member_id, product_id, quantity)
// 4. Return JSON: {success: true, cart_count: X}
```

**5. checkout.php - Thanh toán** - **0.5 điểm**
```php
// Yêu cầu đăng nhập - 0.25 điểm
// - Hiển thị thông tin người đăng nhập
// - Chọn địa chỉ có sẵn (dropdown) - 0.25 điểm
// - Hoặc nhập địa chỉ mới (checkbox toggle)
// - Hiển thị giỏ hàng
// - Chọn phương thức thanh toán - 0.25 điểm:
//   + Tiền mặt (COD)
//   + Chuyển khoản
//   + Online (Momo/VNPay)
// - Tổng tiền + phí ship
// - Nút "Đặt hàng"
```

**6. checkout-process.php**
```php
// Xử lý đặt hàng
// 1. Validate login, cart not empty
// 2. Handle address (existing or new)
// 3. Calculate total
// 4. INSERT INTO orders (member_id, address_id, total_amount, payment_method, status='pending')
// 5. Get order_id
// 6. INSERT INTO order_items (order_id, product_id, quantity, price, subtotal)
//    FROM carts WHERE member_id=?
// 7. DELETE FROM carts WHERE member_id=?
// 8. Redirect to invoice.php?order_id=xxx
```

**7. invoice.php - Hóa đơn** - **0.25 điểm**
```php
// Hiển thị và lưu hóa đơn khi kết thúc giao dịch
// Query: SELECT o.*, a.recipient_name, a.address, a.phone
//        FROM orders o
//        JOIN addresses a ON o.address_id = a.address_id
//        WHERE o.order_id=? AND o.member_id=?
// Nội dung:
// - Logo, Thông tin công ty
// - Mã đơn hàng, Ngày đặt
// - Thông tin khách hàng
// - Địa chỉ giao hàng
// - Bảng sản phẩm (từ order_items)
// - Tổng tiền, Phí ship, Thanh toán
// - Nút "In hóa đơn" (window.print())
```

**8. order-history.php - Lịch sử đơn** - **0.5 điểm**
```php
// Xem lịch sử mua hàng, xem chi tiết
$sql = "SELECT * FROM orders WHERE member_id=? ORDER BY created_at DESC";
// - Filter theo trạng thái (pending/confirmed/shipping/completed/cancelled)
// - Filter theo ngày
// - Pagination
// - Mỗi đơn: Mã, Ngày, Tổng tiền, Trạng thái, Nút "Chi tiết"/"Hóa đơn"
```

**9. order-detail.php**
```php
// Chi tiết đơn hàng
// Query: SELECT o.*, a.*, oi.*, p.product_name, p.image
//        FROM orders o
//        JOIN addresses a ON o.address_id = a.address_id
//        JOIN order_items oi ON o.order_id = oi.order_id
//        JOIN products p ON oi.product_id = p.product_id
//        WHERE o.order_id=? AND o.member_id=?
// - Timeline trạng thái
// - Thông tin đơn hàng đầy đủ
// - Bảng sản phẩm (có ảnh)
// - Nút "Hủy đơn" (nếu status='pending')
```

**10. packages.php - Động hóa gói tập**
```php
// Thay hardcode
$sql = "SELECT * FROM packages WHERE status='active' ORDER BY price";
// Hiển thị: Tên gói, Giá, Thời hạn, Quyền lợi
// Nút "Đăng ký ngay" -> package-register.php?id=xxx (đã update)
```

**11. package-register.php - Đăng ký gói tập**
```php
// Form đăng ký gói
// - Hiển thị thông tin gói (từ GET id)
// - Thông tin người đăng nhập (readonly)
// - Chọn ngày bắt đầu (date input)
// - Chọn phương thức thanh toán
// - Checkbox đồng ý điều khoản
// - Nút "Xác nhận đăng ký"
// Submit AJAX -> package-register-process.php
```

**12. ajax/package-register-process.php**
```php
// Xử lý đăng ký gói tập
// 1. Validate login, package_id, start_date
// 2. Get package info: duration, price
// 3. Calculate end_date = start_date + duration months
// 4. INSERT INTO member_packages (member_id, package_id, start_date, end_date, price, payment_method, status='pending')
// 5. Create notification for admin
// 6. Return JSON: {success: true, member_package_id: X}
```

**13. my-packages.php - Gói tập đã đăng ký**
```php
// Xem gói tập của mình
$sql = "SELECT mp.*, p.package_name, p.duration, p.price
        FROM member_packages mp
        JOIN packages p ON mp.package_id = p.package_id
        WHERE mp.member_id=?
        ORDER BY mp.created_at DESC";
// - Filter theo trạng thái (active/expired/pending)
// - Badge: Đang hoạt động/Đã hết hạn/Chờ thanh toán
// - Hiển thị: Tên gói, Ngày bắt đầu, Ngày hết hạn, Giá, Trạng thái
// - Nút "Gia hạn"/"Đăng ký lại"
```

### Bảng liên quan:
- `products` - Sản phẩm
- `categories` - Danh mục sản phẩm
- `carts` - Giỏ hàng
- `orders` - Đơn hàng
- `order_items` - Chi tiết đơn hàng
- `packages` - Gói tập
- `member_packages` - Gói tập đã đăng ký
- `addresses` - Địa chỉ giao hàng

---

## MODULE 5 – Kho & Thiết bị & Báo cáo & Ưu đãi (PHÚC)

### Chức năng User cần làm:
✅ Gửi phản hồi (feedback)
✅ Xem thông báo
✅ Xem ưu đãi của mình
✅ Tìm kiếm sản phẩm (cơ bản) - **0.5 điểm**
✅ Tìm kiếm nâng cao (có AJAX) - **0.5 điểm**
✅ Phân trang sản phẩm (có AJAX) - **0.5 điểm**

### Files đã tạo:
```
client/
├── feedback.php ❌ (cần tạo)
├── my-notifications.php ❌ (cần tạo)
├── my-promotions.php ❌ (cần tạo)
├── search.php ✅
└── ajax/
    ├── feedback-submit.php ❌ (cần tạo)
    ├── search-basic.php ✅
    └── search-advanced.php ✅
```

### Công việc cần làm:

**1. File CẦN TẠO: feedback.php**
```php
// Form gửi phản hồi
// - Chủ đề (dropdown: Dịch vụ/Sản phẩm/Thiết bị/Khác)
// - Tiêu đề
// - Nội dung (textarea)
// - Rating (1-5 sao)
// - Nút "Gửi phản hồi"
// Submit AJAX -> feedback-submit.php
```

**2. File CẦN TẠO: ajax/feedback-submit.php**
```php
// Xử lý gửi feedback
// 1. Check login
// 2. Validate: subject, title, message, rating
// 3. INSERT INTO feedback (member_id, subject, title, message, rating, status='pending', created_at)
// 4. Create notification for admin
// 5. Return JSON: {success: true, message: "Cảm ơn bạn đã gửi phản hồi!"}
```

**3. File CẦN TẠO: my-notifications.php**
```php
// Xem thông báo cá nhân
$sql = "SELECT * FROM notifications 
        WHERE member_id=? OR type='system'
        ORDER BY created_at DESC";
// - Badge: Đã đọc/Chưa đọc
// - Hiển thị: Icon, Tiêu đề, Nội dung, Thời gian
// - Nút "Đánh dấu đã đọc"
// - Phân loại: Tất cả/Chưa đọc/Hệ thống/Khuyến mãi
```

**4. File CẦN TẠO: my-promotions.php**
```php
// Xem ưu đãi theo hạng hội viên
$sql = "SELECT tp.*, mt.tier_name, tp.discount_percentage
        FROM tier_promotions tp
        JOIN member_tiers mt ON tp.tier_id = mt.tier_id
        JOIN members m ON m.tier_id = mt.tier_id
        WHERE m.member_id=? AND tp.status='active'
        AND CURDATE() BETWEEN tp.start_date AND tp.end_date";
// Hiển thị:
// - Mã ưu đãi
// - Mô tả
// - Giảm giá (%)
// - Ngày bắt đầu - kết thúc
// - Số lần đã dùng
// Query usage: SELECT * FROM promotion_usage WHERE member_id=?
```

**5. search.php - Trang tìm kiếm**
```php
// Form tìm kiếm
// 1. Tìm kiếm cơ bản - 0.5 điểm:
//    - Ô input tìm theo tên
//    - Button "Tìm kiếm"
//    - Gọi AJAX: search-basic.php
// 2. Tìm kiếm nâng cao - 0.5 điểm:
//    - Tên sản phẩm
//    - Danh mục (dropdown)
//    - Khoảng giá (từ - đến)
//    - Button "Tìm kiếm nâng cao"
//    - Gọi AJAX: search-advanced.php
// 3. Hiển thị kết quả
// 4. Phân trang (AJAX) - 0.5 điểm
```

**6. ajax/search-basic.php - Tìm kiếm cơ bản (AJAX)** - **0.5 điểm**
```php
// Tìm kiếm tương đối
$keyword = $_GET['keyword'];
$sql = "SELECT * FROM products 
        WHERE product_name LIKE ? 
        AND status='active'
        LIMIT 20";
$stmt = $conn->prepare($sql);
$search = "%$keyword%";
$stmt->bind_param("s", $search);
$stmt->execute();
// Return JSON: {success: true, data: [...], count: X}
```

**7. ajax/search-advanced.php - Tìm kiếm nâng cao (AJAX)** - **0.5 điểm**
```php
// Tìm kiếm kết hợp nhiều điều kiện
$keyword = $_GET['keyword'];
$category_id = $_GET['category_id'];
$min_price = $_GET['min_price'];
$max_price = $_GET['max_price'];
$page = $_GET['page'] ?? 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM products WHERE status='active'";
$params = [];
$types = "";

if($keyword) {
    $sql .= " AND product_name LIKE ?";
    $params[] = "%$keyword%";
    $types .= "s";
}
if($category_id) {
    $sql .= " AND category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}
if($min_price && $max_price) {
    $sql .= " AND price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd";
}

$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Pagination với AJAX - 0.5 điểm
// Return JSON: {success: true, data: [...], page: X, total_pages: Y}
```

### Bảng liên quan:
- `feedback` - Phản hồi
- `notifications` - Thông báo
- `tier_promotions` - Ưu đãi theo hạng
- `promotion_usage` - Lịch sử sử dụng ưu đãi
- `products` - Sản phẩm (cho tìm kiếm)
- `categories` - Danh mục (cho tìm kiếm)

---

## ✅ TỔNG KẾT YÊU CẦU ĐỒ ÁN


## ✅ TỔNG KẾT YÊU CẦU ĐỒ ÁN

| STT | Yêu cầu | Điểm | Module | Người làm | Trạng thái |
|-----|---------|------|--------|-----------|------------|
| 1 | Hiển thị menu sản phẩm theo danh mục | 0.5 | Module 4 | Ý | ✅ File có |
| 2 | Hiển thị chi tiết sản phẩm | 0.5 | Module 4 | Ý | ✅ File có |
| 3 | Tìm kiếm cơ bản (có AJAX) | 0.5 | Module 5 | Phúc | ✅ File có |
| 4 | Tìm kiếm nâng cao (có AJAX) | 0.5 | Module 5 | Phúc | ✅ File có |
| 5 | Phân trang sản phẩm (có AJAX) | 0.5 | Module 5 | Phúc | ✅ File có |
| 6 | Đăng ký tài khoản (có AJAX) | 0.5 | Module 1 | Bảo | ✅ File có |
| 7 | Đăng nhập + hiển thị thông tin | 0.25 | Module 1 | Bảo | ✅ File có |
| 8 | Giỏ hàng + chọn địa chỉ | 0.25 | Module 4 | Ý | ✅ File có |
| 9 | Chọn phương thức thanh toán | 0.25 | Module 4 | Ý | ✅ File có |
| 10 | Hiển thị & lưu hóa đơn | 0.25 | Module 4 | Ý | ✅ File có |
| 11 | Xem lịch sử đơn hàng | 0.5 | Module 4 | Ý | ✅ File có |
| **TỔNG** | | **4.5đ** | | | **✅ Đủ yêu cầu** |

---

## 📊 PHÂN CHIA CÔNG VIỆC THEO MODULE

| Module | Người làm | Files cần tạo mới | Files cần sửa | Tổng |
|--------|-----------|-------------------|---------------|------|
| Module 1 - Login & Account | Bảo | 0 | 7 | 7 |
| Module 2 - Members & Health | Huy | 2 (my-bmi, my-tier) | 3 | 5 |
| Module 3 - Services & Nutrition | Kiên | 4 (service-register, my-services, my-nutrition, my-schedules) | 2 | 6 |
| Module 4 - Sales & Orders | Ý | 0 | 13 | 13 |
| Module 5 - Warehouse & Reports | Phúc | 4 (feedback, my-notifications, my-promotions + ajax) | 3 | 7 |
| **TỔNG** | | **10 files mới** | **28 files sửa** | **38 files** |

---

## 📋 CHECKLIST CÔNG VIỆC

### Module 1 - BẢO (Login & Account)
- [x] register.php - Form đăng ký AJAX
- [x] ajax/register-process.php - Xử lý đăng ký
- [x] login.php - Form đăng nhập AJAX
- [x] ajax/login-process.php - Xử lý đăng nhập
- [x] logout.php - Đăng xuất
- [x] profile.php - Hồ sơ cá nhân, đổi mật khẩu
- [x] addresses.php - Quản lý địa chỉ
- [x] ajax/address-add.php - Thêm địa chỉ AJAX
- [ ] **Kết nối database cho tất cả files**
- [ ] **Test đăng ký -> đăng nhập -> profile**

### Module 2 - HUY (Members & Health)
- [x] index.php - Trang chủ
- [x] layout/header.php - Menu + cart badge
- [x] layout/footer.php
- [x] classes.php - Lớp tập (động hóa)
- [x] my-packages.php - Gói tập đã đăng ký
- [ ] **my-bmi.php** - Xem BMI (TẠO MỚI)
- [ ] **my-tier.php** - Xem hạng hội viên (TẠO MỚI)
- [ ] **Kết nối database cho tất cả files**
- [ ] **Test hiển thị BMI, Tier, Packages**

### Module 3 - KIÊN (Services & Nutrition)
- [x] services.php - Danh sách dịch vụ (động hóa)
- [x] trainers.php - HLV (động hóa)
- [ ] **service-register.php** - Đăng ký dịch vụ (TẠO MỚI)
- [ ] **ajax/service-register-process.php** (TẠO MỚI)
- [ ] **my-services.php** - Dịch vụ đã đăng ký (TẠO MỚI)
- [ ] **my-nutrition.php** - Kế hoạch dinh dưỡng (TẠO MỚI)
- [ ] **my-schedules.php** - Lịch tập (TẠO MỚI)
- [ ] **Kết nối database cho tất cả files**
- [ ] **Test đăng ký dịch vụ, xem dinh dưỡng**

### Module 4 - Ý (Sales & Orders) - **ĐIỂM NHIỀU NHẤT**
- [x] products.php - Danh sách sản phẩm (0.5đ)
- [x] product-detail.php - Chi tiết sản phẩm (0.5đ)
- [x] cart.php - Giỏ hàng (0.25đ)
- [x] ajax/cart-add.php, cart-update.php, cart-remove.php
- [x] checkout.php - Thanh toán (0.5đ)
- [x] checkout-process.php - Xử lý đặt hàng
- [x] invoice.php - Hóa đơn (0.25đ)
- [x] order-history.php - Lịch sử đơn (0.5đ)
- [x] order-detail.php - Chi tiết đơn
- [x] packages.php - Gói tập (động hóa)
- [x] package-register.php - Đăng ký gói
- [x] ajax/package-register-process.php
- [x] my-packages.php - Gói đã đăng ký
- [ ] **Kết nối database cho TẤT CẢ 13 files**
- [ ] **Test: Browse -> Add to cart -> Checkout -> Invoice**
- [ ] **Test: Đăng ký gói tập -> Xem my-packages**

### Module 5 - PHÚC (Search & Feedback)
- [x] search.php - Trang tìm kiếm
- [x] ajax/search-basic.php - Tìm cơ bản (0.5đ)
- [x] ajax/search-advanced.php - Tìm nâng cao + Phân trang (1.0đ)
- [ ] **feedback.php** - Form phản hồi (TẠO MỚI)
- [ ] **ajax/feedback-submit.php** (TẠO MỚI)
- [ ] **my-notifications.php** - Thông báo (TẠO MỚI)
- [ ] **my-promotions.php** - Ưu đãi (TẠO MỚI)
- [ ] **Kết nối database cho tất cả files**
- [ ] **Test tìm kiếm cơ bản, nâng cao, phân trang**

---

## 🔧 LƯU Ý KỸ THUẬT

### 1. Session Management (Bảo)
```php
// Đầu mỗi file cần login
<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}
?>
```

### 2. Database Connection (Tất cả)
```php
// File: config/db.php (sử dụng file này)
<?php
$host = 'localhost';
$dbname = 'gym_management';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch(Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

// Sử dụng:
require_once '../config/db.php';  // hoặc ../../config/db.php
```

### 3. Prepared Statements (Tất cả)
```php
// ĐÚNG: Dùng prepared statement (chống SQL injection)
$sql = "SELECT * FROM products WHERE category_id = ? AND price < ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("id", $category_id, $max_price);
$stmt->execute();
$result = $stmt->get_result();

// SAI: Không dùng trực tiếp
$sql = "SELECT * FROM products WHERE category_id = $category_id"; // ⚠️ Nguy hiểm!
```

### 4. AJAX Response Format (Tất cả)
```php
// Luôn return JSON
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Thành công!',
    'data' => $data
]);
exit();
```

### 5. Password Security (Bảo)
```php
// Đăng ký: Hash password
$password_hash = password_hash($password, PASSWORD_DEFAULT);

// Đăng nhập: Verify password
if(password_verify($input_password, $stored_hash)) {
    // Đúng
} else {
    // Sai
}
```

### 6. Input Validation (Tất cả)
```php
// Validate email
if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return error("Email không hợp lệ!");
}

// Validate số điện thoại (10-11 số)
if(!preg_match("/^[0-9]{10,11}$/", $phone)) {
    return error("Số điện thoại không hợp lệ!");
}

// Sanitize input
$name = htmlspecialchars(trim($_POST['name']));
```

### 7. Pagination (Phúc, Ý)
```php
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 12; // Sản phẩm mỗi trang
$offset = ($page - 1) * $limit;

$sql = "SELECT * FROM products LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $limit, $offset);

// Tính tổng số trang
$total_sql = "SELECT COUNT(*) as total FROM products";
$total_result = $conn->query($total_sql);
$total_row = $total_result->fetch_assoc();
$total_pages = ceil($total_row['total'] / $limit);
```

---

## 📂 CẤU TRÚC THƯ MỤC ĐẦY ĐỦ

```
client/
├── index.php ✅
├── about.php ✅
├── blog.php ✅
├── contact.php ✅
├── gallery.php ✅
├── bmi-calculator.php ✅
│
├── MODULE 1 - Login & Account (Bảo)
│   ├── register.php ✅
│   ├── login.php ✅
│   ├── logout.php ✅
│   ├── profile.php ✅
│   └── addresses.php ✅
│
├── MODULE 2 - Members & Health (Huy)
│   ├── classes.php ✅
│   ├── my-bmi.php ❌ (TẠO MỚI)
│   ├── my-tier.php ❌ (TẠO MỚI)
│   └── my-packages.php ✅
│
├── MODULE 3 - Services & Nutrition (Kiên)
│   ├── services.php ✅
│   ├── service-register.php ❌ (TẠO MỚI)
│   ├── my-services.php ❌ (TẠO MỚI)
│   ├── my-nutrition.php ❌ (TẠO MỚI)
│   ├── my-schedules.php ❌ (TẠO MỚI)
│   └── trainers.php ✅
│
├── MODULE 4 - Sales & Orders (Ý)
│   ├── products.php ✅
│   ├── product-detail.php ✅
│   ├── cart.php ✅
│   ├── checkout.php ✅
│   ├── checkout-process.php ✅
│   ├── invoice.php ✅
│   ├── order-history.php ✅
│   ├── order-detail.php ✅
│   ├── packages.php ✅
│   ├── package-register.php ✅
│   └── search.php ✅ (cũng thuộc Module 5)
│
├── MODULE 5 - Search & Feedback (Phúc)
│   ├── feedback.php ❌ (TẠO MỚI)
│   ├── my-notifications.php ❌ (TẠO MỚI)
│   └── my-promotions.php ❌ (TẠO MỚI)
│
├── layout/
│   ├── header.php ✅
│   └── footer.php ✅
│
├── assets/
│   └── css/
│       └── custom-shop.css ✅
│
└── ajax/
    ├── MODULE 1 (Bảo)
    │   ├── register-process.php ✅
    │   ├── login-process.php ✅
    │   └── address-add.php ✅
    │
    ├── MODULE 3 (Kiên)
    │   └── service-register-process.php ❌ (TẠO MỚI)
    │
    ├── MODULE 4 (Ý)
    │   ├── cart-add.php ✅
    │   ├── cart-update.php ✅
    │   ├── cart-remove.php ✅
    │   └── package-register-process.php ✅
    │
    └── MODULE 5 (Phúc)
        ├── search-basic.php ✅
        ├── search-advanced.php ✅
        └── feedback-submit.php ❌ (TẠO MỚI)
```

---

## 🎯 TIẾN TRÌNH THỰC HIỆN (Theo thứ tự)

### Tuần 1: Module 1 + Module 2 (Nền tảng)
1. **Bảo**: Hoàn thiện Login/Register/Profile
2. **Huy**: Hoàn thiện Index, Header, Footer, tạo my-bmi.php, my-tier.php
3. **Test**: Đăng ký -> Đăng nhập -> Xem profile

### Tuần 2: Module 4 (Bán hàng - QUAN TRỌNG)
1. **Ý**: Products -> Product Detail -> Cart -> Checkout
2. **Ý**: Order History -> Invoice
3. **Ý**: Packages -> Package Register -> My Packages
4. **Test**: Browse -> Add to Cart -> Checkout -> Invoice (Flow đầy đủ)

### Tuần 3: Module 3 + Module 5
1. **Kiên**: Services -> Service Register -> My Services
2. **Kiên**: My Nutrition -> My Schedules -> Trainers
3. **Phúc**: Search (basic + advanced + pagination)
4. **Phúc**: Feedback -> Notifications -> Promotions
5. **Test**: Tất cả chức năng còn lại

### Tuần 4: Testing & Fix bugs
1. **Tất cả**: Test lại toàn bộ chức năng
2. **Fix bugs** đã phát hiện
3. **Optimize**: Cải thiện UX/UI, tốc độ load
4. **Document**: Viết hướng dẫn sử dụng

---

## 🚀 KẾT LUẬN

### Tổng quan:
- **Tổng cộng**: 38 files (28 đã có + 10 cần tạo mới)
- **Điểm**: 4.5/4.5 (đủ yêu cầu đồ án)
- **Chia theo module** giống Admin (dễ quản lý)
- **Phân công rõ ràng** theo từng người

### Files cần tạo mới (10 files):
1. my-bmi.php (Huy)
2. my-tier.php (Huy)
3. service-register.php (Kiên)
4. my-services.php (Kiên)
5. my-nutrition.php (Kiên)
6. my-schedules.php (Kiên)
7. ajax/service-register-process.php (Kiên)
8. feedback.php (Phúc)
9. my-notifications.php (Phúc)
10. my-promotions.php (Phúc)
11. ajax/feedback-submit.php (Phúc)

### Công việc chính:
- **Kết nối database** cho tất cả files
- **Viết SQL queries** (SELECT, INSERT, UPDATE, DELETE)
- **Implement AJAX** responses
- **Validate** input
- **Test** từng module

### Lưu ý:
- Làm theo đúng module, không nhảy lung tung
- Test kỹ trước khi chuyển sang module khác
- Commit code thường xuyên (Git)
- Hỏi nhau khi gặp vấn đề

**→ Bắt đầu từ Module 1 (Login) vì tất cả chức năng khác đều cần đăng nhập!** 🔐
