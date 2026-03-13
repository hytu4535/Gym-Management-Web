<?php 
session_start();
$page_title = "Quản lý Phản hồi";
include '../includes/config.php';
include '../includes/database.php';
include '../includes/functions.php';

// Handle AJAX actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    switch ($action) {
        case 'update_status':
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
                exit;
            }
            
            $feedbackId = $_POST['id'] ?? 0;
            $status = $_POST['status'] ?? 'new';
            
            if (!$feedbackId) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = updateFeedbackStatus($feedbackId, $status, getCurrentUserId());
            echo json_encode($result);
            break;
            
        case 'delete':
            $feedbackId = $_POST['id'] ?? 0;
            if (!$feedbackId) {
                echo json_encode(['success' => false, 'message' => 'ID không hợp lệ']);
                exit;
            }
            
            $result = deleteFeedback($feedbackId);
            echo json_encode($result);
            break;
            
        case 'get':
            $feedbackId = $_POST['id'] ?? 0;
            $feedback = getFeedbackById($feedbackId);
            
            if (!$feedback) {
                echo json_encode(['success' => false, 'message' => 'Phản hồi không tồn tại']);
                exit;
            }
            
            echo json_encode(['success' => true, 'data' => $feedback]);
            break;
    }
    exit;
}

// Get all feedback
$feedback = getAllFeedback();

include 'layout/header.php'; 
include 'layout/sidebar.php';
?>

<style>
    #feedbackTable td,
    #feedbackTable th {
        vertical-align: middle;
    }

    #feedbackTable td:nth-child(3) {
        max-width: 320px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Phản Hồi</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Phản Hồi</li>
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
                        <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title">Danh sách Phản Hồi</h3>
              </div>
              <div class="card-body">
                                <div class="table-responsive">
                                <table class="table table-bordered table-striped data-table" id="feedbackTable">
                                    <thead>
                  <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Thành Viên</th>
                    <th>Nội Dung</th>
                    <th style="width: 50px;">Rating</th>
                    <th style="width: 130px;">Ngày Tạo</th>
                    <th style="width: 100px;">Trạng Thái</th>
                    <th style="width: 120px;">Hành Động</th>
                  </tr>
                  </thead>
                  <tbody id="feedbackTableBody">
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

<!-- View Feedback Modal -->
<div class="modal fade" id="viewFeedbackModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
            <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Chi Tiết Phản Hồi</h5>
        <button type="button" class="close" data-dismiss="modal">
                    <span class="text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="feedbackContent">
      </div>
      <div class="modal-footer">
        <div id="feedbackActions">
        </div>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    loadFeedback();
});

function resetFeedbackTable() {
    if ($.fn.DataTable.isDataTable('#feedbackTable')) {
        $('#feedbackTable').DataTable().destroy();
    }
}

function initFeedbackTable() {
    $('#feedbackTable').DataTable({
        responsive: true,
        lengthChange: true,
        autoWidth: false,
        pageLength: 10,
        language: {
            search: 'Tìm kiếm:',
            lengthMenu: 'Hiển thị _MENU_ dòng',
            info: 'Hiển thị _START_ đến _END_ của _TOTAL_ dòng',
            infoEmpty: 'Không có dữ liệu',
            zeroRecords: 'Không tìm thấy dữ liệu phù hợp',
            paginate: {
                first: 'Đầu',
                last: 'Cuối',
                next: 'Tiếp',
                previous: 'Trước'
            }
        },
        columnDefs: [
            { orderable: false, targets: 6 }
        ]
    });
}

function loadFeedback() {
    const feedbackData = <?php echo json_encode($feedback); ?>;
    resetFeedbackTable();
    renderTable(feedbackData);
    initFeedbackTable();
}

