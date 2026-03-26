<?php
include '../../includes/database.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'add') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']); // plaintext
    $email    = trim($_POST['email']);
    $role_id  = intval($_POST['role_id']);
    $status   = $_POST['status'] ?? 'active';

    // Kiểm tra bắt buộc nhập mật khẩu
    if (empty($password)) {
        echo "<script>alert('Bạn phải nhập mật khẩu cho user!'); window.history.back();</script>";
        exit();
    }

    // Kiểm tra trùng username hoặc email
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
    $stmtCheck->execute([':username' => $username, ':email' => $email]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo "<script>alert('Username hoặc Email đã tồn tại, vui lòng chọn khác!'); window.history.back();</script>";
        exit();
    }

    // 1. Thêm user
    $stmtUser = $db->prepare("INSERT INTO users (role_id, username, password, email, status, created_at) 
                              VALUES (:role_id, :username, :password, :email, :status, NOW())");
    $stmtUser->bindParam(':role_id', $role_id, PDO::PARAM_INT);
    $stmtUser->bindParam(':username', $username);
    $stmtUser->bindParam(':password', $password); // lưu plaintext
    $stmtUser->bindParam(':email', $email);
    $stmtUser->bindParam(':status', $status);
    $stmtUser->execute();

    $newUserId = $db->lastInsertId();

    // 2. Nếu role là Staff thì thêm vào bảng staff
    if ($role_id == 5) { // giả sử role_id=5 là Staff
        $full_name = $_POST['full_name'] ?? $username; // hoặc lấy từ form
        $position  = $_POST['position'] ?? 'Nhân viên';
        $staffStatus = ($status == 'active') ? 'Active' : 'Inactive';

        $stmtStaff = $db->prepare("INSERT INTO staff (users_id, full_name, position, status) 
                                   VALUES (:users_id, :full_name, :position, :status)");
        $stmtStaff->bindParam(':users_id', $newUserId, PDO::PARAM_INT);
        $stmtStaff->bindParam(':full_name', $full_name);
        $stmtStaff->bindParam(':position', $position);
        $stmtStaff->bindParam(':status', $staffStatus);
        $stmtStaff->execute();
    }

    echo "<script>alert('Thêm user thành công!'); window.location.href='../users.php';</script>";
    exit();
}


if ($action === 'delete') {
    $id = intval($_GET['id']);

    // 1. Kiểm tra xem user này có phải staff không
    $stmtCheckStaff = $db->prepare("SELECT id FROM staff WHERE users_id = :id");
    $stmtCheckStaff->execute([':id' => $id]);
    $staffId = $stmtCheckStaff->fetchColumn();

    // 2. Nếu có staff liên kết thì xóa staff trước
    if ($staffId) {
        $stmtDelStaff = $db->prepare("DELETE FROM staff WHERE id = :staffId");
        $stmtDelStaff->execute([':staffId' => $staffId]);
    }

    // 3. Xóa user
    $stmtDelUser = $db->prepare("DELETE FROM users WHERE id = :id");
    $stmtDelUser->execute([':id' => $id]);

    // 4. Thông báo và quay lại trang users
    echo "<script>alert('Đã xóa user và staff liên kết (nếu có)!'); window.location.href='../users.php';</script>";
    exit();
}

// xử lý edit 
if ($action === 'edit') {
    $id       = intval($_POST['id']);
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $status   = $_POST['status'] ?? 'active';
    $role_id  = intval($_POST['role_id']);
    $password = trim($_POST['password']); // plaintext

    // Kiểm tra trùng username/email (trừ chính user đang sửa)
    $stmtCheck = $db->prepare("SELECT COUNT(*) FROM users WHERE (username = :username OR email = :email) AND id != :id");
    $stmtCheck->execute([':username' => $username, ':email' => $email, ':id' => $id]);
    if ($stmtCheck->fetchColumn() > 0) {
        echo "<script>alert('Username hoặc Email đã tồn tại, vui lòng chọn khác!'); window.history.back();</script>";
        exit();
    }

    // Cập nhật user
    if (!empty($password)) {
        $stmtUser = $db->prepare("UPDATE users SET username=:username, email=:email, password=:password, 
                                  role_id=:role_id, status=:status WHERE id=:id");
        $stmtUser->execute([
            ':username' => $username,
            ':email'    => $email,
            ':password' => $password, // lưu plaintext
            ':role_id'  => $role_id,
            ':status'   => $status,
            ':id'       => $id
        ]);
    } else {
        $stmtUser = $db->prepare("UPDATE users SET username=:username, email=:email, 
                                  role_id=:role_id, status=:status WHERE id=:id");
        $stmtUser->execute([
            ':username' => $username,
            ':email'    => $email,
            ':role_id'  => $role_id,
            ':status'   => $status,
            ':id'       => $id
        ]);
    }

    // Nếu role là Staff thì cập nhật bảng staff
    if ($role_id == 5) {
        $full_name = $_POST['full_name'] ?? $username;
        $position  = $_POST['position'] ?? 'Nhân viên';
        $staffStatus = ($status == 'active') ? 'Active' : 'Inactive';

        // Kiểm tra staff đã tồn tại chưa
        $stmtCheckStaff = $db->prepare("SELECT id FROM staff WHERE users_id = :users_id");
        $stmtCheckStaff->execute([':users_id' => $id]);
        $staffId = $stmtCheckStaff->fetchColumn();

        if ($staffId) {
            // Update staff
            $stmtStaff = $db->prepare("UPDATE staff SET full_name=:full_name, position=:position, status=:status 
                                       WHERE users_id=:users_id");
            $stmtStaff->execute([
                ':full_name' => $full_name,
                ':position'  => $position,
                ':status'    => $staffStatus,
                ':users_id'  => $id
            ]);
        } else {
            // Insert staff mới nếu chưa có
            $stmtStaff = $db->prepare("INSERT INTO staff (users_id, full_name, position, status) 
                                       VALUES (:users_id, :full_name, :position, :status)");
            $stmtStaff->execute([
                ':users_id'  => $id,
                ':full_name' => $full_name,
                ':position'  => $position,
                ':status'    => $staffStatus
            ]);
        }
    }

    echo "<script>alert('Cập nhật user và staff liên kết thành công!'); window.location.href='../users.php';</script>";
    exit();
}

?>
