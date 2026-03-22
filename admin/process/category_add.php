<?php
require_once '../../config/db.php';

if (isset($_POST['btn_add_category'])) {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $sql = "INSERT INTO categories (name, description, status) VALUES ('$name', '$description', '$status')";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Thêm danh mục thành công!'); window.location.href='../categories.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>