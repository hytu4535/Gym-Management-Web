<?php 
$page_title = "Quản lý giỏ hàng";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$sql = "SELECT c.id, c.created_at, c.status, m.full_name, 
               COUNT(ci.id) AS total_items,
               SUM(
                   ci.quantity * 
                   CASE ci.item_type
                       WHEN 'product' THEN p.selling_price
                       WHEN 'package' THEN pkg.price
                       WHEN 'service' THEN s.price
                       ELSE 0
                   END
               ) AS total_price
        FROM carts c
        LEFT JOIN members m ON c.member_id = m.id
        LEFT JOIN cart_items ci ON c.id = ci.cart_id
        LEFT JOIN products p ON ci.item_type = 'product' AND ci.item_id = p.id
        LEFT JOIN membership_packages pkg ON ci.item_type = 'package' AND ci.item_id = pkg.id
        LEFT JOIN services s ON ci.item_type = 'service' AND ci.item_id = s.id
        GROUP BY c.id, c.created_at, c.status, m.full_name
        ORDER BY c.id DESC";
$result = $conn->query($sql);
?>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">Quản lý giỏ hàng</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="index.php">Home</a></li>
              <li class="breadcrumb-item active">Carts</li>
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
                <h3 class="card-title">Danh sách giỏ hàng</h3>
              </div>
              <div class="card-body">
                <table class="table table-bordered table-striped data-table">
                  <thead>
                  <tr>
                    <th>ID</th>
                    <th>Khách hàng</th>
                    <th>Số sản phẩm</th>
                    <th>Tổng tiền</th>
                    <th>Ngày tạo</th>
                    <th>Hành động</th>
                  </tr>
                  </thead>
                  <tbody>
                  <?php 
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {                       
                            $customerName = $row['full_name'] ?? 'Khách vãng lai';
                            $totalItems = $row['total_items'];
                            $totalPrice = number_format($row['total_price'] ?? 0, 0, ',', '.') . 'đ';
                            $createdDate = date('Y-m-d', strtotime($row['created_at']));

                            echo "<tr>";
                            echo "  <td>{$row['id']}</td>";
                            echo "  <td>{$customerName}</td>";
                            echo "  <td>{$totalItems}</td>";
                            echo "  <td class='text-danger font-weight-bold'>{$totalPrice}</td>";
                            echo "  <td>{$createdDate}</td>";
                            echo "  <td>
                                      <a href='cart-items.php?id={$row['id']}' class='btn btn-info btn-sm' title='Xem chi tiết'>
                                        <i class='fas fa-eye'></i>
                                      </a>
                                      <a href='process/cart_delete.php?id={$row['id']}' class='btn btn-danger btn-sm' title='Xóa giỏ hàng' onclick=\"return confirm('Bạn có chắc chắn muốn xóa giỏ hàng này?');\">
                                        <i class='fas fa-trash'></i>
                                      </a>
                                    </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Hiện chưa có giỏ hàng nào trong hệ thống.</td></tr>";
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
