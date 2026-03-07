<?php
require_once '../includes/functions.php';

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_import_status_id'])) {
  $importSlipId = intval($_POST['update_import_status_id']);
  $newStatus = sanitize($_POST['new_status']);

  $allowedStatus = ['Đã nhập', 'Đã hủy'];
  if (!in_array($newStatus, $allowedStatus, true)) {
    echo "<script>alert('Trạng thái cập nhật không hợp lệ!');window.location='import-slips.php';</script>";
    exit;
  }

  $statusCheckStmt = $db->prepare("SELECT status FROM import_slips WHERE id = ?");
  $statusCheckStmt->execute([$importSlipId]);
  $currentStatus = $statusCheckStmt->fetchColumn();

  if ($currentStatus === false) {
    echo "<script>alert('Phiếu nhập không tồn tại!');window.location='import-slips.php';</script>";
    exit;
  }

  if ($currentStatus !== 'Đang chờ duyệt') {
    echo "<script>alert('Chỉ phiếu đang chờ duyệt mới được cập nhật trạng thái!');window.location='import-slips.php';</script>";
    exit;
  }

  $updateStatusStmt = $db->prepare("UPDATE import_slips SET status = ? WHERE id = ?");
  if ($updateStatusStmt->execute([$newStatus, $importSlipId])) {
    echo "<script>alert('Cập nhật trạng thái phiếu nhập thành công!');window.location='import-slips.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi cập nhật trạng thái phiếu nhập!');window.location='import-slips.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_id'])) {
  $supplierId = intval($_POST['supplier_id']);
  $staffId = intval($_POST['staff_id']);
  $totalAmount = floatval($_POST['total_amount']);
  $importDateInput = sanitize($_POST['import_date']);
  $note = sanitize($_POST['note']);
  $status = sanitize($_POST['status']);

  $allowedStatus = ['Đã nhập', 'Đang chờ duyệt', 'Đã hủy'];
  if (!in_array($status, $allowedStatus, true)) {
    $status = 'Đang chờ duyệt';
  }

  $supplierCheckStmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE id = ?");
  $supplierCheckStmt->execute([$supplierId]);

  $staffCheckStmt = $db->prepare("SELECT COUNT(*) FROM staff WHERE id = ?");
  $staffCheckStmt->execute([$staffId]);

  if ((int) $supplierCheckStmt->fetchColumn() === 0) {
    echo "<script>alert('Nhà cung cấp không tồn tại!');window.location='import-slips.php';</script>";
    exit;
  }

  if ((int) $staffCheckStmt->fetchColumn() === 0) {
    echo "<script>alert('Nhân viên không tồn tại!');window.location='import-slips.php';</script>";
    exit;
  }

  $importDate = date('Y-m-d H:i:s', strtotime($importDateInput));
  if ($importDate === '1970-01-01 00:00:00') {
    $importDate = date('Y-m-d H:i:s');
  }

  $insertStmt = $db->prepare("INSERT INTO import_slips (staff_id, supplier_id, total_amount, import_date, note, status) VALUES (?, ?, ?, ?, ?, ?)");
  if ($insertStmt->execute([$staffId, $supplierId, $totalAmount, $importDate, $note, $status])) {
    echo "<script>alert('Tạo phiếu nhập thành công!');window.location='import-slips.php';</script>";
  } else {
    echo "<script>alert('Lỗi khi tạo phiếu nhập!');window.location='import-slips.php';</script>";
  }
  exit;
}

$suppliersStmt = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers = $suppliersStmt->fetchAll();

$staffStmt = $db->query("SELECT id, full_name FROM staff ORDER BY full_name ASC");
$staffs = $staffStmt->fetchAll();

$importSlipsStmt = $db->query("SELECT i.id, i.total_amount, i.import_date, i.status, s.name AS supplier_name, st.full_name AS staff_name FROM import_slips i INNER JOIN suppliers s ON i.supplier_id = s.id INNER JOIN staff st ON i.staff_id = st.id ORDER BY i.id DESC");
$importSlips = $importSlipsStmt->fetchAll();

