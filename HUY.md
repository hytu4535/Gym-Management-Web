# 2.4 THIẾT KẾ XỬ LÝ VÀ CẤU TRÚC SOURCE

## 2.4.1 Kiến trúc thư mục và quy ước code

### a) Cấu trúc thư mục chính

Hệ thống được tổ chức theo mô hình tách biệt giữa khu vực quản trị và khu vực khách hàng, nhằm đảm bảo tính bảo mật, khả năng bảo trì và mở rộng. Cơ cấu thư mục chính bao gồm:

Thư mục `admin/` đóng vai trò là khu vực quản trị hệ thống, chứa các trang điều hành để quản lý toàn bộ nghiệp vụ bao gồm quản lý người dùng, vai trò và quyền hạn, thông tin nhân viên, thông tin hội viên, gói tập và hạng hội viên, huấn luyện viên, lớp học, lịch tập, dịch vụ, dinh dưỡng, sản phẩm, danh mục, đơn hàng, giỏ hàng, nhà cung cấp, phiếu nhập hàng, thiết bị, phản hồi, thông báo và báo cáo. Trong thư mục này, thư con `admin/layout/` chứa các thành phần giao diện dùng chung như header, sidebar và footer, còn `admin/process/` tập hợp các file xử lý form (thêm, chỉnh sửa, xóa) được tách riêng khỏi lớp trình bày.

Thư mục `client/` đại diện cho khu vực người dùng cuối/hội viên, cung cấp các chức năng như đăng ký/đăng nhập, quản lý hồ sơ cá nhân, quản lý giỏ hàng, thanh toán, mua gói tập, đặt lịch tập với huấn luyện viên, xem lịch sử đơn hàng, công cụ tính toán chỉ số BMI, thư viện ảnh, bài viết tin tức và liên hệ. Tương tự như admin, thư mục `client/layout/` chứa các phần tử giao diện chung, trong khi `client/ajax/` tập hợp các endpoint để xử lý các yêu cầu bất đồng bộ.

Thư mục `includes/` lưu trữ các file được sử dụng chung trong toàn hệ thống, bao gồm `auth.php` và `auth_permission.php` cho xác thực và phân quyền, `database.php` và `functions.php` để kết nối cơ sở dữ liệu và cung cấp các hàm tiện ích, cũng như `discount_helper.php` chứa logic xử lý khuyến mãi và giảm giá.

Thư mục `config/` chứa cấu hình kết nối cơ sở dữ liệu trong file `config/db.php`. Thư mục `database/` lưu trữ file SQL schema (`gym_management.sql`) và các bản sao lưu. Thư mục `assets/` chứa các file có liên quan đến giao diện, bao gồm AdminLTE, các plugin, tệp CSS/JavaScript và thư mục upload cho tài nguyên được tải lên. Cuối cùng, file `index.php` ở gốc dự án đóng vai trò là landing page điều hướng người dùng đến khu vực quản trị hoặc khu vực khách hàng.

### b) Quy ước đặt tên và tổ chức code

Để đảm bảo tính nhất quán và dễ bảo trì, hệ thống tuân theo các quy ước lập trình thống nhất. Các file được đặt tên theo chức năng nghiệp vụ mà chúng hỗ trợ, sử dụng định dạng kebab-case như `member-packages.php` hoặc `order-items.php`. File giao diện (presentation) và file xử lý logic (processing) được phân tách rõ ràng: các file giao diện nằm trực tiếp trong `admin/` hoặc `client/`, trong khi các file xử lý form được tổ chức trong thư mục `admin/process/` hoặc tương ứng.

Các chức năng dùng chung được tập hợp trong `includes/functions.php` để tránh trùng lặp mã và tạo điều kiện cho tái sử dụng. Hàm session khởi tạo (`session_start()`) được gọi ở đầu mỗi trang yêu cầu xác thực. Bên cạnh đó, hệ thống sử dụng PDO (PHP Data Objects) cho việc kết nối cơ sở dữ liệu, được quản lý thông qua hàm `getDB()` trong `includes/database.php`.

### c) Thư viện và mã nguồn mở sử dụng

Dự án tận dụng nhiều thư viện mã nguồn mở và framework phổ biến để tăng tốc độ phát triển. AdminLTE được sử dụng cho giao diện quản trị, cung cấp các thành phần UI được thiết kế sẵn. Bootstrap cung cấp hệ thống lưới đáp ứng và thành phần CSS, jQuery hỗ trợ xác định và thao tác DOM, còn FontAwesome cung cấp thư viện icon đa dạng. Ngoài ra, thư mục `assets/plugins` chứa các plugin front-end bổ sung để mở rộng chức năng giao diện người dùng.

## 2.4.2 Thiết kế module và luồng xử lý

### 1) Auth và phân quyền

- Admin đăng nhập tại `admin/login.php`.
- Client đăng nhập/đăng ký tại `client/login.php`, `client/register.php`.
- Phân quyền thao tác quản trị thông qua `includes/auth_permission.php` và mã quyền (ví dụ: `MANAGE_SALES`).

