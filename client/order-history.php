<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';
require_once '../includes/functions.php';

// Kiểm tra bảng đánh giá có tồn tại không để tránh lỗi khi DB chưa có feature này
$checkReviewTable = $conn->query("SHOW TABLES LIKE 'product_reviews'");
$hasReviewTable = $checkReviewTable && $checkReviewTable->num_rows > 0;

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// 1. Lấy thông tin member (id, full_name)
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

// 2. Lấy thêm thông tin từ bảng users (để đồng bộ avatar và email)
$sql_user_data = "SELECT email, avatar FROM users WHERE id = ?";
$stmt_user_data = $conn->prepare($sql_user_data);
$stmt_user_data->bind_param("i", $user_id);
$stmt_user_data->execute();
$user_data = $stmt_user_data->get_result()->fetch_assoc();
$stmt_user_data->close();

$avatarPath = trim((string)($user_data['avatar'] ?? ''));
$avatarUrl = '';
if ($avatarPath !== '') {
    $normalizedAvatarPath = ltrim(str_replace('\\', '/', $avatarPath), '/');
    $avatarUrl = '../' . $normalizedAvatarPath;
}

$filter_status = $_GET['status'] ?? '';
$filter_from = $_GET['from_date'] ?? '';
$filter_to = $_GET['to_date'] ?? '';

$review_notice = '';
if (isset($_GET['review_success'])) {
    $review_notice = 'Đánh giá đã được lưu.';
} elseif (isset($_GET['review_error'])) {
    $review_notice = 'Không thể lưu đánh giá lúc này.';
}

$current_query = [];
if (!empty($filter_status)) {
    $current_query['status'] = $filter_status;
}
if (!empty($filter_from)) {
    $current_query['from_date'] = $filter_from;
}
if (!empty($filter_to)) {
    $current_query['to_date'] = $filter_to;
}
$review_redirect_base = 'order-history.php' . (!empty($current_query) ? '?' . http_build_query($current_query) : '');

function buildReviewRedirectUrl($baseUrl, $extraParams = []) {
    $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
    return $baseUrl . (!empty($extraParams) ? $separator . http_build_query($extraParams) : '');
}

