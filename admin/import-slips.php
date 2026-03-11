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

$importSlipsStmt = $db->query("SELECT i.id, i.total_amount, i.import_date, i.note, i.status, s.name AS supplier_name, st.full_name AS staff_name FROM import_slips i INNER JOIN suppliers s ON i.supplier_id = s.id INNER JOIN staff st ON i.staff_id = st.id ORDER BY i.id DESC");
$importSlips = $importSlipsStmt->fetchAll();

$importSlipDetailsMap = [];
if (!empty($importSlips)) {
  $importIds = array_map(static function ($row) {
    return (int) $row['id'];
  }, $importSlips);

  $placeholders = implode(',', array_fill(0, count($importIds), '?'));
  $detailsSql = "SELECT d.import_id,
                        d.quantity,
                        d.import_price,
                        e.name AS equipment_name,
                        p.name AS product_name
                 FROM import_details d
                 LEFT JOIN equipment e ON e.id = d.equipment_id
                 LEFT JOIN products p ON p.id = d.product_id
                 WHERE d.import_id IN ($placeholders)
                 ORDER BY d.id ASC";
  $detailsStmt = $db->prepare($detailsSql);
  $detailsStmt->execute($importIds);
  $detailRows = $detailsStmt->fetchAll();

  foreach ($detailRows as $detail) {
    $importId = (int) $detail['import_id'];
    if (!isset($importSlipDetailsMap[$importId])) {
      $importSlipDetailsMap[$importId] = [];
    }

    $itemName = $detail['product_name'] ?: $detail['equipment_name'];
    $itemType = $detail['product_name'] ? 'Sản phẩm' : 'Thiết bị';

    $importSlipDetailsMap[$importId][] = [
      'item_name' => $itemName ?: 'N/A',
      'item_type' => $itemType,
      'quantity' => (int) $detail['quantity'],
      'import_price' => (float) $detail['import_price'],
    ];
  }
}

$importSlipsForJs = [];
foreach ($importSlips as $importSlip) {
  $importId = (int) $importSlip['id'];
  $details = $importSlipDetailsMap[$importId] ?? [];

  $importSlipsForJs[$importId] = [
    'id' => $importId,
    'code' => '#PN' . str_pad((string) $importId, 3, '0', STR_PAD_LEFT),
    'supplier_name' => $importSlip['supplier_name'],
    'staff_name' => $importSlip['staff_name'],
    'total_amount' => (float) $importSlip['total_amount'],
    'import_date_display' => date('d/m/Y H:i', strtotime($importSlip['import_date'])),
    'status' => $importSlip['status'],
    'note' => $importSlip['note'] ?? '',
    'details' => $details,
  ];
}

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
                      <button type="button" class="btn btn-info btn-sm view-import-btn" data-id="<?= (int) $importSlip['id'] ?>" title="Xem phiếu nhập">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button type="button" class="btn btn-primary btn-sm print-import-btn" data-id="<?= (int) $importSlip['id'] ?>" title="In phiếu nhập">
                        <i class="fas fa-print"></i>
                      </button>
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