function renderTable(feedbacks) {
    const tbody = $('#feedbackTableBody');
    tbody.empty();
    
    if (feedbacks.length === 0) {
        tbody.append('<tr><td colspan="7" class="text-center text-muted">Không có phản hồi</td></tr>');
        return;
    }
    
    feedbacks.forEach(function(fb) {
        let statusBadge = '';
        const status = fb.status;
        
        if (status === 'new') {
            statusBadge = '<span class="badge badge-danger">Mới</span>';
        } else if (status === 'processing') {
            statusBadge = '<span class="badge badge-warning">Đang xử lý</span>';
        } else if (status === 'processed') {
            statusBadge = '<span class="badge badge-info">Đã xử lý</span>';
        } else if (status === 'closed') {
            statusBadge = '<span class="badge badge-success">Đã đóng</span>';
        }
        
        const createdAt = new Date(fb.created_at).toLocaleDateString('vi-VN');
        const rating = fb.rating ? `${fb.rating}/5` : '-';
        const shortContent = fb.content.substring(0, 50) + (fb.content.length > 50 ? '...' : '');
        
        const row = `
            <tr>
                <td>${fb.id}</td>
                <td>${fb.member_name}</td>
                <td title="${fb.content}">${shortContent}</td>
                <td class="text-center">${rating}</td>
                <td>${createdAt}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button class="btn btn-info" onclick="viewFeedback(${fb.id})" title="Xem chi tiết">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-danger" onclick="deleteFeedbackItem(${fb.id})" title="Xóa phản hồi">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function viewFeedback(id) {
    const formData = new FormData();
    formData.append('action', 'get');
    formData.append('id', id);
    
    $.ajax({
        type: 'POST',
        url: 'feedback.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                const fb = response.data;
                const createdAt = new Date(fb.created_at).toLocaleDateString('vi-VN', {
                    year: 'numeric',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const rating = fb.rating ? `<strong>${fb.rating}/5 ⭐</strong>` : 'Không có đánh giá';
                
                const html = `
                    <div class="form-group">
                        <label><strong>Thành Viên:</strong></label>
                        <p>${fb.member_name}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Ngày:</strong></label>
                        <p>${createdAt}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Đánh Giá:</strong></label>
                        <p>${rating}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Nội Dung:</strong></label>
                        <p style="background: #f5f5f5; padding: 10px; border-radius: 4px;">${fb.content.replace(/\n/g, '<br>')}</p>
                    </div>
                    <div class="form-group">
                        <label><strong>Trạng Thái:</strong></label>
                        <select id="feedbackStatusSelect" class="form-control" style="width: auto;">
                            <option value="new" ${fb.status === 'new' ? 'selected' : ''}>Mới</option>
                            <option value="processing" ${fb.status === 'processing' ? 'selected' : ''}>Đang xử lý</option>
                            <option value="processed" ${fb.status === 'processed' ? 'selected' : ''}>Đã xử lý</option>
                            <option value="closed" ${fb.status === 'closed' ? 'selected' : ''}>Đã đóng</option>
                        </select>
                    </div>
                `;
                
                $('#feedbackContent').html(html);
                
                const actions = `
                    <button type="button" class="btn btn-primary" onclick="updateFeedbackStatus(${fb.id})">Cập Nhật</button>
                `;
                $('#feedbackActions').html(actions);
                
                $('#viewFeedbackModal').modal('show');
            }
        }
    });
}

function updateFeedbackStatus(id) {
    const newStatus = $('#feedbackStatusSelect').val();
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('id', id);
    formData.append('status', newStatus);
    formData.append('csrf_token', $('[name="csrf_token"]').val() || '');
    
    $.ajax({
        type: 'POST',
        url: 'feedback.php',
        data: formData,
        processData: false,
        contentType: false,
        success: function(response) {
            if (response.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Thành công',
                    text: response.message || 'Cập nhật thành công',
                    timer: 1500
                });
                $('#viewFeedbackModal').modal('hide');
                loadFeedback();
            }
        }
    });
}

function deleteFeedbackItem(id) {
    Swal.fire({
        title: 'Xác nhận xóa',
        text: 'Bạn có chắc chắn muốn xóa phản hồi này?',
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
                url: 'feedback.php',
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
                        loadFeedback();
                    }
                }
            });
        }
    });
}
</script>
