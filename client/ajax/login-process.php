<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    if (empty($username) && empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập đẩy đủ tên đăng nhập và mật khẩu!'
        ]);
        exit();
    }

    if (empty($username)){
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập tên đăng nhập hoặc Email!'
        ]);
        exit();
    }

    if (empty($password)){
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập mật khẩu!'
        ]);
        exit();
    }
    
    try {
        // Cho phép đăng nhập bằng username hoặc email, kiểm tra mật khẩu plain text
        $stmt = $conn->prepare("
            SELECT u.id, u.username, u.password, u.role_id, u.status, m.full_name 
            FROM users u
            LEFT JOIN members m ON u.id = m.users_id
            WHERE (u.username = ? OR u.email = ?) AND u.password = ?
        ");
        $stmt->bind_param("sss", $username, $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("Tài khoản hoặc mật khẩu không chính xác!");
        }

        $user = $result->fetch_assoc();

        if ($user['status'] !== 'active') {
            throw new Exception("Tài khoản của bạn đã bị khóa hoặc chưa kích hoạt!");
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
