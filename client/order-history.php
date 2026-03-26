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

<style>
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; }
.sidebar-item:hover, .sidebar-item.active { background:#f36100; color:#fff; text-decoration:none; }
.sidebar-item i { margin-right:8px; width:16px; }
.profile-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:20px; }
.user-avatar { color:#f36100; margin-bottom:15px; }
</style>

<section class="order-history-section spad">
    <div class="container">
        <div class="row">
                        <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <i class="fa fa-user-circle fa-5x" style="color:#f36100;"></i>
                        </div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#888;font-size:13px;"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></p>
                    </div>
                    <hr>
                    <div class="sidebar-menu">
                        <a href="profile.php" class="sidebar-item">
                            <i class="fa fa-user"></i> Thông tin cá nhân
                        </a>
                        <a href="my-membership.php" class="sidebar-item">
                            <i class="fa fa-star"></i> Thông tin hội viên
                        </a>
                        <a href="my-packages.php" class="sidebar-item">
                            <i class="fa fa-ticket"></i> Gói tập của tôi
                        </a>
                        <a href="my-schedules.php" class="sidebar-item">
                            <i class="fa fa-calendar"></i> Lịch tập của tôi
                        </a>
                        <a href="order-history.php" class="sidebar-item" active>
                            <i class="fa fa-shopping-bag"></i> Lịch sử mua hàng
                        </a>
                        <a href="addresses.php" class="sidebar-item">
                            <i class="fa fa-map-marker"></i> Địa chỉ
                        </a>
                        <a href="logout.php" class="sidebar-item text-danger">
                            <i class="fa fa-sign-out"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="order-history-content">
                    <h4 class="mb-4">Danh sách đơn hàng</h4>
                    
                    <div class="order-filter mb-4 p-3" style="background: #f8f9fa; border-radius: 5px;">
                        <form action="order-history.php" method="GET">
                            <div class="row">
                                <div class="col-lg-3 mb-2">
                                    <select class="form-control" name="status">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="delivered" <?php echo $filter_status == 'delivered' ? 'selected' : ''; ?>>Đã giao</option>
                                        <option value="cancelled" <?php echo $filter_status == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <input type="date" class="form-control" name="from_date" value="<?php echo htmlspecialchars($filter_from); ?>">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <input type="date" class="form-control" name="to_date" value="<?php echo htmlspecialchars($filter_to); ?>">
                                </div>
                                <div class="col-lg-3 mb-2">
                                    <button type="submit" class="site-btn w-100" style="padding: 10px;">Lọc đơn</button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="order-list">
                        <?php if ($orders_result->num_rows > 0): ?>
                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                <div class="order-item mb-4">
                                    <div class="order-header p-3" style="background: #fdfdfd;">
                                        <div class="row align-items-center">
                                            <div class="col-md-4">
                                                <strong>Mã đơn:</strong> <span style="color: #e7ab3c;">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></span>
                                            </div>
                                            <div class="col-md-4 text-md-center">
                                                <small class="text-muted"><i class="fa fa-calendar"></i> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></small>
                                            </div>
                                            <div class="col-md-4 text-md-right">
                                                <?php 
                                                    if ($order['status'] === 'pending') echo '<span class="badge badge-warning">Chờ xác nhận</span>';
                                                    elseif ($order['status'] === 'confirmed') echo '<span class="badge badge-primary">Đang giao</span>';
                                                    elseif ($order['status'] === 'delivered') echo '<span class="badge badge-success">Thành công</span>';
                                                    elseif ($order['status'] === 'cancelled') echo '<span class="badge badge-danger">Đã hủy</span>';
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-body p-3">
                                        <div class="row align-items-center">
                                            <div class="col-md-7">
                                                <p class="mb-1">Số lượng: <strong><?php echo $order['total_items']; ?></strong> món</p>
                                                <p class="mb-0">Tổng tiền: <strong style="color: #e7ab3c; font-size: 1.1rem;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></p>
                                            </div>
                                            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                                <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="site-btn btn-sm" style="background: #333;">Hóa đơn</a>
                                                
                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="site-btn btn-sm btn-danger-custom ml-1">Hủy đơn</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fa fa-file-text-o mb-3" style="font-size: 50px; color: #ddd;"></i>
                                <p>Không tìm thấy đơn hàng nào.</p>
                                <a href="products.php" class="site-btn mt-2">Mua sắm ngay</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.profile-sidebar { border: 1px solid #ebebeb; padding: 25px; border-radius: 8px; background: #fff; }
.order-item { border: 1px solid #ebebeb; border-radius: 8px; overflow: hidden; background: #fff; transition: 0.3s; }
.order-item:hover { border-color: #e7ab3c; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.order-header { border-bottom: 1px solid #f2f2f2; }
.btn-sm { padding: 8px 15px; font-size: 12px; }
.btn-danger-custom { background: #dc3545; border: none; }
.btn-danger-custom:hover { background: #a71d2a; }
.badge { font-weight: 500; padding: 6px 12px; border-radius: 20px; }
</style>

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