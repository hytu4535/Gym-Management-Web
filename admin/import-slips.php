<?php
require_once '../includes/functions.php';

$db = getDB();

function importStatusLabelByIndex($statusIndex) {
  switch ((int) $statusIndex) {
    case 1:
      return 'Đã nhập';
    case 2:
      return 'Đang chờ duyệt';
    case 3:
      return 'Đã hủy';
    default:
      return 'Không xác định';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_import_status_id'])) {
  $importSlipId = intval($_POST['update_import_status_id']);
  $newStatusAction = sanitize($_POST['new_status_action'] ?? '');
  $newStatusIndex = null;

  if ($newStatusAction === 'approve') {
    $newStatusIndex = 1;
  } elseif ($newStatusAction === 'cancel') {
    $newStatusIndex = 3;
  }

  if ($newStatusIndex === null) {
    echo "<script>alert('Trạng thái cập nhật không hợp lệ!');window.location='import-slips.php';</script>";
    exit;
  }

  $statusCheckStmt = $db->prepare("SELECT status + 0 FROM import_slips WHERE id = ?");
  $statusCheckStmt->execute([$importSlipId]);
  $currentStatus = $statusCheckStmt->fetchColumn();

  if ($currentStatus === false) {
    echo "<script>alert('Phiếu nhập không tồn tại!');window.location='import-slips.php';</script>";
    exit;
  }

  if ((int) $currentStatus !== 2) {
    echo "<script>alert('Chỉ phiếu đang chờ duyệt mới được cập nhật trạng thái!');window.location='import-slips.php';</script>";
    exit;
  }

  try {
    $db->beginTransaction();

    if ($newStatusIndex === 1) {
      $detailStmt = $db->prepare("SELECT equipment_id, product_id, quantity FROM import_details WHERE import_id = ?");
      $detailStmt->execute([$importSlipId]);
      $detailRows = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

      $updateProductStockStmt = $db->prepare("UPDATE products SET stock_quantity = COALESCE(stock_quantity, 0) + ? WHERE id = ?");
      $updateEquipmentStockStmt = $db->prepare("UPDATE equipment SET quantity = COALESCE(quantity, 0) + ? WHERE id = ?");

      foreach ($detailRows as $detail) {
        $quantity = (int) ($detail['quantity'] ?? 0);
        if ($quantity <= 0) {
          continue;
        }

        $productId = (int) ($detail['product_id'] ?? 0);
        $equipmentId = (int) ($detail['equipment_id'] ?? 0);

        if ($productId > 0) {
          $updateProductStockStmt->execute([$quantity, $productId]);
          if ($updateProductStockStmt->rowCount() === 0) {
            throw new Exception('Không cập nhật được tồn kho sản phẩm.');
          }
        } elseif ($equipmentId > 0) {
          $updateEquipmentStockStmt->execute([$quantity, $equipmentId]);
          if ($updateEquipmentStockStmt->rowCount() === 0) {
            throw new Exception('Không cập nhật được số lượng thiết bị.');
          }
        }
      }
    }

    $updateStatusStmt = $db->prepare("UPDATE import_slips SET status = ? WHERE id = ?");
    if (!$updateStatusStmt->execute([$newStatusIndex, $importSlipId])) {
      throw new Exception('Không thể cập nhật trạng thái phiếu nhập.');
    }

    $db->commit();
    echo "<script>alert('Cập nhật trạng thái phiếu nhập thành công!');window.location='import-slips.php';</script>";
  } catch (Exception $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }

    echo "<script>alert('Lỗi khi cập nhật trạng thái phiếu nhập!');window.location='import-slips.php';</script>";
  }
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['supplier_id'])) {
  $supplierId = intval($_POST['supplier_id']);
  $staffId = intval($_POST['staff_id']);
  $importDateInput = sanitize($_POST['import_date']);
  $note = sanitize($_POST['note']);
  $detailTypes = $_POST['detail_type'] ?? [];
  $detailItemIds = $_POST['detail_item_id'] ?? [];
  $detailQuantities = $_POST['detail_quantity'] ?? [];
  $detailImportPrices = $_POST['detail_import_price'] ?? [];

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

  $details = [];
  $totalAmount = 0;

  foreach ($detailTypes as $idx => $type) {
    $itemType = sanitize($type);
    $itemId = intval($detailItemIds[$idx] ?? 0);
    $quantity = intval($detailQuantities[$idx] ?? 0);
    $importPrice = floatval($detailImportPrices[$idx] ?? 0);

    if (!in_array($itemType, ['product', 'equipment'], true)) {
      continue;
    }

    if ($itemId <= 0 || $quantity <= 0 || $importPrice <= 0) {
      continue;
    }

    $details[] = [
      'type' => $itemType,
      'item_id' => $itemId,
      'quantity' => $quantity,
      'import_price' => $importPrice,
    ];

    $totalAmount += ($quantity * $importPrice);
  }

  if (empty($details)) {
    echo "<script>alert('Vui lòng thêm ít nhất 1 dòng chi tiết hợp lệ cho phiếu nhập!');window.location='import-slips.php';</script>";
    exit;
  }

  foreach ($details as $detail) {
    if ($detail['type'] === 'product') {
      $checkItemStmt = $db->prepare("SELECT COUNT(*) FROM products WHERE id = ?");
    } else {
      $checkItemStmt = $db->prepare("SELECT COUNT(*) FROM equipment WHERE id = ?");
    }

    $checkItemStmt->execute([$detail['item_id']]);
    if ((int) $checkItemStmt->fetchColumn() === 0) {
      echo "<script>alert('Có mục chi tiết không tồn tại. Vui lòng kiểm tra lại!');window.location='import-slips.php';</script>";
      exit;
    }
  }

  $importDate = date('Y-m-d H:i:s', strtotime($importDateInput));
  if ($importDate === '1970-01-01 00:00:00') {
    $importDate = date('Y-m-d H:i:s');
  }

  try {
    $db->beginTransaction();

    // Let DB default set initial status to pending.
    $insertStmt = $db->prepare("INSERT INTO import_slips (staff_id, supplier_id, total_amount, import_date, note) VALUES (?, ?, ?, ?, ?)");
    $result = $insertStmt->execute([$staffId, $supplierId, $totalAmount, $importDate, $note]);

    if (!$result) {
      throw new Exception('Không thể tạo phiếu nhập.');
    }

    $importId = (int) $db->lastInsertId();
    $insertDetailStmt = $db->prepare("INSERT INTO import_details (import_id, equipment_id, product_id, quantity, import_price) VALUES (?, ?, ?, ?, ?)");

    foreach ($details as $detail) {
      $equipmentId = $detail['type'] === 'equipment' ? $detail['item_id'] : null;
      $productId = $detail['type'] === 'product' ? $detail['item_id'] : null;

      $insertDetailStmt->execute([
        $importId,
        $equipmentId,
        $productId,
        $detail['quantity'],
        $detail['import_price'],
      ]);
    }

    $db->commit();
    echo "<script>alert('Tạo phiếu nhập thành công!');window.location='import-slips.php';</script>";
  } catch (Exception $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }

    echo "<script>alert('Lỗi khi tạo phiếu nhập!');window.location='import-slips.php';</script>";
  }
  exit;
}

