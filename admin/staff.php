<?php
session_start();
$page_title = "Quản lý Staff";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('MANAGE_STAFF');

include 'layout/header.php';
include 'layout/sidebar.php';

require_once '../config/db.php';

// Lấy danh sách staff + user liên kết
$sql = "SELECT s.id, s.full_name, s.position, s.status, u.id as users_id, u.username, u.email 
        FROM staff s 
        LEFT JOIN users u ON s.users_id = u.id 
        ORDER BY s.id DESC";
$result = $conn->query($sql);
?>

<div class="content-wrapper">
  <div class="content-header">
    <div class="container-fluid">
      <h1 class="m-0">Quản lý Staff</h1>
    </div>
  </div>

  <section class="content">
    <div class="container-fluid">
      <div class="card">
        <div class="card-header">
          <h3 class="card-title">Danh sách Staff</h3>
          <div class="card-tools">
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addStaffModal">
                <i class="fas fa-plus"></i> Thêm Staff
            </button>
          </div>
        </div>
        <div class="card-body">
          <table class="table table-bordered table-striped data-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Họ tên</th>
                <th>Chức vụ</th>
                <th>User liên kết</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
              </tr>
            </thead>
            <tbody>
              <?php 
              if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                if ($row['status'] == 'active') {
                    $statusBadge = '<span class="badge badge-pill badge-success">
                                      <i class="fas fa-check-circle"></i> Đang làm việc
                                    </span>';
                } else {
                    $statusBadge = '<span class="badge badge-pill badge-danger">
                                      <i class="fas fa-times-circle"></i> Ngừng hoạt động
                                    </span>';
                }

                  $userInfo = $row['username'] ? $row['username']." (".$row['email'].")" : "<i>Chưa liên kết</i>";

                  echo "<tr>";
                  echo "  <td>{$row['id']}</td>";
                  echo "  <td class='font-weight-bold text-primary'>{$row['full_name']}</td>";
                  echo "  <td>{$row['position']}</td>";
                  echo "  <td>{$userInfo}</td>";
                  echo "  <td>{$statusBadge}</td>";
                  echo "  <td>
                            <button class='btn btn-warning btn-sm' data-toggle='modal' 
                                    data-target='#editStaffModal{$row['id']}'>
                              <i class='fas fa-edit'></i>
                            </button>
                            <a href='process/staff_management.php?action=delete&id={$row['id']}' 
                               class='btn btn-danger btn-sm' 
                               onclick=\"return confirm('Xóa staff này?');\">
                              <i class='fas fa-trash'></i>
                            </a>
                          </td>";
                  echo "</tr>";

                  // Modal edit cho từng staff
                  echo "
                  <div class='modal fade' id='editStaffModal{$row['id']}' tabindex='-1'>
                    <div class='modal-dialog modal-lg'>
                      <div class='modal-content'>
                        <div class='modal-header'>
                          <h5 class='modal-title'>Chỉnh sửa Staff</h5>
                          <button type='button' class='close' data-dismiss='modal'>&times;</button>
                        </div>
                        <form action='process/staff_management.php' method='POST'>
                          <input type='hidden' name='action' value='edit'>
                          <input type='hidden' name='id' value='{$row['id']}'>
                          <input type='hidden' name='users_id' value='{$row['users_id']}'>
                          <div class='modal-body'>
                            <div class='form-group'>
                              <label>Họ tên</label>
                              <input type='text' class='form-control' name='full_name' value='".htmlspecialchars($row['full_name'])."' required>
                            </div>
                            <div class='form-group'>
                              <label>Chức vụ</label>
                              <input type='text' class='form-control' name='position' value='".htmlspecialchars($row['position'])."' required>
                            </div>
                            <div class='form-group'>
                              <label>Trạng thái</label>
                              <select class='form-control' name='status'>
                                <option value='Active' ".($row['status']=='Active'?'selected':'').">Active</option>
                                <option value='Inactive' ".($row['status']=='Inactive'?'selected':'').">Inactive</option>
                              </select>
                            </div>
                            <hr>
                            <h6>Tài khoản User liên kết</h6>
                            <div class='form-group'>
                              <label>Username</label>
                              <input type='text' class='form-control' name='username' value='".htmlspecialchars($row['username'])."'>
                            </div>
                            <div class='form-group'>
                              <label>Email</label>
                              <input type='email' class='form-control' name='email' value='".htmlspecialchars($row['email'])."'>
                            </div>
                          </div>
                          <div class='modal-footer'>
                            <button type='button' class='btn btn-secondary' data-dismiss='modal'>Đóng</button>
                            <button type='submit' class='btn btn-primary'>Cập nhật</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>";
                }
              } else {
                echo "<tr><td colspan='6' class='text-center'>Chưa có staff nào.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </section>
</div>

<!-- Modal thêm staff -->
<div class="modal fade" id="addStaffModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Thêm Staff Mới</h5>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
      </div>
      <form action="process/staff_management.php" method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-group">
            <label>Họ tên</label>
            <input type="text" class="form-control" name="full_name" required>
          </div>
          <div class="form-group">
            <label>Chức vụ</label>
            <input type="text" class="form-control" name="position" required>
          </div>
          <div class="form-group">
            <label>Trạng thái</label>
            <select class="form-control" name="status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <hr>
          <h6>Tài khoản User mới</h6>
          <div class="form-group">
            <label>Username</label>
            <input type="text" class="form-control" name="username" required>
          </div>
          <div class="form-group">
            <label>Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <div class="form-group">
            <label>Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary">Lưu Staff</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<!-- Khởi tạo DataTables -->
<script>
  if ($.fn.DataTable.isDataTable('.data-table')) {
    $('.data-table').DataTable().destroy();
  }
  $(document).ready(function() {
    if ($.fn.DataTable.isDataTable('.data-table')) {
      $('.data-table').DataTable().destroy();
    }
    $('.data-table').DataTable({
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": false,
      "responsive": true
    });
  });
</script>
