<?php 
session_start();
$page_title = "Thông báo";
include '../includes/config.php';
include '../includes/database.php';
include '../includes/functions.php';

// Get all users for notification recipient selection
try {
    $db = getDB();
    $stmt = $db->query("SELECT id, name FROM users ORDER BY name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $users = [];
}

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
<<<<<<< HEAD
            <h1 class="m-0">Quản lý Thông Báo</h1>
=======
            <h1 class="m-0">Quản lý Notifications</h1>
>>>>>>> 34a2fa975ad8cea20aeca729e7e1e6c7991c6682
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
<<<<<<< HEAD
              <li class="breadcrumb-item active">Thông Báo</li>
=======
              <li class="breadcrumb-item active">Notifications</li>
>>>>>>> 34a2fa975ad8cea20aeca729e7e1e6c7991c6682
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
<<<<<<< HEAD
                <h3 class="card-title">Các Thông Báo</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addNotificationModal">
                    <i class="fas fa-plus"></i> Gửi Thông Báo
=======
                <h3 class="card-title">Danh sách Notifications</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus"></i> Tạo thông báo
>>>>>>> 34a2fa975ad8cea20aeca729e7e1e6c7991c6682
                  </button>
                </div>
              </div>
              <div class="card-body">
<<<<<<< HEAD
                <table class="table table-bordered table-striped table-hover" id="notificationsTable">
                  <thead class="table-dark">
                  <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Tiêu Đề</th>
                    <th>Người Nhận</th>
                    <th style="width: 130px;">Ngày Tạo</th>
                    <th style="width: 80px;">Trạng Thái</th>
                    <th style="width: 120px;">Hành Động</th>
                  </tr>
                  </thead>
                  <tbody id="notificationsTableBody">
=======
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tiêu đề</th>
                    <th>Nội dung</th>
                    <th>Người nhận</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Bảo trì thiết bị</td>
                    <td>Phòng gym sẽ bảo trì vào 25/01</td>
                    <td>Tất cả</td>
                    <td>2026-01-20</td>
                    <td><span class="badge badge-success">Đã gửi</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Khuyến mãi</td>
                    <td>Giảm 20% gói 6 tháng</td>
                    <td>Members</td>
                    <td>2026-01-22</td>
                    <td><span class="badge badge-warning">Nháp</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
>>>>>>> 34a2fa975ad8cea20aeca729e7e1e6c7991c6682
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<<<<<<< HEAD
  </div>

<!-- Add Notification Modal -->
<div class="modal fade" id="addNotificationModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Gửi Thông Báo</h5>
        <button type="button" class="close" data-dismiss="modal">
          <span>&times;</span>
        </button>
      </div>
      <form id="addNotificationForm">
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
          
          <div class="form-group">
            <label for="notificationUser">Gửi Tới *</label>
            <select class="form-control" id="notificationUser" name="user_id" required>
              <option value="">-- Chọn Người Dùng --</option>
              <?php foreach ($users as $user): ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="form-group">
            <label for="notificationTitle">Tiêu Đề *</label>
            <input type="text" class="form-control" id="notificationTitle" name="title" required placeholder="Nhập tiêu đề thông báo">
          </div>
          
          <div class="form-group">
            <label for="notificationContent">Nội Dung *</label>
            <textarea class="form-control" id="notificationContent" name="content" rows="4" required placeholder="Nhập nội dung thông báo..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Hủy</button>
          <button type="submit" class="btn btn-primary" id="submitNotificationBtn">Gửi Thông Báo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).ready(function() {
    loadNotifications();
    
    $('#addNotificationForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            type: 'POST',
            url: 'send-notification.php',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công',
                        text: 'Gửi thông báo thành công',
                        timer: 1500
                    });
                    $('#addNotificationModal').modal('hide');
                    $('#addNotificationForm')[0].reset();
                    loadNotifications();
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

function loadNotifications() {
    $.ajax({
        type: 'GET',
        url: 'get-notifications.php',
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                renderTable(response.data);
            }
        }
    });
}

function renderTable(notifications) {
    const tbody = $('#notificationsTableBody');
    tbody.empty();
    
    if (notifications.length === 0) {
        tbody.append('<tr><td colspan="6" class="text-center">Không có thông báo</td></tr>');
        return;
    }
    
    notifications.forEach(function(notif) {
        const createdAt = new Date(notif.created_at).toLocaleDateString('vi-VN');
        let readBadge = notif.is_read ? '<span class="badge badge-success">Đã đọc</span>' : '<span class="badge badge-warning">Chưa đọc</span>';
        
        const row = `
            <tr>
                <td>${notif.id}</td>
                <td>${notif.title}</td>
                <td>${notif.user_name}</td>
                <td>${createdAt}</td>
                <td>${readBadge}</td>
                <td>
                    <button class="btn btn-info btn-sm" onclick="viewNotification(${notif.id})" title="Xem">
                        <i class="fas fa-eye"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function viewNotification(id) {
    $.ajax({
        type: 'GET',
        url: 'get-notifications.php?id=' + id,
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                const notif = response.data;
                Swal.fire({
                    title: notif.title,
                    html: '<div style="text-align: left;">' + notif.content.replace(/\n/g, '<br>') + '</div>',
                    icon: 'info',
                    confirmButtonText: 'Đóng'
                });
            }
        }
    });
}
</script>
=======
<?php include 'layout/footer.php'; ?>
>>>>>>> 34a2fa975ad8cea20aeca729e7e1e6c7991c6682
