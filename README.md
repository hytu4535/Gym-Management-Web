# Gym Management Web

Hệ thống quản lý phòng gym xây dựng bằng PHP/MySQL, gồm 2 phần chính:
- Admin panel: quản lý hội viên, gói tập, đơn hàng, dịch vụ, báo cáo.
- Client site: người dùng đăng ký tài khoản, mua gói tập, theo dõi thông tin cá nhân.

## Tính năng nổi bật
- Quản lý hội viên, hạng hội viên, địa chỉ, dịch vụ và huấn luyện viên.
- Quản lý sản phẩm, đơn hàng, giỏ hàng, khuyến mãi.
- Theo dõi lịch tập, lớp học, gói tập hội viên.
- Phân quyền và kiểm soát truy cập trong khu vực quản trị.

## Công nghệ sử dụng
- PHP (thuần, chạy trên Apache)
- MySQL / MariaDB
- HTML, CSS, JavaScript, jQuery, Bootstrap, DataTables

## Yêu cầu môi trường
- XAMPP (khuyến nghị PHP 8+)
- MySQL đang chạy
- Trình duyệt web hiện đại

## Cài đặt nhanh
1. Clone hoặc copy source vào thư mục web server (ví dụ: `htdocs/DoAn`).
2. Tạo database (ví dụ: `gym_management`).
3. Import file SQL: `database/gym_management.sql`.
4. Cập nhật thông tin kết nối DB trong file cấu hình (`config/db.php` hoặc `includes/config.php` tùy môi trường).
5. Mở trình duyệt:
	- Trang client: `http://localhost/DoAn/client/`
	- Trang admin: `http://localhost/DoAn/admin/`

## Cấu trúc thư mục chính
- `admin/`: giao diện và nghiệp vụ quản trị.
- `client/`: giao diện người dùng cuối.
- `includes/`, `config/`: cấu hình, kết nối DB, hàm dùng chung.
- `database/`: file SQL khởi tạo dữ liệu.
- `assets/`: tài nguyên giao diện.

## Ghi chú
- Đây là dự án học tập/phát triển nội bộ, có thể cần tinh chỉnh thêm bảo mật trước khi đưa vào production.
- Nên bật backup database định kỳ khi dùng dữ liệu thật.
