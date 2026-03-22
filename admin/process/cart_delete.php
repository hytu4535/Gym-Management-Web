<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("DELETE FROM cart_items WHERE cart_id = $id");
    $sql_cart = "DELETE FROM carts WHERE id = $id";

    if ($conn->query($sql_cart) === TRUE) {
        echo "<script>alert('Đã xóa giỏ hàng thành công!'); window.location.href='../carts.php';</script>";
    } else {
        echo "<script>alert('Lỗi: " . $conn->error . "'); window.location.href='../carts.php';</script>";
    }
} else {
    header("Location: ../carts.php");
}
?>