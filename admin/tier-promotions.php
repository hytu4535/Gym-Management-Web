<?php 
$page_title = "Quản lý Khuyến Mãi Theo Hạng";
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
            <h1 class="m-0">Quản lý Khuyến Mãi Theo Hạng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Khuyến Mãi</li>
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
                <h3 class="card-title">Danh sách Chương Trình Khuyến Mãi</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPromotionModal">
                    <i class="fas fa-plus"></i> Thêm Khuyến Mãi
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Chương Trình</th>
                    <th>Hạng Áp Dụng</th>
                    <th>Loại Giảm</th>
                    <th>Giá Trị</th>
                    <th>Thời Gian</th>
                    <th>Giới Hạn</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Giảm PT cho hội viên Bạc</td>
                    <td><span class="badge badge-light">Bạc</span></td>
                    <td>Phần trăm</td>
                    <td>10%</td>
                    <td>01/01/2024 - 31/12/2024</td>
                    <td>100 lượt</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Tặng 1 buổi tập Vàng</td>
                    <td><span class="badge badge-warning">Vàng</span></td>
                    <td>Gói dịch vụ</td>
                    <td>1 buổi</td>
                    <td>01/01/2024 - 31/12/2024</td>
                    <td>50 lượt</td>
                    <td><span class="badge badge-success">Active</span></td>
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
