<?php
session_start();

$page_title = "Quản lý Orders";

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_SALES');

include 'layout/header.php'; 
include 'layout/sidebar.php';
require_once '../config/db.php';

$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$filter_to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$filter_city = isset($_GET['city']) ? $_GET['city'] : '';
$filter_district = isset($_GET['district']) ? $_GET['district'] : '';
$filter_member_id = isset($_GET['member_id']) ? (int) $_GET['member_id'] : 0;
$filter_amount_min = isset($_GET['amount_min']) ? $_GET['amount_min'] : '';
$filter_amount_max = isset($_GET['amount_max']) ? $_GET['amount_max'] : '';
$filter_payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';

$has_order_note = false;
$note_column_check = $conn->query("SHOW COLUMNS FROM orders LIKE 'note'");
if ($note_column_check && $note_column_check->num_rows > 0) {
  $has_order_note = true;
}

$order_note_select = $has_order_note ? 'o.note' : 'NULL AS note';

$sql = "SELECT o.id, o.total_amount, o.order_date, o.status, o.payment_method, o.transfer_code, o.proof_img, $order_note_select, m.full_name, 
        a.city, a.district,
        u_staff.full_name AS handler_name 
        FROM orders o 
        LEFT JOIN members m ON o.member_id = m.id 
        LEFT JOIN addresses a ON o.address_id = a.id 
        LEFT JOIN users u_staff ON o.handled_by = u_staff.id 
        WHERE 1=1";

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
if (!empty($filter_member_id)) {
  $sql .= " AND o.member_id = " . (int) $filter_member_id;
}
if ($filter_amount_min !== '' && is_numeric($filter_amount_min)) {
  $sql .= " AND o.total_amount >= " . (float) $filter_amount_min;
}
if ($filter_amount_max !== '' && is_numeric($filter_amount_max)) {
  $sql .= " AND o.total_amount <= " . (float) $filter_amount_max;
}
if (!empty($filter_payment_method)) {
  $sql .= " AND o.payment_method = '" . $conn->real_escape_string($filter_payment_method) . "'";
}

$sql .= " ORDER BY o.id DESC";

$result = $conn->query($sql);

$cities_sql = "SELECT DISTINCT city FROM addresses WHERE city IS NOT NULL AND city != '' ORDER BY city";
$cities_result = $conn->query($cities_sql);

$districts_sql = "SELECT DISTINCT district FROM addresses WHERE district IS NOT NULL AND district != '' ORDER BY district";
$districts_result = $conn->query($districts_sql);

$customers_sql = "SELECT DISTINCT m.id, m.full_name
                  FROM orders o
                  INNER JOIN members m ON o.member_id = m.id
                  ORDER BY m.full_name";
$customers_result = $conn->query($customers_sql);
?>

<style>
  #ordersTable { table-layout: fixed; width: 100%; }
  #ordersTable th, #ordersTable td { 
    vertical-align: middle; 
    word-wrap: break-word; 
  }
  #ordersTable .badge {
    white-space: normal !important; 
    display: inline-block;
  }
  #ordersTable .order-note-cell { width: 130px; text-align: center; white-space: nowrap; }
</style>

