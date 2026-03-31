<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_SALES', 'edit');

require_once '../../config/db.php';

if (isset($_POST['btn_update_order'])) {
    $id = (int)$_POST['id'];
    $new_status = $_POST['status'];
    $currentUserId = (int) ($_SESSION['user_id'] ?? $_SESSION['admin_user_id'] ?? 0);

    if ($currentUserId <= 0) {
        echo "<script>alert('Không tìm thấy tài khoản đang đăng nhập!'); window.history.back();</script>";
        exit();
    }

    $conn->begin_transaction();

    try {
        $checkStmt = $conn->prepare("SELECT status, member_id FROM orders WHERE id = ? FOR UPDATE");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            $conn->rollback();
            echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href = '../orders.php';</script>";
            exit();
        }

        $orderRow = $checkResult->fetch_assoc();
        $current_status = $orderRow['status'];
        $memberId = (int) ($orderRow['member_id'] ?? 0);

        $valid_update = false;
        $confirmHandled = false;
        $isDeliverAction = ($new_status === 'delivered');

        if ($current_status == $new_status) {
            $valid_update = true;
        } elseif ($current_status == 'pending') {
            if ($new_status == 'confirmed' || $new_status == 'cancelled') {
                $valid_update = true;
                $confirmHandled = ($new_status === 'confirmed');
            }
        } elseif ($current_status == 'confirmed') {
            if ($new_status == 'delivered' || $new_status == 'cancelled') {
                $valid_update = true;
            }
        } elseif ($current_status == 'delivered' || $current_status == 'cancelled') {
            $valid_update = false;
        }

        if (!$valid_update) {
            $conn->rollback();
            echo "<script>
                    alert('Không thể cập nhật trạng thái! Trạng thái đơn hàng chỉ có thể cập nhật xuôi:\n- Chờ xử lý → Đã xác nhận / Đã hủy\n- Đã xác nhận → Đã giao / Đã hủy\n- Đã giao và Đã hủy không thể thay đổi');
                    window.history.back();
                  </script>";
            exit();
        }

        if ($confirmHandled) {
            $updateStmt = $conn->prepare("UPDATE orders SET status = 'confirmed', confirmed_by = ? WHERE id = ? AND status = 'pending'");
            $updateStmt->bind_param("ii", $currentUserId, $id);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                $conn->rollback();
                echo "<script>alert('Đơn hàng đã được xử lý trước đó hoặc không còn ở trạng thái chờ xử lý!'); window.history.back();</script>";
                exit();
            }
        } elseif ($isDeliverAction) {
            if ($current_status !== 'confirmed') {
                $conn->rollback();
                echo "<script>alert('Chỉ đơn hàng đã xác nhận mới được xuất hàng!'); window.history.back();</script>";
                exit();
            }

            $updateStmt = $conn->prepare("UPDATE orders SET status = 'delivered', handled_by = ? WHERE id = ? AND status = 'confirmed'");
            $updateStmt->bind_param("ii", $currentUserId, $id);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                $conn->rollback();
                echo "<script>alert('Đơn hàng đã được xử lý trước đó hoặc không còn ở trạng thái xác nhận!'); window.history.back();</script>";
                exit();
            }

            $itemsStmt = $conn->prepare("SELECT item_type, item_id, quantity FROM order_items WHERE order_id = ?");
            $itemsStmt->bind_param("i", $id);
            $itemsStmt->execute();
            $itemsResult = $itemsStmt->get_result();

            $updateProductStockStmt = $conn->prepare("UPDATE products SET stock_quantity = COALESCE(stock_quantity, 0) - ? WHERE id = ?");

            while ($item = $itemsResult->fetch_assoc()) {
                if (($item['item_type'] ?? '') !== 'product') {
                    continue;
                }

                $quantity = (int) ($item['quantity'] ?? 0);
                $productId = (int) ($item['item_id'] ?? 0);

                if ($quantity <= 0 || $productId <= 0) {
                    continue;
                }

                $updateProductStockStmt->bind_param("ii", $quantity, $productId);
                $updateProductStockStmt->execute();

                if ($updateProductStockStmt->affected_rows === 0) {
                    $conn->rollback();
                    echo "<script>alert('Không cập nhật được tồn kho sản phẩm.'); window.history.back();</script>";
                    exit();
                }
            }
        } else {
            $updateStmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ? AND status = ?");
            $updateStmt->bind_param("sis", $new_status, $id, $current_status);
            $updateStmt->execute();

            if ($updateStmt->affected_rows === 0) {
                $conn->rollback();
                echo "<script>alert('Đơn hàng đã được xử lý trước đó hoặc trạng thái không hợp lệ!'); window.history.back();</script>";
                exit();
            }
        }

        if ($memberId > 0) {
            $spentStmt = $conn->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_spent FROM orders WHERE member_id = ? AND status IN ('confirmed', 'delivered')");
            $spentStmt->bind_param("i", $memberId);
            $spentStmt->execute();
            $spentRow = $spentStmt->get_result()->fetch_assoc();
            $spentStmt->close();
            $totalSpent = (float) ($spentRow['total_spent'] ?? 0);

            $tierStmt = $conn->prepare("SELECT id FROM member_tiers WHERE min_spent <= ? ORDER BY min_spent DESC LIMIT 1");
            $tierStmt->bind_param("d", $totalSpent);
            $tierStmt->execute();
            $tierResult = $tierStmt->get_result();
            $tierRow = $tierResult ? $tierResult->fetch_assoc() : null;
            $tierStmt->close();

            $tierId = (int) ($tierRow['id'] ?? 1);

            $updateMemberStmt = $conn->prepare("UPDATE members SET total_spent = ?, tier_id = ? WHERE id = ?");
            $updateMemberStmt->bind_param("dii", $totalSpent, $tierId, $memberId);
            $updateMemberStmt->execute();
            $updateMemberStmt->close();
        }

        $conn->commit();

        echo "<script>
                alert('Cập nhật trạng thái đơn hàng thành công!');
                window.location.href = '../orders.php';
              </script>";
    } catch (Exception $e) {
        $conn->rollback();
        echo "<script>
                alert('Lỗi: " . addslashes($e->getMessage()) . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../orders.php");
    exit();
}
?>