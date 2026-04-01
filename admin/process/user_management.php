<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_ALL', 'view');

include '../../includes/database.php';
require_once '../../includes/functions.php';
$db = getDB();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action == 'update_permissions') {
    $_SESSION['validation_errors'] = ['general' => 'Phân quyền theo user đã bị tắt. Vui lòng phân quyền theo vai trò tại trang Vai trò.'];
    header("Location: ../roles.php");
    exit();
}

// Kiểm tra khả năng tương thích schema cũ/mới
$checkPhoneColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();
$hasPhoneColumn = !empty($checkPhoneColumn);

if ($action == 'add') {
    processRequirePermission('MANAGE_ALL', 'add');

    $username = $_POST['username'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    
    $errors = [];

    // Kiểm tra các trường bắt buộc
    if (empty($username)) {
        $errors['username'] = 'Vui lòng nhập tên đăng nhập';
    }
    if (empty($full_name)) {
        $errors['full_name'] = 'Vui lòng nhập họ tên';
    }
    if (empty($email)) {
        $errors['email'] = 'Vui lòng nhập email';
    }
    if (empty($password)) {
        $errors['password'] = 'Vui lòng nhập mật khẩu';
    }
    if (empty($password_confirm)) {
        $errors['password_confirm'] = 'Vui lòng xác nhận mật khẩu';
    }
    if (empty($role_id)) {
        $errors['role_id'] = 'Vui lòng chọn vai trò';
    }
    
    // Kiểm tra email format
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email không hợp lệ';
    }
    
    // Kiểm tra phone format nếu có
    if (!empty($phone) && !preg_match('/^(?:\+84\d{9}|0\d{9,10})$/', $phone)) {
        $errors['phone'] = 'Vui lòng nhập số điện thoại bắt đầu bằng 0 hoặc +84 và phải có 10-11 số';
    }

    // Kiểm tra password khớp
    if (!empty($password) && !empty($password_confirm) && $password !== $password_confirm) {
        $errors['password_confirm'] = 'Mật khẩu xác nhận không khớp';
    }
    
    // Kiểm tra username tồn tại
    if (!empty($username)) {
        $checkUser = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE username = ?");
        $checkUser->execute([$username]);
        if ($checkUser->fetch()['cnt'] > 0) {
            $errors['username'] = 'Tên đăng nhập này đã tồn tại';
        }
    }
    
    // Kiểm tra email tồn tại
    if (!empty($email)) {
        $checkEmail = $db->prepare("SELECT COUNT(*) as cnt FROM users WHERE email = ?");
        $checkEmail->execute([$email]);
        if ($checkEmail->fetch()['cnt'] > 0) {
            $errors['email'] = 'Email này đã tồn tại';
        }
    }
    
    // Nếu có lỗi, lưu vào session và redirect về form
    if (!empty($errors)) {
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['form_data'] = [
            'username' => $username,
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'role_id' => $role_id
        ];
        header("Location: ../users.php#addUserModal");
        exit();
    }

    // Kiểm tra phone column có tồn tại không
    $checkColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();
    
    if (!empty($checkColumn)) {
        $hashedPassword = hashPassword($password);
        $sql = "INSERT INTO users (username, full_name, password, email, phone, role_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $full_name, $hashedPassword, $email, $phone, $role_id, 'active']);
    } else {
        $hashedPassword = hashPassword($password);
        $sql = "INSERT INTO users (username, full_name, password, email, role_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $db->prepare($sql);
        $stmt->execute([$username, $full_name, $hashedPassword, $email, $role_id, 'active']);
    }

    unset($_SESSION['validation_errors']);
    unset($_SESSION['form_data']);
    header("Location: ../users.php");
    exit();
}

if ($action == 'delete') {
    processRequirePermission('MANAGE_ALL', 'delete');

    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        $_SESSION['validation_errors'] = ['general' => 'Yêu cầu không hợp lệ'];
        header("Location: ../users.php");
        exit();
    }

    try {
        // Nếu user đang được tham chiếu (ví dụ members.users_id), không được xóa cứng.
        // Thay vào đó, chuyển trạng thái sang inactive.
        $refStmt = $db->prepare("SELECT COUNT(*) AS cnt FROM members WHERE users_id = ?");
        $refStmt->execute([$id]);
        $refCount = (int)($refStmt->fetch()['cnt'] ?? 0);

        if ($refCount > 0) {
            $softStmt = $db->prepare("UPDATE users SET status = 'locked' WHERE id = ?");
            $softStmt->execute([$id]);
            $_SESSION['validation_errors'] = ['general' => 'User đang có dữ liệu liên kết (hội viên). Đã chuyển sang trạng thái bị khóa thay vì xóa.'];
            header("Location: ../users.php");
            exit();
        }

        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        header("Location: ../users.php");
        exit();
    } catch (PDOException $e) {
        // Bắt lỗi FK hoặc lỗi DB khác để không fatal
        $_SESSION['validation_errors'] = ['general' => 'Không thể xóa user do đang có dữ liệu liên kết. Vui lòng kiểm tra các bảng liên quan hoặc chuyển trạng thái Inactive.'];
        header("Location: ../users.php");
        exit();
    }
}

