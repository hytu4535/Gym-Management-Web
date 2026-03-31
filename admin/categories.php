<?php 
session_start(); // luôn khởi tạo session

$page_title = "Quản lý danh mục";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_SALES');

require_once __DIR__ . '/process/category_repository.php';

$db = getDB();

$activeCategories = getActiveCategories();
$categoryStatsStmt = $db->query("SELECT SUM(status = 'active') AS active_count, SUM(status = 'inactive') AS inactive_count FROM categories");
$categoryStats = $categoryStatsStmt ? $categoryStatsStmt->fetch(PDO::FETCH_ASSOC) : ['active_count' => 0, 'inactive_count' => 0];
$activeCategoryCount = (int) ($categoryStats['active_count'] ?? 0);
$inactiveCategoryCount = (int) ($categoryStats['inactive_count'] ?? 0);

include 'layout/header.php'; 
include 'layout/sidebar.php';
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Danh mục sản phẩm</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Danh mục</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-success"><i class="fas fa-tags"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Danh mục đang hoạt động</span>
                <span class="info-box-number"><?= $activeCategoryCount ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-6 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-secondary"><i class="fas fa-eye-slash"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Danh mục đã vô hiệu hóa</span>
                <span class="info-box-number"><?= $inactiveCategoryCount ?></span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách danh mục</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addCategoryModal">
                    <i class="fas fa-plus"></i> Thêm
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php if (!empty($activeCategories)): ?>
                    <?php foreach ($activeCategories as $row): ?>
                      <?php
                        $categoryId = (int) ($row['id'] ?? 0);
                        $categoryName = htmlspecialchars((string) ($row['name'] ?? ''), ENT_QUOTES, 'UTF-8');
                        $categoryDescription = htmlspecialchars((string) ($row['description'] ?? ''), ENT_QUOTES, 'UTF-8');
                      ?>
                      <tr>
                        <td><?= $categoryId ?></td>
                        <td class="font-weight-bold"><?= $categoryName ?></td>
                        <td><?= $categoryDescription ?></td>
                        <td><span class="badge badge-success">Đang hoạt động</span></td>
                        <td>
                          <a href="category_edit.php?id=<?= $categoryId ?>" class="btn btn-warning btn-sm" title="Sửa">
                            <i class="fas fa-edit"></i>
                          </a>
                          <a href="process/category_delete.php?id=<?= $categoryId ?>" class="btn btn-danger btn-sm" title="Vô hiệu hóa" onclick="return confirm('Bạn có chắc chắn muốn vô hiệu hóa danh mục này không?');">
                            <i class="fas fa-ban"></i>
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr><td colspan="5" class="text-center">Chưa có danh mục nào.</td></tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
<div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="addCategoryForm" action="process/category_add.php" method="POST" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Thêm danh mục mới</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <div class="form-group">
                      <label for="name">Tên danh mục <span class="text-danger">*</span></label>
                      <input type="text" id="name" name="name" class="form-control" required placeholder="VD: Thực phẩm bổ sung">
                  </div>
                  <div class="form-group">
                      <label for="description">Mô tả</label>
                      <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                  </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" name="btn_add_category" class="btn btn-primary">Lưu lại</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('addCategoryForm').addEventListener('submit', function(e) {
    let isValid = true;
    let firstErrorElement = null; 
    document.querySelectorAll('.is-invalid').forEach(function(el) {
        el.classList.remove('is-invalid');
    });
    document.querySelectorAll('.custom-error-text').forEach(function(el) {
        el.remove();
    });

    function showError(inputId, message) {
        let inputEl = document.getElementById(inputId);
        inputEl.classList.add('is-invalid');
        
        let errorSpan = document.createElement('div');
        errorSpan.className = 'invalid-feedback custom-error-text';
        errorSpan.style.display = 'block';
        errorSpan.innerText = message;
        
        inputEl.parentNode.appendChild(errorSpan);
        isValid = false;

        if (!firstErrorElement) {
            firstErrorElement = inputEl;
        }
    }

    let nameVal = document.getElementById('name').value.trim();
    if (nameVal === '') {
        showError('name', 'Vui lòng nhập tên danh mục.');
    } else if (nameVal.length < 2) {
        showError('name', 'Tên danh mục phải có ít nhất 2 ký tự.');
    }

    let descriptionVal = document.getElementById('description').value.trim();
    if (descriptionVal !== '' && descriptionVal.length < 10) {
      showError('description', 'Mô tả phải có ít nhất 10 ký tự');
    }

    if (!isValid) {
        e.preventDefault();
        if (firstErrorElement) {
            firstErrorElement.focus();
        }
    }
});
</script>


<?php include 'layout/footer.php'; ?>
