# README - Tong hop file/function da chinh sua

Muc dich: ghi nhanh cac file da can thiep va function/khối xu ly da doi de tien ghep code.
Ngay cap nhat: 2026-03-14

## 1) Client - Trang subscription

### client/subscription.php


### client/api.php
- Them function:
  - `handleDeleteNotification(PDO $db, $member, $notificationId)`
    - Chi cho xoa thong bao cua dung user dang dang nhap
    - Chi xoa thong bao da doc (`is_read = 1`)
- Da cap nhat router action:
  - `case 'delete_notification'` -> goi `handleDeleteNotification(...)`
- Action da co va van dung:
  - `case 'mark_notification_read'`

### client/layout/header.php
- Da cap nhat menu de tro den `subscription.php` 
- Da cap nhat dieu kien active:
  - `$current_page == 'subscription.php'`
- Label menu hien tai: `Goi`





## 2) Admin - Notifications (gui theo nhom)

### admin/notifications.php
- Da thay luong tao thong bao tu "gui tung user" sang "gui theo nhom".
- POST xu ly moi:
  - `recipient_group` (gia tri: `all`, `admin`, `staff`, `member`)
- Logic moi:
  - Lay danh sach user active theo nhom (hoac tat ca)
  - Insert thong bao cho tung user trong transaction
- Form modal da doi:
  - Dropdown "Nguoi nhan" -> "Gui toi" voi 4 lua chon

## 3) Functions dung chung - Feedback

### includes/functions.php
- Da chinh lai cac query feedback theo schema dung:
  - `members.users_id` (khong dung `members.user_id`)
  - `users.username` (khong dung `users.name`)
  - `member_name`: `COALESCE(NULLIF(m.full_name,''), mu.username)`
- Function da can thiep:
  - `getAllFeedback($status = null)`
  - `getFeedbackById($id)`




