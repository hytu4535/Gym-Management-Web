<?php
session_start(); // luôn khởi tạo session

$page_title = "Chi tiết đơn hàng";

// kiểm tra đăng nhập
include '../includes/auth.php';

// kết nối DB và kiểm tra quyền
include '../includes/database.php';
include '../includes/auth_permission.php';

// chỉ cho phép user có quyền MANAGE_PRODUCTS_SALES
checkPermission('MANAGE_SALES');

// layout chung
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
$sql_items = "SELECT oi.id AS item_id, oi.item_type, oi.item_name, oi.quantity, oi.price, COALESCE(oi.discount, 0) AS discount, oi.subtotal
              FROM order_items oi
              WHERE oi.order_id = $order_id";
$result_items = $conn->query($sql_items);

$order_items = [];
$total_items_cost = 0;
$base_discount_amount = 0;
$has_physical_products = false;

if ($result_items && $result_items->num_rows > 0) {
  while ($row = $result_items->fetch_assoc()) {
    $row['subtotal'] = (float) ($row['subtotal'] ?? ($row['price'] * $row['quantity']));
    $order_items[] = $row;
    $total_items_cost += $row['subtotal'];
    $base_discount_amount += (float) ($row['discount'] ?? 0);
    if (($row['item_type'] ?? '') === 'product') {
      $has_physical_products = true;
    }
  }
}

$stmt_promo = $conn->prepare("SELECT COALESCE(SUM(applied_amount), 0) AS promo_discount FROM promotion_usage WHERE order_id = ?");
$stmt_promo->bind_param("i", $order_id);
$stmt_promo->execute();
$promo_row = $stmt_promo->get_result()->fetch_assoc();
$stmt_promo->close();

$promotion_discount_amount = (float) ($promo_row['promo_discount'] ?? 0);
$total_discount_amount = $base_discount_amount + $promotion_discount_amount;
$shipping_fee = $has_physical_products ? 30000 : 0;
$subtotal_before_discount = $total_items_cost + $total_discount_amount;
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
                    <th>Đơn giá</th>
                    <th>Giảm giá</th>
                    <th>Thành tiền</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if (!empty($order_items)) {
                        $orderCode = "#ORD" . str_pad($order_id, 3, "0", STR_PAD_LEFT);

                      foreach ($order_items as $item) {
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
                            $subtotal = $item['subtotal'];
                            $base_item_discount = (float) ($item['discount'] ?? 0);
                            $promotion_share = 0;
                            if ($promotion_discount_amount > 0 && $total_items_cost > 0) {
                              $promotion_share = round(($subtotal / $total_items_cost) * $promotion_discount_amount, 0);
                            }
                            $discount = $base_item_discount + $promotion_share;
                            
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
                  <tfoot>
                    <tr>
                      <th colspan="6" class="text-right">Tạm tính:</th>
                      <th colspan="2" class="text-right"><?php echo number_format($subtotal_before_discount, 0, ',', '.'); ?>đ</th>
                    </tr>
                    <tr>
                      <th colspan="6" class="text-right">Tổng số tiền đã giảm:</th>
                      <th colspan="2" class="text-right text-success">-<?php echo number_format($total_discount_amount, 0, ',', '.'); ?>đ</th>
                    </tr>
                    <tr>
                      <th colspan="6" class="text-right">Phí vận chuyển:</th>
                      <th colspan="2" class="text-right"><?php echo number_format($shipping_fee, 0, ',', '.'); ?>đ</th>
                    </tr>
                    <tr>
                      <th colspan="6" class="text-right">Tổng cộng:</th>
                      <th colspan="2" class="text-right text-danger"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</th>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

<?php include 'layout/footer.php'; ?>
