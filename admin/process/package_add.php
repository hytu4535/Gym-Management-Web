<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || (int) ($_SESSION['admin_user_id'] ?? 0) <= 0) {
  header("Location: ../login.php");
  exit();
}

$hasManageAll = in_array('MANAGE_ALL', $_SESSION['permissions'] ?? [], true);
$packageActionSet = $_SESSION['user_action_permissions']['MANAGE_PACKAGES'] ?? [];
$canAddPackage = $hasManageAll || !empty($packageActionSet['add']);
if (!$canAddPackage) {
  header("Location: ../no_permission.php");
  exit();
}


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