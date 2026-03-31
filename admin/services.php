<?php
session_start(); // luôn khởi tạo session

$page_title = "Quản lý dịch vụ";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_SERVICES_NUTRITION
checkPermission('MANAGE_SERVICES_NUTRITION');

include '../includes/functions.php';

$db = getDB();

$filterName = trim((string) ($_GET['name'] ?? ''));
$filterType = trim((string) ($_GET['type'] ?? ''));
$filterPriceMin = trim((string) ($_GET['price_min'] ?? ''));
$filterPriceMax = trim((string) ($_GET['price_max'] ?? ''));
$filterStatus = trim((string) ($_GET['status'] ?? ''));

$serviceTypesFilter = $db->query("SELECT DISTINCT type FROM services WHERE type IS NOT NULL AND type <> '' ORDER BY type ASC")->fetchAll(PDO::FETCH_COLUMN);

$whereClauses = [];
$whereParams = [];
if ($filterName !== '') { $whereClauses[] = 'name LIKE ?'; $whereParams[] = '%' . $filterName . '%'; }
if ($filterType !== '') { $whereClauses[] = 'type = ?'; $whereParams[] = $filterType; }
if ($filterPriceMin !== '' && is_numeric($filterPriceMin)) { $whereClauses[] = 'price >= ?'; $whereParams[] = (float) $filterPriceMin; }
if ($filterPriceMax !== '' && is_numeric($filterPriceMax)) { $whereClauses[] = 'price <= ?'; $whereParams[] = (float) $filterPriceMax; }
if ($filterStatus !== '') { $whereClauses[] = 'status = ?'; $whereParams[] = $filterStatus; }
$whereSql = !empty($whereClauses) ? ' WHERE ' . implode(' AND ', $whereClauses) : '';

function processServiceImageUpload($fileInputName, $existingPath = null) {
  if (!isset($_FILES[$fileInputName]) || !is_array($_FILES[$fileInputName])) {
    return $existingPath;
  }

  $file = $_FILES[$fileInputName];

  if (!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
    return $existingPath;
  }

  if ($file['error'] !== UPLOAD_ERR_OK) {
    throw new RuntimeException('Tải ảnh thất bại. Vui lòng thử lại.');
  }

  if (!is_uploaded_file($file['tmp_name'])) {
    throw new RuntimeException('Tệp tải lên không hợp lệ.');
  }

  $finfo = @getimagesize($file['tmp_name']);
  if ($finfo === false) {
    throw new RuntimeException('Vui lòng chọn đúng tệp hình ảnh.');
  }

  $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
  $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
  if (!in_array($extension, $allowedExtensions, true)) {
    throw new RuntimeException('Ảnh chỉ hỗ trợ định dạng: jpg, jpeg, png, webp, gif.');
  }

  $uploadDirAbsolute = realpath(__DIR__ . '/../assets/uploads');
  if ($uploadDirAbsolute === false) {
    $uploadDirAbsolute = __DIR__ . '/../assets/uploads';
  }

  $serviceDirAbsolute = $uploadDirAbsolute . '/services';
  if (!is_dir($serviceDirAbsolute) && !mkdir($serviceDirAbsolute, 0755, true)) {
    throw new RuntimeException('Không thể tạo thư mục lưu ảnh dịch vụ.');
  }

  $newFileName = 'service_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
  $targetAbsolutePath = $serviceDirAbsolute . '/' . $newFileName;
  if (!move_uploaded_file($file['tmp_name'], $targetAbsolutePath)) {
    throw new RuntimeException('Không thể lưu ảnh dịch vụ.');
  }

  if (!empty($existingPath) && strpos($existingPath, 'assets/uploads/services/') === 0) {
    $oldAbsolutePath = __DIR__ . '/../' . ltrim($existingPath, '/');
    if (is_file($oldAbsolutePath)) {
      @unlink($oldAbsolutePath);
    }
  }

  return 'assets/uploads/services/' . $newFileName;
}

