<?php 
$page_title = "Quản lý Orders";
include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

// Lấy giá trị filter từ form
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filter_to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_city = isset($_GET['city']) ? $_GET['city'] : '';
$filter_district = isset($_GET['district']) ? $_GET['district'] : '';

// Xây dựng câu query với filter
$sql = "SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method, m.full_name, 
        a.city, a.district 
        FROM orders o 
        LEFT JOIN members m ON o.member_id = m.id 
        LEFT JOIN addresses a ON o.address_id = a.id 
        WHERE 1=1";

// Thêm điều kiện filter
if (!empty($filter_status)) {
    $sql .= " AND o.status = '" . $conn->real_escape_string($filter_status) . "'";
}

if (!empty($filter_from_date)) {
    $sql .= " AND DATE(o.order_date) >= '" . $conn->real_escape_string($filter_from_date) . "'";
}

if (!empty($filter_to_date)) {
    $sql .= " AND DATE(o.order_date) <= '" . $conn->real_escape_string($filter_to_date) . "'";
}

if (!empty($filter_city)) {
    $sql .= " AND a.city = '" . $conn->real_escape_string($filter_city) . "'";
}

if (!empty($filter_district)) {
    $sql .= " AND a.district = '" . $conn->real_escape_string($filter_district) . "'";
}

$sql .= " ORDER BY o.id DESC";

$result = $conn->query($sql);

// Lấy danh sách thành phố và quận/huyện để hiển thị trong filter
$cities_sql = "SELECT DISTINCT city FROM addresses WHERE city IS NOT NULL AND city != '' ORDER BY city";
$cities_result = $conn->query($cities_sql);

$districts_sql = "SELECT DISTINCT district FROM addresses WHERE district IS NOT NULL AND district != '' ORDER BY district";
$districts_result = $conn->query($districts_sql);
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
        <!-- Filter Section -->
        <div class="row mb-3">
          <div class="col-12">
            <div class="card card-primary collapsed-card">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-filter"></i> Lọc đơn hàng</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-plus"></i>
                  </button>
                </div>
              </div>
              <div class="card-body">
                <form method="GET" action="orders.php" id="filterForm">
                  <div class="row">
                    <!-- Filter by Status -->
                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Trạng thái đơn hàng</label>
                        <select name="status" class="form-control">
                          <option value="">-- Tất cả trạng thái --</option>
                          <option value="pending" <?php echo ($filter_status == 'pending') ? 'selected' : ''; ?>>🟡 Chờ xử lý</option>
                          <option value="confirmed" <?php echo ($filter_status == 'confirmed') ? 'selected' : ''; ?>>🔵 Đã xác nhận</option>
                          <option value="delivered" <?php echo ($filter_status == 'delivered') ? 'selected' : ''; ?>>🟢 Đã giao</option>
                          <option value="cancelled" <?php echo ($filter_status == 'cancelled') ? 'selected' : ''; ?>>🔴 Đã hủy</option>
                        </select>
                      </div>
                    </div>

                    <!-- Filter by Date Range -->
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Từ ngày</label>
                        <input type="date" name="from_date" class="form-control" value="<?php echo htmlspecialchars($filter_from_date); ?>">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Đến ngày</label>
                        <input type="date" name="to_date" class="form-control" value="<?php echo htmlspecialchars($filter_to_date); ?>">
                      </div>
                    </div>

                    <!-- Filter by City -->
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Thành phố</label>
                        <select name="city" class="form-control">
                          <option value="">-- Tất cả --</option>
                          <?php 
                          if ($cities_result && $cities_result->num_rows > 0) {
                              while($city_row = $cities_result->fetch_assoc()) {
                                  $selected = ($filter_city == $city_row['city']) ? 'selected' : '';
                                  echo "<option value='" . htmlspecialchars($city_row['city']) . "' $selected>" . htmlspecialchars($city_row['city']) . "</option>";
                              }
                          }
                          ?>
                        </select>
                      </div>
                    </div>

                    <!-- Filter by District -->
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Quận/Huyện</label>
                        <select name="district" class="form-control">
                          <option value="">-- Tất cả --</option>
                          <?php 
                          if ($districts_result && $districts_result->num_rows > 0) {
                              while($district_row = $districts_result->fetch_assoc()) {
                                  $selected = ($filter_district == $district_row['district']) ? 'selected' : '';
                                  echo "<option value='" . htmlspecialchars($district_row['district']) . "' $selected>" . htmlspecialchars($district_row['district']) . "</option>";
                              }
                          }
                          ?>
                        </select>
                      </div>
                    </div>

                    <!-- Filter Buttons -->
                    <div class="col-md-1">
                      <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                          <i class="fas fa-search"></i> Lọc
                        </button>
                      </div>
                    </div>
                  </div>

                  <!-- Reset Filter Button -->
                  <div class="row">
                    <div class="col-md-12">
                      <a href="orders.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Xóa bộ lọc
                      </a>
                      <?php 
                      // Hiển thị số lượng kết quả
                      $total_results = $result ? $result->num_rows : 0;
                      echo "<span class='ml-3 text-muted'>Tìm thấy: <strong>$total_results</strong> đơn hàng</span>";
                      ?>
                    </div>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

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
                    <th>Địa điểm giao</th>
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
                            
                            // Hiển thị địa điểm giao hàng
                            $location = '';
                            if (!empty($row['district']) && !empty($row['city'])) {
                                $location = htmlspecialchars($row['district']) . ', ' . htmlspecialchars($row['city']);
                            } elseif (!empty($row['city'])) {
                                $location = htmlspecialchars($row['city']);
                            } elseif (!empty($row['district'])) {
                                $location = htmlspecialchars($row['district']);
                            } else {
                                $location = '<span class="text-muted">Chưa có</span>';
                            }
                            
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
                            echo "  <td>{$location}</td>";
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
                        echo "<tr><td colspan='8' class='text-center'>Chưa có đơn hàng nào trong hệ thống.</td></tr>";
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