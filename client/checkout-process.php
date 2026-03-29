<?php
session_start();
require_once '../config/db.php';
require_once '../includes/discount_helper.php';

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

    $stmt_cart = $conn->prepare("
        SELECT ci.item_type,
               ci.item_id,
               ci.quantity,
               p.selling_price,
               p.stock_quantity,
               p.name,
               mp.package_name,
               mp.price AS package_price,
               mp.duration_months,
               s.name AS service_name,
               s.price AS service_price,
               cs.class_name,
               cs.price_per_session AS class_price,
               cs.schedule_days AS class_schedule_days,
               cs.schedule_start_time AS class_start_time,
               cs.schedule_end_time AS class_end_time,
               c.id as cart_id
        FROM carts c 
        JOIN cart_items ci ON c.id = ci.cart_id
        LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id 
        LEFT JOIN membership_packages mp ON ci.item_type = 'package' AND ci.item_id = mp.id
        LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
        LEFT JOIN class_schedules cs ON ci.item_type = 'class' AND ci.item_id = cs.id
        WHERE c.member_id = ? AND c.status = 'active' FOR UPDATE
    ");
    $stmt_cart->bind_param("i", $member_id); 
    $stmt_cart->execute();
    $cart_res = $stmt_cart->get_result();
    
    if ($cart_res->num_rows === 0) {
        throw new Exception("Giỏ hàng của bạn đang trống!");
    }
    
    $cart_items = [];
    $cart_id = 0;
    $hasPhysicalProducts = false;
    
    while ($row = $cart_res->fetch_assoc()) {
        if ($row['item_type'] === 'product') {
            $hasPhysicalProducts = true;

            if ($row['quantity'] > $row['stock_quantity']) {
                throw new Exception("Sản phẩm '{$row['name']}' chỉ còn {$row['stock_quantity']} cái. Vui lòng giảm số lượng.");
            }

            // Lấy cả giá gốc và % giảm giá từ helper
            $price_info = calculateDiscountedPrice($row['selling_price'], $user_id, $conn);
            $row['original_price'] = $price_info['original_price'];
            $row['discount_percent'] = $price_info['discount_percent'];
            $row['item_name'] = $row['name'];
        } elseif ($row['item_type'] === 'package') {
            $row['quantity'] = 1;
            $row['original_price'] = (float) $row['package_price'];
            $row['discount_percent'] = 0;
            $row['item_name'] = $row['package_name'];
        } elseif ($row['item_type'] === 'class') {
            $row['quantity'] = 1;
            $row['original_price'] = (float) $row['class_price'];
            $row['discount_percent'] = 0;
            $row['item_name'] = $row['class_name'];
        } else {
            $row['quantity'] = 1;
            $row['original_price'] = (float) $row['service_price'];
            $row['discount_percent'] = 0;
            $row['item_name'] = $row['service_name'];
        }
        
        $cart_items[] = $row;
        $cart_id = $row['cart_id']; 
    }
    $stmt_cart->close();

    $address_id = 0;
    if ($hasPhysicalProducts) {
        if ($use_new_address === 1) {
            $full_address = trim($_POST['new_address'] ?? '');
            $district = trim($_POST['district'] ?? '');
            $city = trim($_POST['city'] ?? '');
            
            if (empty($full_address) || empty($district) || empty($city)) {
                throw new Exception("Vui lòng nhập đầy đủ địa chỉ giao hàng mới.");
            }

            $existing_default_id = 0;
            $stmt_default_addr = $conn->prepare("SELECT id FROM addresses WHERE member_id = ? AND is_default = 1 LIMIT 1");
            $stmt_default_addr->bind_param("i", $member_id);
            $stmt_default_addr->execute();
            $default_addr_res = $stmt_default_addr->get_result()->fetch_assoc();
            if ($default_addr_res) {
                $existing_default_id = (int) $default_addr_res['id'];
            }
            $stmt_default_addr->close();

            $new_is_default = $existing_default_id > 0 ? 0 : 1;

            $stmt_insert_addr = $conn->prepare("INSERT INTO addresses (member_id, full_address, city, district, is_default) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt_insert_addr) {
                throw new Exception("Không thể lưu địa chỉ giao hàng mới.");
            }
            $stmt_insert_addr->bind_param("isssi", $member_id, $full_address, $city, $district, $new_is_default);
            $stmt_insert_addr->execute();
            $address_id = (int) $conn->insert_id;
            $stmt_insert_addr->close();
        } else {
            $posted_address_id = isset($_POST['address_id']) ? (int) $_POST['address_id'] : 0;

            if ($posted_address_id > 0) {
                $stmt_check_addr = $conn->prepare("SELECT id FROM addresses WHERE id = ? AND member_id = ? LIMIT 1");
                $stmt_check_addr->bind_param("ii", $posted_address_id, $member_id);
                $stmt_check_addr->execute();
                $checked_addr = $stmt_check_addr->get_result()->fetch_assoc();
                $stmt_check_addr->close();

                if (!$checked_addr) {
                    throw new Exception("Địa chỉ giao hàng đã chọn không hợp lệ.");
                }
                $address_id = (int) $checked_addr['id'];
            } else {
                $stmt_default_or_latest = $conn->prepare("SELECT id FROM addresses WHERE member_id = ? ORDER BY is_default DESC, id DESC LIMIT 1");
                $stmt_default_or_latest->bind_param("i", $member_id);
                $stmt_default_or_latest->execute();
                $fallback_addr = $stmt_default_or_latest->get_result()->fetch_assoc();
                $stmt_default_or_latest->close();

                if (!$fallback_addr) {
                    throw new Exception("Bạn chưa có địa chỉ giao hàng. Vui lòng nhập địa chỉ mới.");
                }
                $address_id = (int) $fallback_addr['id'];
            }
        }
    }
    
    // =====================================================================
    // LOGIC TÍNH TIỀN CHUẨN XÁC: Tạm tính - Tổng Giảm + Phí Ship = Total
    // =====================================================================
    $selected_promotion_id = isset($_POST['promotion']) ? (int)$_POST['promotion'] : (isset($_POST['selected_promotion_id']) ? (int)$_POST['selected_promotion_id'] : (isset($_SESSION['selected_promotion']) ? (int)$_SESSION['selected_promotion'] : 0));
    
    // Sử dụng hàm helper để tính nhất quán với màn hình Cart
    $cart_total = calculateCartTotal($user_id, $conn, $selected_promotion_id);
    
    // 1. Tạm tính (Tổng giá gốc)
    $subtotal_items = $cart_total['subtotal_original'];

    // 2. Tổng giảm giá (Tier discount + Promo discount)
    $total_discount = $cart_total['base_discount_amount'] + $cart_total['promotion_discount'];

    // 3. Phí vận chuyển
    $shipping_fee = $hasPhysicalProducts ? 30000 : 0;

    // 4. Tổng thanh toán cuối cùng
    $total_amount = $subtotal_items - $total_discount + $shipping_fee;
    // =====================================================================

    $status = 'pending';
    $order_note = trim($_POST['note'] ?? '');
    if ($order_note !== '') {
        $order_note = mb_substr($order_note, 0, 2000, 'UTF-8');
    } else {
        $order_note = null;
    }

    $transfer_code = null;
    $proof_img = null;

    if ($payment_method === 'bank_transfer') {
        $transfer_code = trim($_POST['transfer_code'] ?? '');

        if (empty($transfer_code)) {
            throw new Exception("Nội dung chuyển khoản không được để trống.");
        }

        if (!isset($_FILES['proof_image']) || $_FILES['proof_image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Vui lòng upload ảnh biên lai thanh toán.");
        }

        $file = $_FILES['proof_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception("Chỉ chấp nhận file ảnh JPG, JPEG, PNG, WEBP.");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_name = 'order_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_dir = 'assets/uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $upload_path = $upload_dir . $new_name;
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            throw new Exception("Lỗi upload file ảnh.");
        }
        $proof_img = $new_name;
    }

    $stmt_order = $conn->prepare(
           "INSERT INTO orders (member_id, address_id, total_amount, payment_method, status, transfer_code, proof_img, note) 
            VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?)"
    );
    $stmt_order->bind_param("iidsssss", $member_id, $address_id, $total_amount, $payment_method, $status, $transfer_code, $proof_img, $order_note);
    $stmt_order->execute();
    $order_id = $conn->insert_id;
    $stmt_order->close();

    $stmt_item = $conn->prepare("
        INSERT INTO order_items (order_id, item_type, item_id, item_name, price, quantity, discount) 
           VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt_update_stock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
    
    foreach ($cart_items as $item) {
        $item_type = $item['item_type'];
        $item_id = (int) $item['item_id'];
        $item_name = $item['item_name'];
        $item_quantity = (int) $item['quantity'];
        
        // Lưu giá gốc và phần trăm giảm giá để tương thích với invoice.php
        $original_price = $item['original_price'];
        $discount_percent = $item['discount_percent']; 

        $stmt_item->bind_param("isisdid", $order_id, $item_type, $item_id, $item_name, $original_price, $item_quantity, $discount_percent);
        $stmt_item->execute();

        if ($item_type === 'product') {
            $stmt_update_stock->bind_param("ii", $item_quantity, $item_id);
            $stmt_update_stock->execute();
            continue;
        }

        if ($item_type === 'package') {
            $startDate = new DateTime('today');
            $endDate = (clone $startDate)->modify('+' . (int) $item['duration_months'] . ' month');

            $stmt_package = $conn->prepare("INSERT INTO member_packages (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')");
            $startDateString = $startDate->format('Y-m-d');
            $endDateString = $endDate->format('Y-m-d');
            $stmt_package->bind_param("iiss", $member_id, $item_id, $startDateString, $endDateString);
            $stmt_package->execute();
            $stmt_package->close();
            continue;
        }

        if ($item_type === 'service') {
            $startDate = (new DateTime('today'))->format('Y-m-d');
            $stmt_service = $conn->prepare("INSERT INTO member_services (member_id, service_id, start_date, end_date, status) VALUES (?, ?, ?, NULL, 'còn hiệu lực')");
            $stmt_service->bind_param("iis", $member_id, $item_id, $startDate);
            $stmt_service->execute();
            $stmt_service->close();
            continue;
        }

        if ($item_type === 'class') {
            continue;
        }
    }
    $stmt_item->close();
    $stmt_update_stock->close();

    $stmt_del_cart = $conn->prepare("UPDATE carts SET status = 'checked_out' WHERE id = ?");
    $stmt_del_cart->bind_param("i", $cart_id);
    $stmt_del_cart->execute();
    $stmt_del_cart->close();
    
    // Lưu thông tin sử dụng mã ưu đãi (nếu có)
    if ($selected_promotion_id > 0 && $cart_total['has_promotion']) {
        $applied_amount = (float) $cart_total['promotion_discount'];
        $stmt_promo_usage = $conn->prepare("
            INSERT INTO promotion_usage (member_id, promotion_id, order_id, applied_amount, applied_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt_promo_usage->bind_param("iiid", $member_id, $selected_promotion_id, $order_id, $applied_amount);
        $stmt_promo_usage->execute();
        $stmt_promo_usage->close();
    }

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
    
    // Xóa promotion khỏi session sau khi đặt hàng thành công
    unset($_SESSION['selected_promotion']);
    
    header("Location: invoice.php?order_id=" . $order_id);
    exit();

} catch (Exception $e) {
    $conn->rollback(); 
    
    $error_msg = $e->getMessage();
    echo "<script>alert('Lỗi đặt hàng: " . addslashes($error_msg) . "'); window.location.href='cart.php';</script>";
    exit();
}
?>