<?php
session_start();

$page_title = "Quản lý Khuyến mãi";

// kiểm tra đăng nhập
include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PROMOTIONS
checkPermission('MANAGE_PROMOTIONS');

// layout chung
include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
  <section class="content">
    <div class="container-fluid">
      <h1>Quản lý Khuyến mãi</h1>
      <p>Chức năng này chưa được triển khai.</p>
    </div>
  </section>
</div>

<?php include 'layout/footer.php'; ?>
