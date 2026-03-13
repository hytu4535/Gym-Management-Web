<?php
require_once '../../config/db.php';

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql = "DELETE FROM membership_packages WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Đã xóa gói tập thành công!'); window.location.href='../packages.php';</script>";
    } else {
        echo "<script>alert('Không thể xóa! Gói tập này đã được bán cho hội viên.'); window.location.href='../packages.php';</script>";
    }
}
?>