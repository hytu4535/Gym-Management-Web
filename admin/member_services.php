<?php 
session_start(); // luôn khởi tạo session
$page_title = "Gán dịch vụ cho hội viên";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_SERVICES_NUTRITION
checkPermission('MANAGE_SERVICES_NUTRITION');

require_once '../includes/functions.php';

$db = getDB();

if (!function_exists('isPastDate')) {
  function isPastDate($date)
  {
    if (empty($date)) {
      return false;
    }

    try {
      $today = new DateTimeImmutable('today');
      $targetDate = new DateTimeImmutable($date);
      return $targetDate < $today;
    } catch (Exception $e) {
      return false;
    }
  }
}

$filterMemberId = trim((string) ($_GET['member_id'] ?? ''));
$filterServiceId = trim((string) ($_GET['service_id'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterFromDate = trim((string) ($_GET['from_date'] ?? ''));
$filterToDate = trim((string) ($_GET['to_date'] ?? ''));
if (!function_exists('normalizeMemberServiceStatus')) {
  function normalizeMemberServiceStatus($status)
  {
    $map = [
      'còn hiệu lực' => 'còn hiệu lực',
      'đã dùng' => 'đã dùng',
      'hết hạn' => 'hết hạn',
      'bị hủy' => 'bị hủy',
      'active' => 'còn hiệu lực',
      'used' => 'đã dùng',
      'expired' => 'hết hạn',
      'cancelled' => 'bị hủy',
    ];

    if (isset($map[$status])) {
      return $map[$status];
    }

    return 'còn hiệu lực';
  }
}

if (!function_exists('getMemberServiceStorageStatus')) {
  function getMemberServiceStorageStatus(PDO $db, $status)
  {
    static $statusMapCache = [];

    $normalizedStatus = normalizeMemberServiceStatus($status);
    if (isset($statusMapCache[$normalizedStatus])) {
      return $statusMapCache[$normalizedStatus];
    }

    $storageMap = [
      'còn hiệu lực' => 'còn hiệu lực',
      'đã dùng' => 'đã dùng',
      'hết hạn' => 'hết hạn',
      'bị hủy' => 'bị hủy',
    ];

    try {
      $schemaStmt = $db->query('SELECT DATABASE()');
      $schemaName = $schemaStmt ? (string) $schemaStmt->fetchColumn() : '';

      if ($schemaName !== '') {
        $columnStmt = $db->prepare(
          "SELECT COLUMN_TYPE
           FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = ?
             AND TABLE_NAME = 'member_services'
             AND COLUMN_NAME = 'status'
           LIMIT 1"
        );
        $columnStmt->execute([$schemaName]);
        $columnType = (string) $columnStmt->fetchColumn();

        if (preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $columnType, $matches)) {
          $allowedValues = array_map('stripslashes', $matches[1]);

          if (in_array('còn hiệu lực', $allowedValues, true)) {
            $storageMap = [
              'còn hiệu lực' => 'còn hiệu lực',
              'đã dùng' => 'đã dùng',
              'hết hạn' => 'hết hạn',
              'bị hủy' => 'bị hủy',
            ];
          } elseif (in_array('active', $allowedValues, true)) {
            $storageMap = [
              'còn hiệu lực' => 'active',
              'đã dùng' => 'used',
              'hết hạn' => 'expired',
              'bị hủy' => 'cancelled',
            ];
          }
        }
      }
    } catch (PDOException $e) {
      // Fallback to canonical Vietnamese values if metadata lookup fails.
    }

    $statusMapCache = array_merge($statusMapCache, $storageMap);
    return $statusMapCache[$normalizedStatus] ?? $normalizedStatus;
  }
}

if (!function_exists('memberServiceStatusLabel')) {
  function memberServiceStatusLabel($status)
  {
    $labels = [
      'còn hiệu lực' => 'Còn hiệu lực',
      'đã dùng' => 'Đã dùng',
      'hết hạn' => 'Hết hạn',
      'bị hủy' => 'Bị hủy',
    ];

    return $labels[normalizeMemberServiceStatus($status)] ?? $labels['còn hiệu lực'];
  }
}

if (!function_exists('memberServiceStatusBadgeClass')) {
  function memberServiceStatusBadgeClass($status)
  {
    $classes = [
      'còn hiệu lực' => 'success',
      'đã dùng' => 'secondary',
      'hết hạn' => 'warning',
      'bị hủy' => 'danger',
    ];

    return $classes[normalizeMemberServiceStatus($status)] ?? 'success';
  }
}

if (!function_exists('memberServiceCanEdit')) {
  function memberServiceCanEdit($status)
  {
    return normalizeMemberServiceStatus($status) === 'còn hiệu lực';
  }
}

if (!function_exists('buildMemberServiceEndDate')) {
  function buildMemberServiceEndDate($startDate, $endDate = null)
  {
    if (!empty($endDate)) {
      return $endDate;
    }

    try {
      $start = new DateTimeImmutable($startDate);
      return $start->modify('+1 month')->format('Y-m-d');
    } catch (Exception $e) {
      return null;
    }
  }
}

if (!function_exists('memberServiceHasOverlap')) {
  function memberServiceHasOverlap(PDO $db, $memberId, $serviceId, $startDate, $endDate, $excludeId = null)
  {
    $activeStatus = getMemberServiceStorageStatus($db, 'còn hiệu lực');
    $sql = "SELECT id
            FROM member_services
            WHERE member_id = ?
              AND service_id = ?
              AND status = ?
              AND NOT (COALESCE(end_date, DATE_ADD(start_date, INTERVAL 1 MONTH)) < ? OR start_date > ?)";
    $params = [$memberId, $serviceId, $activeStatus, $startDate, $endDate];

    if ($excludeId !== null) {
      $sql .= ' AND id <> ?';
      $params[] = $excludeId;
    }

    $sql .= ' LIMIT 1';

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }
}

if (!function_exists('assignService')) {
  function assignService(PDO $db, $memberId, $serviceId, $startDate, $endDate = null)
  {
    // 1) Validate ngày kết thúc nếu được nhập.
    $resolvedEndDate = buildMemberServiceEndDate($startDate, $endDate);
    if (!$resolvedEndDate) {
      return [false, 'Ngày bắt đầu hoặc ngày kết thúc không hợp lệ.'];
    }

    if ($startDate > $resolvedEndDate) {
      return [false, 'Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc.'];
    }

    // 2) Check service phải đang hoạt động.
    $serviceStmt = $db->prepare("SELECT id FROM services WHERE id = ? AND status = 'hoạt động' LIMIT 1");
    $serviceStmt->execute([$serviceId]);
    if (!$serviceStmt->fetch(PDO::FETCH_ASSOC)) {
      return [false, 'Dịch vụ không tồn tại hoặc không còn hoạt động.'];
    }

    // 3) Không cho gán nếu đã tồn tại record còn hiệu lực cùng member/service.
    if (memberServiceHasOverlap($db, $memberId, $serviceId, $startDate, $resolvedEndDate)) {
      return [false, 'Hội viên này đã có cùng dịch vụ còn hiệu lực trong khoảng thời gian này.'];
    }

    // 4) INSERT mới luôn tạo record còn hiệu lực.
    $statusToStore = getMemberServiceStorageStatus($db, 'còn hiệu lực');

    try {
      // 5) Transaction để tránh ghi nửa chừng.
      $db->beginTransaction();

      $stmt = $db->prepare("INSERT INTO member_services (member_id, service_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
      $stmt->execute([$memberId, $serviceId, $startDate, $resolvedEndDate, $statusToStore]);

      $db->commit();
      return [true, 'Gán dịch vụ cho hội viên thành công!'];
    } catch (PDOException $e) {
      if ($db->inTransaction()) {
        $db->rollBack();
      }

      if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        return [false, 'Hội viên này đã có cùng dịch vụ còn hiệu lực.'];
      }

      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

if (!function_exists('useService')) {
  function useService(PDO $db, $id)
  {
    // 1) Chỉ lấy bản ghi còn hiệu lực và chưa quá hạn.
    $stmt = $db->prepare("SELECT id, status, end_date FROM member_services WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
      return [false, 'Không tìm thấy bản ghi cần sử dụng.'];
    }

    if (normalizeMemberServiceStatus($record['status']) !== 'còn hiệu lực') {
      return [false, 'Chỉ được đánh dấu đã dùng khi dịch vụ còn hiệu lực.'];
    }

    if (!empty($record['end_date']) && isPastDate($record['end_date'])) {
      return [false, 'Dịch vụ đã hết hạn, không thể đánh dấu đã dùng.'];
    }

    try {
      // 2) Đánh dấu đã dùng.
      $stmt = $db->prepare("UPDATE member_services SET status = ? WHERE id = ?");
      $stmt->execute([getMemberServiceStorageStatus($db, 'đã dùng'), $id]);
      return [true, 'Đã cập nhật trạng thái đã dùng.'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

if (!function_exists('cancelService')) {
  function cancelService(PDO $db, $id)
  {
    if ((int) $id <= 0) {
      return [false, 'Thiếu ID bản ghi cần hủy.'];
    }

    // 1) Chỉ hủy nếu bản ghi còn hiệu lực.
    $stmt = $db->prepare("SELECT id, status FROM member_services WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
      return [false, 'Không tìm thấy bản ghi cần hủy.'];
    }

    if (normalizeMemberServiceStatus($record['status']) === 'bị hủy') {
      return [false, 'Bản ghi này đã bị hủy rồi.'];
    }

    try {
      // 2) Không DELETE, chỉ đổi sang bị hủy.
      $stmt = $db->prepare("UPDATE member_services SET status = ? WHERE id = ?");
      $stmt->execute([getMemberServiceStorageStatus($db, 'bị hủy'), $id]);
      return [true, 'Đã hủy dịch vụ thành công.'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

if (!function_exists('updateEndDate')) {
  function updateEndDate(PDO $db, $id, $endDate, $status = null)
  {
    // 1) Chỉ sửa end_date khi còn hiệu lực.
    $stmt = $db->prepare("SELECT id, start_date, end_date, status FROM member_services WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $record = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$record) {
      return [false, 'Không tìm thấy bản ghi cần sửa.'];
    }

    if (normalizeMemberServiceStatus($record['status']) !== 'còn hiệu lực') {
      return [false, 'Chỉ được sửa end_date khi dịch vụ còn hiệu lực.'];
    }

    if (empty($endDate)) {
      $endDate = $record['end_date'];
    }

    if (!empty($endDate) && $endDate < $record['start_date']) {
      return [false, 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.'];
    }

    $currentStatus = normalizeMemberServiceStatus($record['status']);
    $nextStatus = !empty($status) ? normalizeMemberServiceStatus($status) : $currentStatus;

    if ($currentStatus !== 'còn hiệu lực' && $nextStatus !== $currentStatus) {
      return [false, 'Chỉ được đổi trạng thái khi dịch vụ còn hiệu lực.'];
    }

    if ($currentStatus === 'còn hiệu lực' && !in_array($nextStatus, ['còn hiệu lực', 'đã dùng'], true)) {
      return [false, 'Trạng thái chỉ được chuyển sang Đã dùng khi đang còn hiệu lực.'];
    }

    try {
      // 2) Update end_date và trạng thái hợp lệ.
      $stmt = $db->prepare("UPDATE member_services SET end_date = ?, status = ? WHERE id = ?");
      $stmt->execute([
        $endDate,
        getMemberServiceStorageStatus($db, $nextStatus),
        $id
      ]);
      return [true, 'Cập nhật ngày kết thúc thành công.'];
    } catch (PDOException $e) {
      return [false, 'Lỗi: ' . $e->getMessage()];
    }
  }
}

try {
  $expiredStatus = getMemberServiceStorageStatus($db, 'hết hạn');
  $activeStatus = getMemberServiceStorageStatus($db, 'còn hiệu lực');
  $expireStmt = $db->prepare("UPDATE member_services SET status = ? WHERE end_date IS NOT NULL AND end_date < CURDATE() AND status = ?");
  $expireStmt->execute([$expiredStatus, $activeStatus]);
} catch (PDOException $e) {
  // Không chặn trang nếu đồng bộ trạng thái tự động gặp lỗi.
}

$memberServiceWhereClauses = [];
$memberServiceParams = [];

if ($filterMemberId !== '') {
  $memberServiceWhereClauses[] = 'ms.member_id = ?';
  $memberServiceParams[] = (int) $filterMemberId;
}

if ($filterServiceId !== '') {
  $memberServiceWhereClauses[] = 'ms.service_id = ?';
  $memberServiceParams[] = (int) $filterServiceId;
}

if ($filterType !== '') {
  $memberServiceWhereClauses[] = 's.type = ?';
  $memberServiceParams[] = $filterType;
}

if ($filterStatus !== '') {
  $memberServiceWhereClauses[] = 'ms.status = ?';
  $memberServiceParams[] = getMemberServiceStorageStatus($db, $filterStatus);
}

if ($filterFromDate !== '') {
  $memberServiceWhereClauses[] = 'DATE(ms.start_date) >= ?';
  $memberServiceParams[] = $filterFromDate;
}

if ($filterToDate !== '') {
  $memberServiceWhereClauses[] = 'DATE(ms.start_date) <= ?';
  $memberServiceParams[] = $filterToDate;
}

$memberServiceWhereSql = !empty($memberServiceWhereClauses) ? ' WHERE ' . implode(' AND ', $memberServiceWhereClauses) : '';

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'add');

      $member_id = intval($_POST['member_id']);
      $service_id = intval($_POST['service_id']);
      $start_date = sanitize($_POST['start_date']);
      $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;

      [$ok, $message] = assignService($db, $member_id, $service_id, $start_date, $end_date);
      setFlashMessage($ok ? 'success' : 'danger', $message);

        redirect('member_services.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'edit');

      $id = intval($_POST['id']);
      $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
      $status = isset($_POST['status']) ? sanitize($_POST['status']) : null;

      [$ok, $message] = updateEndDate($db, $id, $end_date, $status);
      setFlashMessage($ok ? 'success' : 'danger', $message);

        redirect('member_services.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'delete');

      $id = intval($_POST['id']);
      [$ok, $message] = cancelService($db, $id);
      setFlashMessage($ok ? 'success' : 'danger', $message);

        redirect('member_services.php');
        exit;
    }
}

// Lấy danh sách dịch vụ đã gán kèm tên hội viên và dịch vụ
$stmt = $db->prepare("
  SELECT ms.*, 
       m.full_name AS member_name,
       s.name AS service_name, s.type AS service_type, s.price AS service_price
  FROM member_services ms
  LEFT JOIN members m ON ms.member_id = m.id
  LEFT JOIN services s ON ms.service_id = s.id" . $memberServiceWhereSql . "
  ORDER BY ms.start_date DESC
");
$stmt->execute($memberServiceParams);
$records = $stmt->fetchAll();

foreach ($records as &$record) {
  $record['status'] = normalizeMemberServiceStatus($record['status'] ?? 'còn hiệu lực');
  $record['is_expired'] = !empty($record['end_date']) && isPastDate($record['end_date']);
  if ($record['is_expired'] && $record['status'] === 'còn hiệu lực') {
    $record['status'] = 'hết hạn';
  }
}
unset($record);

// Lấy danh sách hội viên (active)
$stmt = $db->query("SELECT id, full_name, phone FROM members WHERE status = 'active' ORDER BY full_name ASC");
$members = $stmt->fetchAll();

// Lấy danh sách dịch vụ (hoạt động)
$stmt = $db->query("SELECT id, name, type, price FROM services WHERE status = 'hoạt động' ORDER BY name ASC");
$services = $stmt->fetchAll();

// Lấy flash message
$flash = getFlashMessage();

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
            <h1 class="m-0">Gán dịch vụ cho hội viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dịch vụ hội viên</li>
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
          $filterAction = 'member_services.php';
          $filterFieldsHtml = '
            <div class="col-md-3"><div class="form-group mb-0"><label>Hội viên</label><select name="member_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($members as $member) {
            $selected = (string) $filterMemberId === (string) $member['id'] ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . (int) $member['id'] . '" ' . $selected . '>' . htmlspecialchars($member['full_name']) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-3"><div class="form-group mb-0"><label>Dịch vụ</label><select name="service_id" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($services as $svc) {
            $selected = (string) $filterServiceId === (string) $svc['id'] ? 'selected' : '';
            $label = $svc['name'] . ' (' . $svc['type'] . ')';
            $filterFieldsHtml .= '<option value="' . (int) $svc['id'] . '" ' . $selected . '>' . htmlspecialchars($label) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Loại</label><select name="type" class="form-control"><option value="">-- Tất cả --</option><option value="thư giãn" ' . ($filterType === 'thư giãn' ? 'selected' : '') . '>Thư giãn</option><option value="xoa bóp" ' . ($filterType === 'xoa bóp' ? 'selected' : '') . '>Xoa bóp</option><option value="hỗ trợ" ' . ($filterType === 'hỗ trợ' ? 'selected' : '') . '>Hỗ trợ</option></select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="còn hiệu lực" ' . ($filterStatus === 'còn hiệu lực' ? 'selected' : '') . '>Còn hiệu lực</option><option value="đã dùng" ' . ($filterStatus === 'đã dùng' ? 'selected' : '') . '>Đã dùng</option><option value="hết hạn" ' . ($filterStatus === 'hết hạn' ? 'selected' : '') . '>Hết hạn</option><option value="bị hủy" ' . ($filterStatus === 'bị hủy' ? 'selected' : '') . '>Bị hủy</option></select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Từ ngày</label><input type="date" name="from_date" class="form-control" value="' . htmlspecialchars($filterFromDate) . '"></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Đến ngày</label><input type="date" name="to_date" class="form-control" value="' . htmlspecialchars($filterToDate) . '"></div></div>
          ';
          include 'layout/filter-card.php';
        ?>

        <!-- Thông báo -->
        <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <?= $flash['message'] ?>
        </div>
        <?php endif; ?>

        <!-- Thống kê nhanh -->
        <div class="row mb-3">
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-info"><i class="fas fa-concierge-bell"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Tổng đã gán</span>
                <span class="info-box-number"><?= count($records) ?></span>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-success"><i class="fas fa-check-circle"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Còn dùng được</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return normalizeMemberServiceStatus($r['status'] ?? 'còn hiệu lực') === 'còn hiệu lực'; })) ?>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-secondary"><i class="fas fa-history"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Đã sử dụng xong</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return normalizeMemberServiceStatus($r['status'] ?? 'còn hiệu lực') === 'đã dùng'; })) ?>
                </span>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách dịch vụ đã gán</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addModal">
                    <i class="fas fa-plus"></i> Gán dịch vụ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Dịch vụ</th>
                    <th>Loại</th>
                    <th>Giá</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($records as $row): ?>
                  <tr>
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['member_name'] ?? 'N/A') ?></td>
                    <td><?= htmlspecialchars($row['service_name'] ?? 'N/A') ?></td>
                    <td>
                      <?php 
                        $typeClass = ['thư giãn' => 'badge-info', 'xoa bóp' => 'badge-warning', 'hỗ trợ' => 'badge-primary'];
                        $cls = $typeClass[$row['service_type'] ?? ''] ?? 'badge-secondary';
                      ?>
                      <span class="badge <?= $cls ?>"><?= htmlspecialchars($row['service_type'] ?? '') ?></span>
                    </td>
                    <td><?= isset($row['service_price']) ? number_format($row['service_price'], 0, ',', '.') . 'đ' : 'N/A' ?></td>
                    <td><?= date('d/m/Y', strtotime($row['start_date'])) ?></td>
                    <td><?= $row['end_date'] ? date('d/m/Y', strtotime($row['end_date'])) : '<span class="text-muted">—</span>' ?></td>
                    <td>
                      <?php if (!empty($row['is_expired'])): ?>
                        <span class="badge badge-secondary">Hết hạn</span>
                      <?php elseif (normalizeMemberServiceStatus($row['status'] ?? 'còn hiệu lực') === 'còn hiệu lực'): ?>
                        <span class="badge badge-success"><?= memberServiceStatusLabel('còn hiệu lực') ?></span>
                      <?php elseif (normalizeMemberServiceStatus($row['status'] ?? 'còn hiệu lực') === 'đã dùng'): ?>
                        <span class="badge badge-secondary"><?= memberServiceStatusLabel('đã dùng') ?></span>
                      <?php elseif (normalizeMemberServiceStatus($row['status'] ?? 'còn hiệu lực') === 'hết hạn'): ?>
                        <span class="badge badge-warning"><?= memberServiceStatusLabel('hết hạn') ?></span>
                      <?php else: ?>
                        <span class="badge badge-danger"><?= memberServiceStatusLabel('bị hủy') ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $row['id'] ?>"
                        data-member-id="<?= $row['member_id'] ?>"
                        data-member-name="<?= htmlspecialchars($row['member_name'] ?? 'N/A') ?>"
                        data-service-id="<?= $row['service_id'] ?>"
                        data-service-name="<?= htmlspecialchars($row['service_name'] ?? 'N/A') ?>"
                        data-service-type="<?= htmlspecialchars($row['service_type'] ?? '') ?>"
                        data-start-date="<?= $row['start_date'] ?>"
                        data-end-date="<?= $row['end_date'] ?? '' ?>"
                        data-status="<?= htmlspecialchars(normalizeMemberServiceStatus($row['status'] ?? 'còn hiệu lực')) ?>"
                        data-editable="<?= memberServiceCanEdit($row['status'] ?? 'còn hiệu lực') ? '1' : '0' ?>"
                        <?= memberServiceCanEdit($row['status'] ?? 'còn hiệu lực') ? 'data-toggle="modal" data-target="#editModal"' : 'disabled title="Chỉ được sửa khi trạng thái còn hiệu lực"' ?>
                        >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $row['id'] ?>"
                        data-member="<?= htmlspecialchars($row['member_name'] ?? 'N/A') ?>"
                        data-service="<?= htmlspecialchars($row['service_name'] ?? 'N/A') ?>"
                        data-toggle="modal" data-target="#deleteModal">
                        <i class="fas fa-ban"></i>
                      </button>
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

    <!-- Modal Thêm -->
    <div class="modal fade" id="addModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_services.php" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header bg-primary">
              <h5 class="modal-title"><i class="fas fa-concierge-bell"></i> Gán dịch vụ cho hội viên</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control select2" name="member_id" data-field="member_id" style="width: 100%;">
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($members)): ?>
                  <small class="text-danger">Chưa có hội viên nào.</small>
                <?php endif; ?>
              </div>
              <div class="form-group">
                <label>Dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control select2" name="service_id" data-field="service_id" style="width: 100%;">
                  <option value="">-- Chọn dịch vụ --</option>
                  <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>">
                      <?= htmlspecialchars($svc['name']) ?> (<?= $svc['type'] ?>) - <?= number_format($svc['price'], 0, ',', '.') ?>đ
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <?php if (empty($services)): ?>
                  <small class="text-danger">Chưa có dịch vụ nào.</small>
                <?php endif; ?>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" data-field="start_date" value="<?= date('Y-m-d') ?>">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" data-field="end_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" data-field="status">
                  <option value="còn hiệu lực">Còn hiệu lực</option>
                  <option value="đã dùng">Đã dùng</option>
                  <option value="hết hạn">Hết hạn</option>
                  <option value="bị hủy">Bị hủy</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Lưu</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Sửa -->
    <div class="modal fade" id="editModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_services.php" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <div class="modal-header bg-warning">
              <h5 class="modal-title"><i class="fas fa-edit"></i> Sửa dịch vụ hội viên</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Hội viên</label>
                <select class="form-control" name="member_id" id="edit-member_id" data-field="member_id" disabled>
                  <option value="">-- Chọn hội viên --</option>
                  <?php foreach ($members as $member): ?>
                    <option value="<?= $member['id'] ?>">
                      <?= htmlspecialchars($member['full_name']) ?> - <?= htmlspecialchars($member['phone'] ?? '') ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Dịch vụ</label>
                <select class="form-control" name="service_id" id="edit-service_id" data-field="service_id" disabled>
                  <option value="">-- Chọn dịch vụ --</option>
                  <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>">
                      <?= htmlspecialchars($svc['name']) ?> (<?= $svc['type'] ?>) - <?= number_format($svc['price'], 0, ',', '.') ?>đ
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ngày bắt đầu</label>
                <input type="date" class="form-control" name="start_date" id="edit-start_date" data-field="start_date" disabled>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ngày kết thúc</label>
                <input type="date" class="form-control" name="end_date" id="edit-end_date" data-field="end_date">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status" data-field="status">
                  <option value="còn hiệu lực">Còn hiệu lực</option>
                  <option value="đã dùng">Đã dùng</option>
                  <option value="hết hạn">Hết hạn</option>
                  <option value="bị hủy">Bị hủy</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Cập nhật</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Xóa -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="member_services.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header bg-danger">
              <h5 class="modal-title text-white"><i class="fas fa-trash"></i> Xác nhận xóa</h5>
              <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa dịch vụ <strong id="delete-service"></strong> của hội viên <strong id="delete-member"></strong>?</p>
              <p class="text-danger"><small>Hành động này không thể hoàn tác!</small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Xóa</button>
            </div>
          </form>
        </div>
      </div>
    </div>

  </div>

<?php include 'layout/footer.php'; ?>

<!-- Script xử lý modal -->
<script>
$(function() {
  if ($.fn.select2) {
    $('.select2').not('.select2-hidden-accessible').each(function() {
      $(this).select2({
        theme: 'bootstrap4',
        placeholder: 'Tìm kiếm...',
        allowClear: true,
        dropdownParent: $(this).closest('.modal').length ? $(this).closest('.modal') : $(document.body)
      });
    });
  }

  $('#deleteModal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    $('#delete-id').val(button.data('id'));
    $('#delete-member').text(button.data('member'));
    $('#delete-service').text(button.data('service'));
  });

  $('#editModal').on('show.bs.modal', function(event) {
    var button = $(event.relatedTarget);
    var status = button.attr('data-status') || 'còn hiệu lực';

    $('#edit-id').val(button.attr('data-id'));
    $('#edit-member_id').val(button.attr('data-member-id'));
    $('#edit-service_id').val(button.attr('data-service-id'));
    $('#edit-start_date').val(button.attr('data-start-date'));
    $('#edit-end_date').val(button.attr('data-end-date'));
    $('#edit-status').val(status);

    $('#edit-end_date').prop('disabled', false);
    $('#edit-status').prop('disabled', false);
  });
});

(function() {
  function label(field) {
    if (field === 'member_id') return 'Vui lòng chọn hội viên';
    if (field === 'service_id') return 'Vui lòng chọn dịch vụ';
    if (field === 'start_date') return 'Vui lòng chọn ngày bắt đầu';
    if (field === 'end_date') return 'Vui lòng chọn ngày kết thúc';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }
  function box(input) { return input.closest('.form-group')?.querySelector('small.text-danger') || null; }
  function show(input, message) { const b = box(input); if (b) { b.textContent = message; b.style.display = 'block'; } input.classList.add('is-invalid'); }
  function clear(input) { const b = box(input); if (b) { b.textContent = ''; b.style.display = 'none'; } input.classList.remove('is-invalid'); }
  function validate(input) { const field = input.getAttribute('data-field'); const value = String(input.value || '').trim(); clear(input); if (!field || input.disabled) return true; if (!value) { show(input, label(field)); return false; } return true; }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>