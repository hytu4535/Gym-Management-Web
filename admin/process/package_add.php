<?php
require_once '../../config/db.php';

if (isset($_POST['btn_add_package'])) {
    $package_name = $_POST['package_name'];
    $duration_months = $_POST['duration_months'];
    $price = $_POST['price'];
    $description = $_POST['description'];
    $status = $_POST['status'];

    $sql_insert = "INSERT INTO membership_packages (package_name, duration_months, price, description, status) 
                   VALUES ('$package_name', $duration_months, $price, '$description', '$status')";

    if ($conn->query($sql_insert) === TRUE) {
        echo "<script>
                alert('Thêm gói tập thành công!');
                window.location.href = '../packages.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi: " . $conn->error . "');
                window.history.back();
              </script>";
    }
}
?>