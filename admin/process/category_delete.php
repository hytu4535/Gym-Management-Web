<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $sql = "DELETE FROM categories WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa danh mục thành công!'); window.location.href='../categories.php';</script>";
    } else {
        echo "<script>alert('Không thể xóa! Danh mục này đang chứa sản phẩm.'); window.location.href='../categories.php';</script>";
    }
}
?>