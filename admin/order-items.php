<?php
session_start();

$page_title = "Chi tiết đơn hàng";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_SALES');

include 'layout/header.php'; 
include 'layout/sidebar.php';

require_once '../config/db.php';

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($order_id === 0) {
    echo "<script>alert('Không tìm thấy mã đơn hàng!'); window.location.href='orders.php';</script>";
    exit;
}

$sql_order = "SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method, m.full_name, mt.name as tier_name,
              u_staff.full_name as handler_name 
              FROM orders o 
              LEFT JOIN members m ON o.member_id = m.id 
              LEFT JOIN member_tiers mt ON m.tier_id = mt.id
              LEFT JOIN users u_staff ON o.handled_by = u_staff.id
              WHERE o.id = $order_id";
$result_order = $conn->query($sql_order);

if ($result_order->num_rows == 0) {
    echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href='orders.php';</script>";
    exit;
}

$order = $result_order->fetch_assoc();
$sql_items = "SELECT oi.id AS item_id, oi.item_type, oi.item_name, oi.quantity, oi.price
              FROM order_items oi
              WHERE oi.order_id = $order_id";
$result_items = $conn->query($sql_items);

$order_items = [];
$total_items_cost = 0;
$has_physical_products = false;

if ($result_items && $result_items->num_rows > 0) {
  while ($row = $result_items->fetch_assoc()) {
    $row['pure_subtotal'] = (float)$row['price'] * (int)$row['quantity'];
    $order_items[] = $row;
    $total_items_cost += $row['pure_subtotal'];
    if (($row['item_type'] ?? '') === 'product') {
      $has_physical_products = true;
    }
  }
}

