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