// Xử lý thêm dịch vụ
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
    checkPermission('MANAGE_SERVICES_NUTRITION', 'add');

        $name = sanitize($_POST['name']);
        $type = $_POST['type'];
        $price = floatval($_POST['price']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];

        try {
      $imagePath = processServiceImageUpload('img');
      $stmt = $db->prepare("INSERT INTO services (name, img, type, price, description, status) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->execute([$name, $imagePath, $type, $price, $description, $status]);
            setFlashMessage('success', 'Thêm dịch vụ thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
    } catch (RuntimeException $e) {
      setFlashMessage('danger', $e->getMessage());
        }
        redirect('services.php');
        exit;
    }

    if ($_POST['action'] === 'edit') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'edit');

        $id = intval($_POST['id']);
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description']);
        $status = $_POST['status'];
        $oldImagePath = !empty($_POST['old_img']) ? sanitize($_POST['old_img']) : null;

        $currentStmt = $db->prepare("SELECT type, price FROM services WHERE id = ? LIMIT 1");
        $currentStmt->execute([$id]);
        $currentService = $currentStmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentService) {
          setFlashMessage('danger', 'Không tìm thấy dịch vụ cần cập nhật.');
          redirect('services.php');
          exit;
        }

        $usageStmt = $db->prepare("SELECT COUNT(*) AS total FROM member_services WHERE service_id = ? AND status = 'còn hiệu lực'");
        $usageStmt->execute([$id]);
        $buyerCount = (int) ($usageStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        $type = $buyerCount > 0 ? (string) $currentService['type'] : (string) $_POST['type'];
        $price = $buyerCount > 0 ? (float) $currentService['price'] : floatval($_POST['price']);

        try {
          $imagePath = processServiceImageUpload('img', $oldImagePath);
          $stmt = $db->prepare("UPDATE services SET name = ?, img = ?, type = ?, price = ?, description = ?, status = ? WHERE id = ?");
          $stmt->execute([$name, $imagePath, $type, $price, $description, $status, $id]);
            setFlashMessage('success', 'Cập nhật dịch vụ thành công!');
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: ' . $e->getMessage());
        } catch (RuntimeException $e) {
          setFlashMessage('danger', $e->getMessage());
        }
        redirect('services.php');
        exit;
    }

    if ($_POST['action'] === 'delete') {
      checkPermission('MANAGE_SERVICES_NUTRITION', 'delete');

        $id = intval($_POST['id']);
        try {
        $stmt = $db->prepare("SELECT img FROM services WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $serviceToDelete = $stmt->fetch(PDO::FETCH_ASSOC);

        $usageStmt = $db->prepare("SELECT COUNT(*) AS total FROM member_services WHERE service_id = ? AND status = 'còn hiệu lực'");
        $usageStmt->execute([$id]);
        $buyerCount = (int) ($usageStmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0);

        if ($buyerCount > 0) {
          $stmt = $db->prepare("UPDATE services SET status = 'không hoạt động' WHERE id = ?");
          $stmt->execute([$id]);
          setFlashMessage('warning', 'Dịch vụ đã có người mua nên không thể xóa hẳn, đã chuyển trạng thái thành "không hoạt động".');
        } else {
          $stmt = $db->prepare("DELETE FROM services WHERE id = ?");
          $stmt->execute([$id]);

          if (!empty($serviceToDelete['img']) && strpos($serviceToDelete['img'], 'assets/uploads/services/') === 0) {
            $oldAbsolutePath = __DIR__ . '/../' . ltrim($serviceToDelete['img'], '/');
            if (is_file($oldAbsolutePath)) {
              @unlink($oldAbsolutePath);
            }
          }

          setFlashMessage('success', 'Xóa dịch vụ thành công!');
        }
        } catch (PDOException $e) {
            setFlashMessage('danger', 'Lỗi: Không thể xóa dịch vụ. ' . $e->getMessage());
        }
        redirect('services.php');
        exit;
    }
}

// Lấy danh sách dịch vụ
$stmt = $db->prepare("SELECT s.*, (
                      SELECT COUNT(*)
                      FROM member_services ms
                      WHERE ms.service_id = s.id AND ms.status = 'còn hiệu lực'
                    ) AS buyer_count
                    FROM services s" . $whereSql . " ORDER BY s.id DESC");
