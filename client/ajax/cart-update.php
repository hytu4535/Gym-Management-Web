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
    $action = isset($_POST['action']) ? $_POST['action'] : 'set'; 
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
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
        $member_id = $stmt_member->get_result()->fetch_assoc()['id'] ?? 0;
        $stmt_member->close();

        if (!$member_id) throw new Exception("Không tìm thấy thông tin hội viên.");

        $stmt_cart = $conn->prepare("SELECT id FROM carts WHERE member_id = ? AND status = 'active' LIMIT 1");
        $stmt_cart->bind_param("i", $member_id);
        $stmt_cart->execute();
        $cart = $stmt_cart->get_result()->fetch_assoc();
        $stmt_cart->close();

        if (!$cart) throw new Exception("Không tìm thấy giỏ hàng của bạn!");
        $cart_id = $cart['id'];

        $item_type = 'product';
        $stmt_info = $conn->prepare("
            SELECT ci.quantity as cart_qty, p.stock_quantity, p.selling_price 
            FROM cart_items ci 
            JOIN products p ON ci.item_id = p.id 
            WHERE ci.cart_id = ? AND ci.item_type = ? AND ci.item_id = ?
        ");
        $stmt_info->bind_param("isi", $cart_id, $item_type, $product_id);
        $stmt_info->execute();
        $res_info = $stmt_info->get_result();
        
        if ($res_info->num_rows === 0) {
            throw new Exception("Sản phẩm không có trong giỏ hàng!");
        }
        
        $info = $res_info->fetch_assoc();
        $cart_qty = $info['cart_qty'];
        $stock_quantity = $info['stock_quantity'];
        $selling_price = $info['selling_price'];
        $stmt_info->close();
    
        $new_quantity = $cart_qty;
        if ($action === 'increase') {
            $new_quantity = $cart_qty + 1;
        } elseif ($action === 'decrease') {
            $new_quantity = $cart_qty - 1;
        } else { 
            $new_quantity = $quantity; 
        }
        
        $item_subtotal = 0;

        if ($new_quantity <= 0) {
            $stmt_del = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?");
            $stmt_del->bind_param("isi", $cart_id, $item_type, $product_id);
            $stmt_del->execute();
            $stmt_del->close();
        } else {
            if ($new_quantity > $stock_quantity) {
                throw new Exception("Vượt quá hàng tồn kho (Chỉ còn " . $stock_quantity . " sản phẩm)!");
            }

            $stmt_update = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE cart_id = ? AND item_type = ? AND item_id = ?");
            $stmt_update->bind_param("iisi", $new_quantity, $cart_id, $item_type, $product_id);
            $stmt_update->execute();
            $stmt_update->close();
            
            $item_subtotal = $new_quantity * $selling_price; 
        }

        $stmt_total = $conn->prepare("
            SELECT SUM(ci.quantity * p.selling_price) as total 
            FROM cart_items ci 
            JOIN products p ON ci.item_id = p.id 
            WHERE ci.cart_id = ? AND ci.item_type = ?
        ");
        $stmt_total->bind_param("is", $cart_id, $item_type);
        $stmt_total->execute();
        $res_total = $stmt_total->get_result();
        $row_total = $res_total->fetch_assoc();
        
        $cart_total = $row_total['total'] ?? 0;
        $stmt_total->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Đã cập nhật giỏ hàng!',
            'cart_total' => (float)$cart_total,
            'item_subtotal' => (float)$item_subtotal,
            'new_quantity' => (int)$new_quantity
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức yêu cầu không hợp lệ!'
    ]);
}
?>