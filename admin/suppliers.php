<?php 
session_start(); // luôn khởi tạo session

$page_title = "Quản lý nhà cung cấp";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_SALES');

require_once '../config/db.php';

include '../includes/functions.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if (function_exists('ob_get_length') && ob_get_length()) {
    ob_clean();
  }
    header('Content-Type: application/json');

    $requiresCsrf = ['add', 'update', 'delete', 'view', 'search'];
    if (in_array($_POST['action'], $requiresCsrf, true)) {
      if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
        exit;
      }
    }

    $normalizePhone = function ($phone) {
      $digits = preg_replace('/\D+/', '', (string) $phone);
      if (strpos($digits, '84') === 0) {
        $digits = '0' . substr($digits, 2);
      }
      return $digits;
    };

    $validateSupplierData = function (array $data) {
      $errors = [];

      if ($data['name'] === '') {
        $errors['name'] = 'Tên nhà cung cấp không được để trống.';
      }

      if (($data['status'] ?? 'active') === '') {
        $errors['status'] = 'Vui lòng chọn trạng thái.';
      }

      if ($data['phone'] === '') {
        $errors['phone'] = 'Số điện thoại không được để trống.';
      } elseif (!preg_match('/^0\d{9,10}$/', $data['phone'])) {
        $errors['phone'] = 'Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 hoặc 11 số.';
      }

      if ($data['address'] === '') {
        $errors['address'] = 'Địa chỉ không được để trống.';
      }

      return $errors;
    };
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add':
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
                exit;
            }
            
            $data = [
              'name' => trim($_POST['name'] ?? ''),
              'phone' => $normalizePhone($_POST['phone'] ?? ''),
              'address' => trim($_POST['address'] ?? ''),
              'status' => trim($_POST['status'] ?? 'active')
            ];

            $errors = $validateSupplierData($data);

            $duplicateStmt = $db->prepare("SELECT COUNT(*) FROM suppliers WHERE name = ?");
            $duplicateStmt->execute([$data['name']]);
            if ((int) $duplicateStmt->fetchColumn() > 0) {
              $errors['name'] = 'Tên nhà cung cấp đã tồn tại.';
            }

            if (!empty($errors)) {
              echo json_encode([
                'success' => false,
                'message' => implode(' ', array_values($errors)),
                'errors' => $errors
              ]);
                exit;
            }

            // Chặn submit lặp trong thời gian ngắn (double click / spam request).
            $requestFingerprint = md5(implode('|', [
              strtolower($data['name']),
              $data['phone'],
              $data['address'],
              $data['status']
            ]));
            $nowTs = time();
            $lastAdd = $_SESSION['supplier_last_add'] ?? null;
            if (
              is_array($lastAdd)
              && ($lastAdd['fingerprint'] ?? '') === $requestFingerprint
              && ($nowTs - (int) ($lastAdd['ts'] ?? 0)) <= 10
            ) {
              echo json_encode([
                'success' => false,
                'message' => 'Yêu cầu đang được xử lý hoặc vừa xử lý xong. Vui lòng chờ vài giây rồi thử lại.'
              ]);
              exit;
            }

            $result = addSupplier($data);
            if (!empty($result['success'])) {
              $_SESSION['supplier_last_add'] = [
                'fingerprint' => $requestFingerprint,
                'ts' => $nowTs
              ];
            }
            echo json_encode($result);
            break;
            
        case 'update':
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
                exit;
            }
            
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $data = [
              'phone' => $normalizePhone($_POST['phone'] ?? ''),
              'address' => trim($_POST['address'] ?? ''),
              'status' => trim($_POST['status'] ?? 'active')
            ];

            $errors = $validateSupplierData([
              'name' => trim($_POST['name'] ?? ''),
              'phone' => $data['phone'],
              'address' => $data['address'],
              'status' => $data['status']
            ]);
            if (!empty($errors)) {
              echo json_encode([
                'success' => false,
                'message' => implode(' ', array_values($errors)),
                'errors' => $errors
              ]);
                exit;
            }
            
            $result = updateSupplier($id, $data);
            echo json_encode($result);
            break;
            
        case 'delete':
          $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteSupplier($id);
            echo json_encode($result);
            break;
            
        case 'view':
          $id = (int) ($_POST['id'] ?? 0);
            $supplier = getSupplierById($id);
            
            if (!$supplier) {
                echo json_encode(['success' => false, 'message' => 'Nhà cung cấp không tồn tại']);
                exit;
            }
            
            $stats = getSupplierStats($id);
            $supplier['stats'] = $stats;
            
            echo json_encode(['success' => true, 'data' => $supplier]);
            break;
            
        case 'search':
            $keyword = $_POST['keyword'] ?? '';
            
            if (empty($keyword)) {
                $suppliers = getAllSuppliers();
            } else {
                $suppliers = searchSuppliers($keyword);
            }
            
            echo json_encode(['success' => true, 'data' => $suppliers]);
            break;

          default:
            echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
            break;
    }
    exit;
}

