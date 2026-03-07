<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập!'); window.location.href='login.php';</script>";
    exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: cart.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$use_new_address = isset($_POST['use_new_address']) ? (int)$_POST['use_new_address'] : 0;
$payment_method = $_POST['payment_method'] ?? 'cash';

$conn->begin_transaction();

try {
    $stmt_member = $conn->prepare("SELECT id FROM members WHERE users_id = ?");
    $stmt_member->bind_param("i", $user_id);
    $stmt_member->execute();
    $res_member = $stmt_member->get_result();
    
    if ($res_member->num_rows === 0) {
        throw new Exception("Không tìm thấy hồ sơ hội viên của bạn. Vui lòng cập nhật thông tin!");
    }
    $member_id = $res_member->fetch_assoc()['id'];
    $stmt_member->close();

    $address_id = 0;
    if ($use_new_address === 1) {
        $full_address = trim($_POST['new_address'] ?? '');
        $district = trim($_POST['district'] ?? '');
        $city = trim($_POST['city'] ?? '');
        
        if (empty($full_address) || empty($district) || empty($city)) {
            throw new Exception("Vui lòng nhập đầy đủ địa chỉ giao hàng mới.");
        }

        $stmt_insert_addr = $conn->prepare("INSERT INTO addresses (member_id, full_address, city, district, is_default) VALUES (?, ?, ?, ?, 0)");
        if ($stmt_insert_addr) {
             $stmt_insert_addr->bind_param("isss", $member_id, $full_address, $city, $district);
             $stmt_insert_addr->execute();
             $address_id = $conn->insert_id;
             $stmt_insert_addr->close();
        }
    } else {
        $posted_address = $_POST['address_id'] ?? '';
        if ($posted_address === 'default' || empty($posted_address)) {
            $address_id = 0; 
        } else {
            $address_id = (int)$posted_address;
        }
    }
    $stmt_cart = $conn->prepare("
        SELECT ci.item_id as product_id, ci.quantity, p.selling_price, p.stock_quantity, p.name, c.id as cart_id
        FROM carts c 
        JOIN cart_items ci ON c.id = ci.cart_id AND ci.item_type = 'product'
        JOIN products p ON ci.item_id = p.id 
        WHERE c.member_id = ? AND c.status = 'active' FOR UPDATE
    ");
    $stmt_cart->bind_param("i", $member_id); 
    $stmt_cart->execute();
    $cart_res = $stmt_cart->get_result();
    
    if ($cart_res->num_rows === 0) {
        throw new Exception("Giỏ hàng của bạn đang trống!");
    }
    
    $subtotal = 0;
    $cart_items = [];
    $cart_id = 0;
    
    while ($row = $cart_res->fetch_assoc()) {
        if ($row['quantity'] > $row['stock_quantity']) {
            throw new Exception("Sản phẩm '{$row['name']}' chỉ còn {$row['stock_quantity']} cái. Vui lòng giảm số lượng.");
        }
        $cart_items[] = $row;
        $subtotal += ($row['selling_price'] * $row['quantity']);
        $cart_id = $row['cart_id']; 
    }
    $stmt_cart->close();

    $shipping_fee = 30000;
    $total_amount = $subtotal + $shipping_fee;
    $status = 'pending';
    $stmt_order = $conn->prepare("
        INSERT INTO orders (member_id, address_id, total_amount, payment_method, status) 
        VALUES (?, NULLIF(?, 0), ?, ?, ?)
    ");
    $stmt_order->bind_param("iidss", $member_id, $address_id, $total_amount, $payment_method, $status);
    $stmt_order->execute();
    $order_id = $conn->insert_id;
    $stmt_order->close();

    $item_type = 'product';
    $stmt_item = $conn->prepare("
        INSERT INTO order_items (order_id, item_type, item_id, item_name, price, quantity) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    
    foreach ($cart_items as $item) {
        $stmt_item->bind_param("isisdi", $order_id, $item_type, $item['product_id'], $item['name'], $item['selling_price'], $item['quantity']);
        $stmt_item->execute();
        
        $stmt_update_stock->bind_param("ii", $item['quantity'], $item['product_id']);
        $stmt_update_stock->execute();
    }
    $stmt_item->close();
    $stmt_update_stock->close();

    $stmt_del_cart = $conn->prepare("UPDATE carts SET status = 'checked_out' WHERE id = ?");
    $stmt_del_cart->bind_param("i", $cart_id);
    $stmt_del_cart->execute();
    $stmt_del_cart->close();

    // Cập nhật total_spent cho member
    $stmt_update_spent = $conn->prepare("UPDATE members SET total_spent = total_spent + ? WHERE id = ?");
    $stmt_update_spent->bind_param("di", $total_amount, $member_id);
    $stmt_update_spent->execute();
    $stmt_update_spent->close();

    // Lấy total_spent mới để check nâng hạng
    $stmt_check_tier = $conn->prepare("SELECT total_spent, tier_id FROM members WHERE id = ?");
    $stmt_check_tier->bind_param("i", $member_id);
    $stmt_check_tier->execute();
    $member_info = $stmt_check_tier->get_result()->fetch_assoc();
    $stmt_check_tier->close();

    // Tự động nâng hạng dựa trên total_spent
    if ($member_info) {
        $new_total_spent = $member_info['total_spent'];
        
        // Lấy hạng phù hợp với total_spent (hạng cao nhất mà member đủ điều kiện)
        $stmt_tier = $conn->prepare("
            SELECT id, name, level FROM member_tiers 
            WHERE min_spent <= ? AND status = 'active'
            ORDER BY level DESC LIMIT 1
        ");
        $stmt_tier->bind_param("d", $new_total_spent);
        $stmt_tier->execute();
        $tier_result = $stmt_tier->get_result();
        
        if ($tier_result->num_rows > 0) {
            $new_tier = $tier_result->fetch_assoc();
            
            // Nếu hạng mới khác hạng hiện tại thì cập nhật
            if ($new_tier['id'] != $member_info['tier_id']) {
                $stmt_update_tier = $conn->prepare("UPDATE members SET tier_id = ? WHERE id = ?");
                $stmt_update_tier->bind_param("ii", $new_tier['id'], $member_id);
                $stmt_update_tier->execute();
                $stmt_update_tier->close();
            }
        }
        $stmt_tier->close();
    }

    $conn->commit();
    header("Location: invoice.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    $conn->rollback(); 
    
    $error_msg = $e->getMessage();
    echo "<script>alert('Lỗi đặt hàng: " . addslashes($error_msg) . "'); window.location.href='cart.php';</script>";
    exit();
}
?>