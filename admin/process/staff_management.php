<?php
session_start();
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth_permission.php';

checkPermission('MANAGE_STAFF');

$db = getDB();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    // Thêm staff mới
    if ($action === 'add') {
        $full_name = trim($_POST['full_name']);
        $position  = trim($_POST['position']);
        $status    = $_POST['status'] ?? 'Active';

        // Thông tin user mới
        $username  = trim($_POST['username']);
        $password  = trim($_POST['password']); // lưu plaintext
        $email     = trim($_POST['email']);
        $role_id   = 5; // Staff

        // Kiểm tra bắt buộc nhập mật khẩu
        if (empty($password)) {
            echo "<script>alert('Bạn phải nhập mật khẩu cho staff!'); window.history.back();</script>";
            exit();
        }

        // Kiểm tra trùng username hoặc email
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
        $stmtCheck->execute([':username' => $username, ':email' => $email]);
        $exists = $stmtCheck->fetchColumn();

        if ($exists > 0) {
            echo "<script>alert('Username hoặc Email đã tồn tại, vui lòng chọn khác!'); window.history.back();</script>";
            exit();
        }

        // 1. Tạo user mới (lưu mật khẩu plaintext)
        $stmtUser = $db->prepare("INSERT INTO users (role_id, username, password, email, status, created_at) 
                                VALUES (:role_id, :username, :password, :email, 'active', NOW())");
        $stmtUser->bindParam(':role_id', $role_id, PDO::PARAM_INT);
        $stmtUser->bindParam(':username', $username);
        $stmtUser->bindParam(':password', $password); // lưu trực tiếp
        $stmtUser->bindParam(':email', $email);
        $stmtUser->execute();

        $newUserId = $db->lastInsertId();

        // 2. Tạo staff mới liên kết với user vừa tạo
        $stmtStaff = $db->prepare("INSERT INTO staff (users_id, full_name, position, status) 
                                VALUES (:users_id, :full_name, :position, :status)");
        $stmtStaff->bindParam(':users_id', $newUserId, PDO::PARAM_INT);
        $stmtStaff->bindParam(':full_name', $full_name);
        $stmtStaff->bindParam(':position', $position);
        $stmtStaff->bindParam(':status', $status);
        $stmtStaff->execute();

        echo "<script>alert('Thêm staff thành công!'); window.location.href='../staff.php';</script>";
        exit();
    }

    // Sửa staff (và có thể sửa user liên kết)
    if ($action === 'edit') {
        $id        = intval($_POST['id']);
        $users_id  = intval($_POST['users_id']);
        $full_name = trim($_POST['full_name']);
        $position  = trim($_POST['position']);
        $status    = $_POST['status'] ?? 'Active';

        // Cập nhật staff
        $stmt = $db->prepare("UPDATE staff 
                              SET full_name=:full_name, position=:position, status=:status 
                              WHERE id=:id");
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':position', $position);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        // Nếu có sửa thông tin user liên kết
        if (!empty($_POST['username']) && !empty($_POST['email'])) {
            $username = trim($_POST['username']);
            $email    = trim($_POST['email']);

            $sqlUser = "UPDATE users SET username=:username, email=:email WHERE id=:id";
            $stmtUser = $db->prepare($sqlUser);
            $stmtUser->bindParam(':username', $username);
            $stmtUser->bindParam(':email', $email);
            $stmtUser->bindParam(':id', $users_id, PDO::PARAM_INT);
            $stmtUser->execute();
        }

        header("Location: ../staff.php?msg=updated");
        exit();
    }

    // Xóa staff
    if ($action === 'delete') {
        $id = intval($_GET['id']);

        // Lấy users_id liên kết với staff
        $stmt = $db->prepare("SELECT users_id FROM staff WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $users_id = $stmt->fetchColumn();

        // Xóa staff
        $stmtDelStaff = $db->prepare("DELETE FROM staff WHERE id = :id");
        $stmtDelStaff->execute([':id' => $id]);

        // Nếu có users_id thì xóa user liên kết
        if ($users_id) {
            $stmtDelUser = $db->prepare("DELETE FROM users WHERE id = :users_id");
            $stmtDelUser->execute([':users_id' => $users_id]);
        }

        // Thông báo và quay lại trang staff
        echo "<script>alert('Đã xóa staff và user liên kết!'); window.location.href='../staff.php';</script>";
        exit();
    }

} catch (Exception $e) {
    die("Lỗi: " . $e->getMessage());
}