// Get all suppliers for initial display
$suppliers = getAllSuppliers();

      // layout chung
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
            <h1 class="m-0">Quản lý nhà cung cấp</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Nhà cung cấp</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <style>
      #suppliersTable thead th.sortable-column {
        cursor: pointer;
        user-select: none;
        white-space: nowrap;
      }

      #suppliersTable thead th.sortable-column .sort-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 8px;
      }

      #suppliersTable thead th.sortable-column .sort-arrows {
        display: inline-flex;
        flex-direction: column;
        line-height: 0.75;
        font-size: 11px;
        color: #b5bcc3;
      }

      #suppliersTable thead th.sortable-column .sort-arrow-up,
      #suppliersTable thead th.sortable-column .sort-arrow-down {
        opacity: 0.45;
        transition: opacity 0.15s ease, color 0.15s ease;
      }

      #suppliersTable thead th.sortable-column:hover .sort-arrow-up,
      #suppliersTable thead th.sortable-column:hover .sort-arrow-down {
        opacity: 0.8;
      }

      #suppliersTable thead th.sortable-column.sort-asc .sort-arrow-up,
      #suppliersTable thead th.sortable-column.sort-desc .sort-arrow-down {
        opacity: 1;
        color: #1f2d3d;
      }
    </style>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row mb-3">
          <div class="col-12">
            <div class="card card-primary collapsed-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Lọc dữ liệu</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="row align-items-end">
                  <div class="col-md-10">
                    <div class="form-group mb-0">
                      <label>Từ khóa</label>
                      <input type="text" id="searchInput" class="form-control" placeholder="Tìm theo tên, số điện thoại hoặc địa chỉ...">
                    </div>
                  </div>
                  <div class="col-md-2">
                    <div class="form-group mb-0">
                      <button class="btn btn-primary btn-block mb-2" id="searchBtn" type="button">
                        <i class="fas fa-search"></i> Lọc
                      </button>
                      <button class="btn btn-secondary btn-block" id="resetSearchBtn" type="button">
                        <i class="fas fa-redo"></i> Xóa bộ lọc
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách nhà cung cấp</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" id="addSupplierBtn" data-toggle="modal" data-target="#addSupplierModal">
                    <i class="fas fa-plus"></i> Thêm
                  </button>
                </div>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                <table class="table table-bordered table-striped" id="suppliersTable">
                  <thead>
                  <tr>
                    <th class="sortable-column" data-sort-key="id" style="width: 50px;">
                      <span class="sort-header"><span>ID</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th class="sortable-column" data-sort-key="name">
                      <span class="sort-header"><span>Tên nhà cung cấp</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th class="sortable-column" data-sort-key="phone" style="width: 120px;">
                      <span class="sort-header"><span>Số điện thoại</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th class="sortable-column" data-sort-key="address">
                      <span class="sort-header"><span>Địa chỉ</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th class="sortable-column" data-sort-key="status" style="width: 120px;">
                      <span class="sort-header"><span>Trạng thái</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th class="sortable-column" data-sort-key="created_at" style="width: 130px;">
                      <span class="sort-header"><span>Ngày tạo</span><span class="sort-arrows" aria-hidden="true"><i class="fas fa-arrow-up sort-arrow-up"></i><i class="fas fa-arrow-down sort-arrow-down"></i></span></span>
                    </th>
                    <th style="width: 130px;">Hành động</th>
                  </tr>
                  </thead>
                  <tbody id="suppliersTableBody">
                  </tbody>
                </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addSupplierModalLabel">Thêm nhà cung cấp</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addSupplierForm" action="#" method="post" novalidate data-skip-inline-validation="true" onsubmit="return false;">
        <div class="modal-body">
          <input type="hidden" id="supplierId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div class="form-group">
            <label for="supplierName">Tên Nhà Cung Cấp *</label>
            <input type="text" class="form-control" id="supplierName" name="name">
            <small id="supplierNameError" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label for="supplierPhone">Số Điện Thoại *</label>
            <input type="tel" class="form-control" id="supplierPhone" name="phone" placeholder="0901234567 hoặc +84901234567">
            <small id="supplierPhoneError" class="text-danger d-none">Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 hoặc 11 số.</small>
            <small class="form-text text-muted">Bắt buộc nhập số điện thoại bắt đầu bằng 0 và đủ 10 hoặc 11 số.</small>
          </div>
          
          <div class="form-group">
            <label for="supplierAddress">Địa Chỉ *</label>
            <textarea class="form-control" id="supplierAddress" name="address" rows="3" placeholder="Nhập địa chỉ..."></textarea>
            <small id="supplierAddressError" class="text-danger d-none"></small>
          </div>

          <div class="form-group">
            <label for="supplierStatus">Trạng Thái *</label>
            <select class="form-control" id="supplierStatus" name="status">
              <option value="active">Đang hoạt động</option>
              <option value="inactive">Không hoạt động</option>
            </select>
            <small id="supplierStatusError" class="text-danger d-none"></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Thêm mới</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Supplier Modal -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1" role="dialog" aria-labelledby="viewSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewSupplierModalLabel">Chi tiết nhà cung cấp</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="viewSupplierBody">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<!-- Delete Supplier Modal -->
