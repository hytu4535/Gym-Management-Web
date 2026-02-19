<?php session_start();$page_title = "Quản lý Bảo trì Thiết Bị";
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
                'equipment_id' => $_POST['equipment_id'] ?? 0,
                'maintenance_date' => $_POST['maintenance_date'] ?? date('Y-m-d'),
                'description' => $_POST['description'] ?? ''
            ];
            
            if ($data['equipment_id'] <= 0) {
                echo json_encode(['success' => false, 'message' => 'Vui lòng chọn thiết bị']);
                exit;
            }
            
            $result = addMaintenanceRecord($data);
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
                'equipment_id' => $_POST['equipment_id'] ?? 0,
                'maintenance_date' => $_POST['maintenance_date'] ?? date('Y-m-d'),
                'description' => $_POST['description'] ?? ''
            ];
            
            $result = updateMaintenanceRecord($id, $data);
            echo json_encode($result);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteMaintenanceRecord($id);
            echo json_encode($result);
            break;
            
        case 'get':
            $id = $_POST['id'] ?? 0;
            $record = getMaintenanceRecordById($id);
            
            if (!$record) {
                echo json_encode(['success' => false, 'message' => 'Bản ghi không tồn tại']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $record]);
            break;
    }
    exit;
}

// Get data
$maintenance = getAllMaintenanceRecords();
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
            <h1 class="m-0">Bảo Trì Thiết Bị</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Equipment Maintenance</li>
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
                <h3 class="card-title">Lịch sử Bảo Trì Thiết Bị</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMaintenanceModal">
                    <i class="fas fa-plus"></i> Thêm lịch bảo trì
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped table-hover" id="maintenanceTable">
                  <thead class="table-dark">
                  <tr>
                    <th style="width: 50px;">ID</th>
                    <th>ID Thiết Bị</th>
                    <th style="width: 120px;">Ngày Bảo Trì</th>
                    <th>Mô Tả</th>
                    <th style="width: 120px;">Hành Động</th>
                  </tr>
                  </thead>
                  <tbody id="maintenanceTableBody">
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </div>

<!-- Add/Edit Maintenance Modal -->
<div class="modal fade" id="addMaintenanceModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Thêm Bản Ghi Bảo Trì</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="addMaintenanceForm">
        <div class="modal-body">
          <input type="hidden" id="maintenanceId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          
          <div class="form-group">
            <label for="maintenanceEquipment">Thiết Bị *</label>
            <select class="form-control" id="maintenanceEquipment" name="equipment_id" required>
              <option value="">-- Chọn Thiết Bị --</option>
              <?php foreach ($equipment as $eq): ?>
                <option value="<?php echo $eq['id']; ?>"><?php echo htmlspecialchars($eq['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="maintenanceDate">Ngày Bảo Trì *</label>
            <input type="date" class="form-control" id="maintenanceDate" name="maintenance_date" required>
          </div>
          
          <div class="form-group">
            <label for="maintenanceDescription">Mô Tả</label>
            <textarea class="form-control" id="maintenanceDescription" name="description" rows="3" placeholder="Mô tả công việc bảo trì..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitMaintenanceBtn">Thêm Bản Ghi</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    loadMaintenance();
    
    $('#addMaintenanceModal').on('show.bs.modal', function() {
        $('#addMaintenanceForm')[0].reset();
        $('#maintenanceId').val('');
        $('#maintenanceDate').value = new Date().toISOString().split('T')[0];
    });
    
    $('#addMaintenanceForm').on('submit', function(e) {
        e.preventDefault();
        
        const maintenanceId = $('#maintenanceId').val();
        const action = maintenanceId ? 'update' : 'add';
        
        const formData = new FormData(this);
        formData.append('action', action);
        if (maintenanceId) {
            formData.append('id', maintenanceId);
        }
        
        $.ajax({
            type: 'POST',
            url: 'equipment-maintenance.php',
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
                    $('#addMaintenanceModal').modal('hide');
                    loadMaintenance();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Lỗi',
                        text: response.message || 'Có lỗi xảy ra'
                    });
                }
            }
        });
    });
});

function loadMaintenance() {
    const maintenanceData = <?php echo json_encode($maintenance); ?>;
    renderTable(maintenanceData);
}

function renderTable(records) {
    const tbody = $('#maintenanceTableBody');
    tbody.empty();
    
    if (records.length === 0) {
        tbody.append('<tr><td colspan="5" class="text-center">Không có dữ liệu</td></tr>');
        return;
    }
    
    records.forEach(function(record) {
        const date = new Date(record.maintenance_date).toLocaleDateString('vi-VN');
        const row = `
            <tr>
                <td>${record.id}</td>
                <td><strong>${record.equipment_name}</strong></td>
                <td>${date}</td>
                <td>${record.description || '-'}</td>
                <td>
                    <button class="btn btn-warning btn-sm" onclick="editMaintenance(${record.id})">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteMaintenance(${record.id})">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function editMaintenance(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    
    $.ajax({
        type: 'POST',
        url: 'equipment-maintenance.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                const record = response.data;
                $('#maintenanceId').val(record.id);
                $('#maintenanceEquipment').val(record.equipment_id);
                $('#maintenanceDate').val(record.maintenance_date);
                $('#maintenanceDescription').val(record.description);
                
                $('#addMaintenanceModal').modal('show');
            }
        }
    });
}

function deleteMaintenance(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa bản ghi này?',
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
                url: 'equipment-maintenance.php',
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
                        loadMaintenance();
                    }
                }
            });
        }
    });
}
</script>