$page_title = "Quản lý Phiếu Nhập Kho";
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
            <h1 class="m-0">Quản lý Phiếu Nhập Kho</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Phiếu Nhập</li>
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
                <h3 class="card-title">Danh sách Phiếu Nhập Kho</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addImportModal">
                    <i class="fas fa-plus"></i> Tạo Phiếu Nhập
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>Mã Phiếu</th>
                    <th>Nhà Cung Cấp</th>
                    <th>Nhân Viên</th>
                    <th>Tổng Tiền</th>
                    <th>Ngày Nhập</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($importSlips as $importSlip): ?>
                  <tr>
                    <td>#PN<?= str_pad($importSlip['id'], 3, '0', STR_PAD_LEFT) ?></td>
                    <td><?= htmlspecialchars($importSlip['supplier_name']) ?></td>
                    <td><?= htmlspecialchars($importSlip['staff_name']) ?></td>
                    <td><?= number_format((float) $importSlip['total_amount'], 0, ',', '.') ?> VNĐ</td>
                    <td><?= date('d/m/Y', strtotime($importSlip['import_date'])) ?></td>
                    <td>
                      <?php if ($importSlip['status'] === 'Đã nhập'): ?>
                        <span class="badge badge-success">Đã nhập</span>
                      <?php elseif ($importSlip['status'] === 'Đang chờ duyệt'): ?>
                        <span class="badge badge-warning">Đang chờ duyệt</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Đã hủy</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-primary btn-sm"><i class="fas fa-print"></i></button>
                      <?php if ($importSlip['status'] === 'Đang chờ duyệt'): ?>
                        <form method="POST" action="import-slips.php" style="display:inline-block;">
                          <input type="hidden" name="update_import_status_id" value="<?= $importSlip['id'] ?>">
                          <input type="hidden" name="new_status" value="Đã nhập">
                          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Duyệt</button>
                        </form>
                        <form method="POST" action="import-slips.php" style="display:inline-block;">
                          <input type="hidden" name="update_import_status_id" value="<?= $importSlip['id'] ?>">
                          <input type="hidden" name="new_status" value="Đã hủy">
                          <button type="submit" class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Hủy</button>
                        </form>
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

<?php include 'layout/footer.php'; ?>

<!-- Modal Tạo Phiếu Nhập -->
<div class="modal fade" id="addImportModal" tabindex="-1" role="dialog" aria-labelledby="addImportModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="POST" action="import-slips.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addImportModalLabel">Tạo Phiếu Nhập</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?php if (empty($suppliers)): ?>
            <div class="alert alert-warning mb-3">Chưa có nhà cung cấp. Vui lòng thêm nhà cung cấp trước khi tạo phiếu nhập.</div>
          <?php endif; ?>

          <?php if (empty($staffs)): ?>
            <div class="alert alert-warning mb-3">Chưa có nhân viên. Vui lòng thêm nhân viên trước khi tạo phiếu nhập.</div>
          <?php endif; ?>

          <div class="form-group">
            <label for="supplier_id">Nhà Cung Cấp</label>
            <select class="form-control" id="supplier_id" name="supplier_id" required <?= empty($suppliers) ? 'disabled' : '' ?>>
              <option value="">-- Chọn nhà cung cấp --</option>
              <?php foreach ($suppliers as $supplier): ?>
                <option value="<?= $supplier['id'] ?>"><?= htmlspecialchars($supplier['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="staff_id">Nhân Viên</label>
            <select class="form-control" id="staff_id" name="staff_id" required <?= empty($staffs) ? 'disabled' : '' ?>>
              <option value="">-- Chọn nhân viên --</option>
              <?php foreach ($staffs as $staff): ?>
                <option value="<?= $staff['id'] ?>"><?= htmlspecialchars($staff['full_name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="total_amount">Tổng Tiền (VNĐ)</label>
            <input type="number" class="form-control" id="total_amount" name="total_amount" min="0" step="1000" required>
          </div>

          <div class="form-group">
            <label for="import_date">Ngày Nhập</label>
            <input type="datetime-local" class="form-control" id="import_date" name="import_date" required>
          </div>

          <div class="form-group">
            <label for="status">Trạng Thái</label>
            <select class="form-control" id="status" name="status" required>
              <option value="Đang chờ duyệt" selected>Đang chờ duyệt</option>
              <option value="Đã nhập">Đã nhập</option>
              <option value="Đã hủy">Đã hủy</option>
            </select>
          </div>

          <div class="form-group">
            <label for="note">Ghi chú</label>
            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" <?= (empty($suppliers) || empty($staffs)) ? 'disabled' : '' ?>>Tạo phiếu</button>
        </div>
      </form>
    </div>
  </div>
</div>