<div class="modal fade" id="deleteSupplierModal" tabindex="-1" role="dialog" aria-labelledby="deleteSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger">
        <h5 class="modal-title" id="deleteSupplierModalLabel">Xác nhận xóa nhà cung cấp</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="confirmDeleteSupplierForm" action="#" method="post" onsubmit="return false;">
        <div class="modal-body">
          <input type="hidden" id="deleteSupplierId" value="">
          <p class="mb-2">Bạn có chắc chắn muốn xóa nhà cung cấp này?</p>
          <p class="mb-0">Nhà cung cấp: <strong id="deleteSupplierName">-</strong></p>
          <small class="text-muted">Lưu ý: Nếu NCC đã có phiếu nhập thì hệ thống sẽ tự chuyển sang không hoạt động (xóa mềm), chưa có phiếu nhập sẽ xóa hẳn.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-danger" id="confirmDeleteSupplierBtn">Xác nhận</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
const deletingSupplierIds = new Set();
let supplierRowsCache = [];
const supplierSortState = {
  key: 'id',
  direction: 'desc'
};

if (typeof window.Swal === 'undefined') {
  window.Swal = {
    fire: function(options) {
      const config = options || {};
      const title = config.title || 'Thông báo';
      const text = config.text || '';
      const icon = config.icon || 'info';

      if (config.showCancelButton) {
        const confirmed = window.confirm([title, text].filter(Boolean).join('\n'));
        return Promise.resolve({ isConfirmed: confirmed });
      }

      window.alert([title, text].filter(Boolean).join('\n'));
      return Promise.resolve({ isConfirmed: icon !== 'error' });
    }
  };
}

