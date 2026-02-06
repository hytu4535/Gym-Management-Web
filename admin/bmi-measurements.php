<?php 
$page_title = "Lịch Sử Đo BMI";
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
            <h1 class="m-0">Lịch Sử Đo BMI</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Đo BMI</li>
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
                <h3 class="card-title">Danh sách Kết Quả Đo BMI</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addMeasurementModal">
                    <i class="fas fa-plus"></i> Thêm Kết Quả
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Chiều Cao (cm)</th>
                    <th>Cân Nặng (kg)</th>
                    <th>BMI</th>
                    <th>Thể Trạng</th>
                    <th>Máy Đo</th>
                    <th>Ngày Đo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Nguyễn Văn A</td>
                    <td>170</td>
                    <td>65</td>
                    <td>22.5</td>
                    <td><span class="badge badge-success">Bình thường</span></td>
                    <td>BMI-001</td>
                    <td>07/02/2024 08:30</td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Trần Thị B</td>
                    <td>160</td>
                    <td>52</td>
                    <td>20.3</td>
                    <td><span class="badge badge-success">Bình thường</span></td>
                    <td>BMI-001</td>
                    <td>07/02/2024 09:15</td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
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
