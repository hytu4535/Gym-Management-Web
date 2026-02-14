<?php 
$page_title = "Gán dinh dưỡng cho hội viên";
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
            <h1 class="m-0">Gán dinh dưỡng cho hội viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dinh dưỡng hội viên</li>
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
                <h3 class="card-title">Danh sách dinh dưỡng đã gán</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMemberNutritionModal">
                    <i class="fas fa-plus"></i> Gán chế độ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Chế độ dinh dưỡng</th>
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
                    <td>Thực đơn giảm cân</td>
                    <td>2026-01-10</td>
                    <td>2026-03-10</td>
                    <td><span class="badge badge-success">Đang áp dụng</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Nguyễn Tường Huy</td>
                    <td>Thực đơn tăng cơ</td>
                    <td>2026-02-01</td>
                    <td>2026-04-01</td>
                    <td><span class="badge badge-success">Đang áp dụng</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>3</td>
                    <td>Nguyễn Nguyên Bảo</td>
                    <td>Tư vấn dinh dưỡng cá nhân</td>
                    <td>2025-11-15</td>
                    <td>2026-01-15</td>
                    <td><span class="badge badge-secondary">Đã kết thúc</span></td>
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

    <!-- Modal Gán chế độ dinh dưỡng -->
    <div class="modal fade" id="addMemberNutritionModal" tabindex="-1" role="dialog" aria-labelledby="addMemberNutritionModalLabel" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addMemberNutritionModalLabel">Gán chế độ dinh dưỡng cho hội viên</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <form id="addMemberNutritionForm">
              <div class="form-group">
                <label for="mnMemberId">Hội viên</label>
                <select class="form-control" id="mnMemberId" name="member_id" required>
                  <option value="">-- Chọn hội viên --</option>
                  <option value="1">Trương Trung Kiên</option>
                  <option value="2">Nguyễn Tường Huy</option>
                  <option value="3">Nguyễn Nguyên Bảo</option>
                </select>
              </div>
              <div class="form-group">
                <label for="nutritionPlanId">Chế độ dinh dưỡng</label>
                <select class="form-control" id="nutritionPlanId" name="nutrition_plan_id" required>
                  <option value="">-- Chọn chế độ --</option>
                  <option value="1">Thực đơn giảm cân</option>
                  <option value="2">Thực đơn tăng cơ</option>
                  <option value="3">Tư vấn dinh dưỡng cá nhân</option>
                </select>
              </div>
              <div class="form-group">
                <label for="mnStartDate">Ngày bắt đầu</label>
                <input type="date" class="form-control" id="mnStartDate" name="start_date" required>
              </div>
              <div class="form-group">
                <label for="mnEndDate">Ngày kết thúc</label>
                <input type="date" class="form-control" id="mnEndDate" name="end_date">
              </div>
              <div class="form-group">
                <label for="mnStatus">Trạng thái</label>
                <select class="form-control" id="mnStatus" name="status">
                  <option value="đang áp dụng">Đang áp dụng</option>
                  <option value="đã kết thúc">Đã kết thúc</option>
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
