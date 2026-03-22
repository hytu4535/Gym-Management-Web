<?php
session_start();

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';
checkPermission('MANAGE_SALES');

require_once '../config/db.php';

$orderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($orderId <= 0) {
    die('Mã đơn hàng không hợp lệ.');
}

$stmtOrder = $conn->prepare("SELECT o.id, o.order_date, o.total_amount, o.payment_method, o.status,
                                    COALESCE(m.full_name, 'Khách vãng lai') AS customer_name,
                                    CONCAT_WS(', ', a.full_address, a.district, a.city) AS shipping_address
                             FROM orders o
                             LEFT JOIN members m ON o.member_id = m.id
                             LEFT JOIN addresses a ON o.address_id = a.id
                             WHERE o.id = ?");
$stmtOrder->bind_param('i', $orderId);
$stmtOrder->execute();
$orderResult = $stmtOrder->get_result();

if ($orderResult->num_rows === 0) {
    die('Không tìm thấy đơn hàng để lập phiếu xuất.');
}

$order = $orderResult->fetch_assoc();
$stmtOrder->close();

$stmtItems = $conn->prepare("SELECT item_type, item_name, quantity, price,
                                    COALESCE(discount, 0) AS discount,
                                    COALESCE(subtotal, 0) AS subtotal
                             FROM order_items
                             WHERE order_id = ?");
$stmtItems->bind_param('i', $orderId);
$stmtItems->execute();
$itemsResult = $stmtItems->get_result();

$items = [];
$totalQty = 0;
$calculatedAmount = 0;

while ($item = $itemsResult->fetch_assoc()) {
    $lineQty = (int)$item['quantity'];
    $linePrice = (float)$item['price'];
    $lineDiscount = (float)$item['discount'];
    $lineSubtotal = (float)$item['subtotal'];

    if ($lineSubtotal <= 0) {
        $lineSubtotal = ($lineQty * $linePrice) - $lineDiscount;
    }

    $items[] = [
        'item_type' => $item['item_type'],
        'item_name' => $item['item_name'],
        'quantity' => $lineQty,
        'price' => $linePrice,
        'discount' => $lineDiscount,
        'subtotal' => $lineSubtotal
    ];

    $totalQty += $lineQty;
    $calculatedAmount += $lineSubtotal;
}
$stmtItems->close();

$exportCode = 'PX' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);
$orderCode = 'ORD' . str_pad((string)$orderId, 5, '0', STR_PAD_LEFT);

$amountToPrint = ((float)$order['total_amount'] > 0) ? (float)$order['total_amount'] : $calculatedAmount;

if ($order['status'] === 'delivered') {
    $statusLabel = 'Đã giao';
} elseif ($order['status'] === 'confirmed') {
    $statusLabel = 'Đã xác nhận';
} elseif ($order['status'] === 'pending') {
    $statusLabel = 'Chờ xử lý';
} else {
    $statusLabel = 'Đã hủy';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Phiếu xuất <?php echo htmlspecialchars($exportCode); ?></title>
  <style>
    body {
      font-family: Arial, sans-serif;
      color: #222;
      margin: 0;
      padding: 20px;
      background: #f6f7fb;
    }
    .print-wrap {
      max-width: 900px;
      margin: 0 auto;
      background: #fff;
      border: 1px solid #ddd;
      padding: 24px;
    }
    .header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #222;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .title {
      text-align: center;
      margin: 10px 0 16px;
    }
    .title h1 {
      margin: 0;
      font-size: 24px;
      letter-spacing: 1px;
      text-transform: uppercase;
    }
    .sub {
      margin-top: 6px;
      color: #444;
      font-size: 13px;
    }
    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
    }
    .meta td {
      padding: 6px 8px;
      border: 1px solid #e1e1e1;
      font-size: 14px;
    }
    table.items {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px;
    }
    table.items th, table.items td {
      border: 1px solid #333;
      padding: 8px;
      font-size: 14px;
    }
    table.items th {
      background: #f2f2f2;
      text-align: center;
    }
    .right { text-align: right; }
    .center { text-align: center; }
    .summary {
      margin-top: 10px;
      float: right;
      width: 340px;
      border-collapse: collapse;
    }
    .summary td {
      border: 1px solid #333;
      padding: 8px;
      font-size: 14px;
    }
    .sign {
      clear: both;
      margin-top: 60px;
      display: flex;
      justify-content: space-between;
      text-align: center;
    }
    .sign div {
      width: 30%;
      font-size: 14px;
    }
    .sign .space {
      margin-top: 70px;
      font-style: italic;
      color: #666;
      font-size: 13px;
    }
    .actions {
      margin: 16px auto 0;
      max-width: 900px;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    .btn {
      border: none;
      background: #0d6efd;
      color: #fff;
      padding: 10px 14px;
      cursor: pointer;
      border-radius: 4px;
      font-size: 14px;
      text-decoration: none;
      display: inline-block;
    }
    .btn.gray { background: #6c757d; }

    @media print {
      body { background: #fff; padding: 0; }
      .print-wrap { border: none; margin: 0; max-width: none; }
      .actions { display: none; }
      @page { size: A4; margin: 12mm; }
    }
  </style>
</head>
<body>
  <div class="print-wrap">
    <div class="header">
      <div>
        <strong>GYM MANAGEMENT</strong><br>
        Địa chỉ: 273 An Dương Vương, P.3, Q.5, TP.HCM<br>
        Điện thoại: 0900 000 000
      </div>
      <div>
        Mẫu chứng từ: PX-KHO<br>
        Ngày in: <?php echo date('d/m/Y H:i'); ?>
      </div>
    </div>

    <div class="title">
      <h1>Phiếu xuất kho</h1>
      <div class="sub">Mã phiếu: <strong><?php echo htmlspecialchars($exportCode); ?></strong> | Đơn tham chiếu: <strong><?php echo htmlspecialchars($orderCode); ?></strong></div>
    </div>

    <table class="meta">
      <tr>
        <td><strong>Khách hàng</strong>: <?php echo htmlspecialchars($order['customer_name']); ?></td>
        <td><strong>Ngày xuất</strong>: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></td>
      </tr>
      <tr>
        <td><strong>Địa chỉ giao</strong>: <?php echo htmlspecialchars($order['shipping_address'] ?: 'Không có'); ?></td>
        <td><strong>Trạng thái đơn</strong>: <?php echo htmlspecialchars($statusLabel); ?></td>
      </tr>
      <tr>
        <td><strong>Phương thức thanh toán</strong>: <?php echo ($order['payment_method'] === 'online') ? 'Thanh toán online' : 'Tiền mặt'; ?></td>
        <td><strong>Tổng số lượng</strong>: <?php echo $totalQty; ?></td>
      </tr>
    </table>

    <table class="items">
      <thead>
        <tr>
          <th style="width: 50px;">STT</th>
          <th>Tên hàng hóa/dịch vụ</th>
          <th style="width: 130px;">Loại</th>
          <th style="width: 90px;">SL</th>
          <th style="width: 130px;">Đơn giá</th>
          <th style="width: 120px;">Giảm giá</th>
          <th style="width: 140px;">Thành tiền</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($items)): ?>
          <?php foreach ($items as $index => $item): ?>
            <?php
              $itemTypeLabel = 'Khác';
              if ($item['item_type'] === 'product') {
                $itemTypeLabel = 'Sản phẩm';
              } elseif ($item['item_type'] === 'package') {
                $itemTypeLabel = 'Gói tập';
              } elseif ($item['item_type'] === 'service') {
                $itemTypeLabel = 'Dịch vụ';
              }
            ?>
            <tr>
              <td class="center"><?php echo $index + 1; ?></td>
              <td><?php echo htmlspecialchars($item['item_name']); ?></td>
              <td class="center"><?php echo $itemTypeLabel; ?></td>
              <td class="center"><?php echo (int)$item['quantity']; ?></td>
              <td class="right"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
              <td class="right"><?php echo number_format($item['discount'], 0, ',', '.'); ?>đ</td>
              <td class="right"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?>đ</td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="center">Không có dữ liệu chi tiết xuất kho.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>

    <table class="summary">
      <tr>
        <td><strong>Tổng giá trị xuất</strong></td>
        <td class="right"><strong><?php echo number_format($amountToPrint, 0, ',', '.'); ?>đ</strong></td>
      </tr>
    </table>

    <div class="sign">
      <div>
        <strong>Người lập phiếu</strong>
        <div class="space">(Ký, ghi rõ họ tên)</div>
      </div>
      <div>
        <strong>Thủ kho</strong>
        <div class="space">(Ký, ghi rõ họ tên)</div>
      </div>
      <div>
        <strong>Người nhận hàng</strong>
        <div class="space">(Ký, ghi rõ họ tên)</div>
      </div>
    </div>
  </div>

  <div class="actions">
    <a href="export-slips.php" class="btn gray">Quay lại</a>
    <button class="btn" onclick="window.print()">In phiếu xuất</button>
  </div>
</body>
</html>
