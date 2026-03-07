<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Vui lòng đăng nhập!'
        ]);
        exit();
    }
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $user_id = $_SESSION['user_id'];
    
    if ($product_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Dữ liệu không hợp lệ!'
        ]);
        exit();
    }
    
    try {
        $stmt_member = $conn->prepare("SELECT id FROM members WHERE users_id = ?");
        $stmt_member->bind_param("i", $user_id);
        $stmt_member->execute();
        $member_res = $stmt_member->get_result();
        $member_id = $member_res->fetch_assoc()['id'] ?? 0;
        $stmt_member->close();

        if (!$member_id) {
            throw new Exception("Không tìm thấy thông tin hội viên.");
        }
        $stmt_cart = $conn->prepare("SELECT id FROM carts WHERE member_id = ? AND status = 'active' LIMIT 1");
        $stmt_cart->bind_param("i", $member_id);
        $stmt_cart->execute();
        $cart_res = $stmt_cart->get_result();
        $cart = $cart_res->fetch_assoc();
        $stmt_cart->close();

        $cart_count = 0;
        $cart_total = 0;

        if ($cart) {
            $cart_id = $cart['id'];
            
            $item_type = 'product';
            $stmt_delete = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?");
            $stmt_delete->bind_param("isi", $cart_id, $item_type, $product_id);
            $stmt_delete->execute();
            $stmt_delete->close();
 
            $stmt_total = $conn->prepare("
                SELECT SUM(ci.quantity) as total_items, SUM(ci.quantity * p.selling_price) as total_price 
                FROM cart_items ci 
                JOIN products p ON ci.item_id = p.id 
                WHERE ci.cart_id = ? AND ci.item_type = 'product'
            ");
            $stmt_total->bind_param("i", $cart_id);
            $stmt_total->execute();
            $res_total = $stmt_total->get_result();
            $row_total = $res_total->fetch_assoc();
            
            $cart_count = $row_total['total_items'] ?? 0;
            $cart_total = $row_total['total_price'] ?? 0;
            
            $stmt_total->close();
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã xóa sản phẩm khỏi giỏ hàng!',
            'cart_count' => (int)$cart_count,
            'cart_total' => (float)$cart_total
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
        'message' => 'Phương thức yêu cầu không hợp lệ!'
    ]);
}
?>