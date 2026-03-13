<?php 
$page_title = "Quản lý Packages";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$sql = "SELECT id, package_name, duration_months, price, description, status 
        FROM membership_packages 
        ORDER BY id DESC";

$result = $conn->query($sql);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Gói tập</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Packages</li>
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
                <h3 class="card-title">Danh sách Gói tập</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addPackageModal">
                    <i class="fas fa-plus"></i> Thêm Gói tập
                  </button>
                </div>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Tên gói</th>
                    <th>Thời hạn</th>
                    <th>Giá</th>
                    <th>Mô tả</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $formattedPrice = number_format($row['price'], 0, ',', '.') . 'đ';
                            $duration = $row['duration_months'] . ' Tháng';
                            $shortDesc = mb_strimwidth($row['description'] ?? '', 0, 50, "...");
                            
                            if ($row['status'] == 'active') {
                                $statusBadge = '<span class="badge badge-success">Đang mở bán</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-secondary">Ngừng bán</span>';
                            }

                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td class='font-weight-bold text-primary'>{$row['package_name']}</td>";
                            echo "  <td>{$duration}</td>";
                            echo "  <td class='text-danger font-weight-bold'>{$formattedPrice}</td>";
                            echo "  <td><small>{$shortDesc}</small></td>";
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                      <a href='package_edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Sửa thông tin'>
                                          <i class='fas fa-edit'></i>
                                      </a>
                                      <a href='process/package_delete.php?id={$row['id']}' 
                                         class='btn btn-danger btn-sm' title='Xóa gói tập' 
                                         onclick=\"return confirm('Bạn có chắc chắn muốn xóa gói tập này không?');\">
                                          <i class='fas fa-trash'></i>
                                      </a>
                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Hệ thống chưa có gói tập nào. Hãy thêm mới!</td></tr>";
                    }
                  ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <div class="modal fade" id="addPackageModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Thêm Gói Tập Mới</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <form action="process/package_add.php" method="POST">
        <div class="modal-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Tên Gói Tập <span class="text-danger">*</span></label>
                <input type="text" class="form-control" name="package_name" required placeholder="VD: Gói Gym 1 Tháng">
              </div>
              <div class="form-group">
                <label>Thời Hạn (Tháng) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="duration_months" required min="1" value="1">
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Giá Tiền (VNĐ) <span class="text-danger">*</span></label>
                <input type="number" class="form-control" name="price" required min="0" value="0">
              </div>
              <div class="form-group">
                <label>Trạng Thái</label>
                <select class="form-control" name="status">
                  <option value="active">Active (Đang mở bán)</option>
                  <option value="inactive">Inactive (Ngừng bán)</option>
                </select>
              </div>
            </div>
          </div>
          <div class="form-group">
            <label>Mô tả chi tiết</label>
            <textarea class="form-control" name="description" rows="3" placeholder="Nhập mô tả về quyền lợi gói tập..."></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
          <button type="submit" class="btn btn-primary" name="btn_add_package">Lưu Gói Tập</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>