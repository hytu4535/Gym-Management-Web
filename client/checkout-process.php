<?php
session_start();
// TODO: Kiểm tra đăng nhập
// TODO: Kiểm tra request method = POST
// TODO: Validate dữ liệu từ form

require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // TODO: Implement checkout process
    
    // 1. Lấy thông tin từ form
    $address_id = $_POST['address_id'] ?? null;
    $new_address = $_POST['new_address'] ?? null;
    $city = $_POST['city'] ?? null;
    $district = $_POST['district'] ?? null;
    $note = $_POST['note'] ?? '';
    $payment_method = $_POST['payment_method'] ?? 'cash';
    
    // 2. Xử lý địa chỉ (nếu là địa chỉ mới thì insert vào bảng addresses)
    
    // 3. Lấy thông tin giỏ hàng
    
    // 4. Tính tổng tiền
    
    // 5. Tạo order mới trong database
    // INSERT INTO orders (member_id, total_amount, payment_method, status, created_at)
    
    // 6. Tạo order_items từ cart_items
    // INSERT INTO order_items (order_id, product_id, quantity, price)
    
    // 7. Xóa giỏ hàng sau khi đặt hàng thành công
    // DELETE FROM carts WHERE member_id = ?
    
    // 8. Redirect đến trang invoice
    $order_id = 1; // TODO: Get actual order_id after insert
    header('Location: invoice.php?order_id=' . $order_id);
    exit();
} else {
    header('Location: cart.php');
    exit();
}
?>