$(document).ready(function() {
  let isSubmittingSupplierForm = false;

  // Chặn submit mặc định để tránh reload trang khi có xung đột handler.
  const formEl = document.getElementById('addSupplierForm');
  if (formEl) {
    formEl.addEventListener('submit', function(evt) {
      evt.preventDefault();
    }, true);
  }

    // Load suppliers table
    initSupplierSorting();
    loadSuppliers();

  // Handle add supplier button
  $('#addSupplierBtn').on('click', function() {
        $('#addSupplierForm')[0].reset();
        $('#supplierId').val('');
        $('#supplierName').prop('readonly', false);
        $('#supplierStatus').val('active');
        $('#addSupplierModalLabel').text('Thêm nhà cung cấp');
        $('#submitBtn').text('Thêm mới');
        clearSupplierValidationErrors();
    });

    $('#supplierName, #supplierPhone, #supplierAddress').on('input', function() {
      const fieldId = this.id;
      if (fieldId === 'supplierName') {
        clearSupplierNameError();
      } else if (fieldId === 'supplierPhone') {
        clearSupplierPhoneError();
      } else if (fieldId === 'supplierAddress') {
        clearSupplierAddressError();
      }
    });

    $('#supplierStatus').on('change', function() {
      clearSupplierStatusError();
    });

    // Handle form submission
    $('#addSupplierForm').on('submit.supplier', function(e) {
        e.preventDefault();
        e.stopPropagation();

        if (isSubmittingSupplierForm) {
          return false;
        }

        const $submitBtn = $('#submitBtn');
        
        const supplierId = $('#supplierId').val();
        const action = supplierId ? 'update' : 'add';

        const validation = validateSupplierForm();
        if (!validation.isValid) {
          const firstInvalidField = validation.firstInvalidField;
          if (firstInvalidField) {
            $('#' + firstInvalidField).focus();
          }
          return false;
        }

        $('#supplierPhone').val(validation.normalizedPhone);

        isSubmittingSupplierForm = true;
        $submitBtn.prop('disabled', true).text('Đang lưu...');

        const formData = new FormData(this);
        formData.append('action', action);
        if (supplierId) {
            formData.append('id', supplierId);
        }
        
        $.ajax({
            type: 'POST',
            url: 'suppliers.php',
            data: formData,
            processData: false,
            contentType: false,
          dataType: 'json',
            success: function(response) {
                if (response.success) {
                const successMessage = action === 'add' ? 'Thêm nhà cung cấp thành công' : 'Cập nhật nhà cung cấp thành công';
                Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                  text: successMessage,
                        timer: 1500
                    });

                loadSuppliers($('#searchInput').val().trim(), function() {
                  $('#addSupplierModal').modal('hide');
                });
                } else {
                    const serverErrors = response.errors || {};

                    if (serverErrors.name) {
                      showSupplierNameError(serverErrors.name);
                    }
                    if (serverErrors.phone) {
                      showSupplierPhoneError(serverErrors.phone);
                    }
                    if (serverErrors.address) {
                      showSupplierAddressError(serverErrors.address);
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message || 'Có lỗi xảy ra'
                    });
                }
                },
                error: function() {
                  Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: 'Lỗi kết nối server'
                  });
                },
                complete: function() {
                  isSubmittingSupplierForm = false;
                  const currentAction = $('#supplierId').val() ? 'update' : 'add';
                  $submitBtn
                    .prop('disabled', false)
                    .text(currentAction === 'add' ? 'Thêm mới' : 'Cập nhật');
            },
        });

        return false;
    });

    $('#submitBtn').on('click.supplier', function(e) {
      e.preventDefault();
      $('#addSupplierForm').triggerHandler('submit');
    });

    $('#addSupplierModal').on('hidden.bs.modal', function() {
      isSubmittingSupplierForm = false;
      $('#addSupplierForm')[0].reset();
      $('#supplierId').val('');
      $('#supplierName').prop('readonly', false);
      $('#addSupplierModalLabel').text('Thêm nhà cung cấp');
      $('#submitBtn').prop('disabled', false).text('Thêm mới');
      clearSupplierValidationErrors();
    });
    
    // Handle search
    $('#searchBtn').on('click', function() {
      loadSuppliers($('#searchInput').val().trim());
    });

    $('#searchInput').on('keypress', function(e) {
      if (e.which === 13) {
        e.preventDefault();
        loadSuppliers($('#searchInput').val().trim());
      }
    });

    $('#resetSearchBtn').on('click', function() {
      $('#searchInput').val('');
      loadSuppliers('');
    });

    $(document).on('click', '.js-view-supplier', function() {
      const supplierId = Number($(this).data('id') || 0);
      if (supplierId > 0) {
        viewSupplier(supplierId);
      }
    });

    $(document).on('click', '.js-edit-supplier', function() {
      const supplierId = Number($(this).data('id') || 0);
      if (supplierId > 0) {
        editSupplier(supplierId);
      }
    });

    $('#suppliersTableBody').on('click', 'button.js-delete-supplier, button.js-delete-supplier *', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const supplierId = Number($(this).closest('button.js-delete-supplier').data('id') || 0);
      if (supplierId > 0) {
        openDeleteSupplierModal(supplierId);
      }
    });

    $('#confirmDeleteSupplierForm').on('submit', function(e) {
      e.preventDefault();
      e.stopPropagation();

      const supplierId = Number($('#deleteSupplierId').val() || 0);
      if (supplierId > 0) {
        deleteSupplier(supplierId);
      }
      return false;
    });

    $('#confirmDeleteSupplierBtn').on('click', function(e) {
      e.preventDefault();
      $('#confirmDeleteSupplierForm').triggerHandler('submit');
    });

    $('#deleteSupplierModal').on('hidden.bs.modal', function() {
      $('#deleteSupplierId').val('');
      $('#deleteSupplierName').text('-');
      $('#confirmDeleteSupplierBtn').prop('disabled', false).text('Xác nhận');
    });
});

    function loadSuppliers(keyword = '', onDone = null) {
      const formData = new FormData();
      formData.append('action', 'search');
      formData.append('csrf_token', getSupplierCSRFToken());
      formData.append('keyword', keyword);

      return $.ajax({
        type: 'POST',
        url: 'suppliers.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            supplierRowsCache = Array.isArray(response.data) ? response.data : [];
            renderTable(supplierRowsCache);
            if (typeof onDone === 'function') {
              onDone(supplierRowsCache);
            }
          } else {
            supplierRowsCache = [];
            renderTable([]);
            Swal.fire({
              icon: 'error',
              title: 'Lỗi',
              text: response.message || 'Không tải được danh sách nhà cung cấp'
            });
          }
        },
        error: function() {
          supplierRowsCache = [];
          renderTable([]);
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Không tải được danh sách nhà cung cấp'
          });
          if (typeof onDone === 'function') {
            onDone([]);
          }
        }
      });
}

