<?php 
$page_title = "Quản lý Payments";
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
            <h1 class="m-0">Quản lý Payments</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Payments</li>
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
                <h3 class="card-title">Danh sách Payments</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Số tiền</th>
                    <th>Phương thức</th>
                    <th>Ngày TT</th>
                    <th>Trạng thái</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>#ORD001</td>
                    <td>Nguyễn Văn A</td>
                    <td>1,500,000đ</td>
                    <td>Chuyển khoản</td>
                    <td>2026-01-20</td>
                    <td><span class="badge badge-success">Thành công</span></td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>#ORD002</td>
                    <td>Trần Thị B</td>
                    <td>800,000đ</td>
                    <td>Tiền mặt</td>
                    <td>2026-01-21</td>
                    <td><span class="badge badge-success">Thành công</span></td>
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