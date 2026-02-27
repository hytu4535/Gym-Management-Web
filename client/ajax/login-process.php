<?php
// TODO: Implement đăng nhập
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
    
    // TODO: Validate dữ liệu
    if (empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng nhập tên đăng nhập và mật khẩu!'
        ]);
        exit();
    }
    
    try {
        // TODO: Query database
        // SELECT * FROM members WHERE (username = ? OR email = ?) AND status = 'active'
        
        // TODO: Verify password
        // if (password_verify($password, $user['password'])) {
        //     // Đăng nhập thành công
        //     $_SESSION['user_id'] = $user['member_id'];
        //     $_SESSION['username'] = $user['username'];
        //     $_SESSION['full_name'] = $user['full_name'];
        //     $_SESSION['role'] = 'member';
        // }
        
        // TODO: Implement remember me functionality
        // if ($remember_me) {
        //     setcookie('remember_token', $token, time() + (86400 * 30), '/');
        // }
        
        // Sample success response
        echo json_encode([
            'success' => true,
            'message' => 'Đăng nhập thành công!',
            'redirect' => 'index.php'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Có lỗi xảy ra: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
