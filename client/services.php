<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';

$services = [];
$activeMemberServiceIds = [];
$cartServiceIds = [];
$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;

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
        $activeServiceStmt = $conn->prepare("SELECT service_id FROM member_services WHERE member_id = ? AND status = 'còn hiệu lực'");
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
            <?php if (!empty($services)): ?>
                <?php foreach ($services as $service): ?>
                    <?php
                    $serviceId = (int) $service['id'];
                    $isActive = in_array($serviceId, $activeMemberServiceIds, true);
                    $isInCart = in_array($serviceId, $cartServiceIds, true);
                    $buttonLabel = 'Thêm vào giỏ hàng';
                    $buttonDisabled = '';
                    $serviceDescription = $service['description'] ?? 'Dịch vụ chăm sóc và hỗ trợ hội viên.';

                    if ($isActive) {
                        $buttonLabel = 'Đang sử dụng';
                        $buttonDisabled = 'disabled';
                    } elseif ($isInCart) {
                        $buttonLabel = 'Đã có trong giỏ';
                        $buttonDisabled = 'disabled';
                    }

                    $imagePath = !empty($service['img']) ? '../' . ltrim($service['img'], '/') : 'assets/img/services/services-1.jpg';
                    ?>
                    <div class="col-lg-4 col-sm-6 mb-4">
                        <div class="services-item" style="padding-bottom: 20px;">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="<?php echo htmlspecialchars($service['name']); ?>" style="height: 220px; width: 100%; object-fit: cover;">
                            <h4 style="font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars($service['name']); ?></h4>
                            <div style="font-weight: 700; color: #f36100; margin-bottom: 12px;">
                                <?php echo number_format((float) $service['price'], 0, ',', '.'); ?>đ
                                <span style="font-size: 12px; color: #999; font-weight: 600;">/ <?php echo htmlspecialchars($service['type']); ?></span>
                            </div>
                            <div class="d-flex" style="gap:8px;">
                                <button type="button"
                                        class="primary-btn"
                                        style="flex:1;"
                                        onclick="showServiceDetail('<?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?>', '<?php echo htmlspecialchars($serviceDescription, ENT_QUOTES, 'UTF-8'); ?>', '<?php echo number_format((float) $service['price'], 0, ',', '.'); ?>', '<?php echo htmlspecialchars($service['type'], ENT_QUOTES, 'UTF-8'); ?>')">
                                    Xem chi tiết
                                </button>
                                <button type="button"
                                        class="primary-btn"
                                        style="flex:1;"
                                        onclick="addServiceToCart(<?php echo $serviceId; ?>)"
                                        <?php echo $buttonDisabled; ?>>
                                    <?php echo $buttonLabel; ?>
                                </button>
                            </div>
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

<div class="modal fade" id="serviceDetailModal" tabindex="-1" role="dialog" aria-labelledby="serviceDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background:#151515; border:1px solid #2a2a2a; color:#fff;">
            <div class="modal-header" style="border-bottom:1px solid #2a2a2a;">
                <h5 class="modal-title" id="serviceDetailModalLabel" style="color:#ddd;">Chi tiết dịch vụ</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h4 id="serviceDetailName" style="font-weight:700; margin-bottom:10px; color:#ddd;">-</h4>
                <div style="font-weight:700; color:#f36100; margin-bottom:12px;">
                    <span id="serviceDetailPrice">-</span>
                </div>
                <div id="serviceDetailTypeRow" style="margin-bottom:12px; color:#ddd;">
                    <strong>Loại dịch vụ:</strong>
                    <span id="serviceDetailTypeText"></span>
                </div>
                <div id="serviceDetailDescription" style="white-space: pre-wrap; color:#ddd; line-height:1.6;">-</div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #2a2a2a;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

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
function showServiceDetail(name, description, price, type) {
    var normalizedType = (type || '').trim();

    document.getElementById('serviceDetailName').innerText = name || '-';
    document.getElementById('serviceDetailPrice').innerText = (price || '0') + 'đ';
    document.getElementById('serviceDetailDescription').innerText = description || 'Dịch vụ chăm sóc và hỗ trợ hội viên.';

    document.getElementById('serviceDetailTypeText').innerText = normalizedType;
    document.getElementById('serviceDetailTypeRow').style.display = 'block';

    $('#serviceDetailModal').modal('show');
}

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
