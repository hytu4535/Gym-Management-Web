<?php 
$page_title = "Quản lý Hạng Hội Viên";
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
            <h1 class="m-0">Quản lý Hạng Hội Viên</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Hạng Hội Viên</li>
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
                <h3 class="card-title">Danh sách Hạng Hội Viên</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addTierModal">
                    <i class="fas fa-plus"></i> Thêm Hạng
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên Hạng</th>
                    <th>Cấp Độ</th>
                    <th>Chi Tiêu Tối Thiểu</th>
                    <th>Giảm Giá (%)</th>
                    <th>Trạng Thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td><span class="badge badge-secondary">Đồng</span></td>
                    <td>1</td>
                    <td>0 VNĐ</td>
                    <td>0%</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td><span class="badge badge-light">Bạc</span></td>
                    <td>2</td>
                    <td>3,000,000 VNĐ</td>
                    <td>5%</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>3</td>
                    <td><span class="badge badge-warning">Vàng</span></td>
                    <td>3</td>
                    <td>10,000,000 VNĐ</td>
                    <td>10%</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>4</td>
                    <td><span class="badge badge-primary">Bạch Kim</span></td>
                    <td>4</td>
                    <td>30,000,000 VNĐ</td>
                    <td>15%</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>5</td>
                    <td><span class="badge badge-info">Kim Cương</span></td>
                    <td>5</td>
                    <td>50,000,000 VNĐ</td>
                    <td>20%</td>
                    <td><span class="badge badge-success">Active</span></td>
                    <td>
                      <button class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></button>
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
