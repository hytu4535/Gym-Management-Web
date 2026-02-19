<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $sql = "DELETE FROM products WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa sản phẩm thành công!'); window.location.href='../products.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa: " . $conn->error . "'); window.location.href='../products.php';</script>";
    }
}
?>