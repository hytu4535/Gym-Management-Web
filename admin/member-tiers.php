<?php
session_start();
$page_title = "Quản lý Hạng Hội Viên";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_MEMBERS');

$db = getDB();
$message = '';
$messageType = '';

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
  checkPermission('MANAGE_MEMBERS', 'delete');

  $deleteId = (int) $_GET['id'];

  try {
    $countStmt = $db->prepare("SELECT COUNT(*) FROM members WHERE tier_id = ?");
    $countStmt->execute([$deleteId]);
    if ((int) $countStmt->fetchColumn() > 0) {
      throw new Exception("Không thể xóa hạng này vì đang có hội viên sử dụng.");
    }

    $deleteStmt = $db->prepare("DELETE FROM member_tiers WHERE id = ?");
    $deleteStmt->execute([$deleteId]);

    if ($deleteStmt->rowCount() > 0) {
      $message = "Xóa hạng hội viên thành công!";
      $messageType = "success";
    } else {
      $message = "Không tìm thấy hạng hội viên để xóa.";
      $messageType = "warning";
    }
  } catch (Exception $e) {
    $message = "Lỗi: " . $e->getMessage();
    $messageType = "danger";
  }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
      checkPermission('MANAGE_MEMBERS', 'edit');
    } else {
      checkPermission('MANAGE_MEMBERS', 'add');
    }

    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $level = $_POST['level'];
    $min_spent = $_POST['min_spent'];
    $base_discount = $_POST['base_discount'];
    $status = $_POST['status'];
    
    try {
        if ($name === '') throw new Exception("Vui lòng nhập tên hạng.");
        if ($level === '' || $level < 1) throw new Exception("Cấp độ phải >= 1.");
        if ($min_spent === '' || $min_spent < 0) throw new Exception("Chi tiêu tối thiểu phải >= 0.");
        if ($base_discount === '' || $base_discount < 0 || $base_discount > 100) throw new Exception("Giảm giá phải từ 0 đến 100%.");

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            $stmt = $db->prepare("UPDATE member_tiers SET name=?, level=?, min_spent=?, base_discount=?, status=? WHERE id=?");
            $stmt->execute([$name, $level, $min_spent, $base_discount, $status, $id]);
            $message = "Cập nhật hạng hội viên thành công!";
        } else {
            $stmt = $db->prepare("INSERT INTO member_tiers (name, level, min_spent, base_discount, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $level, $min_spent, $base_discount, $status]);
            $message = "Thêm hạng hội viên thành công!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
      $isDuplicateLevel = $e->getCode() === '23000' && (
        stripos($e->getMessage(), 'uk_tier_level') !== false ||
        stripos($e->getMessage(), 'member_tiers.uk_tier_level') !== false
      );

      $isDuplicateName = $e->getCode() === '23000' && (
        stripos($e->getMessage(), 'uk_tier_name') !== false ||
        stripos($e->getMessage(), 'member_tiers.uk_tier_name') !== false
      );

      if ($isDuplicateLevel) {
        $message = "Lỗi: Trùng cấp độ, đang có hạng khác có cấp độ này";
      } elseif ($isDuplicateName) {
        $message = "Lỗi: Trùng tên hạng, đang có hạng khác có tên hạng này";
      } else {
        $message = "Lỗi: " . $e->getMessage();
      }
      $messageType = "danger";
    } catch (Exception $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

$stmt = $db->query("SELECT * FROM member_tiers ORDER BY level");
$tiers = $stmt->fetchAll();

include 'layout/header.php'; 
include 'layout/sidebar.php';
?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Hạng Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Hạng Hội Viên</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show">
          <?php echo $message; ?>
          <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
        <?php endif; ?>
        
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Hạng Hội Viên</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#tierModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Hạng
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table id="tierTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Hạng</th>
                    <th>Cấp Độ</th>
                    <th>Chi Tiêu Tối Thiểu</th>
                    <th>Giảm Giá (%)</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($tiers as $tier): ?>
                  <tr>
                    <td><?php echo $tier['id']; ?></td>
                    <td>
                      <?php 
                      $badgeClass = ['Đồng' => 'secondary', 'Bạc' => 'light', 'Vàng' => 'warning', 'Bạch Kim' => 'primary', 'Kim Cương' => 'info'];
                      $class = $badgeClass[$tier['name']] ?? 'secondary';
                      ?>
                      <span class="badge badge-<?php echo $class; ?>"><?php echo htmlspecialchars($tier['name']); ?></span>
                    </td>
                    <td><?php echo $tier['level']; ?></td>
                    <td><?php echo number_format($tier['min_spent'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo (float)$tier['base_discount']; ?>%</td>
                    <td>
                      <span class="badge badge-<?php echo $tier['status'] == 'active' ? 'success' : 'secondary'; ?>">
                        <?php echo $tier['status'] == 'active' ? 'Hoạt động' : 'Không hoạt động'; ?>
                      </span>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm" onclick='editTier(<?php echo json_encode($tier); ?>)' data-toggle="modal" data-target="#tierModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?action=delete&id=<?php echo $tier['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa hạng này?')">
                        <i class="fas fa-trash"></i>
                      </a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<div class="modal fade" id="tierModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Hạng Hội Viên</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST" id="tierForm" novalidate>
        <div class="modal-body">
          <input type="hidden" name="id" id="tier_id">
          
          <div class="form-group">
            <label>Tên hạng</label>
            <input type="text" name="name" id="name" class="form-control" required>
            <small id="name_error" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label>Cấp độ</label>
            <input type="number" name="level" id="level" class="form-control" required min="1">
            <small id="level_error" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label>Chi tiêu tối thiểu (VNĐ)</label>
            <input type="number" step="0.01" name="min_spent" id="min_spent" class="form-control" required>
            <small id="min_spent_error" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label>Giảm giá cơ bản (%)</label>
            <input type="number" step="0.01" name="base_discount" id="base_discount" class="form-control" required min="0" max="100">
            <small id="base_discount_error" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="status" class="form-control">
              <option value="active">Hoạt động</option>
              <option value="inactive">Không hoạt động</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function clearErrors() {
  const inputs = document.querySelectorAll('#tierForm .form-control');
  inputs.forEach(i => i.classList.remove('is-invalid'));
  const errors = document.querySelectorAll('#tierForm .text-danger');
  errors.forEach(e => {
    e.classList.add('d-none');
    e.textContent = '';
  });
}

function showError(inputId, errorId, message) {
  const input = document.getElementById(inputId);
  const error = document.getElementById(errorId);
  if (input) input.classList.add('is-invalid');
  if (error) {
    error.textContent = message;
    error.classList.remove('d-none');
  }
}

function resetForm() {
  document.getElementById('modalTitle').innerText = 'Thêm Hạng Hội Viên';
  document.getElementById('tier_id').value = '';
  document.getElementById('name').value = '';
  document.getElementById('level').value = '';
  document.getElementById('min_spent').value = '';
  document.getElementById('base_discount').value = '';
  document.getElementById('status').value = 'active';
  clearErrors();
}

function editTier(tier) {
  document.getElementById('modalTitle').innerText = 'Sửa Hạng Hội Viên';
  document.getElementById('tier_id').value = tier.id;
  document.getElementById('name').value = tier.name;
  document.getElementById('level').value = tier.level;
  document.getElementById('min_spent').value = parseFloat(tier.min_spent);
  document.getElementById('base_discount').value = parseFloat(tier.base_discount);
  document.getElementById('status').value = tier.status;
  clearErrors();
}

document.addEventListener('DOMContentLoaded', function() {
  const fields = ['name', 'level', 'min_spent', 'base_discount'];
  
  fields.forEach(function(fieldId) {
    const input = document.getElementById(fieldId);
    if (input) {
      input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
        const error = document.getElementById(fieldId + '_error');
        if (error) error.classList.add('d-none');
      });
    }
  });

  const form = document.getElementById('tierForm');
  if (form) {
    form.addEventListener('submit', function(e) {
      clearErrors();
      let hasError = false;
      let focusInput = null;

      const nameInput = document.getElementById('name');
      if (nameInput.value.trim() === '') {
        showError('name', 'name_error', 'Vui lòng nhập tên hạng.');
        if (!hasError) { focusInput = nameInput; hasError = true; }
      }

      const levelInput = document.getElementById('level');
      const levelValue = parseInt(levelInput.value.trim());
      if (levelInput.value.trim() === '') {
        showError('level', 'level_error', 'Vui lòng nhập cấp độ.');
        if (!hasError) { focusInput = levelInput; hasError = true; }
      } else if (isNaN(levelValue) || levelValue < 1) {
        showError('level', 'level_error', 'Cấp độ phải là số nguyên lớn hơn hoặc bằng 1.');
        if (!hasError) { focusInput = levelInput; hasError = true; }
      }

      const minSpentInput = document.getElementById('min_spent');
      const minSpentValue = parseFloat(minSpentInput.value.trim());
      if (minSpentInput.value.trim() === '') {
        showError('min_spent', 'min_spent_error', 'Vui lòng nhập chi tiêu tối thiểu.');
        if (!hasError) { focusInput = minSpentInput; hasError = true; }
      } else if (isNaN(minSpentValue) || minSpentValue < 0) {
        showError('min_spent', 'min_spent_error', 'Chi tiêu tối thiểu không được nhỏ hơn 0.');
        if (!hasError) { focusInput = minSpentInput; hasError = true; }
      }

      const baseDiscountInput = document.getElementById('base_discount');
      const baseDiscountValue = parseFloat(baseDiscountInput.value.trim());
      if (baseDiscountInput.value.trim() === '') {
        showError('base_discount', 'base_discount_error', 'Vui lòng nhập giảm giá cơ bản.');
        if (!hasError) { focusInput = baseDiscountInput; hasError = true; }
      } else if (isNaN(baseDiscountValue) || baseDiscountValue < 0 || baseDiscountValue > 100) {
        showError('base_discount', 'base_discount_error', 'Giảm giá phải nằm trong khoảng từ 0 đến 100.');
        if (!hasError) { focusInput = baseDiscountInput; hasError = true; }
      }

      if (hasError) {
        e.preventDefault();
        e.stopPropagation();
        if (focusInput) {
          setTimeout(() => focusInput.focus(), 50);
        }
      }
    });
  }
});
</script>

<?php include 'layout/footer.php'; ?>