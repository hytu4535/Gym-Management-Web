<?php 
$page_title = "Quản lý Báo Cáo";
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
            <h1 class="m-0">Quản lý Báo Cáo</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Báo Cáo</li>
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
                <h3 class="card-title">Danh sách Báo Cáo Thống Kê</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addReportModal">
                    <i class="fas fa-plus"></i> Tạo Báo Cáo
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Loại Báo Cáo</th>
                    <th>Tiêu Đề</th>
                    <th>Kỳ Báo Cáo</th>
                    <th>Người Tạo</th>
                    <th>Trạng Thái</th>
                    <th>Ngày Tạo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td><span class="badge badge-success">Doanh thu</span></td>
                    <td>Báo cáo doanh thu tháng 1/2024</td>
                    <td>01/01/2024 - 31/01/2024</td>
                    <td>Admin</td>
                    <td><span class="badge badge-success">Completed</span></td>
                    <td>05/02/2024</td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                      <button class="btn btn-primary btn-sm"><i class="fas fa-download"></i></button>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-archive"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td><span class="badge badge-info">Hội viên</span></td>
                    <td>Thống kê hội viên mới tháng 1</td>
                    <td>01/01/2024 - 31/01/2024</td>
                    <td>Admin</td>
                    <td><span class="badge badge-warning">Draft</span></td>
                    <td>06/02/2024</td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                      <button class="btn btn-success btn-sm"><i class="fas fa-check"></i> Hoàn thành</button>
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