### 2) Module hội viên

- Quản lý thông tin hội viên, địa chỉ, hạng hội viên, gói tập đã đăng ký.
- Theo dõi BMI qua `bmi_devices`, `bmi_measurements`.
- Có cơ chế nâng hạng tự động dựa trên tổng chi tiêu (`members.total_spent`).

### 3) Module huấn luyện và lớp học

- Quản lý PT (`trainers`), lớp (`classes`), lịch tập (`training_schedules`).
- Đăng ký lớp/lịch từ khu vực client.

### 4) Module dịch vụ và dinh dưỡng

- Quản lý dịch vụ (`services`) và gán cho hội viên (`member_services`).
- Quản lý thực đơn (`nutrition_plans`, `nutrition_items`, `nutrition_plan_items`).
- Gán thực đơn cho hội viên (`member_nutrition_plans`).

### 5) Module bán hàng

- Danh mục + sản phẩm (`categories`, `products`).
- Giỏ hàng (`carts`, `cart_items`) hỗ trợ nhiều loại item: `product`, `package`, `service`.
- Đặt hàng (`orders`, `order_items`) có cập nhật tồn kho và cập nhật tổng chi tiêu hội viên.
- Hỗ trợ khuyến mãi qua `promotion_usage` và helper giảm giá.

### 6) Module kho và nhà cung cấp

- Quản lý nhà cung cấp (`suppliers`).
- Tạo và duyệt phiếu nhập (`import_slips`, `import_details`).
- Trạng thái phiếu nhập: Đã nhập / Đang chờ duyệt / Đã hủy.

### 7) Module thiết bị và bảo trì

- Quản lý danh mục thiết bị gym (`equipment`).
- Theo dõi lịch bảo trì (`equipment_maintenance`).

### 8) Module phản hồi, thông báo, báo cáo

- Lưu phản hồi (`feedback`) và thông báo (`notifications`).
- Báo cáo top khách hàng mua nhiều theo khoảng thời gian (`reports.php`).

## 2.4.3 Các truy vấn SQL tiêu biểu (10 truy vấn)

### Query 1 - Danh sách sản phẩm kèm tên danh mục

```sql
SELECT p.id, p.name, p.img, p.unit, p.stock_quantity, p.selling_price, p.status,
       c.name AS category_name
FROM products p
LEFT JOIN categories c ON p.category_id = c.id
ORDER BY p.id DESC;
```

- Mục đích: hiển thị danh sách sản phẩm trang admin.
- Input: không.
- Output: thông tin sản phẩm + tên danh mục.

### Query 2 - Lọc đơn hàng theo nhiều điều kiện

```sql
SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method,
       m.full_name, a.city, a.district
FROM orders o
LEFT JOIN members m ON o.member_id = m.id
LEFT JOIN addresses a ON o.address_id = a.id
WHERE 1 = 1
  AND (? IS NULL OR o.status = ?)
  AND (? IS NULL OR DATE(o.order_date) >= ?)
  AND (? IS NULL OR DATE(o.order_date) <= ?)
  AND (? IS NULL OR a.city = ?)
  AND (? IS NULL OR a.district = ?)
ORDER BY o.id DESC;
```

- Mục đích: lọc đơn hàng theo trạng thái, ngày, địa điểm giao.
- Input: status, from_date, to_date, city, district.
- Output: danh sách đơn hàng đã lọc.

### Query 3 - Lấy tổng số lượng item trong giỏ theo user đăng nhập

```sql
SELECT COALESCE(SUM(ci.quantity), 0) AS total_items
FROM members m
LEFT JOIN carts c ON c.member_id = m.id AND c.status = 'active'
LEFT JOIN cart_items ci ON ci.cart_id = c.id
WHERE m.users_id = ?;
```

- Mục đích: hiện badge giỏ hàng trên header client.
- Input: users_id.
- Output: tổng quantity item trong giỏ.

### Query 4 - Khóa giỏ hàng để checkout an toàn (FOR UPDATE)

```sql
SELECT ci.item_type, ci.item_id, ci.quantity,
       p.selling_price, p.stock_quantity,
       mp.price AS package_price, mp.duration_months,
       s.price AS service_price, c.id AS cart_id
FROM carts c
JOIN cart_items ci ON c.id = ci.cart_id
LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
WHERE c.member_id = ? AND c.status = 'active'
FOR UPDATE;
```

- Mục đích: đọc dữ liệu giỏ và khóa dòng trong transaction checkout.
- Input: member_id.
- Output: tập item để tạo đơn và trừ tồn kho.

### Query 5 - Tạo đơn hàng

```sql
INSERT INTO orders (member_id, address_id, total_amount, payment_method, status)
VALUES (?, NULLIF(?, 0), ?, ?, 'pending');
```

- Mục đích: tạo bản ghi đơn hàng mới.
- Input: member_id, address_id, total_amount, payment_method.
- Output: order_id mới (insert_id).

### Query 6 - Thêm chi tiết đơn hàng