<!-- Modal Xem Phiếu Nhập -->
<div class="modal fade" id="viewImportModal" tabindex="-1" role="dialog" aria-labelledby="viewImportModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewImportModalLabel">Chi tiết phiếu nhập</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="row mb-2">
          <div class="col-md-6"><strong>Mã phiếu:</strong> <span id="view_import_code">-</span></div>
          <div class="col-md-6"><strong>Ngày nhập:</strong> <span id="view_import_date">-</span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Nhà cung cấp:</strong> <span id="view_supplier">-</span></div>
          <div class="col-md-6"><strong>Nhân viên:</strong> <span id="view_staff">-</span></div>
        </div>
        <div class="row mb-2">
          <div class="col-md-6"><strong>Trạng thái:</strong> <span id="view_status">-</span></div>
          <div class="col-md-6"><strong>Tổng tiền:</strong> <span id="view_total">-</span></div>
        </div>
        <div class="row mb-3">
          <div class="col-12"><strong>Ghi chú:</strong> <span id="view_note">-</span></div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0">
            <thead>
              <tr>
                <th>Loại</th>
                <th>Tên mục</th>
                <th class="text-right">SL</th>
                <th class="text-right">Đơn giá nhập</th>
                <th class="text-right">Thành tiền</th>
              </tr>
            </thead>
            <tbody id="view_import_details_body">
              <tr>
                <td colspan="5" class="text-center text-muted">Không có dữ liệu chi tiết.</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    const importSlipMap = <?= json_encode($importSlipsForJs, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function formatCurrency(value) {
      const amount = Number(value) || 0;
      return amount.toLocaleString('vi-VN') + ' VNĐ';
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/\"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function getStatusBadge(status) {
      if (status === 'Đã nhập') {
        return '<span class="badge badge-success">Đã nhập</span>';
      }
      if (status === 'Đang chờ duyệt') {
        return '<span class="badge badge-warning">Đang chờ duyệt</span>';
      }
      return '<span class="badge badge-danger">Đã hủy</span>';
    }

    function buildDetailsRows(details) {
      if (!Array.isArray(details) || details.length === 0) {
        return '<tr><td colspan="5" class="text-center text-muted">Không có dữ liệu chi tiết.</td></tr>';
      }

      return details.map(function (detail) {
        const quantity = Number(detail.quantity) || 0;
        const importPrice = Number(detail.import_price) || 0;
        const lineTotal = quantity * importPrice;

        return '<tr>'
          + '<td>' + escapeHtml(detail.item_type) + '</td>'
          + '<td>' + escapeHtml(detail.item_name) + '</td>'
          + '<td class="text-right">' + quantity.toLocaleString('vi-VN') + '</td>'
          + '<td class="text-right">' + formatCurrency(importPrice) + '</td>'
          + '<td class="text-right">' + formatCurrency(lineTotal) + '</td>'
          + '</tr>';
      }).join('');
    }

    function renderImportDetails(importData) {
      $('#view_import_code').text(importData.code);
      $('#view_import_date').text(importData.import_date_display);
      $('#view_supplier').text(importData.supplier_name || '-');
      $('#view_staff').text(importData.staff_name || '-');
      $('#view_status').html(getStatusBadge(importData.status));
      $('#view_total').text(formatCurrency(importData.total_amount));
      $('#view_note').text(importData.note || 'Không có ghi chú');
      $('#view_import_details_body').html(buildDetailsRows(importData.details));
    }

    function openPrintWindow(importData) {
      const detailsRows = buildDetailsRows(importData.details);
      const printHtml = '<!DOCTYPE html>'
        + '<html lang="vi"><head><meta charset="UTF-8"><title>Phiếu nhập ' + escapeHtml(importData.code) + '</title>'
        + '<style>'
        + 'body{font-family:Arial,sans-serif;padding:20px;color:#222;}'
        + 'h2{margin:0 0 10px;} .meta{margin-bottom:16px;line-height:1.7;}'
        + 'table{width:100%;border-collapse:collapse;} th,td{border:1px solid #ddd;padding:8px;}'
        + 'th{background:#f5f5f5;text-align:left;} .text-right{text-align:right;} .total{margin-top:12px;font-weight:700;text-align:right;}'
        + '</style></head><body>'
        + '<h2>PHIẾU NHẬP KHO ' + escapeHtml(importData.code) + '</h2>'
        + '<div class="meta">'
        + '<div><strong>Ngày nhập:</strong> ' + escapeHtml(importData.import_date_display) + '</div>'
        + '<div><strong>Nhà cung cấp:</strong> ' + escapeHtml(importData.supplier_name || '-') + '</div>'
        + '<div><strong>Nhân viên:</strong> ' + escapeHtml(importData.staff_name || '-') + '</div>'
        + '<div><strong>Trạng thái:</strong> ' + escapeHtml(importData.status || '-') + '</div>'
        + '<div><strong>Ghi chú:</strong> ' + escapeHtml(importData.note || 'Không có ghi chú') + '</div>'
        + '</div>'
        + '<table><thead><tr><th>Loại</th><th>Tên mục</th><th class="text-right">SL</th><th class="text-right">Đơn giá nhập</th><th class="text-right">Thành tiền</th></tr></thead>'
        + '<tbody>' + detailsRows + '</tbody></table>'
        + '<div class="total">Tổng tiền: ' + formatCurrency(importData.total_amount) + '</div>'
        + '</body></html>';

      const printWindow = window.open('', '_blank', 'width=900,height=700');
      if (!printWindow) {
        alert('Trình duyệt đã chặn popup in. Vui lòng cho phép popup và thử lại.');
        return;
      }

      printWindow.document.open();
      printWindow.document.write(printHtml);
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
    }

    $(document).on('click', '.view-import-btn', function () {
      const importId = String($(this).data('id'));
      const importData = importSlipMap[importId];
      if (!importData) {
        alert('Không tìm thấy dữ liệu phiếu nhập.');
        return;
      }

      renderImportDetails(importData);
      $('#viewImportModal').modal('show');
    });

    $(document).on('click', '.print-import-btn', function () {
      const importId = String($(this).data('id'));
      const importData = importSlipMap[importId];
      if (!importData) {
        alert('Không tìm thấy dữ liệu phiếu nhập để in.');
        return;
      }

      openPrintWindow(importData);
    });
  })();
</script>
