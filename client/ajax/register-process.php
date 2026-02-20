<?php
// TODO: Implement đăng ký người dùng mới
// Sử dụng Fetch API từ phía client

header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // TODO: Validate dữ liệu
    if (empty($full_name) || empty($email) || empty($username) || empty($password)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng điền đầy đủ thông tin bắt buộc!'
        ]);
        exit();
    }
    
    // TODO: Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email không hợp lệ!'
        ]);
        exit();
    }
    
    try {
        // TODO: Kiểm tra username hoặc email đã tồn tại chưa
        // SELECT COUNT(*) FROM members WHERE username = ? OR email = ?
        
        // TODO: Hash password
        // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // TODO: Insert vào database
        // INSERT INTO members (full_name, email, phone, username, password, birth_date, gender, address, created_at)
        // VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        
        echo json_encode([
            'success' => true,
            'message' => 'Đăng ký thành công! Vui lòng đăng nhập.'
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
