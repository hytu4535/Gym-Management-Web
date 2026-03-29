<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Địa Chỉ Hội Viên";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_MEMBERS
checkPermission('MANAGE_MEMBERS');

// Xử lý các hành động
$db = getDB();
$formErrors = [];
$formValues = [];

require_once '../includes/address_schema.php';
ensureAddressSchemaPdo($db);
$message = '';
$messageType = '';

// Bộ lọc danh sách
$filter_member_name = trim((string) ($_GET['filter_member_name'] ?? ''));
$filter_full_address = trim((string) ($_GET['filter_full_address'] ?? ''));
$filter_city = trim((string) ($_GET['filter_city'] ?? ''));
$filter_district = trim((string) ($_GET['filter_district'] ?? ''));
$filter_status = trim((string) ($_GET['filter_status'] ?? ''));

// Xử lý xóa
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    try {
    $stmt = $db->prepare("SELECT member_id, is_default FROM addresses WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $targetAddress = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetAddress) {
      throw new Exception('Địa chỉ không tồn tại.');
    }

    $db->beginTransaction();

    $stmt = $db->prepare("DELETE FROM addresses WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    if ((int) $targetAddress['is_default'] === 1) {
      $stmt = $db->prepare("SELECT id FROM addresses WHERE member_id = ? ORDER BY id DESC LIMIT 1");
      $stmt->execute([(int) $targetAddress['member_id']]);
      $newDefaultId = $stmt->fetchColumn();

      if ($newDefaultId) {
        $stmt = $db->prepare("UPDATE addresses SET is_default = 1 WHERE id = ?");
        $stmt->execute([$newDefaultId]);
      }
    }

    $db->commit();
        $message = "Xóa địa chỉ thành công!";
        $messageType = "success";
    } catch (PDOException $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
  } catch (Exception $e) {
    if ($db->inTransaction()) {
      $db->rollBack();
    }
    $message = "Lỗi: " . $e->getMessage();
    $messageType = "danger";
    }
}

