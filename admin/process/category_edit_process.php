<?php
require_once '../../config/db.php';

if (isset($_POST['btn_edit_category'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    $name = $conn->real_escape_string($_POST['name']);
    $description = $conn->real_escape_string($_POST['description']);

    $sql = "UPDATE categories SET 
            name = '$name', 
            description = '$description', 
            status = '$status' 
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Cập nhật danh mục thành công!'); window.location.href='../categories.php';</script>";
    } else {
        echo "<script>alert('Lỗi: " . $conn->error . "'); window.history.back();</script>";
    }
} else {
    header("Location: ../categories.php");
}
?>