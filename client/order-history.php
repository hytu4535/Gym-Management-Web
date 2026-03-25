<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];


$stmt_user = $conn->prepare("SELECT id, full_name FROM members WHERE users_id = ?");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$res_user = $stmt_user->get_result();

if ($res_user->num_rows === 0) {
    echo "<script>alert('Không tìm thấy thông tin hội viên!'); window.location.href='index.php';</script>";
    exit;
}
$member = $res_user->fetch_assoc();
$member_id = $member['id'];
$stmt_user->close();


$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['from_date'] ?? '';
$filter_to = $_GET['to_date'] ?? '';

$query = "
    SELECT o.id, o.order_date, o.status, o.total_amount, 
           COALESCE(SUM(oi.quantity), 0) AS total_items
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.member_id = ?
";
$params = [$member_id];
$types = "i";

if (!empty($filter_status)) {
    $query .= " AND o.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}
if (!empty($filter_from)) {
    $query .= " AND DATE(o.order_date) >= ?";
    $params[] = $filter_from;
    $types .= "s";
}
if (!empty($filter_to)) {
    $query .= " AND DATE(o.order_date) <= ?";
    $params[] = $filter_to;
    $types .= "s";
}

$query .= " GROUP BY o.id ORDER BY o.order_date DESC";

$stmt_orders = $conn->prepare($query);
$stmt_orders->bind_param($types, ...$params);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

include 'layout/header.php'; 
?>

<style>
    /* Banner Breadcrumb */
    .breadcrumb-text h2 { 
        color: #ffffff !important; 
        text-shadow: 2px 2px 5px rgba(0,0,0,0.8); 
    }
    .breadcrumb-text .bt-option a, 
    .breadcrumb-text .bt-option span { 
        color: #ffffff !important; 
        font-weight: bold; 
        text-shadow: 1px 1px 4px rgba(0,0,0,0.9); 
    }

    /* Nền tổng thể */
    .order-history-section {
        background-color: #f7f9fc;
        padding-top: 50px;
        padding-bottom: 60px;
    }

    /* Sidebar Profile */
    .profile-sidebar { 
        background: #ffffff; 
        border-radius: 12px; 
        padding: 30px 20px; 
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: none;
    }
    .profile-avatar img {
        border: 3px solid #e7ab3c !important;
        padding: 3px;
        box-shadow: 0 4px 10px rgba(231, 171, 60, 0.2);
    }
    .profile-menu li a {
        display: block;
        padding: 10px 15px;
        border-radius: 8px;
        transition: all 0.3s ease;
        font-weight: 500;
    }
    .profile-menu li a:hover, .profile-menu li a.active {
        background: #fff8eb;
        color: #e7ab3c !important;
        text-decoration: none;
        padding-left: 20px;
    }

    /* Filter Form */
    .order-filter {
        background: #ffffff !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: none !important;
    }
    .order-filter .form-control {
        border: 1px solid #e1e5eb;
        border-radius: 6px;
        height: 45px;
    }

    /* Order Item Cards */
    .order-item { 
        background: #ffffff; 
        border-radius: 12px; 
        overflow: hidden; 
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
        border: 1px solid #f0f2f5;
        transition: transform 0.3s ease, box-shadow 0.3s ease; 
        margin-bottom: 25px !important;
    }
    .order-item:hover { 
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08); 
        border-color: #e7ab3c;
    }
    .order-header { 
        background: #fafbfc !important; 
        border-bottom: 1px solid #f0f2f5 !important; 
        padding: 15px 20px !important;
    }
    .order-body {
        padding: 20px !important;
    }

    /* Badges Status */
    .badge { 
        font-weight: 600; 
        padding: 8px 15px; 
        border-radius: 6px; 
        font-size: 13px;
    }
    .badge-warning { background: #fff8eb; color: #e7ab3c; border: 1px solid #ffe9c2; }
    .badge-primary { background: #eef5ff; color: #2196f3; border: 1px solid #cbe4ff; }
    .badge-success { background: #eafaf1; color: #28a745; border: 1px solid #c3f0d3; }
    .badge-danger  { background: #ffeeee; color: #dc3545; border: 1px solid #ffd4d4; }

    /* Buttons */
    .btn-sm-custom { 
        padding: 8px 20px; 
        font-size: 13px; 
        border-radius: 6px;
        font-weight: bold;
        text-transform: uppercase;
        border: none;
        transition: all 0.3s ease;
    }
    .btn-invoice { background: #333333; color: #ffffff; }
    .btn-invoice:hover { background: #555555; color: #ffffff; }
    .btn-cancel { background: #fff; color: #dc3545; border: 1px solid #dc3545; }
    .btn-cancel:hover { background: #dc3545; color: #ffffff; }
</style>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Lịch sử mua hàng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Lịch sử</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="order-history-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="profile-sidebar">
                    <div class="profile-avatar text-center mb-3">
                        <i class="fa fa-user-circle fa-5x" style="color:#f36100;"></i>
                    </div>
                    <h4 class="text-center mb-4" style="color: #333; font-weight: 700;"><?php echo htmlspecialchars($member['full_name']); ?></h4>
                    <ul class="profile-menu" style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 8px;"><a href="profile.php" style="color: #555;"><i class="fa fa-user mr-2"></i> Thông tin cá nhân</a></li>
                        <li style="margin-bottom: 8px;"><a href="order-history.php" class="active"><i class="fa fa-shopping-bag mr-2"></i> Lịch sử mua hàng</a></li>
                        <li style="margin-bottom: 8px;"><a href="addresses.php" style="color: #555;"><i class="fa fa-map-marker mr-2"></i> Địa chỉ giao hàng</a></li>
                        <li style="margin-top: 20px; padding-top: 15px; border-top: 1px dashed #eee;"><a href="logout.php" style="color: #dc3545;"><i class="fa fa-sign-out mr-2"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="order-history-content">
                    <h4 class="mb-4" style="font-weight: 700; color: #111;">Danh sách đơn hàng của bạn</h4>
                    
                    <div class="order-filter p-4 mb-4">
                        <form action="order-history.php" method="GET">
                            <div class="row align-items-end">
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                    <label style="font-size: 13px; color: #777; font-weight: bold;">Trạng thái</label>
                                    <select class="form-control" name="status">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Đang giao</option>
                                        <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Thành công</option>
                                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                    <label style="font-size: 13px; color: #777; font-weight: bold;">Từ ngày</label>
                                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($filter_from); ?>">
                                </div>
                                <div class="col-lg-3 col-md-6 mb-3 mb-lg-0">
                                    <label style="font-size: 13px; color: #777; font-weight: bold;">Đến ngày</label>
                                    <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($filter_to); ?>">
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <button type="submit" class="site-btn w-100" style="height: 45px; border-radius: 6px;">LỌC ĐƠN</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="order-list">
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-item">
                                    <div class="order-header">
                                        <div class="row align-items-center">
                                            <div class="col-md-4 mb-2 mb-md-0">
                                                <span style="color: #777; font-size: 14px;">Mã đơn:</span> 
                                                <strong style="color: #111; font-size: 16px;">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                            </div>
                                            <div class="col-md-4 text-md-center mb-2 mb-md-0">
                                                <span style="color: #666; font-size: 14px;"><i class="fa fa-calendar-o mr-1"></i> <?php echo date('d/m/Y - H:i', strtotime($order['order_date'])); ?></span>
                                            </div>
                                            <div class="col-md-4 text-md-right">
                                                <?php 
                                                    if ($order['status'] === 'pending') echo '<span class="badge badge-warning"><i class="fa fa-clock-o mr-1"></i> Chờ xác nhận</span>';
                                                    elseif ($order['status'] === 'confirmed') echo '<span class="badge badge-primary"><i class="fa fa-truck mr-1"></i> Đang giao</span>';
                                                    elseif ($order['status'] === 'delivered') echo '<span class="badge badge-success"><i class="fa fa-check-circle mr-1"></i> Thành công</span>';
                                                    elseif ($order['status'] === 'cancelled') echo '<span class="badge badge-danger"><i class="fa fa-times-circle mr-1"></i> Đã hủy</span>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-7 mb-3 mb-md-0">
                                                <div style="display: flex; align-items: center; gap: 20px;">
                                                    <div>
                                                        <span style="color: #777; font-size: 14px; display: block;">Số lượng món</span>
                                                        <strong style="font-size: 16px; color: #333;"><?php echo $order['total_items']; ?></strong>
                                                    </div>
                                                    <div style="border-left: 1px solid #eee; height: 30px;"></div>
                                                    <div>
                                                        <span style="color: #777; font-size: 14px; display: block;">Tổng thanh toán</span>
                                                        <strong style="color: #e7ab3c; font-size: 20px;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?> VNĐ</strong>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-5 text-md-right d-flex justify-content-md-end justify-content-start gap-2">
                                                <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="btn-sm-custom btn-invoice mr-2 text-decoration-none">Xem Hóa Đơn</a>
                                                
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="btn-sm-custom btn-cancel">Hủy Đơn</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5" style="background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.04);">
                                <img src="https://cdn-icons-png.flaticon.com/512/11329/11329074.png" alt="Empty" style="width: 120px; opacity: 0.5; margin-bottom: 20px;">
                                <h5 style="color: #555;">Chưa có đơn hàng nào</h5>
                                <p style="color: #888; margin-bottom: 20px;">Bạn chưa thực hiện giao dịch nào hoặc không có đơn hàng phù hợp với bộ lọc.</p>
                                <a href="products.php" class="site-btn">MUA SẮM NGAY</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function cancelOrder(orderId) {
    if (confirm('Bạn chắc chắn muốn hủy đơn hàng #' + orderId + '?\nHành động này không thể hoàn tác.')) {
        const formData = new FormData();
        formData.append('order_id', orderId);

        fetch('ajax/order-cancel.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                window.location.reload();
            } else {
                alert('Lỗi: ' + data.message);
            }
        })
        .catch(err => {
            console.error(err);
            alert('Lỗi kết nối máy chủ!');
        });
    }
}
</script>

<?php include 'layout/footer.php'; ?>