if ($action == 'toggle_status') {
    processRequirePermission('MANAGE_ALL', 'edit');

    $id = $_POST['id'] ?? $_GET['id'] ?? '';

    if (empty($id)) {
        $_SESSION['validation_errors'] = ['general' => 'Yêu cầu không hợp lệ'];
        header("Location: ../users.php");
        exit();
    }

    $statusStmt = $db->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $statusStmt->execute([$id]);
    $currentUser = $statusStmt->fetch(PDO::FETCH_ASSOC);

    if (!$currentUser) {
        $_SESSION['validation_errors'] = ['general' => 'Không tìm thấy user cần cập nhật'];
        header("Location: ../users.php");
        exit();
    }

    $newStatus = ($currentUser['status'] ?? '') === 'active' ? 'locked' : 'active';
    $updateStmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $id]);

    header("Location: ../users.php");
    exit();
}

// xử lý edit
if ($action == 'edit') {
    processRequirePermission('MANAGE_ALL', 'edit');

    $id = $_POST['id'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    $errors = [];

    // Kiểm tra các trường bắt buộc
    if (empty($id)) {
        $errors['general'] = 'Yêu cầu không hợp lệ';
    }
    if (empty($role_id)) {
        $errors['role_id'] = 'Vui lòng chọn vai trò';
    }
    if (empty($full_name)) {
        $errors['full_name'] = 'Vui lòng nhập họ tên';
    }

    // Lấy lại username/email hiện tại từ DB để không bị ghi đè bằng chuỗi rỗng từ form disabled
    $currentUser = null;
    if (empty($errors)) {
        $currentStmt = $db->prepare("SELECT username, email, full_name, role_id FROM users WHERE id = ? LIMIT 1");
        $currentStmt->execute([$id]);
        $currentUser = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            $errors['general'] = 'Không tìm thấy user cần cập nhật';
        }

        if (empty($errors)) {
            [$staffCount, $memberCount] = getUserUsageCounts($db, $id);
            $submittedRoleId = (string) $role_id;
            $currentRoleId = (string) ($currentUser['role_id'] ?? '');
            if (($staffCount > 0 || $memberCount > 0) && $submittedRoleId !== $currentRoleId) {
                $errors['general'] = 'Không thể thay đổi vai trò vì tài khoản đã được gán vào hệ thống (staff/member)';
            }
        }
    }
    
    // Kiểm tra phone format nếu có
    if (!empty($phone) && !preg_match('/^(?:\+84\d{9}|0\d{9,10})$/', $phone)) {
        $errors['phone'] = 'Vui lòng nhập số điện thoại bắt đầu bằng 0 hoặc +84 và phải có 10-11 số';
    }

    // Kiểm tra password khớp nếu nhập mật khẩu
    if (!empty($password)) {
        if (empty($password_confirm)) {
            $errors['password_confirm'] = 'Vui lòng xác nhận mật khẩu';
        }
        if ($password !== $password_confirm) {
            $errors['password_confirm'] = 'Mật khẩu xác nhận không khớp';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Mật khẩu phải có ít nhất 6 ký tự';
        }
    }
    
    // Nếu có lỗi, lưu vào session và redirect về form
    if (!empty($errors)) {
        $_SESSION['validation_errors'] = $errors;
        $_SESSION['form_data'] = [
            'full_name' => $full_name,
            'phone' => $phone,
            'role_id' => $role_id
        ];
        header("Location: ../users.php?edit=" . $id . "#editUserModal" . $id);
        exit();
    }

    // Kiểm tra phone column có tồn tại không
    $checkColumn = $db->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='users' AND COLUMN_NAME='phone'")->fetch();

    if (!empty($checkColumn)) {
        if (!empty($password)) {
            $hashedPassword = hashPassword($password);
            $sql = "UPDATE users SET username=?, full_name=?, email=?, phone=?, role_id=?, password=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$currentUser['username'], $full_name, $currentUser['email'], $phone, $role_id, $hashedPassword, $id]);
        } else {
            $sql = "UPDATE users SET username=?, full_name=?, email=?, phone=?, role_id=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$currentUser['username'], $full_name, $currentUser['email'], $phone, $role_id, $id]);
        }
    } else {
        if (!empty($password)) {
            $hashedPassword = hashPassword($password);
            $sql = "UPDATE users SET username=?, full_name=?, email=?, role_id=?, password=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$currentUser['username'], $full_name, $currentUser['email'], $role_id, $hashedPassword, $id]);
        } else {
            $sql = "UPDATE users SET username=?, full_name=?, email=?, role_id=? WHERE id=?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$currentUser['username'], $full_name, $currentUser['email'], $role_id, $id]);
        }
    }

    header("Location: ../users.php");
    exit();
}
?>