$stmt->execute($whereParams);
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
            <h1 class="m-0">Quản lý dịch vụ</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dịch vụ</li>
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
          $filterAction = 'services.php';
          $filterFieldsHtml = '
            <div class="col-md-2"><div class="form-group mb-0"><label>Tên DV</label><input type="text" name="name" class="form-control" value="' . htmlspecialchars($filterName) . '" placeholder="Tên dịch vụ"></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Loại</label><select name="type" class="form-control"><option value="">-- Tất cả --</option>';
          foreach ($serviceTypesFilter as $serviceTypeOption) {
            $selected = $filterType === $serviceTypeOption ? 'selected' : '';
            $filterFieldsHtml .= '<option value="' . htmlspecialchars($serviceTypeOption) . '" ' . $selected . '>' . htmlspecialchars($serviceTypeOption) . '</option>';
          }
          $filterFieldsHtml .= '</select></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Giá từ</label><input type="number" name="price_min" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMin) . '" placeholder=">="></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Giá đến</label><input type="number" name="price_max" class="form-control" min="0" value="' . htmlspecialchars($filterPriceMax) . '" placeholder="<="></div></div>
            <div class="col-md-2"><div class="form-group mb-0"><label>Trạng thái</label><select name="status" class="form-control"><option value="">-- Tất cả --</option><option value="hoạt động" ' . ($filterStatus === 'hoạt động' ? 'selected' : '') . '>Hoạt động</option><option value="không hoạt động" ' . ($filterStatus === 'không hoạt động' ? 'selected' : '') . '>Không hoạt động</option></select></div></div>
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

        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách dịch vụ</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Thêm dịch vụ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table js-admin-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Ảnh</th>
                    <th>Tên dịch vụ</th>
                    <th>Loại</th>
                    <th>Giá (VNĐ)</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php foreach ($services as $service): ?>
                  <?php $serviceImage = !empty($service['img']) ? '../' . ltrim($service['img'], '/') : '../assets/user_template/img/services/services-1.jpg'; ?>
                  <tr>
                    <td><?= $service['id'] ?></td>
                    <td style="width: 90px;">
                      <img src="<?= htmlspecialchars($serviceImage) ?>"
                           alt="<?= htmlspecialchars($service['name']) ?>"
                           style="width: 70px; height: 50px; object-fit: cover; border-radius: 6px;"
                           onerror="this.onerror=null;this.src='../assets/user_template/img/services/services-1.jpg';">
                    </td>
                    <td><?= htmlspecialchars($service['name']) ?></td>
                    <td><?= ucfirst($service['type']) ?></td>
                    <td><?= formatCurrency($service['price']) ?></td>
                    <td><?= htmlspecialchars($service['description'] ?? '') ?></td>
                    <td>
                      <?php if ($service['status'] === 'hoạt động'): ?>
                        <span class="badge badge-success">Hoạt động</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Không hoạt động</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <button class="btn btn-warning btn-sm btn-edit"
                        data-id="<?= $service['id'] ?>"
                        data-name="<?= htmlspecialchars($service['name']) ?>"
                        data-type="<?= $service['type'] ?>"
                        data-price="<?= $service['price'] ?>"
                        data-description="<?= htmlspecialchars($service['description'] ?? '') ?>"
                        data-img="<?= htmlspecialchars($service['img'] ?? '') ?>"
                        data-status="<?= $service['status'] ?>"
                        data-buyer-count="<?= (int) ($service['buyer_count'] ?? 0) ?>"
                        data-toggle="modal" data-target="#editServiceModal">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm btn-delete"
                        data-id="<?= $service['id'] ?>"
                        data-name="<?= htmlspecialchars($service['name']) ?>"
                        data-buyer-count="<?= (int) ($service['buyer_count'] ?? 0) ?>"
                        data-toggle="modal" data-target="#deleteServiceModal">
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

    <!-- Modal Thêm dịch vụ -->
    <div class="modal fade" id="addServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="add">
            <div class="modal-header">
              <h5 class="modal-title">Thêm dịch vụ mới</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên dịch vụ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" placeholder="Nhập tên dịch vụ" data-field="name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Loại dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control" name="type" data-field="type">
                  <option value="thư giãn">Thư giãn</option>
                  <option value="xoa bóp">Xoa bóp</option>
                  <option value="hỗ trợ">Hỗ trợ</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Giá (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" placeholder="Nhập giá dịch vụ" min="0" step="1000" data-field="price">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả dịch vụ" data-field="description"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Hình ảnh dịch vụ</label>
                <input type="file" class="form-control" name="img" accept="image/*" data-field="img">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <small class="text-muted">Ảnh sẽ được lưu tại assets/uploads/services</small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" data-field="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
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

    <!-- Modal Sửa dịch vụ -->
    <div class="modal fade" id="editServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php" enctype="multipart/form-data" novalidate>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-id">
            <input type="hidden" name="old_img" id="edit-old-img">
            <div class="modal-header">
              <h5 class="modal-title">Sửa dịch vụ</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <div class="form-group">
                <label>Tên dịch vụ <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="name" id="edit-name" data-field="name">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Loại dịch vụ <span class="text-danger">*</span></label>
                <select class="form-control" name="type" id="edit-type" data-field="type">
                  <option value="thư giãn">Thư giãn</option>
                  <option value="xoa bóp">Xoa bóp</option>
                  <option value="hỗ trợ">Hỗ trợ</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <small class="text-muted d-block mt-2" id="edit-type-hint" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Giá (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" id="edit-price" min="0" step="1000" data-field="price">
                <small class="text-danger d-block mt-2" style="display:none;"></small>
                <small class="text-muted d-block mt-2" id="edit-price-hint" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Mô tả</label>
                <textarea class="form-control" name="description" id="edit-description" rows="3" data-field="description"></textarea>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
              <div class="form-group">
                <label>Ảnh hiện tại</label>
                <div>
                  <img id="edit-preview-img" src="../assets/user_template/img/services/services-1.jpg" alt="Ảnh dịch vụ" style="width: 120px; height: 80px; object-fit: cover; border-radius: 6px; border: 1px solid #ddd;">
                </div>
              </div>
              <div class="form-group">
                <label>Đổi hình ảnh dịch vụ</label>
                <input type="file" class="form-control" name="img" accept="image/*">
                <small class="text-muted">Để trống nếu không muốn thay đổi ảnh</small>
              </div>
              <div class="form-group">
                <label>Trạng thái</label>
                <select class="form-control" name="status" id="edit-status" data-field="status">
                  <option value="hoạt động">Hoạt động</option>
                  <option value="không hoạt động">Không hoạt động</option>
                </select>
                <small class="text-danger d-block mt-2" style="display:none;"></small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
              <button type="submit" class="btn btn-primary">Cập nhật</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal Xóa dịch vụ -->
    <div class="modal fade" id="deleteServiceModal" tabindex="-1" role="dialog">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form method="POST" action="services.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete-id">
            <div class="modal-header">
              <h5 class="modal-title">Xác nhận xóa</h5>
              <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body">
              <p>Bạn có chắc chắn muốn xóa dịch vụ <strong id="delete-name"></strong>?</p>
              <p class="text-danger"><small>Nếu có người đã đăng ký dịch vụ này, chỉ đổi trạng thái thành "không hoạt động" </small></p>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
              <button type="submit" class="btn btn-danger">Xóa</button>
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
  // Điền dữ liệu vào modal sửa
  $('.btn-edit').on('click', function() {
    const buyerCount = parseInt($(this).data('buyer-count') || 0, 10);
    $('#edit-id').val($(this).data('id'));
    $('#edit-name').val($(this).data('name'));
    $('#edit-type').val($(this).data('type'));
    $('#edit-price').val($(this).data('price'));
    $('#edit-description').val($(this).data('description'));
    const imgPath = $(this).data('img') || '';
    $('#edit-old-img').val(imgPath);
    $('#edit-preview-img').attr('src', imgPath ? ('../' + imgPath.replace(/^\/+/, '')) : '../assets/user_template/img/services/services-1.jpg');
    $('#edit-status').val($(this).data('status'));

    const hasBuyer = buyerCount > 0;
    $('#edit-type').prop('disabled', hasBuyer);
    $('#edit-price').prop('disabled', hasBuyer);
    $('#edit-type-hint').toggle(hasBuyer).text(hasBuyer ? 'Dịch vụ đã có người mua nên không thể đổi loại dịch vụ.' : '');
    $('#edit-price-hint').toggle(hasBuyer).text(hasBuyer ? 'Dịch vụ đã có người mua nên không thể đổi giá.' : '');
  });

  // Điền dữ liệu vào modal xóa
  $('.btn-delete').on('click', function() {
    $('#delete-id').val($(this).data('id'));
    $('#delete-name').text($(this).data('name'));
  });
});

