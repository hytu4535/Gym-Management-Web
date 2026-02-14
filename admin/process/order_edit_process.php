<?php
require_once '../../config/db.php';

if (isset($_POST['btn_update_order'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    $sql = "UPDATE orders SET status = '$status' WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
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