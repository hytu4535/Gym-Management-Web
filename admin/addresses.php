<?php 
$page_title = "Quản lý Địa Chỉ Hội Viên";
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
            <h1 class="m-0">Quản lý Địa Chỉ Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Địa Chỉ</li>
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
                <h3 class="card-title">Danh sách Địa Chỉ Hội Viên</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addAddressModal">
                    <i class="fas fa-plus"></i> Thêm Địa Chỉ
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Địa Chỉ Đầy Đủ</th>
                    <th>Thành Phố</th>
                    <th>Quận/Huyện</th>
                    <th>Mặc Định</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Nguyễn Văn A</td>
                    <td>123 Nguyễn Huệ</td>
                    <td>TP. Hồ Chí Minh</td>
                    <td>Quận 1</td>
                    <td><span class="badge badge-success"><i class="fas fa-check"></i> Mặc định</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Nguyễn Văn A</td>
                    <td>456 Lê Lợi</td>
                    <td>TP. Hồ Chí Minh</td>
                    <td>Quận 3</td>
                    <td><span class="badge badge-secondary">Phụ</span></td>
                    <td>
                      <button class="btn btn-primary btn-sm"><i class="fas fa-check"></i> Đặt mặc định</button>
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
