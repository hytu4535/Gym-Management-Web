<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Gói Tập Hội Viên";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_MEMBERS
checkPermission('MANAGE_MEMBERS');

$permissions = $_SESSION['permissions'] ?? [];
$hasManageAll = in_array('MANAGE_ALL', $permissions, true);
$memberActionSet = $_SESSION['user_action_permissions']['MANAGE_MEMBERS'] ?? null;

if ($hasManageAll) {
  $canAddMemberPackage = true;
  $canEditMemberPackage = true;
  $canDeleteMemberPackage = true;
} elseif (is_array($memberActionSet)) {
  $canAddMemberPackage = !empty($memberActionSet['add']);
  $canEditMemberPackage = !empty($memberActionSet['edit']);
  $canDeleteMemberPackage = !empty($memberActionSet['delete']);
} else {
  $legacyManageMembers = in_array('MANAGE_MEMBERS', $permissions, true);
  $canAddMemberPackage = $legacyManageMembers;
  $canEditMemberPackage = $legacyManageMembers;
  $canDeleteMemberPackage = $legacyManageMembers;
}

// Xử lý các hành động
$db = getDB();
$message = '';
$messageType = '';

$filterMemberId = trim((string) ($_GET['member_id'] ?? ''));
$filterPackageId = trim((string) ($_GET['package_id'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$memberPackageWhereClauses = [];
$memberPackageParams = [];
if ($filterMemberId !== '') {
  $memberPackageWhereClauses[] = 'mp.member_id = ?';
  $memberPackageParams[] = (int) $filterMemberId;
}
if ($filterPackageId !== '') {
  $memberPackageWhereClauses[] = 'mp.package_id = ?';
  $memberPackageParams[] = (int) $filterPackageId;
}
if ($filterStatus !== '') {
  $memberPackageWhereClauses[] = 'mp.status = ?';
  $memberPackageParams[] = $filterStatus;
}
$memberPackageWhereSql = !empty($memberPackageWhereClauses) ? ' WHERE ' . implode(' AND ', $memberPackageWhereClauses) : '';

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
  checkPermission('MANAGE_MEMBERS', 'delete');

    try {
        $stmt = $db->prepare("UPDATE member_packages SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([(int) $_GET['id']]);
        $message = "Đã hủy gói tập thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
      $message = toVietnameseDbError($e, 'Không thể hủy gói tập hội viên.');
        $messageType = "danger";
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
      checkPermission('MANAGE_MEMBERS', 'edit');
    } else {
      checkPermission('MANAGE_MEMBERS', 'add');
    }

    $end_date = $_POST['end_date'];
    $status = $_POST['status'];
    
    try {
        if (isset($_POST['id']) && !empty($_POST['id'])) {
          // Cập nhật: chỉ cho phép sửa ngày hết hạn và trạng thái; member/package/start_date lấy từ bản ghi hiện tại.
          $currentStmt = $db->prepare("SELECT member_id, package_id, start_date FROM member_packages WHERE id = ? LIMIT 1");
          $currentStmt->execute([(int) $_POST['id']]);
          $current = $currentStmt->fetch(PDO::FETCH_ASSOC);

          if (!$current) {
            throw new PDOException('Không tìm thấy gói tập hội viên.');
          }

          $stmt = $db->prepare("UPDATE member_packages SET member_id=?, package_id=?, start_date=?, end_date=?, status=? WHERE id=?");
          $stmt->execute([
            (int) $current['member_id'],
            (int) $current['package_id'],
            $current['start_date'],
            $end_date,
            $status,
            (int) $_POST['id']
          ]);
            $message = "Cập nhật gói tập thành công!";
        } else {
            // Thêm mới
            $member_id = $_POST['member_id'] ?? null;
            $package_id = $_POST['package_id'] ?? null;
            $start_date = $_POST['start_date'] ?? null;

            $stmt = $db->prepare("INSERT INTO member_packages (member_id, package_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $package_id, $start_date, $end_date, $status]);
            $message = "Thêm gói tập thành công!";
        }
        $messageType = "success";
    } catch (PDOException $e) {
      $message = toVietnameseDbError($e, 'Không thể lưu gói tập hội viên.');
        $messageType = "danger";
    }
}

// Lấy danh sách gói tập của hội viên
$stmt = $db->prepare("SELECT mp.*, m.full_name, pkg.package_name, pkg.duration_months, pkg.price 
                    FROM member_packages mp 
                    LEFT JOIN members m ON mp.member_id = m.id 
                    LEFT JOIN membership_packages pkg ON mp.package_id = pkg.id" . $memberPackageWhereSql . " 
                    ORDER BY mp.id DESC");
$stmt->execute($memberPackageParams);
$memberPackages = $stmt->fetchAll();

// Lấy danh sách members và packages cho form
$members = $db->query("SELECT id, full_name FROM members ORDER BY full_name")->fetchAll();
$packages = $db->query("SELECT id, package_name, duration_months, price FROM membership_packages WHERE status = 'active' ORDER BY package_name")->fetchAll();

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
            <h1 class="m-0">Gói Tập Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Gói Tập Hội Viên</li>
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
          $filterAction = 'member-packages.php';
          $filterFieldsHtml = '
            <div class="col-md-4"><div class="form-group mb-0"><label>Hội viên</label><select name="member_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($members as $member) {
            $selected = (string) $filterMemberId === (string) $member['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $member['id'] . '" ' . $selected . '>' . htmlspecialchars((string) ($member['full_name'] ?? '')) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-4"><div class="form-group mb-0"><label>Gói tập</label><select name="package_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($packages as $pkg) {
            $selected = (string) $filterPackageId === (string) $pkg['id'] ? 'selected' : '';
            $label = $pkg['package_name'] . ' - ' . number_format($pkg['price'], 0, ',', '.') . ' VNĐ';
            $filterFieldsHtml .= '<option value="' . (int) $pkg['id'] . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="active" ' . ($filterStatus === 'active' ? 'selected' : '') . '>Đang hoạt động</option><option value="expired" ' . ($filterStatus === 'expired' ? 'selected' : '') . '>Hết hạn</option><option value="cancelled" ' . ($filterStatus === 'cancelled' ? 'selected' : '') . '>Đã hủy</option></select></div></div>
          ';
          include 'layout/filter-card.php';
        ?>
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
                <h3 class="card-title">Danh sách gói tập của hội viên</h3>
                <div class="card-tools">
                  <?php if ($canAddMemberPackage): ?>
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#packageModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Gói Tập
                  </button>
                  <?php endif; ?>
                </div>
              </div>
              <div class="card-body">
                <table id="packageTable" class="table table-bordered table-striped js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Gói tập</th>
                    <th>Thời hạn</th>
                    <th>Giá</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày hết hạn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tr>
                   <tbody>
                   <?php foreach ($memberPackages as $mp): ?>
                   <tr>
                     <td><?php echo $mp['id']; ?></td>
                     <td><?php echo htmlspecialchars((string) ($mp['full_name'] ?? '')); ?></td>
                     <td><?php echo htmlspecialchars((string) ($mp['package_name'] ?? '')); ?></td>
                     <td><?php echo $mp['duration_months']; ?> tháng</td>
                     <td><?php echo number_format($mp['price'], 0, ',', '.'); ?> VNĐ</td>
                     <td><?php echo date('d/m/Y', strtotime($mp['start_date'])); ?></td>
                     <td><?php echo date('d/m/Y', strtotime($mp['end_date'])); ?></td>
                     <td>
                       <?php 
                       $badgeClass = ['active' => 'success', 'expired' => 'danger', 'cancelled' => 'secondary'];
                       $statusText = ['active' => 'Đang hoạt động', 'expired' => 'Hết hạn', 'cancelled' => 'Đã hủy'];
                       $class = $badgeClass[$mp['status']] ?? 'secondary';
                       $text = $statusText[$mp['status']] ?? $mp['status'];
                       ?>
                       <span class="badge badge-<?php echo $class; ?>"><?php echo $text; ?></span>
                     </td>
                     <td>
                       <?php if ($canEditMemberPackage): ?>
                       <button class="btn btn-warning btn-sm" onclick='editPackage(<?php echo json_encode($mp); ?>)' data-toggle="modal" data-target="#packageModal">
                         <i class="fas fa-edit"></i>
                       </button>
                       <?php endif; ?>
                       <?php if ($canDeleteMemberPackage): ?>
                         <a href="?action=delete&id=<?php echo $mp['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn hủy gói tập này không?')">
                           <i class="fas fa-ban"></i>
                         </a>
                       <?php endif; ?>
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

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="packageModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Gói Tập</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST">
        <div class="modal-body">
          <input type="hidden" name="id" id="package_id">
          <div class="form-group">
            <label>Hội viên</label>
            <select name="member_id" id="member_id" class="form-control" required disabled>
              <option value="">--- Chọn hội viên ---</option>
              <?php foreach ($members as $member): ?>
              <option value="<?php echo $member['id']; ?>"><?php echo htmlspecialchars((string) ($member['full_name'] ?? '')); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Gói tập</label>
            <select name="package_id" id="pkg_id" class="form-control" required disabled onchange="updateDates()">
              <option value="">--- Chọn gói tập ---</option>
              <?php foreach ($packages as $pkg): ?>
              <option value="<?php echo $pkg['id']; ?>" data-months="<?php echo $pkg['duration_months']; ?>">
                <?php echo htmlspecialchars((string) ($pkg['package_name'] ?? '')) . " - " . number_format($pkg['price'], 0, ',', '.') . " VNĐ"; ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Ngày bắt đầu</label>
            <input type="date" name="start_date" id="start_date" class="form-control" required disabled onchange="updateEndDate()">
          </div>
          <div class="form-group">
            <label>Ngày hết hạn</label>
            <input type="date" name="end_date" id="end_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Trạng thái</label>
            <select name="status" id="status" class="form-control">
              <option value="active">Đang hoạt động</option>
              <option value="expired">Hết hạn</option>
              <option value="cancelled">Đã hủy</option>
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
function resetForm() {
  document.getElementById('modalTitle').innerText = 'Thêm Gói Tập';
  document.getElementById('package_id').value = '';
  document.getElementById('member_id').value = '';
  document.getElementById('pkg_id').value = '';
  document.getElementById('start_date').value = '';
  document.getElementById('end_date').value = '';
  document.getElementById('status').value = 'active';
  document.getElementById('member_id').disabled = false;
  document.getElementById('pkg_id').disabled = false;
  document.getElementById('start_date').disabled = false;

  var currentTempOption = document.getElementById('current-package-option');
  if (currentTempOption) {
    currentTempOption.remove();
  }
}

function editPackage(pkg) {
  document.getElementById('modalTitle').innerText = 'Sửa Gói Tập';
  document.getElementById('package_id').value = pkg.id;
  document.getElementById('member_id').value = pkg.member_id;
  var packageSelect = document.getElementById('pkg_id');
  var existingOption = packageSelect.querySelector('option[value="' + pkg.package_id + '"]');
  var tempOption = document.getElementById('current-package-option');

  if (tempOption) {
    tempOption.remove();
  }

  if (!existingOption) {
    var newOption = document.createElement('option');
    newOption.id = 'current-package-option';
    newOption.value = pkg.package_id;
    newOption.setAttribute('data-months', pkg.duration_months || '');
    newOption.textContent = (pkg.package_name || 'Gói hiện tại') + ' - ' + Number(pkg.price || 0).toLocaleString('vi-VN') + ' VNĐ';
    packageSelect.insertBefore(newOption, packageSelect.options[1] || null);
  }

  packageSelect.value = pkg.package_id;
  document.getElementById('start_date').value = pkg.start_date;
  document.getElementById('end_date').value = pkg.end_date;
  document.getElementById('status').value = pkg.status;
  document.getElementById('member_id').disabled = true;
  document.getElementById('pkg_id').disabled = true;
  document.getElementById('start_date').disabled = true;
}

function updateEndDate() {
  var select = document.getElementById('pkg_id');
  var selectedOption = select.options[select.selectedIndex];
  var months = selectedOption.getAttribute('data-months');
  var startDate = document.getElementById('start_date').value;
  
  if (months && startDate) {
    var date = new Date(startDate);
    date.setMonth(date.getMonth() + parseInt(months));
    document.getElementById('end_date').value = date.toISOString().split('T')[0];
  }
}

function updateDates() {
  updateEndDate();
}
</script>

<?php include 'layout/footer.php'; ?>