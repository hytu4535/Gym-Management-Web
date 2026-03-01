<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$db = getDB();

function buildReportData(PDO $db, $type, $periodStart, $periodEnd)
{
  $data = [
    'period_start' => $periodStart,
    'period_end' => $periodEnd
  ];

  if ($type === 'doanh thu') {
    $stmt = $db->prepare("SELECT COUNT(*) AS total_orders, COALESCE(SUM(total_amount), 0) AS total_revenue FROM orders WHERE DATE(order_date) BETWEEN ? AND ? AND status IN ('confirmed', 'delivered')");
    $stmt->execute([$periodStart, $periodEnd]);
    $result = $stmt->fetch();

    $importStmt = $db->prepare("SELECT COALESCE(SUM(total_amount), 0) AS total_import FROM import_slips WHERE DATE(import_date) BETWEEN ? AND ? AND status = 'Đã nhập'");
    $importStmt->execute([$periodStart, $periodEnd]);
    $importResult = $importStmt->fetch();

    $revenue = (float) ($result['total_revenue'] ?? 0);
    $importCost = (float) ($importResult['total_import'] ?? 0);

    $data['total_orders'] = (int) ($result['total_orders'] ?? 0);
    $data['total_revenue'] = $revenue;
    $data['total_import_cost'] = $importCost;
    $data['estimated_profit'] = $revenue - $importCost;
  } elseif ($type === 'hoi vien') {
    $stmt = $db->prepare("SELECT COUNT(*) AS new_members FROM members WHERE join_date BETWEEN ? AND ?");
    $stmt->execute([$periodStart, $periodEnd]);
    $newMemberResult = $stmt->fetch();

    $activeStmt = $db->query("SELECT COUNT(*) FROM members WHERE status = 'active'");
    $inactiveStmt = $db->query("SELECT COUNT(*) FROM members WHERE status = 'inactive'");
    $totalStmt = $db->query("SELECT COUNT(*) FROM members");

    $data['total_members'] = (int) $totalStmt->fetchColumn();
    $data['new_members'] = (int) ($newMemberResult['new_members'] ?? 0);
    $data['active_members'] = (int) $activeStmt->fetchColumn();
    $data['inactive_members'] = (int) $inactiveStmt->fetchColumn();
  } elseif ($type === 'thiet bi') {
    $totalStmt = $db->query("SELECT COUNT(*) AS equipment_count, COALESCE(SUM(quantity), 0) AS total_quantity FROM equipment");
    $totalResult = $totalStmt->fetch();

    $maintenanceStmt = $db->prepare("SELECT COUNT(*) FROM equipment_maintenance WHERE maintenance_date BETWEEN ? AND ?");
    $maintenanceStmt->execute([$periodStart, $periodEnd]);

    $inUseStmt = $db->query("SELECT COUNT(*) FROM equipment WHERE status = 'dang su dung'");
    $maintainStmt = $db->query("SELECT COUNT(*) FROM equipment WHERE status = 'bao tri'");
    $stoppedStmt = $db->query("SELECT COUNT(*) FROM equipment WHERE status = 'ngung hoat dong'");

    $data['equipment_count'] = (int) ($totalResult['equipment_count'] ?? 0);
    $data['total_quantity'] = (int) ($totalResult['total_quantity'] ?? 0);
    $data['maintenance_records'] = (int) $maintenanceStmt->fetchColumn();
    $data['in_use_count'] = (int) $inUseStmt->fetchColumn();
    $data['maintenance_count'] = (int) $maintainStmt->fetchColumn();
    $data['stopped_count'] = (int) $stoppedStmt->fetchColumn();
  } elseif ($type === 'dich vu') {
    $totalServicesStmt = $db->query("SELECT COUNT(*) FROM services");
    $activeServicesStmt = $db->query("SELECT COUNT(*) FROM services WHERE status = 'hoạt động'");
    $inactiveServicesStmt = $db->query("SELECT COUNT(*) FROM services WHERE status = 'không hoạt động'");

    $usageStmt = $db->prepare("SELECT COUNT(*) FROM member_services WHERE start_date BETWEEN ? AND ?");
    $usageStmt->execute([$periodStart, $periodEnd]);

    $data['total_services'] = (int) $totalServicesStmt->fetchColumn();
    $data['active_services'] = (int) $activeServicesStmt->fetchColumn();
    $data['inactive_services'] = (int) $inactiveServicesStmt->fetchColumn();
    $data['service_usages_in_period'] = (int) $usageStmt->fetchColumn();
  } else {
    $data['note'] = 'Báo cáo loại khác. Chưa có bộ chỉ số mặc định.';
  }

  return $data;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_report_status_id'])) {
  $reportId = intval($_POST['update_report_status_id']);
  $newStatus = sanitize($_POST['new_status']);
  $allowedStatuses = ['draft', 'completed', 'archived'];

  if (!in_array($newStatus, $allowedStatuses, true)) {
    echo "<script>alert('Trạng thái báo cáo không hợp lệ!');window.location='reports.php';</script>";
    exit;
  }

  $updateStmt = $db->prepare("UPDATE reports SET status = ? WHERE id = ?");
  if ($updateStmt->execute([$newStatus, $reportId])) {
    echo "<script>alert('Cập nhật trạng thái báo cáo thành công!');window.location='reports.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật trạng thái báo cáo!');window.location='reports.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_report_id'])) {
  $reportId = intval($_POST['delete_report_id']);

  $deleteStmt = $db->prepare("DELETE FROM reports WHERE id = ?");
  if ($deleteStmt->execute([$reportId])) {
    echo "<script>alert('Xóa báo cáo thành công!');window.location='reports.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi xóa báo cáo!');window.location='reports.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_report_id'])) {
  $reportId = intval($_POST['edit_report_id']);
  $reportType = sanitize($_POST['edit_report_type']);
  $title = sanitize($_POST['edit_title']);
  $description = sanitize($_POST['edit_description']);
  $periodStart = sanitize($_POST['edit_period_start']);
  $periodEnd = sanitize($_POST['edit_period_end']);
  $status = sanitize($_POST['edit_status']);

  $allowedTypes = ['doanh thu', 'hoi vien', 'thiet bi', 'dich vu', 'khac'];
  $allowedStatuses = ['draft', 'completed', 'archived'];

  if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = 'khac';
  }

  if (!in_array($status, $allowedStatuses, true)) {
    $status = 'draft';
  }

  if (strtotime($periodStart) > strtotime($periodEnd)) {
    echo "<script>alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');window.location='reports.php';</script>";
    exit;
  }

  $reportData = buildReportData($db, $reportType, $periodStart, $periodEnd);
  $reportDataJson = json_encode($reportData, JSON_UNESCAPED_UNICODE);

  $updateStmt = $db->prepare("UPDATE reports SET type = ?, title = ?, description = ?, period_start = ?, period_end = ?, data = ?, status = ? WHERE id = ?");
  if ($updateStmt->execute([$reportType, $title, $description, $periodStart, $periodEnd, $reportDataJson, $status, $reportId])) {
    echo "<script>alert('Cập nhật báo cáo thành công!');window.location='reports.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật báo cáo!');window.location='reports.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_type'])) {
  $reportType = sanitize($_POST['report_type']);
  $title = sanitize($_POST['title']);
  $description = sanitize($_POST['description']);
  $periodStart = sanitize($_POST['period_start']);
  $periodEnd = sanitize($_POST['period_end']);
  $status = sanitize($_POST['status']);

  $allowedTypes = ['doanh thu', 'hoi vien', 'thiet bi', 'dich vu', 'khac'];
  $allowedStatuses = ['draft', 'completed', 'archived'];

  if (!in_array($reportType, $allowedTypes, true)) {
    $reportType = 'khac';
  }

  if (!in_array($status, $allowedStatuses, true)) {
    $status = 'draft';
  }

  if (strtotime($periodStart) > strtotime($periodEnd)) {
    echo "<script>alert('Ngày bắt đầu không được lớn hơn ngày kết thúc!');window.location='reports.php';</script>";
    exit;
  }

  $createdBy = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
  if ($createdBy <= 0) {
    $fallbackUserStmt = $db->query("SELECT id FROM users ORDER BY id ASC LIMIT 1");
    $fallbackUserId = $fallbackUserStmt->fetchColumn();
    $createdBy = $fallbackUserId ? intval($fallbackUserId) : 0;
  }

  if ($createdBy <= 0) {
    echo "<script>alert('Không tìm thấy người tạo báo cáo hợp lệ!');window.location='reports.php';</script>";
    exit;
  }

  $reportData = buildReportData($db, $reportType, $periodStart, $periodEnd);
  $reportDataJson = json_encode($reportData, JSON_UNESCAPED_UNICODE);

  $insertStmt = $db->prepare("INSERT INTO reports (type, title, description, period_start, period_end, created_by, data, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
  if ($insertStmt->execute([$reportType, $title, $description, $periodStart, $periodEnd, $createdBy, $reportDataJson, $status])) {
    echo "<script>alert('Tạo báo cáo thành công!');window.location='reports.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi tạo báo cáo!');window.location='reports.php';</script>";
  }
  exit;
}

$reportsStmt = $db->query("SELECT r.id, r.type, r.title, r.description, r.period_start, r.period_end, r.status, r.created_at, r.data, u.username FROM reports r INNER JOIN users u ON r.created_by = u.id ORDER BY r.id DESC");
$reports = $reportsStmt->fetchAll();

$typeLabels = [
  'doanh thu' => 'Doanh thu',
  'hoi vien' => 'Hội viên',
  'thiet bi' => 'Thiết bị',
  'dich vu' => 'Dịch vụ',
  'khac' => 'Khác'
];

$page_title = "Quản lý Báo Cáo";
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
            <h1 class="m-0">Quản lý Báo Cáo</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Báo Cáo</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Báo Cáo Thống Kê</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addReportModal">
                    <i class="fas fa-plus"></i> Tạo Báo Cáo
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Loại Báo Cáo</th>
                    <th>Tiêu Đề</th>
                    <th>Kỳ Báo Cáo</th>
                    <th>Người Tạo</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($reports as $report): ?>
                  <?php
                    $reportType = $report['type'];
                    $typeBadgeClass = 'badge-secondary';
                    if ($reportType === 'doanh thu') {
                      $typeBadgeClass = 'badge-success';
                    } elseif ($reportType === 'hoi vien') {
                      $typeBadgeClass = 'badge-info';
                    } elseif ($reportType === 'thiet bi') {
                      $typeBadgeClass = 'badge-warning';
                    } elseif ($reportType === 'dich vu') {
                      $typeBadgeClass = 'badge-primary';
                    }
                  ?>
                  <tr>
                    <td><?= $report['id'] ?></td>
                    <td><span class="badge <?= $typeBadgeClass ?>"><?= $typeLabels[$reportType] ?? ucfirst($reportType) ?></span></td>
                    <td><?= htmlspecialchars($report['title']) ?></td>
                    <td><?= date('d/m/Y', strtotime($report['period_start'])) ?> - <?= date('d/m/Y', strtotime($report['period_end'])) ?></td>
                    <td><?= htmlspecialchars($report['username']) ?></td>
                    <td>
                      <?php if ($report['status'] === 'completed'): ?>
                        <span class="badge badge-success">Completed</span>
                      <?php elseif ($report['status'] === 'archived'): ?>
                        <span class="badge badge-secondary">Archived</span>
                      <?php else: ?>
                        <span class="badge badge-warning">Draft</span>
                      <?php endif; ?>
                    </td>
                    <td><?= date('d/m/Y', strtotime($report['created_at'])) ?></td>
                    <td>
                      <button class="btn btn-info btn-sm view-report-btn" 
                        data-title="<?= htmlspecialchars($report['title']) ?>"
                        data-type="<?= htmlspecialchars($typeLabels[$reportType] ?? ucfirst($reportType)) ?>"
                        data-description="<?= htmlspecialchars($report['description'] ?? '') ?>"
                        data-period="<?= date('d/m/Y', strtotime($report['period_start'])) . ' - ' . date('d/m/Y', strtotime($report['period_end'])) ?>"
                        data-data='<?= htmlspecialchars($report['data'] ?: '{}', ENT_QUOTES, 'UTF-8') ?>'>
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm edit-report-btn"
                        data-id="<?= $report['id'] ?>"
                        data-type="<?= htmlspecialchars($report['type'], ENT_QUOTES, 'UTF-8') ?>"
                        data-title="<?= htmlspecialchars($report['title'], ENT_QUOTES, 'UTF-8') ?>"
                        data-description="<?= htmlspecialchars($report['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                        data-period-start="<?= $report['period_start'] ?>"
                        data-period-end="<?= $report['period_end'] ?>"
                        data-status="<?= htmlspecialchars($report['status'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fas fa-edit"></i>
                      </button>
                      <?php if ($report['status'] === 'draft'): ?>
                        <form method="POST" action="reports.php" style="display:inline-block;">
                          <input type="hidden" name="update_report_status_id" value="<?= $report['id'] ?>">
                          <input type="hidden" name="new_status" value="completed">
                          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i></button>
                        </form>
                      <?php endif; ?>
                      <?php if ($report['status'] !== 'archived'): ?>
                        <form method="POST" action="reports.php" style="display:inline-block;">
                          <input type="hidden" name="update_report_status_id" value="<?= $report['id'] ?>">
                          <input type="hidden" name="new_status" value="archived">
                          <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-archive"></i></button>
                        </form>
                      <?php endif; ?>
                      <form method="POST" action="reports.php" style="display:inline-block;" onsubmit="return confirm('Bạn có chắc muốn xóa báo cáo này?');">
                        <input type="hidden" name="delete_report_id" value="<?= $report['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                      </form>
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

<?php include 'layout/footer.php'; ?>

<div class="modal fade" id="addReportModal" tabindex="-1" role="dialog" aria-labelledby="addReportModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="reports.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addReportModalLabel">Tạo Báo Cáo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="report_type">Loại Báo Cáo</label>
            <select class="form-control" id="report_type" name="report_type" required>
              <option value="doanh thu">Doanh thu</option>
              <option value="hoi vien">Hội viên</option>
              <option value="thiet bi">Thiết bị</option>
              <option value="dich vu">Dịch vụ</option>
              <option value="khac">Khác</option>
            </select>
          </div>
          <div class="form-group">
            <label for="title">Tiêu Đề</label>
            <input type="text" class="form-control" id="title" name="title" required maxlength="255">
          </div>
          <div class="form-group">
            <label for="description">Mô tả</label>
            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="period_start">Từ ngày</label>
              <input type="date" class="form-control" id="period_start" name="period_start" required>
            </div>
            <div class="form-group col-md-6">
              <label for="period_end">Đến ngày</label>
              <input type="date" class="form-control" id="period_end" name="period_end" required>
            </div>
          </div>
          <div class="form-group">
            <label for="status">Trạng thái</label>
            <select class="form-control" id="status" name="status" required>
              <option value="draft">Draft</option>
              <option value="completed">Completed</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Tạo báo cáo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editReportModal" tabindex="-1" role="dialog" aria-labelledby="editReportModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="reports.php">
        <input type="hidden" id="edit_report_id" name="edit_report_id">
        <div class="modal-header">
          <h5 class="modal-title" id="editReportModalLabel">Sửa Báo Cáo</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="edit_report_type">Loại Báo Cáo</label>
            <select class="form-control" id="edit_report_type" name="edit_report_type" required>
              <option value="doanh thu">Doanh thu</option>
              <option value="hoi vien">Hội viên</option>
              <option value="thiet bi">Thiết bị</option>
              <option value="dich vu">Dịch vụ</option>
              <option value="khac">Khác</option>
            </select>
          </div>
          <div class="form-group">
            <label for="edit_title">Tiêu Đề</label>
            <input type="text" class="form-control" id="edit_title" name="edit_title" required maxlength="255">
          </div>
          <div class="form-group">
            <label for="edit_description">Mô tả</label>
            <textarea class="form-control" id="edit_description" name="edit_description" rows="3"></textarea>
          </div>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="edit_period_start">Từ ngày</label>
              <input type="date" class="form-control" id="edit_period_start" name="edit_period_start" required>
            </div>
            <div class="form-group col-md-6">
              <label for="edit_period_end">Đến ngày</label>
              <input type="date" class="form-control" id="edit_period_end" name="edit_period_end" required>
            </div>
          </div>
          <div class="form-group">
            <label for="edit_status">Trạng thái</label>
            <select class="form-control" id="edit_status" name="edit_status" required>
              <option value="draft">Draft</option>
              <option value="completed">Completed</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="viewReportModal" tabindex="-1" role="dialog" aria-labelledby="viewReportModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewReportModalLabel">Chi tiết báo cáo</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Tiêu đề:</strong> <span id="view_report_title"></span></p>
        <p><strong>Loại:</strong> <span id="view_report_type"></span></p>
        <p><strong>Kỳ báo cáo:</strong> <span id="view_report_period"></span></p>
        <p><strong>Mô tả:</strong> <span id="view_report_description"></span></p>
        <div class="form-group mb-0">
          <label><strong>Dữ liệu thống kê:</strong></label>
          <pre id="view_report_data" class="border rounded p-2 bg-light" style="max-height: 260px; overflow: auto;"></pre>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
$(document).ready(function() {
  $('.edit-report-btn').on('click', function() {
    $('#edit_report_id').val($(this).data('id'));
    $('#edit_report_type').val($(this).data('type'));
    $('#edit_title').val($(this).data('title'));
    $('#edit_description').val($(this).data('description'));
    $('#edit_period_start').val($(this).data('period-start'));
    $('#edit_period_end').val($(this).data('period-end'));
    $('#edit_status').val($(this).data('status'));
    $('#editReportModal').modal('show');
  });

  $('.view-report-btn').on('click', function() {
    $('#view_report_title').text($(this).data('title'));
    $('#view_report_type').text($(this).data('type'));
    $('#view_report_period').text($(this).data('period'));
    $('#view_report_description').text($(this).data('description') || '-');

    var rawData = $(this).attr('data-data');
    var formattedData = rawData;
    try {
      var parsed = JSON.parse(rawData || '{}');
      formattedData = JSON.stringify(parsed, null, 2);
    } catch (e) {
      formattedData = rawData || '{}';
    }

    $('#view_report_data').text(formattedData);
    $('#viewReportModal').modal('show');
  });
});
</script>
