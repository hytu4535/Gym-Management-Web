# Hướng dẫn Sử dụng - Gym Management System

## Dành cho Quản trị viên (Admin)

### Đăng nhập

1. Truy cập: `http://localhost:8000/admin/login.php`
2. Nhập thông tin:
   - Username: `admin`
   - Password: `admin123`
3. Click "Đăng nhập"

### Dashboard

Sau khi đăng nhập, bạn sẽ thấy Dashboard với:
- Tổng số thành viên
- Số gói tập đang hoạt động
- Tổng đơn hàng
- Số lượng thiết bị

### Quản lý Users

**Thêm user mới:**
1. Menu: Quản lý tài khoản → Users
2. Click nút "Thêm User"
3. Điền form:
   - Tên đăng nhập (unique)
   - Email (unique)
   - Mật khẩu (tối thiểu 6 ký tự)
   - Vai trò: Admin/Staff/Trainer/Member
4. Click "Lưu"

**Sửa user:**
1. Click icon ✏️ ở hàng muốn sửa
2. Cập nhật thông tin
3. Click "Cập nhật"

**Xóa user:**
1. Click icon 🗑️ ở hàng muốn xóa
2. Xác nhận xóa

### Quản lý Hội viên (Members)

**Đăng ký hội viên mới:**
1. Menu: Quản lý hội viên → Members
2. Click "Thêm Member"
3. Điền thông tin:
   - Họ tên
   - Email, SĐT
   - Giới tính, Ngày sinh
   - Địa chỉ
   - CMND/CCCD
   - Liên hệ khẩn cấp
   - Ghi chú sức khỏe
4. Chọn gói tập
5. Thanh toán
6. Click "Đăng ký"

**Gia hạn gói tập:**
1. Tìm hội viên trong danh sách
2. Click "Gia hạn"
3. Chọn gói tập mới
4. Thanh toán
5. Xác nhận

### Quản lý Gói tập (Packages)

**Tạo gói tập mới:**
1. Menu: Quản lý gói tập → Membership Packages
2. Click "Thêm Gói tập"
3. Điền:
   - Tên gói (VD: Gói 1 tháng)
   - Thời hạn (số ngày)
   - Giá
   - Mô tả
   - Tính năng (JSON format)
4. Click "Lưu"

**Ví dụ JSON tính năng:**
```json
{
  "gym_access": true,
  "pool_access": false,
  "personal_trainer": false,
  "nutrition_plan": false
}
```

### Quản lý Huấn luyện viên (Trainers)

**Thêm HLV:**
1. Menu: Quản lý huấn luyện viên → Trainers
2. Click "Thêm Trainer"
3. Điền thông tin:
   - Họ tên
   - Chuyên môn (VD: Yoga, Cardio)
   - Chứng chỉ
   - Kinh nghiệm (năm)
   - Giá/giờ
   - Upload ảnh
4. Click "Lưu"

### Quản lý Lịch tập (Training Schedules)

**Đặt lịch:**
1. Menu: Quản lý huấn luyện viên → Training Schedules
2. Click "Thêm lịch"
3. Chọn:
   - Hội viên
   - Huấn luyện viên
   - Ngày tập
   - Giờ bắt đầu - Giờ kết thúc
4. Ghi chú (optional)
5. Click "Đặt lịch"

**Hệ thống tự động:**
- Kiểm tra lịch trống của HLV
- Gửi thông báo cho HLV và hội viên
- Nhắc nhở trước 1 giờ

### Quản lý Đơn hàng (Orders)

**Xử lý đơn hàng:**
1. Menu: Quản lý bán hàng → Orders
2. Click vào đơn hàng cần xử lý
3. Xem chi tiết:
   - Khách hàng
   - Sản phẩm
   - Số lượng
   - Tổng tiền
4. Cập nhật trạng thái:
   - Pending → Processing
   - Processing → Completed
   - Cancelled (nếu hủy)
