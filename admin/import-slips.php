<?php 
session_start();
$page_title = "Quản lý Phiếu Nhập Kho";
include '../includes/config.php';
include '../includes/database.php';
include '../includes/functions.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'add':
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
                exit;
            }
            

            // Lấy staff_id từ user đăng nhập, nếu không có thì tạo mới
            $user_id = getCurrentUserId();
            if (!$user_id) {
              echo json_encode(['success' => false, 'message' => 'Bạn chưa đăng nhập hoặc phiên đăng nhập đã hết. Vui lòng đăng nhập lại!']);
              exit;
            }
            $db = getDB();
            $stmt = $db->prepare('SELECT id FROM staff WHERE users_id = ? LIMIT 1');
            $stmt->execute([$user_id]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($staff && isset($staff['id'])) {
              $staff_id = $staff['id'];
            } else {
              // Tạo staff mới cho user này
              $user_stmt = $db->prepare('SELECT username, email FROM users WHERE id = ? LIMIT 1');
              $user_stmt->execute([$user_id]);
              $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
              $full_name = $user['username'] ?? ('User_' . $user_id);
              $insert_stmt = $db->prepare('INSERT INTO staff (users_id, full_name, status) VALUES (?, ?, ?)');
              $insert_stmt->execute([$user_id, $full_name, 'active']);
              $staff_id = $db->lastInsertId();
            }
            $data = [
              'staff_id' => $staff_id,
              'supplier_id' => $_POST['supplier_id'] ?? 0,
              'total_amount' => $_POST['total_amount'] ?? 0,
              'import_date' => $_POST['import_date'] ?? date('Y-m-d H:i:s'),
              'note' => $_POST['note'] ?? '',
              'status' => $_POST['status'] ?? 'Đang chờ duyệt',
              'details' => json_decode($_POST['details'] ?? '[]', true)
            ];
            
            if ($data['supplier_id'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn nhà cung cấp']);
                exit;
            }
            
            try {
              $result = addImportSlip($data);
              echo json_encode($result);
            } catch (Throwable $e) {
              error_log('[IMPORT_SLIP_ADD_ERROR] ' . $e->getMessage() . "\n" . $e->getTraceAsString());
              echo json_encode(['success' => false, 'message' => 'Lỗi server: ' . $e->getMessage()]);
            }
            break;
            
        case 'update':
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
                exit;
            }
            
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $data = [
                'total_amount' => $_POST['total_amount'] ?? 0,
                'note' => $_POST['note'] ?? '',
                'status' => $_POST['status'] ?? 'Đang chờ duyệt'
            ];
            
            $result = updateImportSlip($id, $data);
            echo json_encode($result);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteImportSlip($id);
            echo json_encode($result);
            break;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $slip = getImportSlipById($id);
            $details = getImportDetails($id);
            
            if (!$slip) {
                echo json_encode(['success' => false, 'message' => 'Phiếu nhập không tồn tại']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $slip, 'details' => $details]);
            break;
    }
    exit;
}

// Get data
$imports = getAllImportSlips();
$suppliers = getAllSuppliers();

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
            <h1 class="m-0">Quản lý Phiếu Nhập</h1>
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
                    <i class="fas fa-plus"></i> Thêm Phiếu Nhập
                  </button>
                </div>
              </div>
              <div class="card-body">

                <table class="table table-bordered table-striped table-hover" id="importsTable">
                  <thead class="table-dark">
                  <tr>
                    <th>Nhà Cung Cấp</th>
                    <th>Ngày Nhập</th>
                    <th style="width: 120px;">Số Tiền</th>
                    <th style="width: 100px;">Trạng Thái</th>
                    <th style="width: 120px;">Hành Động</th>
                  </tr>
                  </thead>
                  <tbody id="importsTableBody">

                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
          </div>
        </div>
      </div>
    </section>

  </div>

