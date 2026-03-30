<?php 
session_start(); // luôn khởi tạo session

$page_title = "Quản lý Nhà Cung Cấp";

require_once '../config/db.php';

include '../includes/functions.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  if (function_exists('ob_get_length') && ob_get_length()) {
    ob_clean();
  }
    header('Content-Type: application/json');

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

      if ($data['phone'] === '') {
        $errors['phone'] = 'Số điện thoại không được để trống.';
      } elseif (!preg_match('/^0\d{9}$/', $data['phone'])) {
        $errors['phone'] = 'Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 số.';
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
              'address' => trim($_POST['address'] ?? '')
            ];

            $errors = $validateSupplierData($data);
            if (!empty($errors)) {
              echo json_encode([
                'success' => false,
                'message' => implode(' ', array_values($errors)),
                'errors' => $errors
              ]);
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
            
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $data = [
              'name' => trim($_POST['name'] ?? ''),
              'phone' => $normalizePhone($_POST['phone'] ?? ''),
              'address' => trim($_POST['address'] ?? '')
            ];

            $errors = $validateSupplierData($data);
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
        <div class="row mb-3">
          <div class="col-12">
            <div class="card card-primary collapsed-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Lọc nhà cung cấp</h3>
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
                <h3 class="card-title">Danh sách Nhà Cung Cấp</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm ml-2" id="addSupplierBtn" data-toggle="modal" data-target="#addSupplierModal">
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
      <form id="addSupplierForm" novalidate>
        <div class="modal-body">
          <input type="hidden" id="supplierId" value="">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

          <div id="supplierFormErrors" class="alert alert-danger d-none" role="alert">
            <ul id="supplierFormErrorsList" class="mb-0 pl-3"></ul>
          </div>
          
          <div class="form-group">
            <label for="supplierName">Tên Nhà Cung Cấp *</label>
            <input type="text" class="form-control" id="supplierName" name="name">
            <small id="supplierNameError" class="text-danger d-none"></small>
          </div>
          
          <div class="form-group">
            <label for="supplierPhone">Số Điện Thoại *</label>
            <input type="tel" class="form-control" id="supplierPhone" name="phone" placeholder="0901234567 hoặc +84901234567">
            <small id="supplierPhoneError" class="text-danger d-none">Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 số.</small>
            <small class="form-text text-muted">Bắt buộc nhập số điện thoại bắt đầu bằng 0 và đủ 10 số.</small>
          </div>
          
          <div class="form-group">
            <label for="supplierAddress">Địa Chỉ *</label>
            <textarea class="form-control" id="supplierAddress" name="address" rows="3" placeholder="Nhập địa chỉ..."></textarea>
            <small id="supplierAddressError" class="text-danger d-none"></small>
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
  $('#addSupplierBtn').on('click', function() {
        $('#addSupplierForm')[0].reset();
        $('#supplierId').val('');
        $('#addSupplierModalLabel').text('Thêm Nhà Cung Cấp');
        $('#submitBtn').text('Thêm NCC');
        clearSupplierValidationErrors();
    });

    $('#supplierName, #supplierPhone, #supplierAddress').on('input', function() {
      clearSupplierFormErrors();

      const fieldId = this.id;
      if (fieldId === 'supplierName') {
        clearSupplierNameError();
      } else if (fieldId === 'supplierPhone') {
        clearSupplierPhoneError();
      } else if (fieldId === 'supplierAddress') {
        clearSupplierAddressError();
      }
    });
    
    // Handle form submission
    $('#addSupplierForm').on('submit', function(e) {
        e.preventDefault();
        
        const supplierId = $('#supplierId').val();
        const action = supplierId ? 'update' : 'add';

        const validation = validateSupplierForm();
        if (!validation.isValid) {
          const firstInvalidField = validation.firstInvalidField;
          if (firstInvalidField) {
            $('#' + firstInvalidField).focus();
          }
          return;
        }

        $('#supplierPhone').val(validation.normalizedPhone);

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
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: action === 'add' ? 'Thêm nhà cung cấp thành công' : 'Cập nhật nhà cung cấp thành công',
                        timer: 1500
                    });
                    
                    $('#addSupplierModal').modal('hide');
                    loadSuppliers($('#searchInput').val().trim());
                } else {
                    const serverErrors = response.errors || {};
                    const errorMessages = [];

                    if (serverErrors.name) {
                      showSupplierNameError(serverErrors.name);
                      errorMessages.push(serverErrors.name);
                    }
                    if (serverErrors.phone) {
                      showSupplierPhoneError(serverErrors.phone);
                      errorMessages.push(serverErrors.phone);
                    }
                    if (serverErrors.address) {
                      showSupplierAddressError(serverErrors.address);
                      errorMessages.push(serverErrors.address);
                    }

                    if (errorMessages.length > 0) {
                      showSupplierFormErrors(errorMessages);
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
            }
        });
    });
    
    // Handle search
    $('#searchBtn, #searchInput').on('click keypress', function(e) {
        if (e.type === 'keypress' && e.which !== 13) return;
        
        const keyword = $('#searchInput').val().trim();
        loadSuppliers(keyword);
    });

    $('#resetSearchBtn').on('click', function() {
      $('#searchInput').val('');
      loadSuppliers('');
    });
});

    function loadSuppliers(keyword = '') {
      const formData = new FormData();
      formData.append('action', 'search');
      formData.append('keyword', keyword);

      $.ajax({
        type: 'POST',
        url: 'suppliers.php',
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function(response) {
          if (response.success) {
            renderTable(response.data || []);
          } else {
            renderTable([]);
          }
        },
        error: function() {
          renderTable([]);
          Swal.fire({
            icon: 'error',
            title: 'Lỗi',
            text: 'Không tải được danh sách nhà cung cấp'
          });
        }
      });
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
      $('#supplierPhone').val(supplier.phone || '');
      $('#supplierAddress').val(supplier.address || '');
      clearSupplierValidationErrors();

      $('#addSupplierModalLabel').text('Chỉnh sửa Nhà Cung Cấp');
      $('#submitBtn').text('Cập nhật NCC');
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
              dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Thành công',
                            text: 'Xóa nhà cung cấp thành công',
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
  } else if (!/^0\d{9}$/.test(normalizedPhone)) {
    const msg = 'Số điện thoại không hợp lệ. Vui lòng nhập số bắt đầu bằng 0 và đủ 10 số.';
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

  if (errors.length > 0) {
    showSupplierFormErrors(errors);
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

function showSupplierFormErrors(messages) {
  const html = messages.map(function(message) {
    return '<li>' + escapeHtmlText(message) + '</li>';
  }).join('');

  $('#supplierFormErrorsList').html(html);
  $('#supplierFormErrors').removeClass('d-none');
}

function clearSupplierFormErrors() {
  $('#supplierFormErrorsList').empty();
  $('#supplierFormErrors').addClass('d-none');
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

function clearSupplierValidationErrors() {
  clearSupplierFormErrors();
  clearSupplierNameError();
  clearSupplierPhoneError();
  clearSupplierAddressError();
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