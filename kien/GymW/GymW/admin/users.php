<?php 
$page_title = "Quản lý Users";
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
            <h1 class="m-0">Quản lý Users</h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Users</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách Users</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addUserModal">
                    <i class="fas fa-plus"></i> Thêm User
                  </button>
                </div>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="usersTable" class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên đăng nhập</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>admin</td>
                    <td>admin@gym.com</td>
                    <td><span class="badge badge-danger">Admin</span></td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>2026-01-15</td>
                    <td>
                      <button class="btn btn-info btn-sm" title="Xem">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>staff01</td>
                    <td>staff01@gym.com</td>
                    <td><span class="badge badge-info">Staff</span></td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>2026-01-16</td>
                    <td>
                      <button class="btn btn-info btn-sm" title="Xem">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>3</td>
                    <td>member01</td>
                    <td>member01@gmail.com</td>
                    <td><span class="badge badge-secondary">Member</span></td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>2026-01-17</td>
                    <td>
                      <button class="btn btn-info btn-sm" title="Xem">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>4</td>
                    <td>trainer01</td>
                    <td>trainer01@gym.com</td>
                    <td><span class="badge badge-warning">Trainer</span></td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>2026-01-18</td>
                    <td>
                      <button class="btn btn-info btn-sm" title="Xem">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  <tr>
                    <td>5</td>
                    <td>user_inactive</td>
                    <td>inactive@gmail.com</td>
                    <td><span class="badge badge-secondary">Member</span></td>
                    <td><span class="badge badge-danger">Inactive</span></td>
                    <td>2026-01-10</td>
                    <td>
                      <button class="btn btn-info btn-sm" title="Xem">
                        <i class="fas fa-eye"></i>
                      </button>
                      <button class="btn btn-warning btn-sm" title="Sửa">
                        <i class="fas fa-edit"></i>
                      </button>
                      <button class="btn btn-danger btn-sm" title="Xóa">
                        <i class="fas fa-trash"></i>
                      </button>
                    </td>
                  </tr>
                  </tbody>
                </table>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->
          </div>
          <!-- /.col -->
        </div>
        <!-- /.row -->
      </div>
      <!-- /.container-fluid -->
    </section>
    <!-- /.content -->

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addUserModalLabel">Thêm User Mới</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="addUserForm">
              <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" class="form-control" id="username" name="username" required>
              </div>
              <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
              </div>
              <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>
              <div class="form-group">
                <label for="role">Vai trò</label>
                <select class="form-control" id="role" name="role" required>
                  <option value="">Chọn vai trò</option>
                  <option value="admin">Admin</option>
                  <option value="staff">Staff</option>
                  <option value="trainer">Trainer</option>
                  <option value="member">Member</option>
                </select>
              </div>
            </form>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            <button type="button" class="btn btn-primary">Lưu</button>
          </div>
        </div>
      </div>
    </div>
    <!-- /.modal -->

<?php include 'layout/footer.php'; ?>