<?php
require_once '../../config/db.php';
if (isset($_POST['btn_edit_package'])) {
    
    $id = (int)$_POST['id'];
    $package_name = $_POST['package_name'];
    $duration_months = (int)$_POST['duration_months'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $description = $_POST['description'];
    $package_name = $conn->real_escape_string($package_name);
    $description = $conn->real_escape_string($description);

    $sql_update = "UPDATE membership_packages SET 
                    package_name = '$package_name', 
                    duration_months = $duration_months, 
                    price = $price, 
                    status = '$status', 
                    description = '$description' 
                   WHERE id = $id";

    if ($conn->query($sql_update) === TRUE) {
        echo "<script>
                alert('Cập nhật gói tập thành công!');
                window.location.href = '../packages.php';
              </script>";
    } else {
        echo "<script>
                alert('Lỗi khi cập nhật: " . $conn->error . "');
                window.history.back();
              </script>";
    }
} else {
    header("Location: ../packages.php");
    exit();
}
?>