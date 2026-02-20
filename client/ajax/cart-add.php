<?php
// TODO: Thêm sản phẩm vào giỏ hàng
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Kiểm tra đăng nhập
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng!',
            'redirect' => 'login.php'
        ]);
        exit();
    }
    
    $product_id = $_POST['product_id'] ?? 0;
    $quantity = $_POST['quantity'] ?? 1;
    $user_id = $_SESSION['user_id'];
    
    // TODO: Validate
    if (empty($product_id) || $quantity <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ!'
        ]);
        exit();
    }
    
    try {
        // TODO: Kiểm tra sản phẩm tồn tại
        // SELECT * FROM products WHERE product_id = ?
        
        // TODO: Kiểm tra sản phẩm đã có trong giỏ chưa
        // SELECT * FROM carts WHERE member_id = ? AND product_id = ?
        
        // Nếu đã có thì UPDATE quantity
        // UPDATE carts SET quantity = quantity + ? WHERE member_id = ? AND product_id = ?
        
        // Nếu chưa có thì INSERT
        // INSERT INTO carts (member_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())
        
        // TODO: Lấy tổng số lượng sản phẩm trong giỏ
        // SELECT SUM(quantity) as total FROM carts WHERE member_id = ?
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã thêm sản phẩm vào giỏ hàng!',
            'cart_count' => 0 // TODO: Real cart count
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
