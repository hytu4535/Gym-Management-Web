<?php
// TODO: Thêm địa chỉ mới cho user
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng đăng nhập!'
        ]);
        exit();
    }
    
    $full_name = $_POST['full_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $city = $_POST['city'] ?? '';
    $district = $_POST['district'] ?? '';
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    
    // TODO: Validate
    if (empty($full_name) || empty($phone) || empty($address)) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng điền đầy đủ thông tin!'
        ]);
        exit();
    }
    
    try {
        // TODO: Nếu đặt làm mặc định, set tất cả địa chỉ khác thành không mặc định
        // if ($is_default) {
        //     UPDATE addresses SET is_default = 0 WHERE member_id = ?
        // }
        
        // TODO: Insert địa chỉ mới
        // INSERT INTO addresses (member_id, full_name, phone, address, city, district, is_default, created_at)
        // VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm địa chỉ mới!'
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
