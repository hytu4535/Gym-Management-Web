<?php 
$page_title = "Gán dịch vụ cho hội viên";
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
            <h1 class="m-0">Gán dịch vụ cho hội viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dịch vụ hội viên</li>
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
                <h3 class="card-title">Danh sách dịch vụ đã gán</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMemberServiceModal">
                    <i class="fas fa-plus"></i> Gán dịch vụ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Dịch vụ</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày kết thúc</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Trương Trung Kiên</td>
                    <td>Xông hơi khô</td>
                    <td>2026-01-15</td>
                    <td>2026-02-15</td>
                    <td><span class="badge badge-success">Còn hiệu lực</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Nguyễn Tường Huy</td>
                    <td>Massage toàn thân</td>
                    <td>2026-01-20</td>
                    <td>2026-02-20</td>
                    <td><span class="badge badge-success">Còn hiệu lực</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>3</td>
                    <td>Nguyễn Nguyên Bảo</td>
                    <td>Tư vấn dinh dưỡng</td>
                    <td>2025-12-01</td>
                    <td>2026-01-01</td>
                    <td><span class="badge badge-secondary">Đã dùng</span></td>
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

    <!-- Modal Gán dịch vụ -->
    <div class="modal fade" id="addMemberServiceModal" tabindex="-1" role="dialog" aria-labelledby="addMemberServiceModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addMemberServiceModalLabel">Gán dịch vụ cho hội viên</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="addMemberServiceForm">
              <div class="form-group">
                <label for="memberId">Hội viên</label>
                <select class="form-control" id="memberId" name="member_id" required>
                  <option value="">-- Chọn hội viên --</option>
                  <option value="1">Trương Trung Kiên</option>
                  <option value="2">Nguyễn Tường Huy</option>
                  <option value="3">Nguyễn Nguyên Bảo</option>
                </select>
              </div>
              <div class="form-group">
                <label for="serviceId">Dịch vụ</label>
                <select class="form-control" id="serviceId" name="service_id" required>
                  <option value="">-- Chọn dịch vụ --</option>
                  <option value="1">Xông hơi khô</option>
                  <option value="2">Massage toàn thân</option>
                  <option value="3">Tư vấn dinh dưỡng</option>
                </select>
              </div>
              <div class="form-group">
                <label for="startDate">Ngày bắt đầu</label>
                <input type="date" class="form-control" id="startDate" name="start_date" required>
              </div>
              <div class="form-group">
                <label for="endDate">Ngày kết thúc</label>
                <input type="date" class="form-control" id="endDate" name="end_date">
              </div>
              <div class="form-group">
                <label for="msStatus">Trạng thái</label>
                <select class="form-control" id="msStatus" name="status">
                  <option value="còn hiệu lực">Còn hiệu lực</option>
                  <option value="đã dùng">Đã dùng</option>
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

  </div>

<?php include 'layout/footer.php'; ?>
