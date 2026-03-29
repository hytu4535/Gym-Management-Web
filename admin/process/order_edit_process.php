<?php
session_start();
require_once '../../config/db.php';

if (isset($_POST['btn_update_order'])) {
    $id = (int)$_POST['id'];
    $new_status = $_POST['status'];
    $conn->begin_transaction();

    try {
        $check_sql = "SELECT status, member_id, total_amount FROM orders WHERE id = ? FOR UPDATE";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("i", $id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        
        if ($check_result->num_rows == 0) {
            throw new Exception('Đơn hàng không tồn tại!');
        }
        
        $order_data = $check_result->fetch_assoc();
        $current_status = $order_data['status'];
        $member_id = (int)$order_data['member_id'];
        $total_amount = (float)$order_data['total_amount'];
        $stmt_check->close();
        $valid_update = false;
        
        if ($current_status == $new_status) {
            $valid_update = true; 
        } elseif ($current_status == 'pending') {
            if ($new_status == 'confirmed' || $new_status == 'cancelled') $valid_update = true;
        } elseif ($current_status == 'confirmed') {
            if ($new_status == 'delivered' || $new_status == 'cancelled') $valid_update = true;
        } elseif ($current_status == 'delivered' || $current_status == 'cancelled') {
            $valid_update = false; 
        }
        
        if (!$valid_update) {
            throw new Exception('Không thể cập nhật trạng thái! Trạng thái đơn hàng chỉ có thể cập nhật xuôi:\n- Chờ xử lý → Đã xác nhận / Đã hủy\n- Đã xác nhận → Đã giao / Đã hủy\n- Đã giao và Đã hủy không thể thay đổi');
        }
        if ($current_status !== $new_status) {
            $stmt_update = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt_update->bind_param("si", $new_status, $id);
            $stmt_update->execute();
            $stmt_update->close();
            if ($new_status === 'delivered' && $member_id > 0) {
                $stmt_spent = $conn->prepare("UPDATE members SET total_spent = total_spent + ? WHERE id = ?");
                $stmt_spent->bind_param("di", $total_amount, $member_id);
                $stmt_spent->execute();
                $stmt_spent->close();
                $stmt_get_spent = $conn->prepare("SELECT total_spent, tier_id FROM members WHERE id = ?");
                $stmt_get_spent->bind_param("i", $member_id);
                $stmt_get_spent->execute();
                $member_info = $stmt_get_spent->get_result()->fetch_assoc();
                $stmt_get_spent->close();

                if ($member_info) {
                    $new_total_spent = $member_info['total_spent'];
                    $stmt_tier = $conn->prepare("
                        SELECT id FROM member_tiers 
                        WHERE min_spent <= ? AND status = 'active'
                        ORDER BY level DESC LIMIT 1
                    ");
                    $stmt_tier->bind_param("d", $new_total_spent);
                    $stmt_tier->execute();
                    $tier_res = $stmt_tier->get_result();
                    
                    if ($tier_res->num_rows > 0) {
                        $new_tier = $tier_res->fetch_assoc();
                        if ($new_tier['id'] != $member_info['tier_id']) {
                            $stmt_upg = $conn->prepare("UPDATE members SET tier_id = ? WHERE id = ?");
                            $stmt_upg->bind_param("ii", $new_tier['id'], $member_id);
                            $stmt_upg->execute();
                            $stmt_upg->close();
                        }
                    }
                    $stmt_tier->close();
                }
            }


            if ($new_status === 'cancelled') {
                $stmt_items = $conn->prepare("SELECT item_id, quantity FROM order_items WHERE order_id = ? AND item_type = 'product'");
                $stmt_items->bind_param("i", $id);
                $stmt_items->execute();
                $items = $stmt_items->get_result();
                
                $stmt_restock = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ?");
                while ($item = $items->fetch_assoc()) {
                    $stmt_restock->bind_param("ii", $item['quantity'], $item['item_id']);
                    $stmt_restock->execute();
                }
                $stmt_items->close();
                $stmt_restock->close();
            }
        }

        $conn->commit();
        echo "<script>
                alert('Cập nhật trạng thái đơn hàng thành công!');
                window.location.href = '../orders.php';
              </script>";

    } catch (Exception $e) {
        $conn->rollback();
        $error_msg = addslashes($e->getMessage());
        echo "<script>
                alert('{$error_msg}');
                window.history.back();
              </script>";
    }

} else {
    header("Location: ../orders.php");
    exit();
}
?>