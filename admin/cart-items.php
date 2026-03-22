<?php 
$page_title = "Chi tiết giỏ hàng";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$cart_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($cart_id === 0) {
    echo "<script>alert('Không tìm thấy mã giỏ hàng!'); window.location.href='carts.php';</script>";
    exit;
}

// Lấy thông tin giỏ hàng
$sql_cart = "SELECT c.id, c.created_at, c.status, m.full_name 
             FROM carts c 
             LEFT JOIN members m ON c.member_id = m.id 
             WHERE c.id = $cart_id";
$result_cart = $conn->query($sql_cart);

if ($result_cart->num_rows == 0) {
    echo "<script>alert('Giỏ hàng không tồn tại!'); window.location.href='carts.php';</script>";
    exit;
}

$cart = $result_cart->fetch_assoc();

// Lấy các items trong giỏ
$sql_items = "SELECT 
                ci.id AS item_id,
                ci.item_type,
                ci.item_id AS ref_id,
                ci.quantity,
                CASE ci.item_type
                    WHEN 'product' THEN p.name
                    WHEN 'package' THEN pkg.package_name
                    WHEN 'service' THEN s.name
                END as item_name,
                CASE ci.item_type
                    WHEN 'product' THEN p.selling_price
                    WHEN 'package' THEN pkg.price
                    WHEN 'service' THEN s.price
                END as price
              FROM cart_items ci
              LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
              LEFT JOIN membership_packages pkg ON ci.item_type = 'package' AND ci.item_id = pkg.id
              LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
              WHERE ci.cart_id = $cart_id";
$result_items = $conn->query($sql_items);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Chi tiết giỏ hàng #<?php echo $cart_id; ?></h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item"><a href="carts.php">Carts</a></li>
              <li class="breadcrumb-item active">Cart Items</li>
            </ol>
          </div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
        <!-- Thông tin giỏ hàng -->
        <div class="row">
          <div class="col-12">
            <div class="card card-info">
              <div class="card-header">
                <h3 class="card-title">Thông tin giỏ hàng</h3>
              </div>
              <div class="card-body">
                <div class="row">
                  <div class="col-md-6">
                    <p><strong>Khách hàng:</strong> <?php echo $cart['full_name'] ?? 'Khách vãng lai'; ?></p>
                    <p><strong>Ngày tạo:</strong> <?php echo date('d/m/Y H:i', strtotime($cart['created_at'])); ?></p>
                  </div>
                  <div class="col-md-6">
                    <p><strong>Trạng thái:</strong> 
                      <?php 
                        if ($cart['status'] == 'active') {
                            echo '<span class="badge badge-success">Đang hoạt động</span>';
                        } else {
                            echo '<span class="badge badge-secondary">Đã thanh toán</span>';
                        }
                      ?>
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Danh sách items -->
        <div class="row">
          <div class="col-12">
            <div class="card">
              <div class="card-header">
                <h3 class="card-title">Danh sách sản phẩm trong giỏ</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Loại</th>
                    <th>Tên sản phẩm</th>
                    <th>Số lượng</th>
                    <th>Đơn giá</th>
                    <th>Thành tiền</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    $total_amount = 0;
                    if ($result_items && $result_items->num_rows > 0) {
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
                            
                            $price = $item['price'] ?? 0;
                            $quantity = $item['quantity'];
                            $subtotal = $price * $quantity;
                            $total_amount += $subtotal;
                            
                            echo "<tr>";
                            echo "  <td>{$item['item_id']}</td>";
                            echo "  <td><span class='badge {$badge_class}'>{$item_type_label}</span></td>";
                            echo "  <td class='text-primary font-weight-bold'>" . ($item['item_name'] ?? '<em>Đã bị xóa</em>') . "</td>";
                            echo "  <td>{$quantity}</td>";
                            echo "  <td>" . number_format($price, 0, ',', '.') . "đ</td>";
                            echo "  <td class='text-danger font-weight-bold'>" . number_format($subtotal, 0, ',', '.') . "đ</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Giỏ hàng trống.</td></tr>";
                    }
                  ?>
                  </tbody>
                  <?php if ($result_items && $result_items->num_rows > 0): ?>
                  <tfoot>
                    <tr>
                      <th colspan="5" class="text-right">Tổng cộng:</th>
                      <th class="text-danger font-weight-bold"><?php echo number_format($total_amount, 0, ',', '.'); ?>đ</th>
                    </tr>
                  </tfoot>
                  <?php endif; ?>
                </table>
              </div>
              <div class="card-footer">
                <a href="carts.php" class="btn btn-secondary">
                  <i class="fas fa-arrow-left"></i> Quay lại
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

<?php include 'layout/footer.php'; ?>
