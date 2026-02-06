<?php 
$page_title = "Quản lý Dịch Vụ";
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
            <h1 class="m-0">Quản lý Dịch Vụ</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Dịch Vụ</li>
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
                <h3 class="card-title">Danh sách Dịch Vụ</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addServiceModal">
                    <i class="fas fa-plus"></i> Thêm Dịch Vụ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Dịch Vụ</th>
                    <th>Loại</th>
                    <th>Giá (VNĐ)</th>
                    <th>Mô Tả</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Massage thư giãn</td>
                    <td><span class="badge badge-info">Thư giãn</span></td>
                    <td>200,000</td>
                    <td>Massage toàn thân 60 phút</td>
                    <td><span class="badge badge-success">Hoạt động</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Xoa bóp chân</td>
                    <td><span class="badge badge-primary">Xoa bóp</span></td>
                    <td>150,000</td>
                    <td>Xoa bóp chân sau tập 30 phút</td>
                    <td><span class="badge badge-success">Hoạt động</span></td>
                    <td>
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
