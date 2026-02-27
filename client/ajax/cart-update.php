<?php
// TODO: Cập nhật số lượng sản phẩm trong giỏ hàng
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
    $action = $_POST['action'] ?? 'set'; // set, increase, decrease
    $quantity = $_POST['quantity'] ?? 1;
    $user_id = $_SESSION['user_id'];
    
    try {
        // TODO: Kiểm tra sản phẩm có trong giỏ không
        // SELECT * FROM carts WHERE member_id = ? AND product_id = ?
        
        if ($action === 'increase') {
            // UPDATE carts SET quantity = quantity + 1 WHERE member_id = ? AND product_id = ?
        } elseif ($action === 'decrease') {
            // UPDATE carts SET quantity = quantity - 1 WHERE member_id = ? AND product_id = ?
            // Nếu quantity <= 0 thì xóa luôn
            // DELETE FROM carts WHERE member_id = ? AND product_id = ? AND quantity <= 0
        } else {
            // UPDATE carts SET quantity = ? WHERE member_id = ? AND product_id = ?
        }
        
        // TODO: Tính lại tổng tiền
        // SELECT c.*, p.price, (c.quantity * p.price) as subtotal
        // FROM carts c
        // JOIN products p ON c.product_id = p.product_id
        // WHERE c.member_id = ?
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng!',
            'cart_total' => 0, // TODO: Real cart total
            'item_subtotal' => 0 // TODO: Item subtotal
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
