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

    // Lấy trạng thái hiện tại của đơn hàng
    $check_sql = "SELECT status FROM orders WHERE id = $id";
    $check_result = $conn->query($check_sql);
    
    if ($check_result->num_rows == 0) {
        echo "<script>
                alert('Đơn hàng không tồn tại!');
                window.location.href = '../orders.php';
              </script>";
        exit();
    }
    
    $current_status = $check_result->fetch_assoc()['status'];
    
    // Validate status progression
    $valid_update = false;
    
    if ($current_status == $new_status) {
        $valid_update = true; // Không thay đổi
    } elseif ($current_status == 'pending') {
        if ($new_status == 'confirmed' || $new_status == 'cancelled') {
            $valid_update = true;
        }
    } elseif ($current_status == 'confirmed') {
        if ($new_status == 'delivered' || $new_status == 'cancelled') {
            $valid_update = true;
        }
    } elseif ($current_status == 'delivered' || $current_status == 'cancelled') {
        $valid_update = false;
    }
    
    if (!$valid_update) {
        echo "<script>
                alert('Không thể cập nhật trạng thái! Trạng thái đơn hàng chỉ có thể cập nhật xuôi:\\n- Chờ xử lý → Đã xác nhận / Đã hủy\\n- Đã xác nhận → Đã giao / Đã hủy\\n- Đã giao và Đã hủy không thể thay đổi');
                window.history.back();
              </script>";
        exit();
    }

    // FIX LỖI Ở ĐÂY: Ưu tiên lấy session của Admin, nếu không có mới lấy user_id
    $handled_by = isset($_SESSION['admin_user_id']) ? (int)$_SESSION['admin_user_id'] : (isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'NULL'); 
    
    $sql = "UPDATE orders SET status = '$new_status', handled_by = $handled_by WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        $memberIdStmt = $conn->prepare("SELECT member_id FROM orders WHERE id = ? LIMIT 1");
        $memberIdStmt->bind_param("i", $id);
        $memberIdStmt->execute();
        $memberIdResult = $memberIdStmt->get_result();
        $memberRow = $memberIdResult ? $memberIdResult->fetch_assoc() : null;
        $memberIdStmt->close();

        if ($memberRow) {
            $memberId = (int) $memberRow['member_id'];

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

        echo "<script>
                alert('Cập nhật trạng thái đơn hàng thành công!');
                window.location.href = '../orders.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi: " . $conn->error . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../orders.php");
    exit();
}
?>