<?php 
$page_title = "Lịch Sử Sử Dụng Khuyến Mãi";
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
            <h1 class="m-0">Lịch Sử Sử Dụng Khuyến Mãi</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Lịch Sử KM</li>
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
                <h3 class="card-title">Danh sách Sử Dụng Khuyến Mãi</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội Viên</th>
                    <th>Hạng</th>
                    <th>Chương Trình KM</th>
                    <th>Đơn Hàng</th>
                    <th>Số Tiền Giảm</th>
                    <th>Ngày Áp Dụng</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <tr>
                    <td>1</td>
                    <td>Nguyễn Văn A</td>
                    <td><span class="badge badge-light">Bạc</span></td>
                    <td>Giảm PT cho hội viên Bạc</td>
                    <td>#DH001</td>
                    <td>50,000 VNĐ</td>
                    <td>05/02/2024 10:30</td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>2</td>
                    <td>Trần Thị B</td>
                    <td><span class="badge badge-warning">Vàng</span></td>
                    <td>Tặng 1 buổi tập Vàng</td>
                    <td>#DH002</td>
                    <td>100,000 VNĐ</td>
                    <td>06/02/2024 14:20</td>
                    <td>
                      <button class="btn btn-info btn-sm"><i class="fas fa-eye"></i></button>
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