$review_posted = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']));
if ($review_posted) {
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $review_target = trim($_POST['review_target'] ?? '');
    $review_content = trim($_POST['review_content'] ?? '');
    $review_rating = isset($_POST['review_rating']) ? (int) $_POST['review_rating'] : 0;
    $review_payload = json_encode([
        'author' => $member['full_name'],
        'content' => $review_content,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($order_id <= 0 || $review_target === '' || $review_content === '' || $review_rating < 1 || $review_rating > 5) {
        header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_error' => 1]));
        exit;
    }

    $stmt_order_check = $conn->prepare("SELECT id FROM orders WHERE id = ? AND member_id = ? AND status = 'delivered' LIMIT 1");
    $stmt_order_check->bind_param('ii', $order_id, $member_id);
    $stmt_order_check->execute();
    $order_check_result = $stmt_order_check->get_result();

    if ($order_check_result->num_rows === 0) {
        $stmt_order_check->close();
        header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_error' => 1]));
        exit;
    }
    $stmt_order_check->close();

    $stmt_products = $conn->prepare("SELECT DISTINCT p.id, p.name FROM order_items oi JOIN products p ON p.id = oi.item_id WHERE oi.order_id = ? AND oi.item_type = 'product' ORDER BY p.name ASC");
    $stmt_products->bind_param('i', $order_id);
    $stmt_products->execute();
    $products_result = $stmt_products->get_result();
    $order_products = [];
    while ($row = $products_result->fetch_assoc()) {
        $order_products[] = $row;
    }
    $stmt_products->close();

    if (empty($order_products)) {
        header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_error' => 1]));
        exit;
    }

    $target_product_ids = [];
    if ($review_target === 'all') {
        foreach ($order_products as $product_row) {
            $target_product_ids[] = (int) $product_row['id'];
        }
    } else {
        $target_product_id = (int) $review_target;
        $allowed_product_ids = array_map(function ($product_row) {
            return (int) $product_row['id'];
        }, $order_products);
        if (!in_array($target_product_id, $allowed_product_ids, true)) {
            header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_error' => 1]));
            exit;
        }
        $target_product_ids[] = $target_product_id;
    }

    $conn->begin_transaction();
    try {
        $stmt_update_product = $conn->prepare('UPDATE products SET rating = ?, review = ? WHERE id = ?');
        foreach ($target_product_ids as $target_product_id) {
            $stmt_update_product->bind_param('dsi', $review_rating, $review_payload, $target_product_id);
            $stmt_update_product->execute();
        }
        $stmt_update_product->close();

        $conn->commit();
        header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_success' => 1]));
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header('Location: ' . buildReviewRedirectUrl($review_redirect_base, ['review_error' => 1]));
        exit;
    }
}

$query = "
    SELECT o.id, o.order_date, o.status, o.total_amount, 
           COALESCE(SUM(oi.quantity), 0) AS total_items,
        COALESCE(MAX(order_addr.full_address), MAX(default_addr.full_address), MAX(m.address)) AS shipping_address,
        COALESCE(MAX(order_addr.district), MAX(default_addr.district), '') AS shipping_district,
        COALESCE(MAX(order_addr.city), MAX(default_addr.city), '') AS shipping_city
    FROM orders o
    JOIN members m ON o.member_id = m.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN addresses default_addr ON default_addr.member_id = o.member_id AND default_addr.is_default = 1
    LEFT JOIN addresses order_addr ON order_addr.id = o.address_id AND order_addr.member_id = o.member_id
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
$orders = [];
if ($orders_result) {
    while ($order_row = $orders_result->fetch_assoc()) {
        $orders[] = $order_row;
    }
}

$order_ids = array_map(function ($order_row) {
    return (int) $order_row['id'];
}, $orders);

$order_products_map = [];
$review_lookup_map = [];
$review_stats_map = [];
if (!empty($order_ids)) {
    $order_ids_list = implode(',', array_map('intval', $order_ids));
    $order_items_sql = "
        SELECT oi.order_id, p.id AS product_id, COALESCE(oi.item_name, p.name) AS product_name, p.rating AS review_rating, p.review AS review_content
        FROM order_items oi
        JOIN products p ON p.id = oi.item_id
        WHERE oi.order_id IN ($order_ids_list) AND oi.item_type = 'product'
        ORDER BY oi.order_id DESC, oi.id ASC
    ";
    $order_items_result = $conn->query($order_items_sql);
    if ($order_items_result) {
        while ($order_item = $order_items_result->fetch_assoc()) {
            $order_id_key = (int) $order_item['order_id'];
            $product_id_key = (int) $order_item['product_id'];
            $parsedReview = parseProductReviewPayload($order_item['review_content'] ?? '');

            if (!isset($order_products_map[$order_id_key])) {
                $order_products_map[$order_id_key] = [];
            }

            $order_products_map[$order_id_key][] = [
                'id' => $product_id_key,
                'name' => $order_item['product_name'],
                'rating' => (float) ($order_item['review_rating'] ?? 0),
                'content' => $parsedReview['content'],
            ];

            $review_lookup_map[$order_id_key][$product_id_key] = [
                'rating' => (float) ($order_item['review_rating'] ?? 0),
                'content' => $parsedReview['content'],
                'author' => $parsedReview['author'] !== '' ? $parsedReview['author'] : 'Hội viên',
            ];
        }
    }

    foreach ($review_lookup_map as $orderIdKey => $productRows) {
        foreach ($productRows as $productIdKey => $reviewData) {
            $review_stats_map[(int) $productIdKey] = [
                'review_count' => trim((string) $reviewData['content']) !== '' ? 1 : 0,
                'avg_rating' => (float) $reviewData['rating'],
            ];
        }
    }
}

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
                        <a href="profile.php">Hồ sơ</a>
                        <span>Lịch sử mua hàng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Đồng bộ CSS Sidebar từ trang Profile */
.sidebar-item { display:block; padding:10px 15px; color:#333; border-radius:5px; margin-bottom:5px; text-decoration:none; transition: 0.2s; }
.sidebar-item:hover, .sidebar-item.active { background:#f36100; color:#fff; text-decoration:none; }
.sidebar-item i { margin-right:8px; width:16px; }
.profile-sidebar { background:#fff; border-radius:8px; padding:20px; box-shadow:0 2px 10px rgba(0,0,0,.08); position:sticky; top:20px; }
.user-avatar { margin-bottom:15px; }
.user-avatar-image,
.user-avatar-placeholder {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 4px solid #f36100;
    background: #fff;
    object-fit: cover;
    margin: 0 auto;
}
.user-avatar-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f36100;
}

/* Các thuộc tính CSS dành cho order */
.order-item { border: 1px solid #ebebeb; border-radius: 8px; overflow: hidden; background: #fff; transition: 0.3s; }
.order-item:hover { border-color: #e7ab3c; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
.order-header { border-bottom: 1px solid #f2f2f2; }
.btn-sm { padding: 8px 15px; font-size: 12px; }
.badge { font-weight: 500; padding: 6px 12px; border-radius: 20px; }
</style>

<section class="profile-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="profile-sidebar">
                    <div class="user-info text-center">
                        <div class="user-avatar">
                            <?php if ($avatarUrl !== ''): ?>
                                <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Avatar" class="user-avatar-image">
                            <?php else: ?>
                                <div class="user-avatar-placeholder">
                                    <i class="fa fa-user-circle fa-5x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h5 class="mt-3"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?></h5>
                        <p style="color:#888;font-size:13px;"><?php echo htmlspecialchars($user_data['email'] ?? $_SESSION['email'] ?? ''); ?></p>
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
                        <a href="order-history.php" class="sidebar-item active">
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
                <div class="profile-content" style="background-color: aliceblue; padding: 30px; border-radius: 8px;">
                    <h4 class="mb-4">Danh sách đơn hàng</h4>
                    
                    <div class="order-filter mb-4 p-3" style="background: #fff; border: 1px solid #ebebeb; border-radius: 8px;">
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

                    <?php if (!empty($review_notice)): ?>
                        <div class="alert alert-info"><?php echo htmlspecialchars($review_notice); ?></div>
                    <?php endif; ?>

                    <div class="order-list">
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
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
                                                <p class="mb-1">Địa chỉ giao hàng: <strong><?php echo htmlspecialchars($order['shipping_address'] ?? 'Chưa cập nhật'); ?></strong></p>
                                                <?php if (!empty($order['shipping_district']) || !empty($order['shipping_city'])): ?>
                                                <p class="mb-1 text-muted" style="font-size: 13px;">
                                                    <?php echo htmlspecialchars(trim(($order['shipping_district'] ?? '') . (!empty($order['shipping_district']) && !empty($order['shipping_city']) ? ', ' : '') . ($order['shipping_city'] ?? ''))); ?>
                                                </p>
                                                <?php endif; ?>
                                                <p class="mb-0">Tổng tiền: <strong style="color: #e7ab3c; font-size: 1.1rem;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</strong></p>
                                            </div>
                                            <div class="col-md-5 text-md-right mt-3 mt-md-0">
                                                <a href="invoice.php?order_id=<?php echo $order['id']; ?>" class="site-btn btn-sm" style="background: #333;">Hóa đơn</a>
                                                
                                                <?php if ($order['status'] === 'delivered'): ?>
                                                    <button type="button" class="site-btn btn-sm ml-1" onclick="openReviewModal(<?php echo $order['id']; ?>)">Đánh giá</button>
                                                <?php endif; ?>

                                                <?php if ($order['status'] === 'pending'): ?>
                                                    <button onclick="cancelOrder(<?php echo $order['id']; ?>)" class="site-btn btn-sm ml-1" style="background: #dc3545;">Hủy đơn</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5" style="background: #fff; border-radius: 8px;">
                                <i class="fa fa-file-text-o mb-3" style="font-size: 50px; color: #ddd;"></i>
                                <h5>Chưa có đơn hàng nào</h5>
                                <p class="text-muted">Bạn chưa có lịch sử mua hàng nào. Hãy bắt đầu mua sắm ngay!</p>
                                <a href="products.php" class="site-btn mt-2">Mua sắm ngay</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="reviewModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Đánh giá sản phẩm trong hóa đơn</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="<?php echo htmlspecialchars($review_redirect_base); ?>">
                <div class="modal-body">
                    <input type="hidden" name="order_id" id="review-order-id" value="">
                    <div class="form-group">
                        <label>Chọn sản phẩm</label>
                        <select class="form-control" name="review_target" id="review-target" required onchange="loadReviewTarget()">
                            <option value="">-- Chọn sản phẩm trong hóa đơn --</option>
                            <option value="all">Đánh giá tất cả sản phẩm trong hóa đơn</option>
                        </select>
                        <small class="form-text text-muted">Dropdown chỉ hiển thị các sản phẩm thuộc hóa đơn đang chọn.</small>
                    </div>
                    <div class="alert alert-light border mb-3" id="review-product-summary" style="display:none;"></div>
                    <div class="form-group">
                        <label>Nội dung</label>
                        <textarea class="form-control" name="review_content" id="review-content" rows="4" required placeholder="Nhập nội dung đánh giá..."></textarea>
                    </div>
                    <div class="form-group">
                        <label>Đánh giá sao</label>
                        <select class="form-control" name="review_rating" id="review-rating" required>
                            <option value="">-- Chọn số sao --</option>
                            <option value="5">5 - Rất tốt</option>
                            <option value="4">4 - Tốt</option>
                            <option value="3">3 - Ổn</option>
                            <option value="2">2 - Chưa hài lòng</option>
                            <option value="1">1 - Rất tệ</option>
                        </select>
                    </div>
                    <div class="alert alert-secondary mb-0" id="review-edit-note" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
                    <button type="submit" name="submit_review" class="site-btn">Lưu đánh giá</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var orderReviewData = <?php echo json_encode($order_products_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
var reviewLookupData = <?php echo json_encode($review_lookup_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
var reviewStatsData = <?php echo json_encode($review_stats_map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

function formatReviewSummary(productId) {
    var stat = reviewStatsData[productId] || null;
    if (!stat || !stat.review_count) {
        return 'Chưa có đánh giá nào từ hội viên.';
    }
    return 'Trung bình ' + stat.avg_rating.toFixed(1) + '/5 từ ' + stat.review_count + ' lượt đánh giá.';
}

function openReviewModal(orderId) {
    var select = document.getElementById('review-target');
    var orderIdField = document.getElementById('review-order-id');
    var contentField = document.getElementById('review-content');
    var ratingField = document.getElementById('review-rating');
    var note = document.getElementById('review-edit-note');
    var summary = document.getElementById('review-product-summary');

    orderIdField.value = orderId;

    select.innerHTML = '<option value="">-- Chọn sản phẩm trong hóa đơn --</option><option value="all">Đánh giá tất cả sản phẩm trong hóa đơn</option>';

    var products = orderReviewData[orderId] || [];
    products.forEach(function (product) {
        var option = document.createElement('option');
        option.value = product.id;
        var stat = reviewStatsData[product.id] || null;
        if (stat && stat.review_count > 0) {
            option.textContent = product.name + ' - ' + stat.avg_rating.toFixed(1) + '/5 (' + stat.review_count + ' đánh giá)';
        } else {
            option.textContent = product.name + ' - Chưa có đánh giá';
        }
        select.appendChild(option);
    });

    contentField.value = '';
    ratingField.value = '';
    note.style.display = 'none';
    note.textContent = '';
    summary.style.display = 'none';
    summary.textContent = '';

    $('#reviewModal').modal('show');
}

function loadReviewTarget() {
    var orderId = document.getElementById('review-order-id').value;
    var target = document.getElementById('review-target').value;
    var contentField = document.getElementById('review-content');
    var ratingField = document.getElementById('review-rating');
    var note = document.getElementById('review-edit-note');
    var summary = document.getElementById('review-product-summary');

    if (!orderId || !target) {
        contentField.value = '';
        ratingField.value = '';
        note.style.display = 'none';
        note.textContent = '';
        summary.style.display = 'none';
        summary.textContent = '';
        return;
    }

    if (target === 'all') {
        contentField.value = '';
        ratingField.value = '';
        note.style.display = 'block';
        note.textContent = 'Đánh giá này sẽ được áp dụng cho tất cả sản phẩm trong hóa đơn.';
        summary.style.display = 'none';
        summary.textContent = '';
        return;
    }

    summary.style.display = 'block';
    summary.textContent = formatReviewSummary(target);

    var reviewData = (reviewLookupData[orderId] || {})[target] || null;
    if (reviewData) {
        contentField.value = reviewData.content || '';
        ratingField.value = reviewData.rating || '';
        note.style.display = 'block';
        note.textContent = 'Bạn đang chỉnh sửa đánh giá đã có cho sản phẩm này.';
    } else {
        contentField.value = '';
        ratingField.value = '';
        note.style.display = 'block';
        note.textContent = 'Sản phẩm này chưa có đánh giá, bạn có thể nhập mới.';
    }
}

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