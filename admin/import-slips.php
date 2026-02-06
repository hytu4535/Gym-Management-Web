<?php 
$page_title = "Quản lý Phiếu Nhập Kho";
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
            <h1 class="m-0">Quản lý Phiếu Nhập Kho</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Phiếu Nhập</li>
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
                <h3 class="card-title">Danh sách Phiếu Nhập Kho</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addImportModal">
                    <i class="fas fa-plus"></i> Tạo Phiếu Nhập
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>Mã Phiếu</th>
                    <th>Nhà Cung Cấp</th>
                    <th>Nhân Viên</th>
                    <th>Tổng Tiền</th>
                    <th>Ngày Nhập</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>#PN001</td>
                    <td>Công ty Thể Thao Đại Việt</td>
                    <td>Nguyễn Văn A</td>
                    <td>52,000,000 VNĐ</td>
                    <td>01/02/2024</td>
                    <td><span class="badge badge-success">Đã nhập</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-primary btn-sm"><i class="fas fa-print"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>#PN002</td>
                    <td>Whey Store VN</td>
                    <td>Nguyễn Văn A</td>
                    <td>15,000,000 VNĐ</td>
                    <td>02/02/2024</td>
                    <td><span class="badge badge-warning">Đang chờ duyệt</span></td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-success btn-sm"><i class="fas fa-check"></i> Duyệt</button>
                      <button class="btn btn-danger btn-sm"><i class="fas fa-times"></i> Hủy</button>
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
