<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

$services = [];
$activeMemberServiceIds = [];
$cartServiceIds = [];
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

function normalizeClientServiceStatus($status) {
    $status = strtolower(trim((string) $status));

    $aliases = [
        'còn hiệu lực' => 'active',
        'đã dùng' => 'used',
        'hết hạn' => 'expired',
        'bị hủy' => 'cancelled',
    ];

    if (isset($aliases[$status])) {
        return $aliases[$status];
    }

    $allowed = ['active', 'used', 'expired', 'cancelled'];

    return in_array($status, $allowed, true) ? $status : 'active';
}

$serviceQuery = $conn->query("SELECT id, name, img, type, price, description FROM services WHERE status = 'hoạt động' ORDER BY id ASC");
if ($serviceQuery) {
    while ($row = $serviceQuery->fetch_assoc()) {
        $services[] = $row;
    }
}

if ($userId > 0) {
    $memberStmt = $conn->prepare("SELECT id FROM members WHERE users_id = ? LIMIT 1");
    $memberStmt->bind_param("i", $userId);
    $memberStmt->execute();
    $member = $memberStmt->get_result()->fetch_assoc();
    $memberStmt->close();

    $memberId = (int) ($member['id'] ?? 0);

    if ($memberId > 0) {
        $activeServiceStmt = $conn->prepare("SELECT service_id FROM member_services WHERE member_id = ? AND status = 'còn hiệu lực' AND end_date >= CURDATE()");
        $activeServiceStmt->bind_param("i", $memberId);
        $activeServiceStmt->execute();
        $activeServiceResult = $activeServiceStmt->get_result();

        while ($row = $activeServiceResult->fetch_assoc()) {
            $activeMemberServiceIds[] = (int) $row['service_id'];
        }
        $activeServiceStmt->close();

        $cartStmt = $conn->prepare("SELECT ci.item_id FROM carts c JOIN cart_items ci ON ci.cart_id = c.id AND ci.item_type = 'service' WHERE c.member_id = ? AND c.status = 'active'");
        $cartStmt->bind_param("i", $memberId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();

        while ($row = $cartResult->fetch_assoc()) {
            $cartServiceIds[] = (int) $row['item_id'];
        }
        $cartStmt->close();
    }
}

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">

            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Dịch vụ</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Dịch vụ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Services Section Begin -->
<section class="services-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Dịch vụ của chúng tôi</span>
                    <h2>CHỌN DỊCH VỤ PHÙ HỢP</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 mb-4">
                <div class="alert alert-info mb-0">Mỗi lần đăng ký dịch vụ tương đương 1 lần sử dụng, thời hạn 1 tháng kể từ ngày đăng ký.</div>
            </div>
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <?php
                    $serviceId = (int) $service['id'];
                    $isActive = in_array($serviceId, $activeMemberServiceIds, true);
                    $isInCart = in_array($serviceId, $cartServiceIds, true);
                    $buttonLabel = 'Thêm vào giỏ hàng';
                    $buttonDisabled = '';

                    if ($isActive) {
                        $buttonLabel = 'Đã đăng ký';
                        $buttonDisabled = 'disabled';
                    } elseif ($isInCart) {
                        $buttonLabel = 'Đã có trong giỏ';
                        $buttonDisabled = 'disabled';
                    }

                    if (!empty($service['img'])) {
                        $storedImagePath = ltrim($service['img'], '/');
                        if (strpos($storedImagePath, 'assets/uploads/services/') === 0 || strpos($storedImagePath, 'assets/') === 0) {
                            $imagePath = '../' . $storedImagePath;
                        } else {
                            $imagePath = '../assets/uploads/services/' . $storedImagePath;
                        }
                    } else {
                        $imagePath = 'assets/img/services/services-1.jpg';
                    }
                    ?>
                    <div class="col-lg-4 col-sm-6 mb-4">
                        <div class="services-item" style="padding-bottom: 20px;">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" style="height: 220px; width: 100%; object-fit: cover;">
                            <h4 style="color: #ffffff; text-shadow: 0 2px 6px rgba(0, 0, 0, 0.45);">
                                <?php echo htmlspecialchars($service['name']); ?>
                            </h4>
                            <p style="min-height: 72px;"><?php echo htmlspecialchars($service['description'] ?? 'Dịch vụ chăm sóc và hỗ trợ hội viên.'); ?></p>
                            <div style="font-weight: 700; color: #f36100; margin-bottom: 12px;">
                                <?php echo number_format((float) $service['price'], 0, ',', '.'); ?>đ
                                <span style="font-size: 12px; color: #999; font-weight: 600;">/ <?php echo htmlspecialchars($service['type']); ?></span>
                            </div>
                            <button type="button"
                                    class="primary-btn"
                                    onclick="addServiceToCart(<?php echo $serviceId; ?>)"
                                    <?php echo $buttonDisabled; ?>>
                                <?php echo $buttonLabel; ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-lg-12">
                    <div class="alert alert-warning text-center">Hiện chưa có dịch vụ nào đang mở bán.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Services Section End -->

<!-- Banner Section Begin -->
<section class="banner-section set-bg" data-setbg="assets/img/banner-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="bs-text">
                    <h2>Đăng ký ngay để nhận thêm ưu đãi</h2>
                    <div class="bt-tips">Nơi sức khỏe, sắc đẹp và thể hình gặp nhau.</div>
                    <a href="contact.php" class="primary-btn btn-normal">Liên hệ ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End -->

<?php include 'layout/footer.php'; ?>

<script>
function addServiceToCart(serviceId) {
    var formData = new FormData();
    formData.append('item_type', 'service');
    formData.append('service_id', serviceId);
    formData.append('quantity', 1);

    fetch('ajax/cart-add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === true) {
            var cartCountElements = document.querySelectorAll('.cart-count, #cart-count, .cart-badge');
            cartCountElements.forEach(function(el) {
                el.innerText = data.cart_count;
                el.style.display = 'flex';
            });

            if (confirm(data.message + "\nBạn có muốn chuyển đến Giỏ hàng để thanh toán không?")) {
                window.location.href = 'cart.php';
            } else {
                window.location.reload();
            }
        } else {
            alert('Thông báo: ' + data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        alert('Có lỗi xảy ra kết nối với máy chủ!');
    });
}
</script>
