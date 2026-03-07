<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng đăng nhập để thực hiện thao tác này!'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Phương thức không hợp lệ!'
    ]);
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;

if ($order_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Mã đơn hàng không hợp lệ!'
    ]);
    exit();
}

$conn->begin_transaction();

try {
    $stmt_member = $conn->prepare("SELECT id FROM members WHERE users_id = ?");
    $stmt_member->bind_param("i", $user_id);
    $stmt_member->execute();
    $res_member = $stmt_member->get_result();
    
    if ($res_member->num_rows === 0) {
        throw new Exception("Không tìm thấy thông tin hội viên.");
    }
    $member_id = $res_member->fetch_assoc()['id'];
    $stmt_member->close();

    $stmt_order = $conn->prepare("SELECT status FROM orders WHERE id = ? AND member_id = ? FOR UPDATE");
    $stmt_order->bind_param("ii", $order_id, $member_id);
    $stmt_order->execute();
    $res_order = $stmt_order->get_result();

    if ($res_order->num_rows === 0) {
        throw new Exception("Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn này!");
    }

    $order = $res_order->fetch_assoc();
    if ($order['status'] !== 'pending') {
        throw new Exception("Đơn hàng đã được xác nhận hoặc đang giao, không thể hủy vào lúc này!");
    }
    $stmt_order->close();

    $stmt_cancel = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
    $stmt_cancel->bind_param("i", $order_id);
    $stmt_cancel->execute();
    $stmt_cancel->close();

    $stmt_items = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND item_type = 'product'");
    $stmt_items->bind_param("i", $order_id);
    $stmt_items->execute();
    $res_items = $stmt_items->get_result();

    $stmt_restore_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
    
    while ($item = $res_items->fetch_assoc()) {
        $stmt_restore_stock->bind_param("ii", $item['quantity'], $item['item_id']);
        $stmt_restore_stock->execute();
    }
    
    $stmt_items->close();
    $stmt_restore_stock->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Hủy đơn hàng thành công! Sản phẩm đã được hoàn lại kho.'
    ]);

} catch (Exception $e) {
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>