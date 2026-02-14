<?php 
$page_title = "Quản lý Orders";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$sql = "SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method, m.full_name 
        FROM orders o 
        LEFT JOIN members m ON o.member_id = m.id 
        ORDER BY o.id DESC";

$result = $conn->query($sql);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý Orders</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Orders</li>
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
                <h3 class="card-title">Danh sách Orders</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Tổng tiền</th>
                    <th>Phương thức</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $orderCode = "#ORD" . str_pad($row['id'], 3, "0", STR_PAD_LEFT);
                            $customerName = $row['full_name'] ?? 'Khách vãng lai';
                            $orderDate = date('d/m/Y H:i', strtotime($row['order_date']));
                            $formattedPrice = number_format($row['total_amount'], 0, ',', '.') . 'đ';                
                            $paymentMethod = ($row['payment_method'] == 'online') 
                                ? '<span class="text-primary"><i class="fas fa-credit-card"></i> Online</span>' 
                                : '<span class="text-success"><i class="fas fa-money-bill-wave"></i> Tiền mặt</span>';

                            if ($row['status'] == 'delivered') {
                                $statusBadge = '<span class="badge badge-success">Đã giao</span>';
                            } elseif ($row['status'] == 'confirmed') {
                                $statusBadge = '<span class="badge badge-info">Đã xác nhận</span>';
                            } elseif ($row['status'] == 'pending') {
                                $statusBadge = '<span class="badge badge-warning">Chờ xử lý</span>';
                            } else {
                                $statusBadge = '<span class="badge badge-danger">Đã hủy</span>';
                            }

                            echo "<tr>";
                            echo "  <td class='font-weight-bold'>{$orderCode}</td>";
                            echo "  <td>{$customerName}</td>";
                            echo "  <td>{$orderDate}</td>";
                            echo "  <td class='text-danger font-weight-bold'>{$formattedPrice}</td>";
                            echo "  <td>{$paymentMethod}</td>";
                            echo "  <td>{$statusBadge}</td>";
                            echo "  <td>
                                        <a href='order-items.php?id={$row['id']}' class='btn btn-info btn-sm' title='Xem chi tiết'>
                                            <i class='fas fa-eye'></i>
                                        </a>
                                        <a href='order_edit.php?id={$row['id']}' class='btn btn-warning btn-sm' title='Cập nhật trạng thái'>
                                            <i class='fas fa-edit'></i>
                                        </a>
                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' class='text-center'>Chưa có đơn hàng nào trong hệ thống.</td></tr>";
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