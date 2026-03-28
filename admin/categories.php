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

include 'layout/header.php'; 
include 'layout/sidebar.php';

require_once '../config/db.php';

$filterName = trim((string) ($_GET['name'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') {
  $whereClauses[] = 'name LIKE ?';
  $whereParams[] = '%' . $filterName . '%';
}
if ($filterStatus !== '') {
  $whereClauses[] = 'status = ?';
  $whereParams[] = $filterStatus;
}
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$sql = "SELECT * FROM categories" . $whereSql . " ORDER BY id DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($whereParams);
$result = $stmt->get_result();
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
        <?php
          $filterMode = 'server';
          $filterAction = 'categories.php';
          $filterFieldsHtml = '
            <div class="col-md-6">
              <div class="form-group mb-0">
                <label>Tên danh mục</label>
                <input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Nhập tên danh mục">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group mb-0">
                <label>Trạng thái</label>
                <select name="status" class="form-control">
                  <option value="">-- Tất cả trạng thái --</option>
                  <option value="active" ' . ($filterStatus === 'active' ? 'selected' : '') . '>Active</option>
                  <option value="inactive" ' . ($filterStatus === 'inactive' ? 'selected' : '') . '>Inactive</option>
                </select>
              </div>
            </div>
          ';
          include 'layout/filter-card.php';
        ?>
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
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $statusBadge = ($row['status'] == 'active') 
                                ? '<span class="badge badge-success">Đang hoạt động</span>' 
                                : '<span class="badge badge-secondary">Tạm ẩn</span>';
                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td class='font-weight-bold'>{$row['name']}</td>";
                            echo "  <td>{$row['description']}</td>";
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                        <a href='category_edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Sửa'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                        
                                        <a href='process/category_delete.php?id={$row['id']}' class='btn btn-danger btn-sm' title='Xóa' onclick=\"return confirm('Bạn có chắc chắn muốn xóa danh mục này không? Lưu ý: Nếu danh mục đang chứa sản phẩm thì có thể sẽ không xóa được.');\">
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center'>Chưa có danh mục nào.</td></tr>";
                    }
                    ?>
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
                  <div class="form-group">
                      <label for="status">Trạng thái <span class="text-danger">*</span></label>
                      <select id="status" name="status" class="form-control">
                          <option value="active">Active</option>
                          <option value="inactive">Inactive</option>
                      </select>
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
    if (descriptionVal === '') {
      showError('description', 'Vui lòng nhập mô tả');
    } else if (descriptionVal.length < 10) {
      showError('description', 'Mô tả phải có ít nhất 10 ký tự');
    }

    let statusVal = document.getElementById('status').value;
    if (statusVal === '') {
        showError('status', 'Vui lòng chọn trạng thái.');
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
