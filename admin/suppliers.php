<?php 
session_start();
$page_title = "Qu\u1ea3n l\u00fd Nh\u00e0 Cung C\u1ea5p";
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
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? ''
            ];
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Tên nhà cung cấp không được để trống']);
                exit;
            }
            
            $result = addSupplier($data);
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
                'phone' => $_POST['phone'] ?? '',
                'address' => $_POST['address'] ?? ''
            ];
            
            if (empty($data['name'])) {
                echo json_encode(['success' => false, 'message' => 'Tên nhà cung cấp không được để trống']);
                exit;
            }
            
            $result = updateSupplier($id, $data);
            echo json_encode($result);
            break;
            
        case 'delete':
            $id = $_POST['id'] ?? 0;
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteSupplier($id);
            echo json_encode($result);
            break;
            
        case 'view':
            $id = $_POST['id'] ?? 0;
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
    }
    exit;
}

// Get all suppliers for initial display
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
            <h1 class="m-0">Quản lý Nhà Cung Cấp</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Nhà Cung Cấp</li>
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
                <h3 class="card-title">Danh sách Nhà Cung Cấp</h3>
                <div class="card-tools">
                  <div class="input-group input-group-sm" style="width: 150px;">
                    <input type="text" id="searchInput" class="form-control" placeholder="Tìm kiếm...">
                    <div class="input-group-append">
                      <button class="btn btn-default" id="searchBtn">
                        <i class="fas fa-search"></i>
                      </button>
                    </div>
                  </div>
                  <button type="button" class="btn btn-primary btn-sm ml-2" data-toggle="modal" data-target="#addSupplierModal">
                    <i class="fas fa-plus"></i> Thêm NCC
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped table-hover" id="suppliersTable">
                  <thead class="table-dark">
                  <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Tên Nhà Cung Cấp</th>
                    <th style="width: 120px;">Số Điện Thoại</th>
                    <th>Địa Chỉ</th>
                    <th style="width: 130px;">Ngày Tạo</th>
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
    </section>

  </div>

<!-- Add/Edit Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addSupplierModalLabel">Thêm Nhà Cung Cấp</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form id="addSupplierForm">
        <div class="modal-body">
          <input type="hidden" id="supplierId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          
          <div class="form-group">
            <label for="supplierName">Tên Nhà Cung Cấp *</label>
            <input type="text" class="form-control" id="supplierName" name="name" required>
          </div>
          
          <div class="form-group">
            <label for="supplierPhone">Số Điện Thoại</label>
            <input type="tel" class="form-control" id="supplierPhone" name="phone" placeholder="0901234567">
          </div>
          
          <div class="form-group">
            <label for="supplierAddress">Địa Chỉ</label>
            <textarea class="form-control" id="supplierAddress" name="address" rows="3" placeholder="Nhập địa chỉ..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitBtn">Thêm NCC</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- View Supplier Modal -->
<div class="modal fade" id="viewSupplierModal" tabindex="-1" role="dialog" aria-labelledby="viewSupplierModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewSupplierModalLabel">Chi tiết Nhà Cung Cấp</h5>
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

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    // Load suppliers table
    loadSuppliers();
    
    // Handle add supplier button
    $('#addSupplierModal').on('show.bs.modal', function() {
        $('#addSupplierForm')[0].reset();
        $('#supplierId').val('');
        $('#addSupplierModalLabel').text('Thêm Nhà Cung Cấp');
        $('#submitBtn').text('Thêm NCC');
    });
    
    // Handle form submission
    $('#addSupplierForm').on('submit', function(e) {
        e.preventDefault();
        
        const supplierId = $('#supplierId').val();
        const action = supplierId ? 'update' : 'add';
        
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
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: action === 'add' ? 'Thêm nhà cung cấp thành công' : 'Cập nhật nhà cung cấp thành công',
                        timer: 1500
                    });
                    
                    $('#addSupplierModal').modal('hide');
                    loadSuppliers();
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
    
    // Handle search
    $('#searchBtn, #searchInput').on('click keypress', function(e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        
        const keyword = $('#searchInput').val();
        const formData = new FormData();
        formData.append('action', 'search');
        formData.append('keyword', keyword);
        
        $.ajax({
            type: 'POST',
            url: 'suppliers.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    renderTable(response.data);
                }
            }
        });
    });
});

function loadSuppliers() {
    const suppliersData = <?php echo json_encode($suppliers); ?>;
    renderTable(suppliersData);
}

function renderTable(suppliers) {
    const tbody = $('#suppliersTableBody');
    tbody.empty();
    
    if (suppliers.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">Không có dữ liệu</td></tr>');
        return;
    }
    
    suppliers.forEach(function(supplier) {
        const createdAt = new Date(supplier.created_at).toLocaleDateString('vi-VN');
        const row = `
            <tr>
                <td>${supplier.id}</td>
                <td><strong>${supplier.name}</strong></td>
                <td>${supplier.phone || '-'}</td>
                <td>${supplier.address || '-'}</td>
                <td>${createdAt}</td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="viewSupplier(${supplier.id})" title="Xem chi tiết">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="editSupplier(${supplier.id})" title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="deleteSupplier(${supplier.id})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function viewSupplier(id) {
    const formData = new FormData();
    formData.append('action', 'view');
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
                
                const html = `
                    <div class="form-group">
                        <label><strong>ID:</strong></label>
                        <p>${supplier.id}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Tên Nhà Cung Cấp:</strong></label>
                        <p>${supplier.name}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Số Điện Thoại:</strong></label>
                        <p>${supplier.phone || '-'}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Địa Chỉ:</strong></label>
                        <p>${supplier.address || '-'}</p>
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
                $('#viewSupplierModalLabel').text('Chi tiết - ' + supplier.name);
                $('#viewSupplierModal').modal('show');
            }
        }
    });
}

function editSupplier(id) {
    const suppliersData = <?php echo json_encode($suppliers); ?>;
    const supplier = suppliersData.find(s => s.id == id);
    
    if (supplier) {
        $('#supplierId').val(supplier.id);
        $('#supplierName').val(supplier.name);
        $('#supplierPhone').val(supplier.phone || '');
        $('#supplierAddress').val(supplier.address || '');
        
        $('#addSupplierModalLabel').text('Chỉnh sửa Nhà Cung Cấp');
        $('#submitBtn').text('Cập nhật NCC');
        
        $('#addSupplierModal').modal('show');
    }
}

function deleteSupplier(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa nhà cung cấp này?',
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
                url: 'suppliers.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Xóa nhà cung cấp thành công',
                            timer: 1500
                        });
                        loadSuppliers();
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

function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount || 0);
}
</script>
