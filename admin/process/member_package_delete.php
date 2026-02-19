<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM member_packages WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa dữ liệu thành công!'); window.location.href='../member-packages.php';</script>";
    } else {
        echo "<script>alert('Lỗi khi xóa: " . $conn->error . "'); window.location.href='../member-packages.php';</script>";
    }
}
?>