$stmt_promo = $conn->prepare("
    SELECT pu.applied_amount AS promo_discount, 
           COALESCE(tp.name, 'Mã ưu đãi') AS promotion_name 
    FROM promotion_usage pu 
    LEFT JOIN tier_promotions tp ON tp.id = pu.promotion_id 
    WHERE pu.order_id = ? 
    ORDER BY pu.id DESC LIMIT 1
");
$stmt_promo->bind_param("i", $order_id);
$stmt_promo->execute();
$promo_row = $stmt_promo->get_result()->fetch_assoc();
$stmt_promo->close();

$promotion_discount_amount = (float) ($promo_row['promo_discount'] ?? 0);
$promotion_name = $promo_row['promotion_name'] ?? 'Mã ưu đãi';
$shipping_fee = $has_physical_products ? 30000 : 0;
$final_total = (float)$order['total_amount'];

$base_discount_amount = $total_items_cost - $promotion_discount_amount + $shipping_fee - $final_total;
$base_discount_amount = max(0, round($base_discount_amount, 0));
$base_discount_percent = $total_items_cost > 0 ? ($base_discount_amount / $total_items_cost) * 100 : 0;

$subtotal_before_discount = $total_items_cost;
?>

<style>
    .content-wrapper { background-color: #f4f6f9; }
    .card-modern { border: none; box-shadow: 0 0 15px rgba(0,0,0,0.05); border-radius: 10px; }
    
    .info-card {
        background: #fff;
        border: 1px solid #edf1f5;
        border-radius: 8px;
        padding: 20px;
        height: 100%;
        border-left: 4px solid #007bff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.02);
    }
    .info-card.customer { border-left-color: #17a2b8; }
    .info-card.order { border-left-color: #ffc107; }
    .info-card.payment { border-left-color: #28a745; }
    
    .info-card h6 { color: #8fa0b2; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; letter-spacing: 0.5px; margin-bottom: 15px; }
    .info-card p { color: #3e4b5b; font-size: 0.95rem; margin-bottom: 6px; }
    .info-card p strong { color: #1e293b; font-weight: 600; display: inline-block; min-width: 100px; }
    
    .table-custom th { background-color: #f8fafc; color: #475569; font-weight: 600; border-top: none; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 0.85rem; }
    .table-custom td { vertical-align: middle; color: #334155; border-color: #f1f5f9; padding: 12px 15px; }
    .table-custom tbody tr:hover { background-color: #f8fafc; }
    
    .summary-box { background: #f8fafc; border-radius: 8px; padding: 20px; border: 1px solid #e2e8f0; }
    .summary-table th { text-align: right; width: 60%; color: #64748b; font-weight: 500; padding: 8px 15px; border: none; }
    .summary-table td { text-align: right; font-weight: 600; color: #334155; padding: 8px 15px; border: none; }
    .summary-table .total-row th { color: #0f172a; font-weight: 700; font-size: 1.1rem; border-top: 1px dashed #cbd5e1; padding-top: 15px; }
    .summary-table .total-row td { color: #ef4444; font-weight: 700; font-size: 1.3rem; border-top: 1px dashed #cbd5e1; padding-top: 15px; }
    
    .badge-pill { padding: 0.4em 0.8em; font-weight: 500; }
</style>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-3 mt-2">
          <div class="col-sm-6">
            <h1 class="m-0 font-weight-bold text-dark">
                Chi tiết đơn hàng 
                <span class="text-primary ml-2">#ORD<?php echo str_pad($order_id, 3, "0", STR_PAD_LEFT); ?></span>
            </h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right bg-transparent p-0">
              <li class="breadcrumb-item"><a href="index.php" class="text-muted">Home</a></li>
              <li class="breadcrumb-item"><a href="orders.php" class="text-muted">Đơn hàng</a></li>
              <li class="breadcrumb-item active text-dark font-weight-bold">Chi tiết</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <section class="content pb-4">
      <div class="container-fluid">
        
        <div class="row mb-4">
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="info-card customer">
                    <h6><i class="fas fa-user-circle mr-1"></i> Khách hàng</h6>
                    <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['full_name'] ?? 'Khách vãng lai'); ?></p>
                </div>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="info-card order">
                    <h6><i class="fas fa-shopping-bag mr-1"></i> Đơn hàng</h6>
                    <p><strong>Ngày đặt:</strong> <?php echo date('H:i - d/m/Y', strtotime($order['order_date'])); ?></p>
                    <p><strong>Người duyệt:</strong> <?php echo !empty($order['handler_name']) ? "<span class='text-primary'><i class='fas fa-user-check'></i> " . htmlspecialchars($order['handler_name']) . "</span>" : 'Chưa có người xử lý'; ?></p>
                    <p><strong>Trạng thái:</strong> 
                        <?php 
                            $status_badges = [
                                'pending' => '<span class="badge badge-warning badge-pill">Chờ xử lý</span>',
                                'confirmed' => '<span class="badge badge-primary badge-pill">Đã xác nhận</span>',
                                'delivered' => '<span class="badge badge-success badge-pill">Đã giao / Hoàn thành</span>',
                                'cancelled' => '<span class="badge badge-danger badge-pill">Đã hủy</span>'
                            ];
                            echo $status_badges[$order['status']] ?? '<span class="badge badge-secondary badge-pill">Khác</span>';
                        ?>
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-card payment">
                    <h6><i class="fas fa-wallet mr-1"></i> Thanh toán</h6>
                    <p><strong>Phương thức:</strong> 
                        <?php echo ($order['payment_method'] == 'cash') ? 'Tiền mặt (COD)' : 'Chuyển khoản (Online)'; ?>
                    </p>
                </div>
            </div>
        </div>

        <div class="card card-modern">
          <div class="card-body p-0">
            
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                  <thead>
                  <tr>
                    <th width="5%" class="text-center">STT</th>
                    <th width="15%" class="text-center">Loại</th>
                    <th width="45%">Sản phẩm / Dịch vụ</th>
                    <th width="10%" class="text-center">Số lượng</th>
                    <th width="10%" class="text-right">Đơn giá</th>
                    <th width="15%" class="text-right">Thành tiền</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if (!empty($order_items)) {
                        $stt = 1;
                        foreach ($order_items as $item) {
                            $item_type = $item['item_type'];
                            $badge_class = '';
                            $type_label = '';
                            
                            switch($item_type) {
                                case 'product': $type_label = 'Sản phẩm'; $badge_class = 'badge-success'; break;
                                case 'package': $type_label = 'Gói tập'; $badge_class = 'badge-primary'; break;
                                case 'service': $type_label = 'Dịch vụ'; $badge_class = 'badge-warning'; break;
                                case 'class': $type_label = 'Lớp học'; $badge_class = 'badge-info'; break;
                                default: $type_label = 'Khác'; $badge_class = 'badge-secondary'; break;
                            }
                            
                            $price = $item['price'];
                            $quantity = $item['quantity'];
                            $subtotal = $item['pure_subtotal']; 
                            
                            echo "<tr>";
                            echo "  <td class='text-center text-muted'>{$stt}</td>";                                    
                            echo "  <td class='text-center'><span class='badge {$badge_class}'>{$type_label}</span></td>"; 
                            echo "  <td class='font-weight-bold text-dark'>" . htmlspecialchars($item['item_name']) . "</td>"; 
                            echo "  <td class='text-center'>{$quantity}</td>";                                          
                            echo "  <td class='text-right'>" . number_format($price, 0, ',', '.') . " đ</td>";         
                            echo "  <td class='text-right font-weight-bold text-dark'>" . number_format($subtotal, 0, ',', '.') . " đ</td>"; 
                            echo "</tr>";
                            $stt++;
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center py-5 text-muted'><i class='fas fa-box-open fa-2x mb-3 d-block opacity-50'></i>Không có chi tiết mặt hàng cho đơn này.</td></tr>";
                    }
                  ?>
                  </tbody>
                </table>
            </div>

            <div class="row mt-4 px-4 pb-4">
                <div class="col-lg-6">
                    </div>
                <div class="col-lg-6">
                    <div class="summary-box">
                        <table class="table summary-table table-sm mb-0">
                            <tr>
                                <th>Tạm tính:</th>
                                <td><?php echo number_format($subtotal_before_discount, 0, ',', '.'); ?> đ</td>
                            </tr>
                            
                            <?php if($base_discount_amount > 0): ?>
                            <tr>
                                <th>Giảm giá hạng <?php echo htmlspecialchars($order['tier_name'] ?? 'Thành viên'); ?> (<?php echo number_format($base_discount_percent, 0); ?>%):</th>
                                <td class="text-success">- <?php echo number_format($base_discount_amount, 0, ',', '.'); ?> đ</td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if($promotion_discount_amount > 0): ?>
                            <tr>
                                <th>Khuyến mãi (<?php echo htmlspecialchars($promotion_name); ?>):</th>
                                <td class="text-success">- <?php echo number_format($promotion_discount_amount, 0, ',', '.'); ?> đ</td>
                            </tr>
                            <?php endif; ?>
                            
                            <tr>
                                <th>Phí giao hàng:</th>
                                <td><?php echo number_format($shipping_fee, 0, ',', '.'); ?> đ</td>
                            </tr>
                            <tr class="total-row">
                                <th>Khách phải trả:</th>
                                <td><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> đ</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

          </div>
        </div>

        <div class="row mt-3">
            <div class="col-12 text-right">
                <a href="orders.php" class="btn btn-light border shadow-sm mr-2 font-weight-bold text-secondary">
                    <i class="fas fa-arrow-left mr-1"></i> Trở về
                </a>
                <a href="order_edit.php?id=<?php echo $order_id; ?>" class="btn btn-primary shadow-sm font-weight-bold px-4">
                    Cập nhật trạng thái <i class="fas fa-chevron-right ml-1"></i>
                </a>
            </div>
        </div>

      </div>
    </section>
  </div>

<?php include 'layout/footer.php'; ?>