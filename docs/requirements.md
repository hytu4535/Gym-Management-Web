# Tài liệu Yêu cầu Hệ thống

## 1. Yêu cầu chức năng

### 1.1 Module Quản trị (Admin)

#### 1.1.1 Quản lý Tài khoản
- **Users Management**
  - Thêm/Sửa/Xóa người dùng
  - Phân quyền cho người dùng
  - Kích hoạt/Khóa tài khoản
  - Reset mật khẩu
  
- **Roles Management**
  - Tạo/Sửa/Xóa vai trò
  - Gán quyền cho vai trò
  - Quản lý phân quyền chi tiết

#### 1.1.2 Quản lý Hội viên
- **Members Management**
  - Đăng ký hội viên mới
  - Cập nhật thông tin hội viên
  - Xem lịch sử tập luyện
  - Quản lý gói tập của hội viên
  - Gia hạn/Hủy gói tập
  
- **Staff Management**
  - Quản lý nhân viên
  - Phân ca làm việc
  - Theo dõi hiệu suất

#### 1.1.3 Quản lý Gói tập
- **Packages Management**
  - Tạo/Sửa/Xóa gói tập
  - Thiết lập giá, thời hạn
  - Quản lý ưu đãi, khuyến mại
  
- **Member Packages**
  - Theo dõi gói tập của hội viên
  - Lịch sử đăng ký
  - Thông báo sắp hết hạn

#### 1.1.4 Quản lý Huấn luyện viên
- **Trainers Management**
  - Thêm/Sửa/Xóa huấn luyện viên
  - Quản lý chuyên môn, chứng chỉ
  - Đánh giá hiệu suất
  
- **Training Schedules**
  - Lập lịch tập cho hội viên
  - Phân công huấn luyện viên
  - Theo dõi buổi tập
  - Thông báo lịch tập

#### 1.1.5 Quản lý Bán hàng
- **Categories**
  - Quản lý danh mục sản phẩm
  
- **Orders Management**
  - Xử lý đơn hàng
  - Theo dõi trạng thái đơn
  - Lịch sử đơn hàng
  
- **Payments**
  - Xác nhận thanh toán
  - Quản lý phương thức thanh toán
  - Báo cáo doanh thu
  
- **Carts**
  - Theo dõi giỏ hàng chưa thanh toán

#### 1.1.6 Quản lý Thiết bị
- **Equipment Management**
  - Danh sách thiết bị
  - Theo dõi tình trạng
  - Lịch sử sử dụng
  
- **Equipment Maintenance**
  - Lập lịch bảo trì
  - Ghi chú sửa chữa
  - Chi phí bảo trì

#### 1.1.7 Phản hồi & Thông báo
- **Feedback**
  - Xem phản hồi của khách hàng
  - Trả lời, xử lý phản hồi
  - Phân tích đánh giá
  
- **Notifications**
  - Tạo thông báo hệ thống
  - Gửi email/SMS
  - Lịch sử thông báo

#### 1.1.8 Dashboard
- Tổng quan thành viên
- Doanh thu theo tháng/năm
- Top gói tập bán chạy
- Thiết bị cần bảo trì
- Feedback mới nhất
- Lịch tập hôm nay

### 1.2 Module Khách hàng (Client)

#### 1.2.1 Trang chủ
- Giới thiệu phòng gym
- Slider hình ảnh
- Dịch vụ nổi bật
- Huấn luyện viên
- Testimonials
- Liên hệ

#### 1.2.2 Đăng ký/Đăng nhập
- Đăng ký tài khoản mới
- Đăng nhập thành viên
- Quên mật khẩu
- Xác thực email

#### 1.2.3 Hồ sơ cá nhân
- Xem/Cập nhật thông tin
- Đổi mật khẩu
- Xem gói tập hiện tại
- Lịch sử tập luyện

#### 1.2.4 Gói tập
- Danh sách gói tập
- Chi tiết gói tập
- Đăng ký gói tập online
- So sánh gói tập

#### 1.2.5 Lịch tập
- Đặt lịch tập với PT
- Xem lịch sử buổi tập
- Hủy/Đổi lịch

#### 1.2.6 Cửa hàng
- Xem sản phẩm
- Thêm vào giỏ hàng
- Thanh toán online
- Theo dõi đơn hàng

## 2. Yêu cầu phi chức năng

### 2.1 Hiệu năng
- Thời gian tải trang < 3 giây
- Xử lý được 100 người dùng đồng thời
- Database query optimization

### 2.2 Bảo mật
- Mã hóa mật khẩu (bcrypt)
- Prepared Statements
- CSRF protection
- XSS prevention
- Session timeout (30 phút)
- SSL/HTTPS

### 2.3 Giao diện
- Responsive design (mobile, tablet, desktop)
- Browser compatibility (Chrome, Firefox, Safari, Edge)
- AdminLTE 3.2 cho admin panel
- User-friendly interface

### 2.4 Khả năng mở rộng
- Code có cấu trúc rõ ràng
- Comment đầy đủ
- Dễ bảo trì
- Có thể thêm module mới

### 2.5 Backup & Recovery
- Backup database tự động hàng ngày
- Log hệ thống
- Khôi phục dữ liệu

## 3. Ràng buộc kỹ thuật

### 3.1 Công nghệ
- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx
- Bootstrap 4

### 3.2 Hosting
- Shared hosting tương thích
- RAM >= 512MB
- Disk space >= 5GB
- Bandwidth không giới hạn

## 4. Use Cases quan trọng

### UC-01: Đăng ký hội viên mới
**Actor:** Admin/Staff  
**Precondition:** Đã đăng nhập hệ thống  
**Main Flow:**
1. Nhân viên chọn "Thêm hội viên"
2. Nhập thông tin: Họ tên, Email, SĐT, CMND
3. Chọn gói tập
4. Nhập thông tin thanh toán
5. Hệ thống tạo tài khoản và gửi email xác nhận
6. In thẻ hội viên

### UC-02: Đặt lịch tập với PT
**Actor:** Member  
**Precondition:** Đã có gói tập còn hạn  
**Main Flow:**
1. Hội viên chọn huấn luyện viên
2. Chọn ngày giờ mong muốn
3. Hệ thống kiểm tra lịch trống
4. Xác nhận đặt lịch
5. Gửi thông báo cho HLV và hội viên

### UC-03: Mua sản phẩm
**Actor:** Member/Guest  
**Precondition:** Có tài khoản (hoặc không)  
**Main Flow:**
1. Khách hàng duyệt sản phẩm
2. Thêm vào giỏ hàng
3. Xem giỏ hàng, cập nhật số lượng
4. Nhập thông tin giao hàng
5. Chọn phương thức thanh toán
6. Xác nhận đơn hàng
7. Nhận email xác nhận

## 5. Tiêu chí nghiệm thu

- ✅ Hoàn thành đầy đủ các chức năng yêu cầu
- ✅ Giao diện thân thiện, dễ sử dụng
- ✅ Không có lỗi nghiêm trọng (critical bugs)
- ✅ Code clean, có comment
- ✅ Tài liệu đầy đủ
- ✅ Demo thành công trước giảng viên
