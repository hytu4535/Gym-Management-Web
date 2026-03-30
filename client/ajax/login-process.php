<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../includes/functions.php';
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu!'
        ]);
        exit();
    }
    
    try {
        // Cho phép đăng nhập bằng username hoặc email, xác thực bằng bcrypt và hỗ trợ dữ liệu cũ
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.password, u.role_id, u.status, m.full_name 
            FROM users u
            LEFT JOIN members m ON u.id = m.users_id
            WHERE u.username = ? OR u.email = ?
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Tài khoản hoặc mật khẩu không chính xác!");
        }

        $user = $result->fetch_assoc();

        $isPasswordValid = password_verify($password, $user['password']);
        $isLegacyPasswordValid = !$isPasswordValid && (
            $password === $user['password'] || md5($password) === $user['password']
        );

        if (!$isPasswordValid && !$isLegacyPasswordValid) {
            throw new Exception("Tài khoản hoặc mật khẩu không chính xác!");
        }

        if ($user['status'] !== 'active') {
            throw new Exception("Tài khoản của bạn đã bị khóa hoặc chưa kích hoạt!");
        }

        if ($isLegacyPasswordValid) {
            $newHashedPassword = hashPassword($password);
            $upgradeStmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upgradeStmt->bind_param("si", $newHashedPassword, $user['id']);
            $upgradeStmt->execute();
            $upgradeStmt->close();
        }

        // Đăng nhập thành công: Gán Session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
        
        if ($remember_me) {
            setcookie('remember_token', $user['id'], time() + (86400 * 30), '/');
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'redirect' => 'index.php'
        ]);
        
        $stmt->close();
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
}
?>