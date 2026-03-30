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

$filterMemberId = trim((string) ($_GET['member_id'] ?? ''));
$filterServiceId = trim((string) ($_GET['service_id'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));
$filterFromDate = trim((string) ($_GET['from_date'] ?? ''));
$filterToDate = trim((string) ($_GET['to_date'] ?? ''));

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
  $memberServiceParams[] = $filterStatus;
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

function isPastDate($date)
{
  if (empty($date)) {
    return false;
  }

  try {
    $today = new DateTimeImmutable('today');
    $endDate = new DateTimeImmutable($date);
    return $endDate < $today;
  } catch (Exception $e) {
    return false;
  }
}

try {
  $db->exec("UPDATE member_services SET status = 'đã dùng' WHERE end_date IS NOT NULL AND end_date < CURDATE() AND status <> 'đã dùng'");
} catch (PDOException $e) {
  // Không chặn trang nếu đồng bộ trạng thái tự động gặp lỗi.
}

// Xử lý CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
    checkPermission('MANAGE_SERVICES_NUTRITION', 'add');

        $member_id = intval($_POST['member_id']);
        $service_id = intval($_POST['service_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        $status = sanitize($_POST['status']);

      if ($end_date !== null && isPastDate($end_date)) {
        $status = 'đã dùng';
      }

        try {
            $stmt = $db->prepare("INSERT INTO member_services (member_id, service_id, start_date, end_date, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$member_id, $service_id, $start_date, $end_date, $status]);
            setFlashMessage('success', 'Gán dịch vụ cho hội viên thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('member_services.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'edit');

        $id = intval($_POST['id']);
        $member_id = intval($_POST['member_id']);
        $service_id = intval($_POST['service_id']);
        $start_date = sanitize($_POST['start_date']);
        $end_date = !empty($_POST['end_date']) ? sanitize($_POST['end_date']) : null;
        $status = sanitize($_POST['status']);

      $currentStmt = $db->prepare("SELECT end_date FROM member_services WHERE id = ? LIMIT 1");
      $currentStmt->execute([$id]);
      $currentRecord = $currentStmt->fetch(PDO::FETCH_ASSOC);

      if (!$currentRecord) {
        setFlashMessage('danger', 'Không tìm thấy bản ghi cần sửa.');
        redirect('member_services.php');
        exit;
      }

      if (!empty($currentRecord['end_date']) && isPastDate($currentRecord['end_date'])) {
        setFlashMessage('danger', 'Bản ghi này đã quá ngày kết thúc nên không thể sửa nữa.');
        redirect('member_services.php');
        exit;
      }

      if ($end_date !== null && isPastDate($end_date)) {
        $status = 'đã dùng';
      }

        try {
            $stmt = $db->prepare("UPDATE member_services SET member_id = ?, service_id = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
            $stmt->execute([$member_id, $service_id, $start_date, $end_date, $status, $id]);
            setFlashMessage('success', 'Cập nhật thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
        redirect('member_services.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'delete');

        $id = intval($_POST['id']);
        try {
            $stmt = $db->prepare("DELETE FROM member_services WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Xóa thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        }
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
  $record['is_expired'] = !empty($record['end_date']) && isPastDate($record['end_date']);
  if ($record['is_expired']) {
    $record['status'] = 'đã dùng';
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
            <div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="còn hiệu lực" ' . ($filterStatus === 'còn hiệu lực' ? 'selected' : '') . '>Còn hiệu lực</option><option value="đã dùng" ' . ($filterStatus === 'đã dùng' ? 'selected' : '') . '>Đã dùng</option></select></div></div>
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
                <span class="info-box-text">Còn hiệu lực</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return $r['status'] === 'còn hiệu lực'; })) ?>
                </span>
              </div>
            </div>
          </div>
          <div class="col-md-4 col-sm-6">
            <div class="info-box">
              <span class="info-box-icon bg-secondary"><i class="fas fa-history"></i></span>
              <div class="info-box-content">
                <span class="info-box-text">Đã dùng</span>
                <span class="info-box-number">
                  <?= count(array_filter($records, function($r) { return $r['status'] === 'đã dùng'; })) ?>
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
                        <span class="badge badge-secondary">Hết hiệu lực</span>
                      <?php elseif ($row['status'] === 'còn hiệu lực'): ?>
                        <span class="badge badge-success">Còn hiệu lực</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Đã dùng</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button type="button" class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $row['id'] ?>"
                        data-member_id="<?= $row['member_id'] ?>"
                        data-service_id="<?= $row['service_id'] ?>"
                        data-start_date="<?= $row['start_date'] ?>"
                        data-end_date="<?= $row['end_date'] ?? '' ?>"
                        data-status="<?= htmlspecialchars($row['status']) ?>"
                        <?= !empty($row['is_expired']) ? 'disabled title="Bản ghi đã quá ngày kết thúc nên không thể sửa"' : 'data-toggle="modal" data-target="#editModal"' ?>
                        >
                        <i class="fas fa-edit"></i>
                      </button>
                      <button type="button" class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $row['id'] ?>"
                        data-member="<?= htmlspecialchars($row['member_name'] ?? 'N/A') ?>"
                        data-service="<?= htmlspecialchars($row['service_name'] ?? 'N/A') ?>"
                        data-toggle="modal" data-target="#deleteModal">
                        <i class="fas fa-trash"></i>
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
                <label>Hội viên <span class="text-danger">*</span></label>
                <select class="form-control" name="member_id" id="edit-member_id" data-field="member_id">
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
                <label>Dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control" name="service_id" id="edit-service_id" data-field="service_id">
                  <option value="">-- Chọn dịch vụ --</option>
                  <?php foreach ($services as $svc): ?>
                    <option value="<?= $svc['id'] ?>">
                      <?= htmlspecialchars($svc['name']) ?> (<?= $svc['type'] ?>) - <?= number_format($svc['price'], 0, ',', '.') ?>đ
                    </option>
                  <?php endforeach; ?>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày bắt đầu <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="start_date" id="edit-start_date" data-field="start_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Ngày kết thúc</label>
                    <input type="date" class="form-control" name="end_date" id="edit-end_date" data-field="end_date">
                    <small class="text-danger d-block mt-2" style="display:none;"></small>
                  </div>
                </div>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status" data-field="status">
                  <option value="còn hiệu lực">Còn hiệu lực</option>
                  <option value="đã dùng">Đã dùng</option>
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
    $('.select2').select2({ theme: 'bootstrap4', placeholder: 'Tìm kiếm...', allowClear: true });
  }

  $('.btn-edit').on('click', function() {
    $('#edit-id').val($(this).data('id'));
    $('#edit-member_id').val($(this).data('member_id'));
    $('#edit-service_id').val($(this).data('service_id'));
    $('#edit-start_date').val($(this).data('start_date'));
    $('#edit-end_date').val($(this).data('end_date'));
    $('#edit-status').val($(this).data('status'));
  });

  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-member').text($(this).data('member'));
    $('#delete-service').text($(this).data('service'));
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
  function validate(input) { const field = input.getAttribute('data-field'); const value = String(input.value || '').trim(); clear(input); if (!field) return true; if (!value) { show(input, label(field)); return false; } return true; }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>