```sql
INSERT INTO order_items (order_id, item_type, item_id, item_name, price, quantity)
VALUES (?, ?, ?, ?, ?, ?);
```

- Mục đích: lưu từng dòng sản phẩm/dịch vụ/gói tập trong đơn.
- Input: order_id, item info, giá, số lượng.
- Output: các dòng order_items.

### Query 7 - Trừ tồn kho sau khi đặt hàng thành công

```sql
UPDATE products
SET stock_quantity = stock_quantity - ?
WHERE id = ?;
```

- Mục đích: cập nhật số lượng tồn kho sản phẩm vật lý.
- Input: số lượng mua, product_id.
- Output: tồn kho mới.

### Query 8 - Cập nhật tổng chi tiêu và nâng hạng hội viên

```sql
UPDATE members
SET total_spent = total_spent + ?
WHERE id = ?;

SELECT id, name, level
FROM member_tiers
WHERE min_spent <= ? AND status = 'active'
ORDER BY level DESC
LIMIT 1;
```

- Mục đích: cập nhật tổng chi và tìm hạng phù hợp để nâng hạng.
- Input: total_amount, member_id, total_spent_mới.
- Output: thông tin hạng mới nếu đủ điều kiện.

### Query 9 - Tạo và liệt kê phiếu nhập kho

```sql
INSERT INTO import_slips (staff_id, supplier_id, total_amount, import_date, note, status)
VALUES (?, ?, ?, ?, ?, ?);

SELECT i.id, i.total_amount, i.import_date, i.status,
       s.name AS supplier_name,
       st.full_name AS staff_name
FROM import_slips i
JOIN suppliers s ON i.supplier_id = s.id
JOIN staff st ON i.staff_id = st.id
ORDER BY i.id DESC;
```

- Mục đích: tạo phiếu nhập và hiển thị danh sách phiếu nhập.
- Input: thông tin nhà cung cấp, nhân viên, tổng tiền, ngày nhập.
- Output: danh sách phiếu nhập có thông tin liên kết.

### Query 10 - Báo cáo top 5 khách hàng mua nhiều

```sql
SELECT o.member_id,
       COALESCE(m.full_name, CONCAT('Khách #', o.member_id)) AS customer_name,
       COUNT(o.id) AS order_count,
       SUM(o.total_amount) AS total_purchase
FROM orders o
LEFT JOIN members m ON m.id = o.member_id
WHERE DATE(o.order_date) BETWEEN ? AND ?
  AND o.status <> 'cancelled'
GROUP BY o.member_id, m.full_name
ORDER BY total_purchase DESC
LIMIT 5;
```

- Mục đích: thống kê top khách hàng theo doanh thu.
- Input: từ ngày, đến ngày.
- Output: top 5 khách + số đơn + tổng mua.

## 2.4.4 Đánh giá cấu trúc hiện tại và đề xuất bổ sung

### Điểm mạnh

- Bao phủ nhiều nghiệp vụ thực tế của phòng gym.
- Tách biệt khu vực admin/client rõ ràng.
- Có transaction cho checkout, có check tồn kho, có logic nâng hạng.
- Có RBAC cơ bản ở khu vực admin.

### Điểm cần hoàn thiện

- Chưa thống nhất tầng truy cập dữ liệu (mysqli và PDO song song).
- Một số trang còn xây dựng SQL dynamic bằng nối chuỗi (dù đã escape), nên ưu tiên prepared statement toàn bộ.
- Chưa có tài liệu API/nội quy code chuẩn hóa cho team.
- Chưa có bộ test tự động (unit/integration).

### Đề xuất bổ sung để đồ án đầy đủ hơn

- Chuẩn hóa 1 kiểu DB layer (ưu tiên PDO + prepared statements).
- Thêm sơ đồ ERD và sequence diagram cho luồng checkout, đăng ký gói tập, đặt lịch PT.
- Viết bộ migration/schema versioning.
- Bổ sung test:
  - Test logic giảm giá và tính tổng đơn.
  - Test transaction checkout (rollback khi lỗi tồn kho).
  - Test phân quyền cho các route admin.
- Bổ sung log audit cho các thao tác nhạy cảm: sửa giá, hủy đơn, duyệt phiếu nhập.

## 2.4.5 Hướng dẫn chạy nhanh

1. Import CSDL từ `database/gym_management.sql` vào MySQL.
2. Cấu hình kết nối trong `config/db.php` và `includes/config.php`.
3. Đặt project trong `htdocs` (XAMPP), truy cập:
   - Trang tổng: `http://localhost/DoAn/`
   - Admin: `http://localhost/DoAn/admin/`
   - Client: `http://localhost/DoAn/client/`
4. Nếu cần, chạy PHP built-in server cho debug:
   - `php -S localhost:8000`

---

Nội dung trên có thể đưa trực tiếp vào báo cáo đồ án mục Thiết kế xử lý và cấu trúc source, và đã cập nhật theo đúng hiện trạng source code của nhóm.