<!-- Add/Edit Import Modal -->
<div class="modal fade" id="addImportModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Thêm Phiếu Nhập</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="addImportForm">
        <div class="modal-body">
          <input type="hidden" id="importId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          
          <div class="form-group">
            <label for="importSupplier">Nhà Cung Cấp *</label>
            <select class="form-control" id="importSupplier" name="supplier_id" required>
              <option value="">-- Chọn Nhà Cung Cấp --</option>
              <?php foreach ($suppliers as $sup): ?>
                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="importDate">Ngày Nhập</label>
            <input type="datetime-local" class="form-control" id="importDate" name="import_date">
          </div>
          
          <div class="form-group">
            <label for="importNote">Ghi Chú</label>
            <textarea class="form-control" id="importNote" name="note" rows="3"></textarea>
          </div>
          
          <div class="form-group">
            <label for="importStatus">Trạng Thái</label>
            <select class="form-control" id="importStatus" name="status">
              <option value="Đang chờ duyệt">Đang chờ duyệt</option>
              <option value="Đã nhập">Đã nhập</option>
              <option value="Đã hủy">Đã hủy</option>
            </select>
          </div>
          
          <div class="form-group">
            <label for="importTotalAmount">Tổng Tiền</label>
            <input type="number" class="form-control" id="importTotalAmount" name="total_amount" value="0" min="0" step="1000">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitImportBtn">Thêm Phiếu Nhập</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
  console.log('document ready');
  loadImports();
    
  $('#addImportModal').on('show.bs.modal', function() {
    console.log('show.bs.modal');
    $('#addImportForm')[0].reset();
    $('#importId').val('');
  });
    
  $(document).on('submit', '#addImportForm', function(e) {
    console.log('submit import form');
    e.preventDefault();
        
    const importId = $('#importId').val();
    const action = importId ? 'update' : 'add';
        
    const formData = new FormData(this);
    formData.append('action', action);
    if (importId) {
      formData.append('id', importId);
    }
        
    $.ajax({
      type: 'POST',
      url: 'import-slips.php',
      data: formData,
      processData: false,
      contentType: false,
      success: function(response) {
        console.log('AJAX success', response);
        if (response && response.success) {
          Swal.fire({
            icon: 'success',
            title: 'Thành công',
            text: response.message || 'Thao tác thành công',
            timer: 1500
          });
          $('#addImportModal').modal('hide');
          loadImports();
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: (response && response.message) ? response.message : 'Có lỗi xảy ra'
          });
        }
      },
      error: function(xhr, status, error) {
        console.error('AJAX error', status, error, xhr.responseText);
        Swal.fire({
          icon: 'error',
          title: 'Lỗi kết nối',
          text: 'Không thể gửi dữ liệu lên server!'
        });
      }
    });
  });
});

function loadImports() {
    const importsData = <?php echo json_encode($imports); ?>;
    renderTable(importsData);
}

function renderTable(imports) {
    const tbody = $('#importsTableBody');
    tbody.empty();
    
    if (imports.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>');
        return;
    }
    
    imports.forEach(function(imp) {
        let statusBadge = '';
        if (imp.status === 'Đã nhập') {
            statusBadge = '<span class="badge badge-success">Đã nhập</span>';
        } else if (imp.status === 'Đang chờ duyệt') {
            statusBadge = '<span class="badge badge-warning">Chờ duyệt</span>';
        } else if (imp.status === 'Đã hủy') {
            statusBadge = '<span class="badge badge-danger">Đã hủy</span>';
        }
        
        const date = new Date(imp.import_date).toLocaleDateString('vi-VN');
        const amount = new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(imp.total_amount || 0);
        
        const row = `
            <tr>
                <td>${imp.id}</td>
                <td><strong>${imp.supplier_name}</strong></td>
                <td>${date}</td>
                <td class="text-right">${amount}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="editImport(${imp.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteImport(${imp.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function editImport(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    
    $.ajax({
        type: 'POST',
        url: 'import-slips.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                const imp = response.data;
                $('#importId').val(imp.id);
                $('#importSupplier').val(imp.supplier_id);
                $('#importDate').val(imp.import_date);
                $('#importNote').val(imp.note);
                $('#importStatus').val(imp.status);
                $('#importTotalAmount').val(imp.total_amount);
                
                $('#addImportModal').modal('show');
            }
        }
    });
}

function deleteImport(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa phiếu nhập này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            $.ajax({
                type: 'POST',
                url: 'import-slips.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Xóa thành công',
                            timer: 1500
                        });
                        loadImports();
                    }
                }
            });
        }
    });
}
</script>
