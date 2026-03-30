<?php
require_once __DIR__ . '/_permission_guard.php';
processRequirePermission('MANAGE_PACKAGES', 'add');

require_once '../../config/db.php';

if (isset($_POST['btn_add_package'])) {
  $package_name = trim($_POST['package_name'] ?? '');
  $duration_months = trim($_POST['duration_months'] ?? '');
  $price = trim($_POST['price'] ?? '');
  $description = $_POST['description'];
  $status = $_POST['status'];

  if ($package_name === '' || $duration_months === '' || $price === '') {
    echo "<script>
        alert('Vui lòng nhập đầy đủ các trường bắt buộc.');
        window.history.back();
        </script>";
    exit;
  }

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