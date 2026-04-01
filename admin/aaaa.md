# Nội dung thuyết trình đồ án

## 1. Mở đầu ngắn gọn
“Em xin phép trình bày đồ án web quản lý phòng gym. Hệ thống được xây dựng để quản lý đồng thời tài khoản, hội viên, nhân viên, gói tập, lớp học, đơn hàng, thiết bị và các nghiệp vụ liên quan trong một nền tảng thống nhất. Mục tiêu chính là giúp quản lý tập trung, giảm thao tác thủ công, hạn chế dữ liệu trùng và đảm bảo toàn vẹn dữ liệu khi vận hành thực tế.”

## 2. Giới thiệu cho client
“Nếu nhìn từ góc độ người dùng cuối, hệ thống chia thành 2 nhóm chính: client và admin.

Client là phần dành cho khách hàng và hội viên. Ở đây người dùng có thể đăng ký tài khoản, đăng nhập, xem dịch vụ, xem gói tập, đặt lịch, đăng ký gói, mua sản phẩm, theo dõi đơn hàng, xem lịch sử và cập nhật thông tin cá nhân. Các thao tác này được tối ưu để người dùng tự thao tác mà không cần hỗ trợ thủ công từ nhân viên.”

## 3. Giới thiệu cho admin
“Phần admin là khu vực quản trị nội bộ. Tại đây quản trị viên có thể quản lý users, staff, members, roles, departments, categories, products, orders, packages, services, schedules, feedback, notifications và các báo cáo thống kê.

Mục tiêu của phần admin là kiểm soát dữ liệu, phân quyền đúng vai trò, và đảm bảo mỗi nghiệp vụ đều được kiểm tra cả ở giao diện lẫn backend.”

## 4. Logic tổng quát của hệ thống
“Về kiến trúc dữ liệu, hệ thống dùng PHP PDO + MySQL. Em ưu tiên các nguyên tắc sau:

1. Kiểm tra dữ liệu ở cả frontend và backend.
2. Không tin dữ liệu từ request thủ công.
3. Với các thao tác ghi dữ liệu quan trọng, luôn validate lại từ database.
4. Các bảng có liên kết phải có ràng buộc để tránh trùng và tránh mất toàn vẹn dữ liệu.

Nhờ vậy, hệ thống không chỉ chạy đúng trên giao diện mà còn an toàn khi người dùng can thiệp trực tiếp vào request.”

## 5. Luồng cho trang client
“Ở phần client, quy trình chính là: người dùng tạo tài khoản, đăng nhập, xem thông tin dịch vụ hoặc gói tập, sau đó thực hiện đăng ký hoặc đặt mua.

Khi người dùng thao tác, hệ thống sẽ kiểm tra trạng thái đăng nhập, kiểm tra dữ liệu nhập hợp lệ, rồi mới ghi xuống database. Một số nghiệp vụ như đơn hàng, lịch tập hay đăng ký gói sẽ có kiểm tra trùng và kiểm tra ràng buộc để tránh tạo dữ liệu sai.”

## 6. Luồng cho trang admin
“Ở phần admin, mỗi chức năng đều có kiểm soát quyền theo role.

Khi vào trang quản lý, hệ thống sẽ kiểm tra quyền truy cập trước. Sau đó dữ liệu được hiển thị theo bảng, có phân trang, lọc và modal thêm/sửa. Khi submit, backend luôn kiểm tra lại bằng PDO query trước khi insert hoặc update.”

## 7. Logic quản lý Staff
“Với trang quản lý staff, em thiết kế theo các điểm chính sau:

1. Danh sách hiển thị đầy đủ: ID, họ tên, email, số điện thoại, chức vụ, phòng ban, trạng thái và hành động.
2. Form thêm/sửa dùng dropdown chọn tài khoản để tránh nhập sai.
3. Họ tên và số điện thoại không cho nhập tay, mà tự lấy từ tài khoản đã chọn để đồng bộ dữ liệu.
4. Chức vụ lấy từ bảng roles.
5. Phòng ban lấy từ bảng departments.
6. Trạng thái chỉ cho các giá trị hợp lệ như đang làm, đã nghỉ việc, tạm nghỉ.

Phần quan trọng nhất là chống trùng. Trước khi thêm staff, hệ thống kiểm tra user đó đã tồn tại trong staff chưa, và cũng kiểm tra luôn trong members để đảm bảo một user không thể vừa là staff vừa là member. Nếu đã được gán rồi thì hệ thống chặn ngay, kể cả khi người dùng gửi request thủ công.”

## 8. Logic quản lý Members
“Với trang quản lý hội viên, em áp dụng cùng tư duy:

1. Dropdown chọn tài khoản chỉ lấy user phù hợp.
2. Họ tên và số điện thoại được tự động đổ ra từ tài khoản đã chọn.
3. Backend kiểm tra user có đúng role hội viên trước khi cho lưu.
4. Nếu tài khoản đã tồn tại trong members hoặc staff thì hệ thống chặn không cho tạo mới.
5. Khi sửa, không cho đổi tài khoản liên kết để tránh lệch dữ liệu lịch sử.

Đây là phần rất quan trọng vì hội viên là dữ liệu gốc cho các nghiệp vụ sau như đặt lịch, đăng ký gói, đơn hàng và tính tổng chi tiêu.”

## 9. Logic quản lý Users
“Ở trang quản lý users, em bổ sung kiểm tra để không cho đổi role nếu tài khoản đã được sử dụng trong hệ thống.

Cụ thể, trước khi update role, backend sẽ kiểm tra user đó có tồn tại trong staff hoặc members không. Nếu đã tồn tại rồi thì không cho đổi role nữa. Lý do là vì đổi role lúc này có thể làm lệch toàn bộ dữ liệu nghiệp vụ đang tham chiếu tới user đó.

Trên giao diện, dropdown role của user đã được sử dụng sẽ bị khóa và hiển thị cảnh báo để người dùng biết rằng tài khoản này không thể đổi vai trò.”

## 10. Chốt ý về toàn vẹn dữ liệu
“Điểm nhấn của đồ án là đảm bảo toàn vẹn dữ liệu. Em không chỉ kiểm tra ở giao diện mà còn kiểm tra lại ở backend và có ràng buộc ở database. Điều này giúp hệ thống an toàn hơn khi chạy thực tế, hạn chế lỗi do thao tác sai hoặc cố tình can thiệp request.”

## 11. Câu kết
“Tổng kết lại, hệ thống của em không chỉ đáp ứng chức năng quản lý phòng gym mà còn tập trung vào tính đúng đắn của dữ liệu, phân quyền và kiểm soát luồng nghiệp vụ. Em xin hết phần trình bày, và sẵn sàng trả lời câu hỏi nếu thầy/cô cần em giải thích sâu hơn ở từng module.”




