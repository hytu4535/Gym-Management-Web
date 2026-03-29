<?php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['admin_logged_in']) || (int) ($_SESSION['admin_user_id'] ?? 0) <= 0) {
    header("Location: ../login.php");
    exit();
}

$hasManageAll = in_array('MANAGE_ALL', $_SESSION['permissions'] ?? [], true);
$packageActionSet = $_SESSION['user_action_permissions']['MANAGE_PACKAGES'] ?? [];
$canEditPackage = $hasManageAll || !empty($packageActionSet['edit']);
if (!$canEditPackage) {
    header("Location: ../no_permission.php");
    exit();
}


if (isset($_POST['btn_edit_package'])) {
    
    $id = (int)$_POST['id'];
    $package_name = trim($_POST['package_name'] ?? '');
    $duration_months = trim($_POST['duration_months'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $status = $_POST['status'];
    $description = $_POST['description'];

    if ($package_name === '' || $duration_months === '' || $price === '') {
        echo "<script>
                alert('Vui lòng nhập đầy đủ các trường bắt buộc.');
                window.history.back();
              </script>";
        exit;
    }

    $duration_months = (int)$duration_months;
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