// Xử lý đặt mặc định
if (isset($_GET['action']) && $_GET['action'] == 'set_default' && isset($_GET['id'])) {
    try {
        // Lấy member_id của địa chỉ
    $stmt = $db->prepare("SELECT member_id FROM addresses WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $member_id = $stmt->fetchColumn();

    if (!$member_id) {
      throw new Exception('Địa chỉ không tồn tại.');
    }
        
        // Bỏ mặc định tất cả địa chỉ của member
        $stmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE member_id = ?");
        $stmt->execute([$member_id]);
        
        // Đặt địa chỉ này là mặc định
        $stmt = $db->prepare("UPDATE addresses SET is_default = 1 WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        
        $message = "Đã đặt địa chỉ mặc định!";
        $messageType = "success";
    } catch (PDOException $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    } catch (Exception $e) {
      $message = "Lỗi: " . $e->getMessage();
      $messageType = "danger";
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $member_id = isset($_POST['member_id']) ? (int) $_POST['member_id'] : 0;
  $address_id = isset($_POST['id']) && $_POST['id'] !== '' ? (int) $_POST['id'] : 0;
  $full_address = trim((string) ($_POST['full_address'] ?? ''));
  $city = trim((string) ($_POST['city'] ?? ''));
  $district = trim((string) ($_POST['district'] ?? ''));
  $ward = trim((string) ($_POST['ward'] ?? ''));
  $type = trim((string) ($_POST['type'] ?? 'home'));
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $allowedTypes = ['home', 'work', 'other'];
    
    try {
    if ($member_id <= 0) {
      throw new Exception("Vui lòng chọn hội viên hợp lệ.");
    }

    if ($full_address === '') {
      throw new Exception("Địa chỉ đầy đủ là bắt buộc.");
    }

    if ($city === '' || $district === '' || $ward === '') {
      throw new Exception("Vui lòng chọn đầy đủ Tỉnh/Thành, Quận/Huyện và Phường/Xã.");
    }

    if (!in_array($type, $allowedTypes, true)) {
      throw new Exception("Loại địa chỉ không hợp lệ.");
    }

    $memberCheckStmt = $db->prepare("SELECT COUNT(*) FROM members WHERE id = ?");
    $memberCheckStmt->execute([$member_id]);
    if ((int) $memberCheckStmt->fetchColumn() === 0) {
      throw new Exception("Hội viên không tồn tại.");
    }

    $full_address = preg_replace('/\s+/u', ' ', $full_address);
    $city = $city === '' ? null : preg_replace('/\s+/u', ' ', $city);
    $district = $district === '' ? null : preg_replace('/\s+/u', ' ', $district);
    $ward = $ward === '' ? null : preg_replace('/\s+/u', ' ', $ward);

    $duplicateStmt = $db->prepare("SELECT COUNT(*) FROM addresses WHERE member_id = ? AND full_address = ? AND IFNULL(city, '') = ? AND IFNULL(district, '') = ? AND IFNULL(ward, '') = ? AND IFNULL(type, '') = ? AND id <> ?");
    $duplicateStmt->execute([$member_id, $full_address, $city ?? '', $district ?? '', $ward ?? '', $type, $address_id]);
    if ((int) $duplicateStmt->fetchColumn() > 0) {
      throw new Exception("Địa chỉ này đã tồn tại cho hội viên đã chọn.");
    }

        if (isset($_POST['id']) && !empty($_POST['id'])) {
      // Nếu chuyển địa chỉ này thành mặc định thì bỏ mặc định các địa chỉ khác của hội viên.
      if ($is_default) {
        $stmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE member_id = ? AND id <> ?");
        $stmt->execute([$member_id, $address_id]);
      }

            // Cập nhật
        $stmt = $db->prepare("UPDATE addresses SET member_id=?, full_address=?, city=?, district=?, ward=?, type=?, is_default=? WHERE id=?");
      $stmt->execute([$member_id, $full_address, $city, $district, $ward, $type, $is_default, $address_id]);
            $message = "Cập nhật địa chỉ thành công!";
        } else {
            // Nếu đặt mặc định, bỏ mặc định các địa chỉ khác
            if ($is_default) {
                $stmt = $db->prepare("UPDATE addresses SET is_default = 0 WHERE member_id = ?");
                $stmt->execute([$member_id]);
            }
            
            // Thêm mới
        $stmt = $db->prepare("INSERT INTO addresses (member_id, full_address, city, district, ward, type, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$member_id, $full_address, $city, $district, $ward, $type, $is_default]);
            $message = "Thêm địa chỉ thành công!";
        }
        $messageType = "success";
        } catch (Exception $e) {
        $message = "Lỗi: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Lấy danh sách địa chỉ
    $whereParts = [];
    $params = [];

    if ($filter_member_name !== '') {
      $whereParts[] = "m.full_name LIKE ?";
      $params[] = '%' . $filter_member_name . '%';
    }
    if ($filter_full_address !== '') {
      $whereParts[] = "a.full_address LIKE ?";
      $params[] = '%' . $filter_full_address . '%';
    }
    if ($filter_city !== '') {
      $whereParts[] = "a.city LIKE ?";
      $params[] = '%' . $filter_city . '%';
    }
    if ($filter_district !== '') {
      $whereParts[] = "a.district LIKE ?";
      $params[] = '%' . $filter_district . '%';
    }
    if ($filter_status === 'default') {
      $whereParts[] = "a.is_default = 1";
    } elseif ($filter_status === 'secondary') {
      $whereParts[] = "a.is_default = 0";
    }

    $sql = "SELECT a.*, m.full_name
        FROM addresses a
        LEFT JOIN members m ON a.member_id = m.id";
    if (!empty($whereParts)) {
      $sql .= " WHERE " . implode(' AND ', $whereParts);
    }
    $sql .= " ORDER BY m.full_name, a.is_default DESC, a.id DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
$addresses = $stmt->fetchAll();

// Lấy danh sách hội viên cho form
$members = $db->query("SELECT id, full_name, phone FROM members WHERE status = 'active' ORDER BY full_name")->fetchAll();

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
            <h1 class="m-0">Quản lý Địa Chỉ Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Địa Chỉ</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
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
                <h3 class="card-title">Danh sách Địa Chỉ</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addressModal" onclick="resetForm()">
                    <i class="fas fa-plus"></i> Thêm Địa Chỉ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <form method="GET" class="mb-3">
                  <div class="row">
                    <div class="col-md-3 mb-2">
                      <input type="text" name="filter_member_name" class="form-control" placeholder="Lọc theo tên hội viên" value="<?php echo htmlspecialchars($filter_member_name); ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                      <input type="text" name="filter_full_address" class="form-control" placeholder="Lọc theo địa chỉ đầy đủ" value="<?php echo htmlspecialchars($filter_full_address); ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                      <input type="text" name="filter_city" class="form-control" placeholder="Lọc theo thành phố" value="<?php echo htmlspecialchars($filter_city); ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                      <input type="text" name="filter_district" class="form-control" placeholder="Lọc theo quận/huyện" value="<?php echo htmlspecialchars($filter_district); ?>">
                    </div>
                    <div class="col-md-2 mb-2">
                      <select name="filter_status" class="form-control">
                        <option value="">-- Trạng thái --</option>
                        <option value="default" <?php echo $filter_status === 'default' ? 'selected' : ''; ?>>Mặc định</option>
                        <option value="secondary" <?php echo $filter_status === 'secondary' ? 'selected' : ''; ?>>Phụ</option>
                      </select>
                    </div>
                  </div>
                  <div class="d-flex">
                    <button type="submit" class="btn btn-info btn-sm mr-2">
                      <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="addresses.php" class="btn btn-secondary btn-sm">
                      <i class="fas fa-times"></i> Xóa bộ lọc
                    </a>
                  </div>
                </form>

                <table id="addressTable" class="table table-bordered table-striped">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Địa Chỉ Đầy Đủ</th>
                    <th>Thành Phố</th>
                    <th>Quận/Huyện</th>
                    <th>Phường/Xã</th>
                    <th>Loại</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($addresses as $address): ?>
                  <tr>
                    <td><?php echo $address['id']; ?></td>
                    <td><?php echo htmlspecialchars($address['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($address['full_address']); ?></td>
                    <td><?php echo htmlspecialchars($address['city'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($address['district'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($address['ward'] ?? 'N/A'); ?></td>
                    <td>
                      <?php
                        $typeLabel = 'Nhà riêng';
                        if (($address['type'] ?? 'home') === 'work') {
                          $typeLabel = 'Cơ quan';
                        } elseif (($address['type'] ?? 'home') === 'other') {
                          $typeLabel = 'Khác';
                        }
                      ?>
                      <span class="badge badge-light"><?php echo $typeLabel; ?></span>
                    </td>
                    <td>
                      <?php if ($address['is_default']): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Mặc định</span>
                      <?php else: ?>
                        <span class="badge badge-secondary">Phụ</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if (!$address['is_default']): ?>
                        <a href="?action=set_default&id=<?php echo $address['id']; ?>" class="btn btn-info btn-sm" title="Đặt mặc định">
                          <i class="fas fa-check"></i>
                        </a>
                      <?php endif; ?>
                      <button class="btn btn-warning btn-sm" onclick='editAddress(<?php echo json_encode($address); ?>)' data-toggle="modal" data-target="#addressModal" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <a href="?action=delete&id=<?php echo $address['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
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

<!-- Modal Thêm/Sửa -->
<div class="modal fade" id="addressModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title" id="modalTitle">Thêm Địa Chỉ</h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form method="POST" id="addressForm" data-address-picker="1" data-mode="admin" novalidate>
        <div class="modal-body">
          <input type="hidden" name="id" id="address_id">
          <div class="form-group">
            <label>Hội viên <span class="text-danger">*</span></label>
            <select name="member_id" id="member_id" class="form-control">
              <option value="">--- Chọn hội viên ---</option>
              <?php foreach ($members as $member): ?>
              <option value="<?php echo $member['id']; ?>">
                <?php echo htmlspecialchars($member['full_name']) . ' - ' . htmlspecialchars($member['phone']); ?>
              </option>
              <?php endforeach; ?>
            </select>
            <small class="text-danger d-none form-field-error" data-error-for="member_id"></small>
          </div>
          <div class="form-group">
            <label>Địa chỉ đầy đủ <span class="text-danger">*</span></label>
            <textarea name="full_address" id="full_address" class="form-control" rows="3" maxlength="255" placeholder="Số nhà, tên đường..."></textarea>
            <small class="text-danger d-none form-field-error" data-error-for="full_address"></small>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tỉnh / Thành phố</label>
                <select name="city" id="city" class="form-control">
                  <option value="">-- Chọn Tỉnh / Thành phố --</option>
                </select>
                <small class="text-danger d-none form-field-error" data-error-for="city"></small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Quận / Huyện</label>
                <select name="district" id="district" class="form-control">
                  <option value="">-- Chọn Quận / Huyện --</option>
                </select>
                <small class="text-danger d-none form-field-error" data-error-for="district"></small>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Phường / Xã</label>
                <select name="ward" id="ward" class="form-control">
                  <option value="">-- Chọn Phường / Xã --</option>
                </select>
                <small class="text-danger d-none form-field-error" data-error-for="ward"></small>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Loại địa chỉ</label>
                <select name="type" id="type" class="form-control">
                  <option value="home">Nhà riêng</option>
                  <option value="work">Cơ quan</option>
                  <option value="other">Khác</option>
                </select>
                <small class="text-danger d-none form-field-error" data-error-for="type"></small>
              </div>
            </div>
          </div>
          <div class="form-group">
            <div class="custom-control custom-checkbox">
              <input type="checkbox" name="is_default" id="is_default" class="custom-control-input">
              <label class="custom-control-label" for="is_default">Đặt làm địa chỉ mặc định</label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" id="addressSubmitBtn">Lưu</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="../assets/js/address-picker.js"></script>

<script>
function resetForm() {
  document.getElementById('modalTitle').innerText = 'Thêm Địa Chỉ';
  document.getElementById('addressSubmitBtn').disabled = false;
  document.getElementById('addressSubmitBtn').innerText = 'Lưu';
  AddressPicker.resetForm(document.getElementById('addressForm'));
}

function editAddress(address) {
  document.getElementById('modalTitle').innerText = 'Sửa Địa Chỉ';
  document.getElementById('addressSubmitBtn').disabled = false;
  document.getElementById('addressSubmitBtn').innerText = 'Lưu';
  AddressPicker.fillForm(document.getElementById('addressForm'), address);
}

// Initialize DataTable
$(document).ready(function() {
  $('#addressForm').on('submit', function(e) {
    if (!AddressPicker.validateForm(this)) {
      e.preventDefault();
      return;
    }

    var submitBtn = $('#addressSubmitBtn');
    submitBtn.prop('disabled', true);
    submitBtn.text('Đang lưu...');
  });

    $('#addressTable').DataTable({
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/Vietnamese.json"
        },
        "pageLength": 10,
        "order": [[0, "desc"]]
    });
});
</script>

<?php include 'layout/footer.php'; ?>
