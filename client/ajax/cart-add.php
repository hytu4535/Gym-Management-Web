<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';

function jsonResponse($success, $message, $extra = []) {
    echo json_encode(array_merge([
        'success' => $success,
        'message' => $message,
    ], $extra));
    exit();
}

function getMemberIdByUser($conn, $user_id) {
    $stmt_member = $conn->prepare("SELECT id FROM members WHERE users_id = ?");
    $stmt_member->bind_param("i", $user_id);
    $stmt_member->execute();
    $member_id = $stmt_member->get_result()->fetch_assoc()['id'] ?? 0;
    $stmt_member->close();

    return (int) $member_id;
}

function getOrCreateActiveCart($conn, $member_id) {
    $stmt_cart_id = $conn->prepare("SELECT id FROM carts WHERE member_id = ? AND status = 'active' LIMIT 1");
    $stmt_cart_id->bind_param("i", $member_id);
    $stmt_cart_id->execute();
    $cart_res = $stmt_cart_id->get_result()->fetch_assoc();
    $stmt_cart_id->close();

    if ($cart_res) {
        return (int) $cart_res['id'];
    }

    $stmt_new_cart = $conn->prepare("INSERT INTO carts (member_id, status) VALUES (?, 'active')");
    $stmt_new_cart->bind_param("i", $member_id);
    $stmt_new_cart->execute();
    $cart_id = $conn->insert_id;
    $stmt_new_cart->close();

    return (int) $cart_id;
}

function getCartCount($conn, $cart_id) {
    $stmt_count = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as total FROM cart_items WHERE cart_id = ?");
    $stmt_count->bind_param("i", $cart_id);
    $stmt_count->execute();
    $cart_count = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();

    return (int) $cart_count;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(false, 'Vui lòng đăng nhập!', ['redirect' => 'login.php']);
    }

    $item_type = isset($_POST['item_type']) ? trim($_POST['item_type']) : 'product';
    if (!in_array($item_type, ['product', 'package', 'service', 'class'], true)) {
        jsonResponse(false, 'Loại mục không hợp lệ!');
    }

    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $package_id = isset($_POST['package_id']) ? (int)$_POST['package_id'] : 0;
    $service_id = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
    $item_id = $item_type === 'package' ? $package_id : ($item_type === 'service' ? $service_id : ($item_type === 'class' ? (int)($_POST['class_id'] ?? 0) : $product_id));
    $quantity = isset($_POST['quantity']) && is_numeric($_POST['quantity']) ? (int)$_POST['quantity'] : 1;
    if ($quantity < 1) {
        $quantity = 1;
    }
    if ($item_type === 'package') {
        $quantity = 1;
    }

    $user_id = (int) $_SESSION['user_id'];

    try {
        if ($item_id <= 0) {
            throw new Exception('Dữ liệu không hợp lệ.');
        }

        $member_id = getMemberIdByUser($conn, $user_id);
        if (!$member_id) {
            throw new Exception('Không tìm thấy thông tin hội viên.');
        }

        if ($item_type === 'product') {
            $stmt_product = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
            $stmt_product->bind_param("i", $item_id);
            $stmt_product->execute();
            $product = $stmt_product->get_result()->fetch_assoc();
            $stmt_product->close();

            if (!$product) {
                throw new Exception('Sản phẩm không tồn tại.');
            }
        } elseif ($item_type === 'package') {
            $stmt_package = $conn->prepare("SELECT id FROM membership_packages WHERE id = ? AND status = 'active'");
            $stmt_package->bind_param("i", $item_id);
            $stmt_package->execute();
            $package = $stmt_package->get_result()->fetch_assoc();
            $stmt_package->close();

            if (!$package) {
                throw new Exception('Gói tập không tồn tại.');
            }

            $stmt_registered = $conn->prepare("SELECT id FROM member_packages WHERE member_id = ? AND package_id = ? AND status = 'active' LIMIT 1");
            $stmt_registered->bind_param("ii", $member_id, $item_id);
            $stmt_registered->execute();
            $registered = $stmt_registered->get_result()->fetch_assoc();
            $stmt_registered->close();

            if ($registered) {
                throw new Exception('Bạn đang sử dụng gói tập này rồi.');
            }
        } elseif ($item_type === 'class') {
            $stmt_class = $conn->prepare("SELECT id, class_name, price_per_session FROM class_schedules WHERE id = ? AND status = 'active'");
            $stmt_class->bind_param("i", $item_id);
            $stmt_class->execute();
            $class = $stmt_class->get_result()->fetch_assoc();
            $stmt_class->close();

            if (!$class) {
                throw new Exception('Lớp tập không tồn tại hoặc đã ngưng hoạt động.');
            }
        } else {
            $stmt_service = $conn->prepare("SELECT id FROM services WHERE id = ? AND status = 'hoạt động'");
            $stmt_service->bind_param("i", $item_id);
            $stmt_service->execute();
            $service = $stmt_service->get_result()->fetch_assoc();
            $stmt_service->close();

            if (!$service) {
                throw new Exception('Dịch vụ không tồn tại hoặc đã ngưng hoạt động.');
            }
        }

        $cart_id = getOrCreateActiveCart($conn, $member_id);
        $stmt_item = $conn->prepare("SELECT id, quantity FROM cart_items WHERE cart_id = ? AND item_type = ? AND item_id = ?");
        $stmt_item->bind_param("isi", $cart_id, $item_type, $item_id);
        $stmt_item->execute();
        $item_res = $stmt_item->get_result()->fetch_assoc();
        $stmt_item->close();

        if ($item_res) {
            if ($item_type === 'package') {
                throw new Exception('Gói tập này đã có trong giỏ hàng.');
            }

            if ($item_type === 'service') {
                throw new Exception('Dịch vụ này đã có trong giỏ hàng.');
            }

            if ($item_type === 'class') {
                throw new Exception('Lớp tập này đã có trong giỏ hàng.');
            }

            $new_qty = $item_res['quantity'] + $quantity;
            if ($new_qty > $product['stock_quantity']) {
                throw new Exception('Vượt quá tồn kho!');
            }

            $stmt_up = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
            $stmt_up->bind_param("ii", $new_qty, $item_res['id']);
            $stmt_up->execute();
            $stmt_up->close();
        } else {
            if ($item_type === 'product' && $quantity > $product['stock_quantity']) {
                throw new Exception('Vượt quá tồn kho!');
            }

            $stmt_in = $conn->prepare("INSERT INTO cart_items (cart_id, item_type, item_id, quantity) VALUES (?, ?, ?, ?)");
            $stmt_in->bind_param("isii", $cart_id, $item_type, $item_id, $quantity);
            $stmt_in->execute();
            $stmt_in->close();
        }

        $cart_count = getCartCount($conn, $cart_id);
        if ($item_type === 'package') {
            $successMessage = 'Đã thêm gói tập vào giỏ hàng!';
        } elseif ($item_type === 'service') {
            $successMessage = 'Đã thêm dịch vụ vào giỏ hàng!';
        } elseif ($item_type === 'class') {
            $successMessage = 'Đã thêm lớp tập vào giỏ hàng!';
        } else {
            $successMessage = 'Đã thêm vào giỏ!';
        }

        jsonResponse(true, $successMessage, ['cart_count' => $cart_count]);
    } catch (Exception $e) {
        jsonResponse(false, $e->getMessage());
    }
}
?>