<?php 
$page_title = "Quản lý Chế Độ Dinh Dưỡng";
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
            <h1 class="m-0">Quản lý Chế Độ Dinh Dưỡng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dinh Dưỡng</li>
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
                <h3 class="card-title">Danh sách Chế Độ Dinh Dưỡng</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addNutritionModal">
                    <i class="fas fa-plus"></i> Thêm Chế Độ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Chế Độ</th>
                    <th>Loại</th>
                    <th>Calories/Ngày</th>
                    <th>BMI Range</th>
                    <th>Giá (VNĐ)</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Thực đơn tăng cơ</td>
                    <td><span class="badge badge-success">Thực đơn</span></td>
                    <td>2500</td>
                    <td>18-24</td>
                    <td>500,000</td>
                    <td><span class="badge badge-success">Hoạt động</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Tư vấn giảm cân</td>
                    <td><span class="badge badge-info">Tư vấn</span></td>
                    <td>1800</td>
                    <td>25-30</td>
                    <td>300,000</td>
                    <td><span class="badge badge-success">Hoạt động</span></td>
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

<?php include 'layout/footer.php'; ?>
