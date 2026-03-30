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
    
    $item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : 'product';
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $class_id = isset($_POST['class_id']) ? (int)$_POST['class_id'] : 0;
    $item_id = $item_type === 'package' ? $package_id : ($item_type === 'service' ? $service_id : ($item_type === 'class' ? $class_id : $product_id));
    $user_id = $_SESSION['user_id'];
    
    if (!in_array($item_type, ['product', 'package', 'service', 'class'], true) || $item_id <= 0) {
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
            
            $stmt_delete = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?");
            $stmt_delete->bind_param("isi", $cart_id, $item_type, $item_id);
            $stmt_delete->execute();
            $stmt_delete->close();
 
            $stmt_total = $conn->prepare("
                SELECT COALESCE(SUM(ci.quantity), 0) as total_items,
                       COALESCE(SUM(
                           CASE
                               WHEN ci.item_type = 'product' THEN ci.quantity * p.selling_price
                               WHEN ci.item_type = 'package' THEN ci.quantity * mp.price
                               WHEN ci.item_type = 'service' THEN ci.quantity * s.price
                               WHEN ci.item_type = 'class' THEN ci.quantity * cs.price_per_session
                               ELSE 0
                           END
                       ), 0) as total_price
                FROM cart_items ci 
                LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
                LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
                LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
                LEFT JOIN class_schedules cs ON ci.item_type = 'class' AND ci.item_id = cs.id
                WHERE ci.cart_id = ?
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
            'message' => $item_type === 'package' ? 'Đã xóa gói tập khỏi giỏ hàng!' : ($item_type === 'service' ? 'Đã xóa dịch vụ khỏi giỏ hàng!' : ($item_type === 'class' ? 'Đã xóa lớp tập khỏi giỏ hàng!' : 'Đã xóa sản phẩm khỏi giỏ hàng!')),
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