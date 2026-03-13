<?php 
$page_title = "Chi tiết đơn hàng";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    echo "<script>alert('Không tìm thấy mã đơn hàng!'); window.location.href='orders.php';</script>";
    exit;
}
$sql_order = "SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method, m.full_name 
              FROM orders o 
              LEFT JOIN members m ON o.member_id = m.id 
              WHERE o.id = $order_id";
$result_order = $conn->query($sql_order);

if ($result_order->num_rows == 0) {
    echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href='orders.php';</script>";
    exit;
}

$order = $result_order->fetch_assoc();
$sql_items = "SELECT oi.id AS item_id, oi.item_type, oi.item_name, oi.quantity, oi.price, oi.discount, oi.subtotal
              FROM order_items oi
              WHERE oi.order_id = $order_id";
$result_items = $conn->query($sql_items);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Chi tiết đơn hàng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Order Items</li>
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
                <h3 class="card-title">Danh sách Order Items</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Mã đơn hàng</th>
                    <th>Loại</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Giá</th>
                    <th>Giảm giá</th>
                    <th>Thành tiền</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result_items && $result_items->num_rows > 0) {
                        $orderCode = "#ORD" . str_pad($order_id, 3, "0", STR_PAD_LEFT);

                        while($item = $result_items->fetch_assoc()) {
                            $item_type = $item['item_type'];
                            $item_type_label = '';
                            $badge_class = '';
                            
                            switch($item_type) {
                                case 'product':
                                    $item_type_label = 'Sản phẩm';
                                    $badge_class = 'badge-success';
                                    break;
                                case 'package':
                                    $item_type_label = 'Gói tập';
                                    $badge_class = 'badge-primary';
                                    break;
                                case 'service':
                                    $item_type_label = 'Dịch vụ';
                                    $badge_class = 'badge-warning';
                                    break;
                            }
                            
                            $price = $item['price'];
                            $quantity = $item['quantity'];
                            $discount = $item['discount'];
                            $subtotal = $item['subtotal'];
                            
                            echo "<tr>";
                            echo "  <td>{$item['item_id']}</td>";                                    
                            echo "  <td><span class='badge badge-info'>{$orderCode}</span></td>";    
                            echo "  <td><span class='badge {$badge_class}'>{$item_type_label}</span></td>"; 
                            echo "  <td class='text-primary font-weight-bold'>" . $item['item_name'] . "</td>"; 
                            echo "  <td>{$quantity}</td>";                                           
                            echo "  <td>" . number_format($price, 0, ',', '.') . "đ</td>";         
                            echo "  <td>" . number_format($discount, 0, ',', '.') . "đ</td>";       
                            echo "  <td class='text-danger font-weight-bold'>" . number_format($subtotal, 0, ',', '.') . "đ</td>"; 
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='8' class='text-center'>Không có chi tiết mặt hàng cho đơn này.</td></tr>";
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
