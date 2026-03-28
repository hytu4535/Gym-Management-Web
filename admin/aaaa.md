Tôi vừa thêm bảng department và sửa lại bảng staff:
CREATE TABLE `departments` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci

CREATE TABLE `staff` (
  `id` int NOT NULL AUTO_INCREMENT,
  `users_id` int NOT NULL,
  `full_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `department_id` int DEFAULT NULL,
  `status` enum('active','inactive','on_leave') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'active',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `users_id` (`users_id`),
  KEY `department_id` (`department_id`),
  CONSTRAINT `staff_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`),
  CONSTRAINT `staff_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci


Bây giờ hãy sửa trang admin/staff.php theo yêu cầu sau:
- các cột dataTable của giao diện staff:
ID | Họ tên | Email | SĐT | Chức vụ | Phòng ban | Trạng thái | Hành động

- Form thêm/sửa:
+ Tài khoản / Email: dạng dropdown có lọc nhanh (thông báo đỏ tránh trùng: kiểm tra bảng staff nếu có tài khoản trùng ở trong đó rồi thì không cho sử dụng) (form thêm/sửa của trang admin/members.php là mẫu phần này)
+ Họ tên: KHÔNG CHO NHẬP TAY, lấy theo “tài khoản / email” được chọn. (form thêm/sửa của trang admin/members.php là mẫu phần này)
+ Số điện thoại: KHÔNG CHO NHẬP TAY, lấy theo “tài khoản / email” được chọn. (form thêm/sửa của trang admin/members.php là mẫu phần này)
+ chức vụ: dropdown, lấy từ bảng roles
+ phòng ban: dropdown, lấy từ bảng departments
+ trạng thái: dropdown, dữ liệu có sẵn “đang làm”, “đã nghỉ việc”, “tạm nghỉ”




