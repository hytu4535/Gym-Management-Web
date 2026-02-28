<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập!', 'redirect' => 'login.php']);
        exit();
    }
    
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1; 
    }

    $user_id = $_SESSION['user_id']; 

    try {
        $stmt_member = $conn->prepare("SELECT id FROM members WHERE users_id = ?");
        $stmt_member->bind_param("i", $user_id);
        $stmt_member->execute();
        $member_id = $stmt_member->get_result()->fetch_assoc()['id'] ?? 0;
        if (!$member_id) throw new Exception("Không tìm thấy thông tin hội viên.");

        $stmt_stock = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
        $stmt_stock->bind_param("i", $product_id);
        $stmt_stock->execute();
        $product = $stmt_stock->get_result()->fetch_assoc();
        if (!$product) throw new Exception("Sản phẩm không tồn tại.");
        $stmt_cart_id = $conn->prepare("SELECT id FROM carts WHERE member_id = ? AND status = 'active' LIMIT 1");
        $stmt_cart_id->bind_param("i", $member_id);
        $stmt_cart_id->execute();
        $cart_res = $stmt_cart_id->get_result()->fetch_assoc();

        if ($cart_res) {
            $cart_id = $cart_res['id'];
        } else {
            $stmt_new_cart = $conn->prepare("INSERT INTO carts (member_id, status) VALUES (?, 'active')");
            $stmt_new_cart->bind_param("i", $member_id);
            $stmt_new_cart->execute();
            $cart_id = $conn->insert_id;
        }

        $item_type = 'product';
        $stmt_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?");
        $stmt_item->bind_param("isi", $cart_id, $item_type, $product_id);
        $stmt_item->execute();
        $item_res = $stmt_item->get_result()->fetch_assoc();

        if ($item_res) {
            $new_qty = $item_res['quantity'] + $quantity;
            if ($new_qty > $product['stock_quantity']) throw new Exception("Vượt quá tồn kho!");
            
            $stmt_up = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt_up->bind_param("ii", $new_qty, $item_res['id']);
            $stmt_up->execute();
        } else {
            if ($quantity > $product['stock_quantity']) throw new Exception("Vượt quá tồn kho!");
            
            $stmt_in = $conn->prepare("INSERT INTO cart_items (cart_id, item_type, item_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt_in->bind_param("isii", $cart_id, $item_type, $product_id, $quantity);
            $stmt_in->execute();
        }

        $stmt_count = $conn->prepare("SELECT SUM(quantity) as total FROM cart_items WHERE cart_id = ?");
        $stmt_count->bind_param("i", $cart_id);
        $stmt_count->execute();
        $cart_count = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;

        echo json_encode(['success' => true, 'message' => 'Đã thêm vào giỏ!', 'cart_count' => (int)$cart_count]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>