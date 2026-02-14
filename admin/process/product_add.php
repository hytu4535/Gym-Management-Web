<?php
require_once '../../config/db.php'; 

if (isset($_POST['btn_add_product'])) {
    $name = $_POST['name'];
    $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : 'NULL';
    $unit = $_POST['unit'];
    $selling_price = $_POST['selling_price'];
    $stock_quantity = $_POST['stock_quantity'];
    $status = $_POST['status'];

    $sql_insert = "INSERT INTO products (category_id, name, unit, stock_quantity, selling_price, status) 
                   VALUES ($category_id, '$name', '$unit', $stock_quantity, $selling_price, '$status')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>
                alert('Thêm sản phẩm thành công!');
                window.location.href = '../products.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}
?>