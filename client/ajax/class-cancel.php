<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit();
}

if (empty($_POST['class_id'])) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin lớp tập!']);
    exit();
}

require_once '../../config/db.php';

$member_id = intval($_SESSION['user_id']);
$class_id  = intval($_POST['class_id']);

// Kiểm tra đăng ký active
$stmt = $conn->prepare("SELECT id FROM class_registrations WHERE member_id = ? AND class_id = ? AND status = 'active'");
$stmt->bind_param('ii', $member_id, $class_id);
$stmt->execute();
$reg = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reg) {
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy đăng ký!']);
    exit();
}

// Hủy đăng ký
$stmt = $conn->prepare("UPDATE class_registrations SET status = 'cancelled' WHERE id = ?");
$stmt->bind_param('i', $reg['id']);
$stmt->execute();
$stmt->close();

// Giảm enrolled_count (không để âm)
$stmt = $conn->prepare("UPDATE class_schedules SET enrolled_count = GREATEST(0, enrolled_count - 1) WHERE id = ?");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$stmt->close();

$conn->close();
echo json_encode(['success' => true, 'message' => 'Hủy đăng ký lớp tập thành công!']);