(function() {
  function msg(field) {
    if (field === 'name') return 'Vui lòng nhập tên dịch vụ';
    if (field === 'type') return 'Vui lòng chọn loại dịch vụ';
    if (field === 'price') return 'Vui lòng nhập giá dịch vụ hợp lệ';
    if (field === 'description') return 'Vui lòng nhập mô tả dịch vụ';
    if (field === 'img') return 'Vui lòng chọn hình ảnh dịch vụ';
    if (field === 'status') return 'Vui lòng chọn trạng thái';
    return 'Vui lòng nhập dữ liệu hợp lệ';
  }
  function box(input) { return input.closest('.form-group')?.querySelector('small.text-danger') || null; }
  function show(input, message) { const b = box(input); if (b) { b.textContent = message; b.style.display = 'block'; } input.classList.add('is-invalid'); }
  function clear(input) { const b = box(input); if (b) { b.textContent = ''; b.style.display = 'none'; } input.classList.remove('is-invalid'); }
  function validate(input) {
    const field = input.getAttribute('data-field');
    const value = String(input.value || '').trim();
    clear(input);
    if (!field) return true;
    if (field === 'price') { if (!value || Number(value) < 0) { show(input, msg(field)); return false; } return true; }
    if (field === 'img') { if (!value) { show(input, msg(field)); return false; } return true; }
    if (!value) { show(input, msg(field)); return false; }
    return true;
  }
  document.addEventListener('invalid', function(e){ const form = e.target.closest('form'); if (form && form.hasAttribute('novalidate')) e.preventDefault(); }, true);
  document.addEventListener('input', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('change', function(e){ if (e.target.hasAttribute && e.target.hasAttribute('data-field')) validate(e.target); }, true);
  document.addEventListener('submit', function(e){ if (!e.target.hasAttribute || !e.target.hasAttribute('novalidate')) return; let ok = true; e.target.querySelectorAll('[data-field]').forEach(function(field){ if (!validate(field)) ok = false; }); if (!ok) e.preventDefault(); }, true);
})();
</script>