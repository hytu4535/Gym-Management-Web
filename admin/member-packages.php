<?php 
$page_title = "Quản lý Member Packages";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$sql = "SELECT mp.id, m.full_name, pkg.package_name, mp.start_date, mp.end_date, mp.status 
        FROM member_packages mp
        LEFT JOIN members m ON mp.member_id = m.id
        LEFT JOIN membership_packages pkg ON mp.package_id = pkg.id
        ORDER BY mp.id DESC";

$result = $conn->query($sql);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Member Packages</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Member Packages</li>
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
                <h3 class="card-title">Danh sách gói tập của hội viên</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Hội viên</th>
                    <th>Gói tập</th>
                    <th>Ngày bắt đầu</th>
                    <th>Ngày hết hạn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $memberName = $row['full_name'] ?? '<span class="text-danger">Đã xóa</span>';
                            $packageName = $row['package_name'] ?? '<span class="text-danger">Gói đã xóa</span>';
                            
                            $startDate = date('d/m/Y', strtotime($row['start_date']));
                            $endDate = date('d/m/Y', strtotime($row['end_date']));

                            if ($row['status'] == 'active') {
                                $today = date('Y-m-d');
                                if ($today > $row['end_date']) {
                                    $statusBadge = '<span class="badge badge-warning">Cần gia hạn</span>';
                                } else {
                                    $statusBadge = '<span class="badge badge-success">Đang hoạt động</span>';
                                }
                            } elseif ($row['status'] == 'expired') {
                                $statusBadge = '<span class="badge badge-secondary">Đã hết hạn</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-danger">Đã hủy</span>';
                            }

                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td class='font-weight-bold'>{$memberName}</td>";
                            echo "  <td>{$packageName}</td>";
                            echo "  <td>{$startDate}</td>";
                            echo "  <td class='text-danger'>{$endDate}</td>";
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                        <a href='member_package_edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Gia hạn / Sửa'>
                                            <i class='fas fa-edit'></i>
                                        </a>

                                        <a href='process/member_package_delete.php?id={$row['id']}' class='btn btn-danger btn-sm' title='Xóa' onclick=\"return confirm('Bạn có chắc chắn muốn xóa dữ liệu này không?');\">
                                            <i class='fas fa-trash'></i>
                                        </a>
                                    </td>";
                            echo "</tr>";
                          }
                      } else {
                        echo "<tr><td colspan='7' class='text-center'>Chưa có dữ liệu hội viên đăng ký gói.</td></tr>";
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

<?php include 'layout/footer.php'; ?>
