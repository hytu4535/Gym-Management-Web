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

// Kiểm tra lớp tồn tại và còn chỗ
$stmt = $conn->prepare("SELECT id, capacity, enrolled_count FROM class_schedules WHERE id = ? AND status = 'active'");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    echo json_encode(['success' => false, 'message' => 'Lớp tập không tồn tại hoặc đã đóng!']);
    exit();
}

if ($class['enrolled_count'] >= $class['capacity']) {
    echo json_encode(['success' => false, 'message' => 'Lớp tập đã đầy!']);
    exit();
}

// Kiểm tra đã đăng ký chưa
$stmt = $conn->prepare("SELECT id, status FROM class_registrations WHERE member_id = ? AND class_id = ?");
$stmt->bind_param('ii', $member_id, $class_id);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($existing) {
    if ($existing['status'] === 'active') {
        echo json_encode(['success' => false, 'message' => 'Bạn đã đăng ký lớp này rồi!']);
        exit();
    }
    // Đã hủy trước đó → kích hoạt lại
    $stmt = $conn->prepare("UPDATE class_registrations SET status = 'active', registered_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $existing['id']);
    $stmt->execute();
    $stmt->close();
} else {
    $stmt = $conn->prepare("INSERT INTO class_registrations (member_id, class_id) VALUES (?, ?)");
    $stmt->bind_param('ii', $member_id, $class_id);
    $stmt->execute();
    $stmt->close();
}

// Tăng enrolled_count
$stmt = $conn->prepare("UPDATE class_schedules SET enrolled_count = enrolled_count + 1 WHERE id = ?");
$stmt->bind_param('i', $class_id);
$stmt->execute();
$stmt->close();

$conn->close();
echo json_encode(['success' => true, 'message' => 'Đăng ký lớp tập thành công!']);