<div class="content-wrapper">
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

    <section class="content">
      <div class="container-fluid">
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

                    <div class="col-md-3">
                      <div class="form-group">
                        <label>Tên khách hàng</label>
                        <select name="member_id" class="form-control">
                          <option value="">-- Tất cả --</option>
                          <?php if ($customers_result && $customers_result->num_rows > 0): ?>
                            <?php while ($customer_row = $customers_result->fetch_assoc()): ?>
                              <option value="<?php echo (int) $customer_row['id']; ?>" <?php echo ($filter_member_id == (int) $customer_row['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($customer_row['full_name']); ?></option>
                            <?php endwhile; ?>
                          <?php endif; ?>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Mức tiền từ</label>
                        <input type="number" name="amount_min" class="form-control" min="0" value="<?php echo htmlspecialchars($filter_amount_min); ?>" placeholder=">=">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Mức tiền đến</label>
                        <input type="number" name="amount_max" class="form-control" min="0" value="<?php echo htmlspecialchars($filter_amount_max); ?>" placeholder="<=">
                      </div>
                    </div>

                    <div class="col-md-2">
                      <div class="form-group">
                        <label>Phương thức</label>
                        <select name="payment_method" class="form-control">
                          <option value="">-- Tất cả --</option>
                          <option value="cash" <?php echo ($filter_payment_method == 'cash') ? 'selected' : ''; ?>>Tiền mặt</option>
                          <option value="online" <?php echo ($filter_payment_method == 'online') ? 'selected' : ''; ?>>Online</option>
                          <option value="bank_transfer" <?php echo ($filter_payment_method == 'bank_transfer') ? 'selected' : ''; ?>>Chuyển khoản</option>
                        </select>
                      </div>
                    </div>

                    <div class="col-md-1">
                      <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-block">
                          <i class="fas fa-search"></i> Lọc
                        </button>
                      </div>
                    </div>
                  </div>

                  <div class="row">
                    <div class="col-md-12">
                      <a href="orders.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-redo"></i> Xóa bộ lọc
                      </a>
                      <?php 
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
                <div class="table-responsive">
                <table class="table table-bordered table-striped data-table" id="ordersTable">
                  <thead>
                  <tr>
                    <th>Mã ĐH</th>
                    <th>Khách hàng</th>
                    <th>Ngày đặt</th>
                    <th>Địa điểm giao</th>
                    <th>Tổng tiền</th>
                    <th>Phương thức</th>
                    <th style="width: 130px;">Ghi chú</th>
                    <th>Nội dung CK</th>
                    <th>Bằng chứng</th>
                    <th>Người phụ trách</th>
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
                            
                            $handlerName = !empty($row['handler_name']) ? "<span class='badge badge-primary'> " . htmlspecialchars($row['handler_name']) . "</span>" : '<span class="text-muted">Chưa có dữ liệu</span>';
                            
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
                                 : (($row['payment_method'] == 'bank_transfer') 
                                ? '<span class="text-info"><i class="fas fa-university"></i> Chuyển khoản</span>' 
                                : '<span class="text-success"><i class="fas fa-money-bill-wave"></i> Tiền mặt</span>');

                            $noteText = trim($row['note'] ?? '');
                            $orderNote = !empty($noteText)
                              ? nl2br(htmlspecialchars($noteText))
                              : '<span class="text-muted">-</span>';

                            $transferCode = $row['transfer_code'] ?? '<span class="text-muted">-</span>';
                            $proofImg = '';
                            if (!empty($row['proof_img'])) {
                                $img_path = '../client/assets/uploads/' . $row['proof_img'];
                                $proofImg = '<a href="' . $img_path . '" target="_blank"><img src="' . $img_path . '" alt="Biên lai" style="width: 50px; height: auto;"></a>';
                            } else {
                                $proofImg = '<span class="text-muted">-</span>';
                            }

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
                            if (!empty($noteText)) {
                              $escapedNoteText = htmlspecialchars($noteText, ENT_QUOTES, 'UTF-8');
                              echo "  <td class='order-note-cell'><button type='button' class='btn btn-outline-info btn-sm' data-toggle='modal' data-target='#orderNoteModal' data-note='{$escapedNoteText}'><i class='fas fa-sticky-note'></i> Xem ghi chú</button></td>";
                            } else {
                              echo "  <td class='order-note-cell'><span class='text-muted'>-</span></td>";
                            }
                            echo "  <td>{$transferCode}</td>";
                            echo "  <td>{$proofImg}</td>";
                            echo "  <td>{$handlerName}</td>";
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
                      echo "<tr><td colspan='12' class='text-center'>Chưa có đơn hàng nào trong hệ thống.</td></tr>";
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

<div class="modal fade" id="orderNoteModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title">Ghi chú đơn hàng</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Đóng">
          <span aria-hidden="true" class="text-white">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="orderNoteContent" style="white-space: pre-wrap; word-break: break-word;"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
      </div>
    </div>
  </div>
</div>

<?php include 'layout/footer.php'; ?>

<script>
$(document).on('click', '[data-target="#orderNoteModal"]', function() {
    var note = $(this).data('note') || '';
    $('#orderNoteContent').html(note ? $('<div>').text(note).html().replace(/\n/g, '<br>') : '<span class="text-muted">Không có ghi chú</span>');
});
</script>