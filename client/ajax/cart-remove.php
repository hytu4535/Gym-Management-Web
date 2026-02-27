<?php
// TODO: Xóa sản phẩm khỏi giỏ hàng
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
    
    $product_id = $_POST['product_id'] ?? 0;
    $user_id = $_SESSION['user_id'];
    
    if (empty($product_id)) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ!'
        ]);
        exit();
    }
    
    try {
        // TODO: Xóa sản phẩm khỏi giỏ hàng
        // DELETE FROM carts WHERE member_id = ? AND product_id = ?
        
        // TODO: Tính lại tổng tiền và số lượng
        // SELECT COUNT(*) as count, SUM(c.quantity * p.price) as total
        // FROM carts c
        // JOIN products p ON c.product_id = p.product_id
        // WHERE c.member_id = ?
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!',
            'cart_count' => 0, // TODO: Real count
            'cart_total' => 0 // TODO: Real total
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