$suppliersStmt = $db->query("SELECT id, name FROM suppliers ORDER BY name ASC");
$suppliers = $suppliersStmt->fetchAll();

$staffStmt = $db->query("SELECT id, full_name FROM staff ORDER BY full_name ASC");
$staffs = $staffStmt->fetchAll();

$productsStmt = $db->query("SELECT id, name FROM products ORDER BY name ASC");
$products = $productsStmt->fetchAll();

$equipmentStmt = $db->query("SELECT id, name FROM equipment ORDER BY name ASC");
$equipmentList = $equipmentStmt->fetchAll();

$importSlipsStmt = $db->query("SELECT i.id, i.total_amount, i.import_date, i.note, i.status, (i.status + 0) AS status_idx, s.name AS supplier_name, st.full_name AS staff_name FROM import_slips i INNER JOIN suppliers s ON i.supplier_id = s.id INNER JOIN staff st ON i.staff_id = st.id ORDER BY i.id DESC");
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
  $statusLabel = importStatusLabelByIndex($importSlip['status_idx'] ?? 0);
  $details = $importSlipDetailsMap[$importId] ?? [];

  $importSlipsForJs[$importId] = [
    'id' => $importId,
    'code' => '#PN' . str_pad((string) $importId, 3, '0', STR_PAD_LEFT),
    'supplier_name' => $importSlip['supplier_name'],
    'staff_name' => $importSlip['staff_name'],
    'total_amount' => (float) $importSlip['total_amount'],
    'import_date_display' => date('d/m/Y H:i', strtotime($importSlip['import_date'])),
    'status' => $statusLabel,
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
                      <?php $displayStatus = importStatusLabelByIndex($importSlip['status_idx'] ?? 0); ?>
                      <?php if ($displayStatus === 'Đã nhập'): ?>
                        <span class="badge badge-success">Đã nhập</span>
                      <?php elseif ($displayStatus === 'Đang chờ duyệt'): ?>
                        <span class="badge badge-warning">Đang chờ duyệt</span>
                      <?php elseif ($displayStatus === 'Đã hủy'): ?>
                        <span class="badge badge-danger">Đã hủy</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Không xác định</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-info btn-sm view-import-btn" data-id="<?= (int) $importSlip['id'] ?>" title="Xem phiếu nhập">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button type="button" class="btn btn-primary btn-sm print-import-btn" data-id="<?= (int) $importSlip['id'] ?>" title="In phiếu nhập">
                        <i class="fas fa-print"></i>
                      </button>
                      <?php if ($displayStatus === 'Đang chờ duyệt'): ?>
                        <form method="POST" action="import-slips.php" style="display:inline-block;">
                          <input type="hidden" name="update_import_status_id" value="<?= $importSlip['id'] ?>">
                          <input type="hidden" name="new_status_action" value="approve">
                          <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Duyệt</button>
                        </form>
                        <form method="POST" action="import-slips.php" style="display:inline-block;">
                          <input type="hidden" name="update_import_status_id" value="<?= $importSlip['id'] ?>">
                          <input type="hidden" name="new_status_action" value="cancel">
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

          <?php if (empty($products) && empty($equipmentList)): ?>
            <div class="alert alert-warning mb-3">Chưa có sản phẩm hoặc thiết bị để nhập kho. Vui lòng tạo dữ liệu trước khi lập phiếu.</div>
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
            <label>Chi tiết nhập kho</label>
            <div class="table-responsive mb-2">
              <table class="table table-bordered table-sm mb-0" id="import_detail_table">
                <thead>
                  <tr>
                    <th style="width: 20%;">Loại</th>
                    <th style="width: 30%;">Tên mục</th>
                    <th style="width: 15%;">SL</th>
                    <th style="width: 20%;">Đơn giá nhập</th>
                    <th style="width: 15%;">Thành tiền</th>
                    <th style="width: 50px;"></th>
                  </tr>
                </thead>
                <tbody id="import_detail_body"></tbody>
              </table>
            </div>
            <button type="button" class="btn btn-outline-primary btn-sm" id="add_import_detail_row">
              <i class="fas fa-plus"></i> Thêm dòng
            </button>
          </div>

          <div class="form-group">
            <label for="total_amount">Tổng Tiền (VNĐ)</label>
            <input type="number" class="form-control" id="total_amount" min="0" step="1000" readonly>
          </div>

          <div class="form-group">
            <label for="import_date">Ngày Nhập</label>
            <input type="datetime-local" class="form-control" id="import_date" name="import_date" required>
          </div>

          <div class="form-group">
            <label>Trạng Thái</label>
            <input type="text" class="form-control" value="Đang chờ duyệt" readonly>
            <input type="hidden" name="status" value="Đang chờ duyệt">
          </div>

          <div class="form-group">
            <label for="note">Ghi chú</label>
            <textarea class="form-control" id="note" name="note" rows="3"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" <?= (empty($suppliers) || empty($staffs) || (empty($products) && empty($equipmentList))) ? 'disabled' : '' ?>>Tạo phiếu</button>
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
    const productOptions = <?= json_encode(array_map(static function ($item) {
      return ['id' => (int) $item['id'], 'name' => $item['name']];
    }, $products), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const equipmentOptions = <?= json_encode(array_map(static function ($item) {
      return ['id' => (int) $item['id'], 'name' => $item['name']];
    }, $equipmentList), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
      if (status === 'Đã hủy') {
        return '<span class="badge badge-danger">Đã hủy</span>';
      }
      return '<span class="badge badge-secondary">Không xác định</span>';
    }

    function getItemOptionsByType(type) {
      return type === 'equipment' ? equipmentOptions : productOptions;
    }

    function buildItemOptionsHtml(type, selectedId) {
      const options = getItemOptionsByType(type);
      let html = '<option value="">-- Chọn mục --</option>';

      options.forEach(function (option) {
        const isSelected = Number(selectedId) === Number(option.id) ? ' selected' : '';
        html += '<option value="' + option.id + '"' + isSelected + '>' + escapeHtml(option.name) + '</option>';
      });

      return html;
    }

    function updateDetailRowTotal($row) {
      const quantity = Number($row.find('.detail-quantity').val()) || 0;
      const importPrice = Number($row.find('.detail-price').val()) || 0;
      const lineTotal = quantity * importPrice;
      $row.find('.detail-line-total').text(formatCurrency(lineTotal));
      return lineTotal;
    }

    function updateImportTotal() {
      let total = 0;
      $('#import_detail_body tr').each(function () {
        total += updateDetailRowTotal($(this));
      });

      $('#total_amount').val(total);
    }

    function createDetailRow(initialType) {
      const type = initialType === 'equipment' ? 'equipment' : 'product';
      const $row = $('<tr>\
        <td>\
          <select class="form-control form-control-sm detail-type" name="detail_type[]" required>\
            <option value="product">Sản phẩm</option>\
            <option value="equipment">Thiết bị</option>\
          </select>\
        </td>\
        <td>\
          <select class="form-control form-control-sm detail-item" name="detail_item_id[]" required></select>\
        </td>\
        <td>\
          <input type="number" class="form-control form-control-sm detail-quantity" name="detail_quantity[]" min="1" step="1" required>\
        </td>\
        <td>\
          <input type="number" class="form-control form-control-sm detail-price" name="detail_import_price[]" min="1" step="any" required>\
        </td>\
        <td class="text-right align-middle detail-line-total">0 VNĐ</td>\
        <td class="text-center">\
          <button type="button" class="btn btn-sm btn-outline-danger remove-detail-row"><i class="fas fa-times"></i></button>\
        </td>\
      </tr>');

      $row.find('.detail-type').val(type);
      $row.find('.detail-item').html(buildItemOptionsHtml(type));
      return $row;
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

    $('#add_import_detail_row').on('click', function () {
      const hasProduct = productOptions.length > 0;
      const hasEquipment = equipmentOptions.length > 0;

      if (!hasProduct && !hasEquipment) {
        alert('Không có dữ liệu sản phẩm hoặc thiết bị để thêm chi tiết.');
        return;
      }

      const defaultType = hasProduct ? 'product' : 'equipment';
      const $row = createDetailRow(defaultType);
      $('#import_detail_body').append($row);
      updateImportTotal();
    });

    $(document).on('change', '.detail-type', function () {
      const $row = $(this).closest('tr');
      const type = $(this).val();
      $row.find('.detail-item').html(buildItemOptionsHtml(type));
    });

    $(document).on('input change', '.detail-quantity, .detail-price', function () {
      updateImportTotal();
    });

    $(document).on('click', '.remove-detail-row', function () {
      $(this).closest('tr').remove();
      updateImportTotal();
    });

    $('#addImportModal form').on('submit', function (event) {
      if ($('#import_detail_body tr').length === 0) {
        event.preventDefault();
        alert('Vui lòng thêm ít nhất 1 dòng chi tiết trước khi tạo phiếu nhập.');
        return;
      }

      let invalid = false;
      $('#import_detail_body tr').each(function () {
        const type = $(this).find('.detail-type').val();
        const item = $(this).find('.detail-item').val();
        const quantity = Number($(this).find('.detail-quantity').val()) || 0;
        const price = Number($(this).find('.detail-price').val()) || 0;
        const options = getItemOptionsByType(type);

        if (!item || quantity <= 0 || price <= 0 || options.length === 0) {
          invalid = true;
        }
      });

      if (invalid) {
        event.preventDefault();
        alert('Vui lòng kiểm tra lại dữ liệu chi tiết (loại, mục, số lượng, đơn giá).');
        return;
      }

      updateImportTotal();
    });

    if ((productOptions.length + equipmentOptions.length) > 0) {
      $('#add_import_detail_row').trigger('click');
    }
  })();
</script>