5. Click "Cập nhật"

### Quản lý Thanh toán (Payments)

**Xác nhận thanh toán:**
1. Menu: Quản lý bán hàng → Payments
2. Tìm payment cần xác nhận
3. Kiểm tra thông tin
4. Cập nhật trạng thái:
   - Pending → Completed
   - Failed (nếu thất bại)
5. Nhập Transaction ID (nếu có)
6. Click "Xác nhận"

### Quản lý Thiết bị (Equipment)

**Thêm thiết bị:**
1. Menu: Quản lý thiết bị → Equipment
2. Click "Thêm thiết bị"
3. Điền:
   - Tên thiết bị
   - Loại (Cardio/Strength/etc)
   - Số lượng
   - Ngày mua
   - Hạn bảo hành
4. Click "Lưu"

**Lập lịch bảo trì:**
1. Menu: Quản lý thiết bị → Equipment Maintenance
2. Click "Thêm lịch bảo trì"
3. Chọn thiết bị
4. Chọn ngày bảo trì
5. Loại: Routine/Repair/Inspection
6. Mô tả công việc
7. Chi phí dự kiến
8. Click "Lưu"

### Phản hồi & Thông báo

**Xem feedback:**
1. Menu: Phản hồi & Thông báo → Feedback
2. Xem danh sách feedback mới
3. Click để xem chi tiết
4. Trả lời feedback (optional)
5. Đánh dấu "Đã xem" hoặc "Đã giải quyết"

**Tạo thông báo:**
1. Menu: Phản hồi & Thông báo → Notifications
2. Click "Tạo thông báo"
3. Điền:
   - Tiêu đề
   - Nội dung
   - Người nhận (User ID hoặc "all")
   - Loại: info/warning/success/error
4. Click "Gửi"

---

## Dành cho Hội viên (Members)

### Đăng ký tài khoản

1. Truy cập trang chủ
2. Click "Đăng ký"
3. Điền form đăng ký
4. Xác thực email
5. Đăng nhập

### Xem gói tập

1. Menu: Gói tập
2. Xem danh sách các gói
3. So sánh tính năng
4. Chọn gói phù hợp

### Đăng ký gói tập

1. Chọn gói tập muốn đăng ký
2. Click "Đăng ký ngay"
3. Xác nhận thông tin
4. Chọn phương thức thanh toán
5. Thanh toán
6. Nhận xác nhận qua email

### Đặt lịch tập với PT

1. Menu: Lịch tập
2. Click "Đặt lịch mới"
3. Chọn huấn luyện viên
4. Chọn ngày giờ
5. Xác nhận đặt lịch

### Mua sản phẩm

1. Menu: Cửa hàng
2. Duyệt sản phẩm
3. Click "Thêm vào giỏ"
4. Vào giỏ hàng
5. Cập nhật số lượng (nếu cần)
6. Click "Thanh toán"
7. Điền thông tin giao hàng
8. Chọn phương thức thanh toán
9. Xác nhận đơn hàng

### Xem lịch sử

1. Menu: Hồ sơ
2. Tab "Lịch sử tập luyện"
3. Xem các buổi tập đã hoàn thành
4. Xem đánh giá từ HLV

---

## Lưu ý chung

### Bảo mật

- **Đổi mật khẩu mặc định** ngay sau lần đăng nhập đầu tiên
- **Không chia sẻ** tài khoản admin
- **Đăng xuất** khi không sử dụng
- **Sao lưu** database định kỳ

### Hiệu suất

- Xóa dữ liệu cũ định kỳ
- Optimize database mỗi tháng
- Clear cache khi cần thiết

### Hỗ trợ

Nếu gặp vấn đề, liên hệ:
- Email: support@gym.com
- Phone: 0123-456-789
- GitHub Issues: [Link]

---

**Cập nhật lần cuối:** 29/01/2026  
**Version:** 1.0.0