function renderTable(suppliers) {
    const tbody = $('#suppliersTableBody');
    tbody.empty();

  const sortedSuppliers = sortSuppliersForDisplay(suppliers || []);
    
  if (sortedSuppliers.length === 0) {
    tbody.append('<tr><td colspan="7" class="text-center">Không có dữ liệu</td></tr>');
        return;
    }
    
  sortedSuppliers.forEach(function(supplier) {
        const createdAt = new Date(supplier.created_at).toLocaleDateString('vi-VN');
      const statusBadge = getSupplierStatusBadge(supplier.status);
      const safeName = escapeHtmlText(supplier.name || '-');
      const safePhone = escapeHtmlText(supplier.phone || '-');
      const safeAddress = escapeHtmlText(supplier.address || '-');
        const row = `
            <tr>
                <td>${supplier.id}</td>
          <td><strong>${safeName}</strong></td>
          <td>${safePhone}</td>
          <td>${safeAddress}</td>
          <td>${statusBadge}</td>
                <td>${createdAt}</td>
                <td>
                  <button type="button" class="btn btn-info btn-sm js-view-supplier" data-id="${supplier.id}" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                  <button type="button" class="btn btn-warning btn-sm js-edit-supplier" data-id="${supplier.id}" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-danger btn-sm js-delete-supplier" data-id="${supplier.id}" title="Xóa nhà cung cấp">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function initSupplierSorting() {
  $('#suppliersTable thead').on('click', 'th.sortable-column', function() {
    const nextKey = String($(this).data('sort-key') || '').trim();
    if (!nextKey) {
      return;
    }

    if (supplierSortState.key === nextKey) {
      supplierSortState.direction = supplierSortState.direction === 'asc' ? 'desc' : 'asc';
    } else {
      supplierSortState.key = nextKey;
      supplierSortState.direction = 'asc';
    }

    updateSupplierSortIndicators();
    renderTable(supplierRowsCache);
  });

  updateSupplierSortIndicators();
}

function updateSupplierSortIndicators() {
  const $headers = $('#suppliersTable thead th.sortable-column');
  $headers.removeClass('sort-asc sort-desc');

  const $activeHeader = $('#suppliersTable thead th.sortable-column[data-sort-key="' + supplierSortState.key + '"]');
  if (supplierSortState.direction === 'asc') {
    $activeHeader.addClass('sort-asc');
  } else {
    $activeHeader.addClass('sort-desc');
  }
}

function sortSuppliersForDisplay(suppliers) {
  const rows = Array.isArray(suppliers) ? suppliers.slice() : [];
  const key = supplierSortState.key;
  const direction = supplierSortState.direction === 'asc' ? 1 : -1;

  rows.sort(function(a, b) {
    const aValue = getSupplierSortValue(a, key);
    const bValue = getSupplierSortValue(b, key);

    const aEmpty = aValue === null || aValue === undefined || aValue === '';
    const bEmpty = bValue === null || bValue === undefined || bValue === '';
    if (aEmpty && bEmpty) {
      return 0;
    }
    if (aEmpty) {
      return 1;
    }
    if (bEmpty) {
      return -1;
    }

    if (typeof aValue === 'number' && typeof bValue === 'number') {
      return (aValue - bValue) * direction;
    }

    return String(aValue).localeCompare(String(bValue), 'vi', { sensitivity: 'base', numeric: true }) * direction;
  });

  return rows;
}

function getSupplierSortValue(supplier, key) {
  if (!supplier || !key) {
    return '';
  }

  if (key === 'id') {
    return Number(supplier.id || 0);
  }
  if (key === 'phone') {
    const digits = String(supplier.phone || '').replace(/\D+/g, '');
    return digits === '' ? 0 : Number(digits);
  }
  if (key === 'created_at') {
    const ts = new Date(supplier.created_at || '').getTime();
    return Number.isNaN(ts) ? 0 : ts;
  }
  if (key === 'status') {
    return (supplier.status || '') === 'active' ? 1 : 0;
  }

  return String(supplier[key] || '');
}

function viewSupplier(id) {
    const formData = new FormData();
    formData.append('action', 'view');
  formData.append('csrf_token', getSupplierCSRFToken());
    formData.append('id', id);
    
    $.ajax({
        type: 'POST',
        url: 'suppliers.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const supplier = response.data;
                const createdAt = new Date(supplier.created_at).toLocaleDateString('vi-VN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
              const safeName = escapeHtmlText(supplier.name || '-');
              const safePhone = escapeHtmlText(supplier.phone || '-');
              const safeAddress = escapeHtmlText(supplier.address || '-');
              const safeStatus = getSupplierStatusBadge(supplier.status);
                
                const html = `
                    <div class="form-group">
                        <label><strong>ID:</strong></label>
                        <p>${supplier.id}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Tên Nhà Cung Cấp:</strong></label>
                  <p>${safeName}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Số Điện Thoại:</strong></label>
                  <p>${safePhone}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Địa Chỉ:</strong></label>
                  <p>${safeAddress}</p>
                    </div>
                    <div class="form-group">
                      <label><strong>Trạng Thái:</strong></label>
                  <p>${safeStatus}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Ngày Tạo:</strong></label>
                        <p>${createdAt}</p>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label><strong>Số phiếu nhập:</strong></label>
                                <p class="h5 text-primary">${supplier.stats.imports}</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label><strong>Tổng giá trị nhập:</strong></label>
                                <p class="h5 text-success">${formatCurrency(supplier.stats.total_value)}</p>
                            </div>
                        </div>
                    </div>
                `;
                
                $('#viewSupplierBody').html(html);
                $('#viewSupplierModalLabel').text('Chi tiết nhà cung cấp - ' + supplier.name);
                $('#viewSupplierModal').modal('show');
                            } else {
                                Swal.fire({
                                  icon: 'error',
                                  title: 'Lỗi',
                                  text: response.message || 'Không lấy được thông tin nhà cung cấp'
                                });
            }
                        },
                        error: function() {
                            Swal.fire({
                              icon: 'error',
                              title: 'Lỗi',
                              text: 'Lỗi kết nối server'
                            });
        }
    });
}

function editSupplier(id) {
  const formData = new FormData();
  formData.append('action', 'view');
  formData.append('csrf_token', getSupplierCSRFToken());
  formData.append('id', id);

  $.ajax({
    type: 'POST',
    url: 'suppliers.php',
    data: formData,
    processData: false,
    contentType: false,
    dataType: 'json',
    success: function(response) {
      if (!response.success || !response.data) {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          text: response.message || 'Không lấy được thông tin nhà cung cấp'
        });
        return;
      }

      const supplier = response.data;
      $('#supplierId').val(supplier.id);
      $('#supplierName').val(supplier.name || '');
      $('#supplierName').prop('readonly', true);
      $('#supplierPhone').val(supplier.phone || '');
      $('#supplierAddress').val(supplier.address || '');
      $('#supplierStatus').val(supplier.status || 'active');
      clearSupplierValidationErrors();

      $('#addSupplierModalLabel').text('Chỉnh sửa nhà cung cấp');
      $('#submitBtn').text('Cập nhật');
      $('#addSupplierModal').modal('show');
    },
    error: function() {
      Swal.fire({
        icon: 'error',
        title: 'Lỗi',
        text: 'Lỗi kết nối server'
      });
    }
  });
}

function openDeleteSupplierModal(id) {
  if (deletingSupplierIds.has(id)) {
    return;
  }

  const $btn = $('#suppliersTableBody').find('button.js-delete-supplier[data-id="' + id + '"]').first();
  const supplierName = $btn.closest('tr').find('td:nth-child(2)').text().trim() || ('NCC #' + id);

  $('#deleteSupplierId').val(id);
  $('#deleteSupplierName').text(supplierName);
  $('#confirmDeleteSupplierBtn').prop('disabled', false).text('Xác nhận');
  $('#deleteSupplierModal').modal('show');
}

function deleteSupplier(id) {
    if (deletingSupplierIds.has(id)) {
      return;
    }

    deletingSupplierIds.add(id);
    $('#confirmDeleteSupplierBtn').prop('disabled', true).text('Đang xử lý...');

    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('csrf_token', getSupplierCSRFToken());
    formData.append('id', id);

    $.ajax({
      type: 'POST',
      url: 'suppliers.php',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#deleteSupplierModal').modal('hide');
          Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: response.message || 'Cập nhật trạng thái nhà cung cấp thành công',
            timer: 1500
          });
          loadSuppliers($('#searchInput').val().trim());
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: response.message || 'Có lỗi xảy ra'
          });
        }
      },
      error: function(xhr) {
        Swal.fire({
          icon: 'error',
          title: 'Lỗi',
          text: xhr.responseText || 'Lỗi kết nối server'
        });
      },
      complete: function() {
        deletingSupplierIds.delete(id);
        $('#confirmDeleteSupplierBtn').prop('disabled', false).text('Xác nhận');
      }
    });
}

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount || 0);
}

function normalizeSupplierPhoneInput(phone) {
  if (!phone) {
    return '';
  }

  let normalized = String(phone).replace(/\D+/g, '');
  if (normalized.startsWith('84')) {
    normalized = '0' + normalized.slice(2);
  }

  return normalized;
}

function validateSupplierForm() {
  clearSupplierValidationErrors();

  const name = $('#supplierName').val().trim();
  const rawPhone = $('#supplierPhone').val().trim();
  const normalizedPhone = normalizeSupplierPhoneInput(rawPhone);
  const address = $('#supplierAddress').val().trim();
  const status = $('#supplierStatus').val();

  const errors = [];
  let firstInvalidField = '';

  if (name === '') {
    const msg = 'Tên nhà cung cấp không được để trống.';
    showSupplierNameError(msg);
    errors.push(msg);
    firstInvalidField = firstInvalidField || 'supplierName';
  }

  if (normalizedPhone === '') {
    const msg = 'Số điện thoại không được để trống.';
    showSupplierPhoneError(msg);
    errors.push(msg);
    firstInvalidField = firstInvalidField || 'supplierPhone';
  } else if (!/^0\d{9,10}$/.test(normalizedPhone)) {
    const msg = 'Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 hoặc 11 số.';
    showSupplierPhoneError(msg);
    errors.push(msg);
    firstInvalidField = firstInvalidField || 'supplierPhone';
  }

  if (address === '') {
    const msg = 'Địa chỉ không được để trống.';
    showSupplierAddressError(msg);
    errors.push(msg);
    firstInvalidField = firstInvalidField || 'supplierAddress';
  }

  if (!status) {
    const msg = 'Vui lòng chọn trạng thái.';
    showSupplierStatusError(msg);
    errors.push(msg);
    firstInvalidField = firstInvalidField || 'supplierStatus';
  }

  if (errors.length > 0) {
    return {
      isValid: false,
      normalizedPhone: normalizedPhone,
      firstInvalidField: firstInvalidField
    };
  }

  return {
    isValid: true,
    normalizedPhone: normalizedPhone,
    firstInvalidField: ''
  };
}

function showSupplierNameError(message) {
  $('#supplierName').addClass('is-invalid');
  $('#supplierNameError').text(message).removeClass('d-none');
}

function clearSupplierNameError() {
  $('#supplierName').removeClass('is-invalid');
  $('#supplierNameError').addClass('d-none').text('');
}

function showSupplierPhoneError(message) {
  $('#supplierPhone').addClass('is-invalid');
  $('#supplierPhoneError').text(message).removeClass('d-none');
}

function clearSupplierPhoneError() {
  $('#supplierPhone').removeClass('is-invalid');
  $('#supplierPhoneError').addClass('d-none').text('');
}

function showSupplierAddressError(message) {
  $('#supplierAddress').addClass('is-invalid');
  $('#supplierAddressError').text(message).removeClass('d-none');
}

function clearSupplierAddressError() {
  $('#supplierAddress').removeClass('is-invalid');
  $('#supplierAddressError').addClass('d-none').text('');
}

function showSupplierStatusError(message) {
  $('#supplierStatus').addClass('is-invalid');
  $('#supplierStatusError').text(message).removeClass('d-none');
}

function clearSupplierStatusError() {
  $('#supplierStatus').removeClass('is-invalid');
  $('#supplierStatusError').addClass('d-none').text('');
}

function clearSupplierValidationErrors() {
  clearSupplierNameError();
  clearSupplierPhoneError();
  clearSupplierAddressError();
  clearSupplierStatusError();
}

function getSupplierStatusBadge(status) {
  if (status === 'inactive') {
    return '<span class="badge badge-secondary">Không hoạt động</span>';
  }

  return '<span class="badge badge-success">Đang hoạt động</span>';
}

function getSupplierCSRFToken() {
  return $('input[name="csrf_token"]').val() || '';
}

function escapeHtmlText(text) {
  return String(text)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/\"/g, '&quot;')
    .replace(/'/g, '&#39;');
}
</script>