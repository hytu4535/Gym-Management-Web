<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
        exit();
    }

    $member_id = $_SESSION['user_id'];
    $full_address = trim($_POST['address'] ?? '');
    $city         = trim($_POST['city'] ?? '');
    $district     = trim($_POST['district'] ?? '');
    $is_default   = isset($_POST['is_default']) ? 1 : 0;

    if (empty($full_address) || empty($city) || empty($district)) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng điền đầy đủ thông tin!']);
        exit();
    }

    try {
        if ($is_default) {
            $stmt_reset = $conn->prepare("UPDATE addresses SET is_default=0 WHERE member_id=?");
            if(!$stmt_reset){ throw new Exception($conn->error); }
            $stmt_reset->bind_param("i", $member_id);
            $stmt_reset->execute();
        }

        $stmt = $conn->prepare("INSERT INTO addresses (member_id, full_address, city, district, is_default) 
                                VALUES (?, ?, ?, ?, ?)");
        if(!$stmt){ throw new Exception($conn->error); }
        $stmt->bind_param("isssi", $member_id, $full_address, $city, $district, $is_default);
        $stmt->execute();

        echo json_encode(['success' => true, 'message' => 'Đã thêm địa chỉ mới!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: '.$e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
