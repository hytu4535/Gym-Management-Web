<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Packages";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PACKAGES
checkPermission('MANAGE_PACKAGES');

// layout chung
include 'layout/header.php'; 
include 'layout/sidebar.php';

// kết nối DB (nếu bạn dùng file config riêng thì giữ nguyên)
require_once '../config/db.php';

function mysqli_bind_dynamic($stmt, string $types, array &$params): void {
  if ($types === '' || empty($params)) {
    return;
  }

  $bindArgs = [$types];
  foreach ($params as $key => $value) {
    $bindArgs[] = &$params[$key];
  }

  call_user_func_array([$stmt, 'bind_param'], $bindArgs);
}

$filterName = trim((string) ($_GET['package_name'] ?? ''));
$filterDurationMin = trim((string) ($_GET['duration_min'] ?? ''));
$filterDurationMax = trim((string) ($_GET['duration_max'] ?? ''));
$filterPriceMin = trim((string) ($_GET['price_min'] ?? ''));
$filterPriceMax = trim((string) ($_GET['price_max'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$whereClauses = [];
$whereTypes = '';
$whereParams = [];
if ($filterName !== '') {
  $whereClauses[] = 'package_name LIKE ?';
  $whereTypes .= 's';
  $whereParams[] = '%' . $filterName . '%';
}
if ($filterDurationMin !== '' && is_numeric($filterDurationMin)) {
  $whereClauses[] = 'duration_months >= ?';
  $whereTypes .= 'i';
  $whereParams[] = (int) $filterDurationMin;
}
if ($filterDurationMax !== '' && is_numeric($filterDurationMax)) {
  $whereClauses[] = 'duration_months <= ?';
  $whereTypes .= 'i';
  $whereParams[] = (int) $filterDurationMax;
}
if ($filterPriceMin !== '' && is_numeric($filterPriceMin)) {
  $whereClauses[] = 'price >= ?';
  $whereTypes .= 'd';
  $whereParams[] = (float) $filterPriceMin;
}
if ($filterPriceMax !== '' && is_numeric($filterPriceMax)) {
  $whereClauses[] = 'price <= ?';
  $whereTypes .= 'd';
  $whereParams[] = (float) $filterPriceMax;
}
if ($filterStatus !== '') {
  $whereClauses[] = 'status = ?';
  $whereTypes .= 's';
  $whereParams[] = $filterStatus;
}
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

$sql = "SELECT id, package_name, duration_months, price, description, status 
        FROM membership_packages" . $whereSql . " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
if ($stmt && !empty($whereParams)) {
  mysqli_bind_dynamic($stmt, $whereTypes, $whereParams);
}

$stmt->execute();
$result = $stmt->get_result();
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Gói tập</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Packages</li>
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
          $filterAction = 'packages.php';
          $filterFieldsHtml = '
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Tên gói</label>
                <input type="text" name="package_name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Nhập tên gói">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Thời hạn từ</label>
                <input type="number" name="duration_min" class="form-control" min="0" value="' . htmlspecialchars($filterDurationMin) . '" placeholder=">=">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Thời hạn đến</label>
                <input type="number" name="duration_max" class="form-control" min="0" value="' . htmlspecialchars($filterDurationMax) . '" placeholder="<=">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Giá từ</label>
                <input type="number" name="price_min" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMin) . '" placeholder=">=">
              </div>
            </div>
            <div class="col-md-2">
              <div class="form-group mb-0">
                <label>Giá đến</label>
                <input type="number" name="price_max" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMax) . '" placeholder="<=">
              </div>
            </div>
            <div class="col-md-2">
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
                <h3 class="card-title">Danh sách Gói tập</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPackageModal">
                    <i class="fas fa-plus"></i> Thêm Gói tập
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên gói</th>
                    <th>Thời hạn</th>
                    <th>Giá</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $formattedPrice = number_format($row['price'], 0, ',', '.') . 'đ';
                            $duration = $row['duration_months'] . ' Tháng';
                            $shortDesc = mb_strimwidth($row['description'] ?? '', 0, 50, "...");
                            
                            if ($row['status'] == 'active') {
                                $statusBadge = '<span class="badge badge-success">Đang mở bán</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-secondary">Ngừng bán</span>';
                            }

                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td class='font-weight-bold text-primary'>{$row['package_name']}</td>";
                            echo "  <td>{$duration}</td>";
                            echo "  <td class='text-danger font-weight-bold'>{$formattedPrice}</td>";
                            echo "  <td><small>{$shortDesc}</small></td>";
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                      <a href='package_edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Sửa thông tin'>
                                          <i class='fas fa-edit'></i>
                                      </a>
                                      <a href='process/package_delete.php?id={$row['id']}' 
                                         class='btn btn-danger btn-sm' title='Xóa gói tập' 
                                         onclick=\"return confirm('Bạn có chắc chắn muốn xóa gói tập này không?');\">
                                          <i class='fas fa-trash'></i>
                                      </a>
                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Hệ thống chưa có gói tập nào. Hãy thêm mới!</td></tr>";
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

  <div class="modal fade" id="addPackageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Thêm Gói Tập Mới</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="process/package_add.php" method="POST" novalidate id="packageAddForm">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tên Gói Tập <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="package_name" data-field="package_name" placeholder="VD: Gói Gym 1 Tháng">
                <small class="text-danger d-none">Vui lòng nhập tên gói tập.</small>
              </div>
              <div class="form-group">
                <label>Thời Hạn (Tháng) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="duration_months" data-field="duration_months" min="1" value="1">
                <small class="text-danger d-none">Vui lòng nhập thời hạn gói tập.</small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Giá Tiền (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" data-field="price" min="0" value="0">
                <small class="text-danger d-none">Vui lòng nhập giá tiền.</small>
              </div>
              <div class="form-group">
                <label>Trạng Thái</label>
                <select class="form-control" name="status">
                  <option value="active">Active (Đang mở bán)</option>
                  <option value="inactive">Inactive (Ngừng bán)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Mô tả chi tiết</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả về quyền lợi gói tập..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" name="btn_add_package">Lưu Gói Tập</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
function packageValidateField(input) {
  var formGroup = input.closest('.form-group');
  var errorBox = formGroup ? formGroup.querySelector('small.text-danger') : null;
  var value = String(input.value || '').trim();

  if (!errorBox) return true;

  if (!value) {
    errorBox.classList.remove('d-none');
    input.classList.add('is-invalid');
    return false;
  }

  errorBox.classList.add('d-none');
  input.classList.remove('is-invalid');
  return true;
}

document.addEventListener('DOMContentLoaded', function() {
  var form = document.getElementById('packageAddForm');
  if (!form) return;

  form.querySelectorAll('[data-field]').forEach(function(field) {
    field.addEventListener('input', function() { packageValidateField(field); });
    field.addEventListener('blur', function() { packageValidateField(field); });
  });

  form.addEventListener('submit', function(event) {
    var isValid = true;
    form.querySelectorAll('[data-field]').forEach(function(field) {
      if (!packageValidateField(field)) isValid = false;
    });

    if (!isValid) {
      event.preventDefault();
      event.stopPropagation();
    }
  });
});
</script>