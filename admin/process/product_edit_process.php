<?php
require_once '../../config/db.php';

if (isset($_POST['btn_edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $selling_price = $_POST['selling_price'];
    $stock_quantity = $_POST['stock_quantity'];

    $sql = "UPDATE products SET 
            name = '$name', 
            category_id = $category_id, 
            selling_price = $selling_price, 
            stock_quantity = $stock_quantity 
            WHERE id = $id";

    if ($conn->query($sql) === TRUE) {
        echo "<script>alert('Cập nhật thành công!'); window.location.href='../products.php';</script>";
    } else {
        echo "Lỗi: " . $conn->error;
    }
}
?>