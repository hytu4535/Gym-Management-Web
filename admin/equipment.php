<?php 
session_start();
$page_title = "Quản lý Thiết Bị";
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
            
            $data = [
                'name' => $_POST['name'] ?? '',
                'quantity' => $_POST['quantity'] ?? 1,
                'status' => $_POST['status'] ?? 'dang su dung'
            ];
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Tên thiết bị không được để trống']);
                exit;
            }
            
            $result = addEquipment($data);
            echo json_encode($result);
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
                'name' => $_POST['name'] ?? '',
                'quantity' => $_POST['quantity'] ?? 1,
                'status' => $_POST['status'] ?? 'dang su dung'
            ];
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Tên thiết bị không được để trống']);
                exit;
            }
            
            $result = updateEquipment($id, $data);
            echo json_encode($result);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteEquipment($id);
            echo json_encode($result);
            break;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $equipment = getEquipmentById($id);
            
            if (!$equipment) {
                echo json_encode(['success' => false, 'message' => 'Thiết bị không tồn tại']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $equipment]);
            break;
    }
    exit;
}

// Get all equipment for initial display
$equipment = getAllEquipment();

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
            <h1 class="m-0">Quản lý Thiết Bị</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Equipment</li>
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
                <h3 class="card-title">Danh sách thiết bị</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Thêm thiết bị
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped table-hover" id="equipmentTable">
                  <thead class="table-dark">
                  <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Tên thiết bị</th>
                    <th style="width: 100px;">Số lượng</th>
                    <th style="width: 120px;">Tình trạng</th>
                    <th style="width: 130px;">Hành động</th>
                  </tr>
                  </thead>
                  <tbody id="equipmentTableBody">
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>

<!-- Add/Edit Equipment Modal -->
<div class="modal fade" id="addEquipmentModal" tabindex="-1" role="dialog" aria-labelledby="addEquipmentModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addEquipmentModalLabel">Thêm Thiết Bị</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addEquipmentForm">
        <div class="modal-body">
          <input type="hidden" id="equipmentId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          
          <div class="form-group">
            <label for="equipmentName">Tên Thiết Bị *</label>
            <input type="text" class="form-control" id="equipmentName" name="name" required>
          </div>
          
          <div class="form-group">
            <label for="equipmentQuantity">Số Lượng</label>
            <input type="number" class="form-control" id="equipmentQuantity" name="quantity" value="1" min="1">
          </div>
          
          <div class="form-group">
            <label for="equipmentStatus">Tình Trạng</label>
            <select class="form-control" id="equipmentStatus" name="status">
              <option value="dang su dung">Đang Sử Dụng</option>
              <option value="bao tri">Bảo Trì</option>
              <option value="ngung hoat dong">Ngừng Hoạt Động</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitEquipmentBtn">Thêm Thiết Bị</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // Load equipment table
    loadEquipment();
    
    // Handle add equipment button
    $('#addEquipmentModal').on('show.bs.modal', function() {
        $('#addEquipmentForm')[0].reset();
        $('#equipmentId').val('');
        $('#addEquipmentModalLabel').text('Thêm Thiết Bị');
        $('#submitEquipmentBtn').text('Thêm Thiết Bị');
    });
    
    // Handle form submission
    $('#addEquipmentForm').on('submit', function(e) {
        e.preventDefault();
        
        const equipmentId = $('#equipmentId').val();
        const action = equipmentId ? 'update' : 'add';
        
        const formData = new FormData(this);
        formData.append('action', action);
        if (equipmentId) {
            formData.append('id', equipmentId);
        }
        
        $.ajax({
            type: 'POST',
            url: 'equipment.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: response.message || 'Thao tác thành công',
                        timer: 1500
                    });
                    
                    $('#addEquipmentModal').modal('hide');
                    loadEquipment();
                } else {
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
            }
        });
    });
});

function loadEquipment() {
    const equipmentData = <?php echo json_encode($equipment); ?>;
    renderTable(equipmentData);
}

function renderTable(equipments) {
    const tbody = $('#equipmentTableBody');
    tbody.empty();
    
    if (equipments.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>');
        return;
    }
    
    equipments.forEach(function(equip) {
        let statusBadge = '';
        const status = equip.status;
        
        if (status === 'dang su dung') {
            statusBadge = '<span class="badge badge-success">Đang Sử Dụng</span>';
        } else if (status === 'bao tri') {
            statusBadge = '<span class="badge badge-warning">Bảo Trì</span>';
        } else if (status === 'ngung hoat dong') {
            statusBadge = '<span class="badge badge-danger">Ngừng Hoạt Động</span>';
        }
        
        const row = `
            <tr>
                <td>${equip.id}</td>
                <td><strong>${equip.name}</strong></td>
                <td class="text-center">${equip.quantity}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="editEquipment(${equip.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteEquipment(${equip.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function editEquipment(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    
    $.ajax({
        type: 'POST',
        url: 'equipment.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const equip = response.data;
                $('#equipmentId').val(equip.id);
                $('#equipmentName').val(equip.name);
                $('#equipmentQuantity').val(equip.quantity);
                $('#equipmentStatus').val(equip.status);
                
                $('#addEquipmentModalLabel').text('Chỉnh sửa Thiết Bị');
                $('#submitEquipmentBtn').text('Cập nhật Thiết Bị');
                
                $('#addEquipmentModal').modal('show');
            }
        }
    });
}

function deleteEquipment(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa thiết bị này?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Xóa',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            $.ajax({
                type: 'POST',
                url: 'equipment.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: response.message || 'Xóa thành công',
                            timer: 1500
                        });
                        loadEquipment();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Lỗi',
                            text: response.message || 'Có lỗi xảy ra'
                        });
                    }
                }
            });
        }
    });
}
</script>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Máy chạy bộ</td>
                    <td>5</td>
                    <td><span class="badge badge-success">Tốt</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Tạ đơn</td>
                    <td>Strength</td>
                    <td>20</td>
                    <td><span class="badge badge-success">Tốt</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php include 'layout/footer.php'; ?>
