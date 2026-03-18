<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

$packages = [];
$registeredPackageIds = [];
$cartPackageIds = [];
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

$packageQuery = $conn->query("SELECT id, package_name, duration_months, price, description FROM membership_packages WHERE status = 'active' ORDER BY duration_months ASC, id ASC");
if ($packageQuery) {
    while ($row = $packageQuery->fetch_assoc()) {
        $packages[] = $row;
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
        $registeredStmt = $conn->prepare("SELECT package_id FROM member_packages WHERE member_id = ? AND status = 'active'");
        $registeredStmt->bind_param("i", $memberId);
        $registeredStmt->execute();
        $registeredResult = $registeredStmt->get_result();

        while ($row = $registeredResult->fetch_assoc()) {
            $registeredPackageIds[] = (int) $row['package_id'];
        }
        $registeredStmt->close();

        $cartStmt = $conn->prepare("SELECT ci.item_id FROM carts c JOIN cart_items ci ON ci.cart_id = c.id AND ci.item_type = 'package' WHERE c.member_id = ? AND c.status = 'active'");
        $cartStmt->bind_param("i", $memberId);
        $cartStmt->execute();
        $cartResult = $cartStmt->get_result();

        while ($row = $cartResult->fetch_assoc()) {
            $cartPackageIds[] = (int) $row['item_id'];
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
                    <h2>Gói tập</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Gói tập</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Pricing Section Begin -->
<section class="pricing-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Gói tập của chúng tôi</span>
                    <h2>CHỌN GÓI TẬP PHÙ HỢP VỚI BẠN</h2>
                </div>
            </div>
        </div>
        <div class="row justify-content-center">
            <?php if (!empty($packages)): ?>
                <?php foreach ($packages as $package): ?>
                    <?php
                    $packageId = (int) $package['id'];
                    $isRegistered = in_array($packageId, $registeredPackageIds, true);
                    $isInCart = in_array($packageId, $cartPackageIds, true);
                    $buttonLabel = 'Thêm vào giỏ hàng';
                    $buttonClass = 'primary-btn pricing-btn';
                    $buttonDisabled = '';

                    if ($isRegistered) {
                        $buttonLabel = 'Đang sử dụng';
                        $buttonClass .= ' disabled';
                        $buttonDisabled = 'disabled';
                    } elseif ($isInCart) {
                        $buttonLabel = 'Đã có trong giỏ';
                        $buttonClass .= ' disabled';
                        $buttonDisabled = 'disabled';
                    }

                    $benefits = preg_split('/\r\n|\r|\n/', trim((string) $package['description']));
                    if (count(array_filter($benefits)) <= 1) {
                        $benefits = [
                            'Sử dụng phòng tập trong toàn bộ thời hạn gói',
                            'Thiết bị tập luyện đầy đủ và không giới hạn giờ mở cửa',
                            'Hỗ trợ tư vấn tập luyện từ đội ngũ phòng gym',
                        ];
                    }
                    ?>
                    <div class="col-lg-4 col-md-8 mb-4" id="package-<?php echo $packageId; ?>">
                        <div class="ps-item h-100">
                            <h3><?php echo htmlspecialchars($package['package_name']); ?></h3>
                            <div class="pi-price">
                                <h2><?php echo number_format((float) $package['price'], 0, ',', '.'); ?>đ</h2>
                                <span><?php echo (int) $package['duration_months']; ?> THÁNG</span>
                            </div>
                            <ul>
                                <?php foreach ($benefits as $benefit): ?>
                                    <?php if (trim($benefit) !== ''): ?>
                                        <li><?php echo htmlspecialchars(trim($benefit)); ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button"
                                    class="<?php echo $buttonClass; ?>"
                                    onclick="addPackageToCart(<?php echo $packageId; ?>)"
                                    <?php echo $buttonDisabled; ?>>
                                <?php echo $buttonLabel; ?>
                            </button>
                            <a href="my-packages.php" class="thumb-icon" title="Xem gói tập của tôi"><i class="fa fa-ticket"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-lg-8">
                    <div class="alert alert-warning text-center">Hiện chưa có gói tập nào đang mở bán.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Pricing Section End -->

<!-- Banner Section Begin -->
<section class="banner-section set-bg" data-setbg="assets/img/banner-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="bs-text">
                    <h2>Đăng ký ngay để nhận thêm ưu đãi</h2>
                    <div class="bt-tips">Nơi sức khỏe, sắc đẹp và thể hình gặp nhau.</div>
                    <a href="contact.php" class="primary-btnbtn-normal">Liên hệ ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End -->

<script>
function addPackageToCart(packageId) {
    var formData = new FormData();
    formData.append('item_type', 'package');
    formData.append('package_id', packageId);
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

<?php include 'layout/footer.php'; ?>
