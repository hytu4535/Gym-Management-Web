<?php
session_start();
require_once '../../config/db.php';
require_once '../../includes/discount_helper.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!']);
    exit;
}

$user_id = $_SESSION['user_id'];
$promotion_id = isset($_POST['promotion_id']) ? (int)$_POST['promotion_id'] : 0;

// Nếu promotion_id = 0 thì xóa promotion đang áp dụng
if ($promotion_id == 0) {
    unset($_SESSION['selected_promotion']);
    echo json_encode(['success' => true, 'message' => 'Đã bỏ ưu đãi']);
    exit;
}

// Kiểm tra promotion có hợp lệ không
$promotion = getPromotionById($promotion_id, $conn);

if (!$promotion) {
    echo json_encode(['success' => false, 'message' => 'Ưu đãi không tồn tại hoặc đã hết hạn!']);
    exit;
}

// Kiểm tra tier có phù hợp không
$tier_info = getMemberTierDiscount($user_id, $conn);

if ($promotion['tier_id'] != $tier_info['tier_id']) {
    echo json_encode(['success' => false, 'message' => 'Ưu đãi này không dành cho hạng của bạn!']);
    exit;
}

// Lưu vào session
$_SESSION['selected_promotion'] = $promotion_id;

echo json_encode([
    'success' => true, 
    'message' => 'Đã áp dụng ưu đãi: ' . $promotion['name']
]);
