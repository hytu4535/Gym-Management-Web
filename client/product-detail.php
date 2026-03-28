
<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';
require_once '../includes/discount_helper.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($product_id == 0) {
    header('Location: products.php');
    exit;
}
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = $product_id AND p.status = 'active'";
$product_result = $conn->query($sql);

if ($product_result->num_rows == 0) {
    echo "<script>alert('Sản phẩm không tồn tại hoặc đã ngừng kinh doanh!'); window.location.href='products.php';</script>";
    exit;
}

$product = $product_result->fetch_assoc();

$imageFile = $product['img'] ?? null;
$imgPath = $imageFile ? "../assets/uploads/products/{$imageFile}" : "../assets/uploads/products/default-product.jpg";

$unit = $product['unit'] ?? 'Sản phẩm';
$stock = $product['stock_quantity'] ?? 0;
$short_description = $product['short_description'] ?? '';
$detail_description = trim($product['description'] ?? '');
$display_description = $short_description !== '' ? $short_description : $detail_description;
$review_count = 0;
$review_avg = 0.0;
$rating = isset($product['rating']) ? (float) $product['rating'] : 0.0;
$review_items = [];
$hasReviewTable = false;
$reviewTableCheck = $conn->query("SHOW TABLES LIKE 'product_reviews'");
if ($reviewTableCheck && $reviewTableCheck->num_rows > 0) {
    $hasReviewTable = true;

    $stmt_review_stats = $conn->prepare("SELECT COUNT(*) AS review_count, ROUND(AVG(rating), 1) AS avg_rating FROM product_reviews WHERE product_id = ? AND status = 'approved'");
    $stmt_review_stats->bind_param('i', $product_id);
    $stmt_review_stats->execute();
    $review_stats_result = $stmt_review_stats->get_result();
    if ($review_stats_result && ($review_stats = $review_stats_result->fetch_assoc())) {
        $review_count = (int) ($review_stats['review_count'] ?? 0);
        $review_avg = (float) ($review_stats['avg_rating'] ?? 0);
    }
    $stmt_review_stats->close();

    $stmt_review_items = $conn->prepare("SELECT pr.rating, pr.content, pr.created_at, m.full_name FROM product_reviews pr JOIN members m ON m.id = pr.member_id WHERE pr.product_id = ? AND pr.status = 'approved' ORDER BY pr.created_at DESC LIMIT 6");
    $stmt_review_items->bind_param('i', $product_id);
    $stmt_review_items->execute();
    $review_items_result = $stmt_review_items->get_result();
    while ($review_row = $review_items_result->fetch_assoc()) {
        $review_items[] = $review_row;
    }
    $stmt_review_items->close();
}

if ($review_count > 0) {
    $rating = $review_avg;
}

// Tính giá sau giảm theo tier
$price_info = calculateDiscountedPrice($product['selling_price'], $user_id, $conn);

include 'layout/header.php'; 
?>

<style>
    /* Chỉnh chữ phần Banner (Breadcrumb) nổi bật trên ảnh nền */
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

    /* Đảm bảo phần thông tin nền trắng, chữ đen/xám dễ đọc */
    .product-details-section { 
        background-color: #ffffff; 
        color: #333333; 
        padding-top: 50px;
    }
    .product-details .pd-title h3 { 
        color: #111111; 
        font-weight: 700; 
        margin-top: 5px; 
        margin-bottom: 15px; 
    }
    .product-details .pd-title span { 
        color: #e7ab3c; 
        font-weight: bold; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
    }
    .product-rating-summary {
        display: flex;
        align-items: center;
        gap: 14px;
        margin: 10px 0 18px;
        flex-wrap: wrap;
    }
    .product-rating-score {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #f36100;
        font-weight: 700;
    }
    .product-rating-score .stars {
        letter-spacing: 2px;
        font-size: 18px;
    }
    .review-summary-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 999px;
        background: #fff4e8;
        color: #9a4b00;
        font-size: 13px;
        font-weight: 600;
    }
    .review-list {
        display: grid;
        gap: 14px;
        margin-top: 18px;
    }
    .review-item {
        background: #fff;
        border: 1px solid #e8e8e8;
        border-radius: 10px;
        padding: 16px 18px;
    }
    .review-item-head {
        display: flex;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 10px;
    }
    .reviewer-name { font-weight: 700; color: #111; }
    .review-stars { color: #f36100; letter-spacing: 1px; font-size: 14px; }
    .review-date { color: #888; font-size: 12px; }
    .review-empty {
        background: #f8f9fb;
        border: 1px dashed #d7dce3;
        border-radius: 10px;
        padding: 24px;
        text-align: center;
        color: #666;
    }
    .product-details .pd-desc p { 
        color: #444444; 
        font-size: 16px; 
    }
    .product-details .pd-tags li { 
        color: #555555; 
    }
    .product-details .pd-tags li span { 
        color: #111111; 
        font-weight: bold; 
    }

    /* Chỉnh nút thêm vào giỏ và ô số lượng ngang hàng, gọn gàng */
    .product-details .quantity { 
        display: flex; 
        align-items: center; 
        gap: 15px; 
        margin-top: 20px; 
        margin-bottom: 30px; 
    }
    .product-details .pro-qty input { 
        width: 70px; 
        height: 45px; 
        text-align: center; 
        border: 1px solid #ccc; 
        border-radius: 4px; 
        font-weight: bold; 
        color: #333; 
        font-size: 16px;
    }
    .product-details .primary-btn.pd-cart { 
        background: #e7ab3c; 
        color: #fff; 
        padding: 12px 30px; 
        border-radius: 4px; 
        font-weight: bold; 
        text-transform: uppercase; 
        border: none; 
        transition: all 0.3s ease; 
        text-decoration: none;
    }
    .product-details .primary-btn.pd-cart:hover { 
        background: #333333; 
        color: #ffffff; 
    }

    /* Phần Tabs ở dưới */
    .product-details-tab .nav-tabs .nav-link { 
        color: #666666; 
        font-weight: bold; 
    }
    .product-details-tab .nav-tabs .nav-link.active { 
        color: #e7ab3c; 
    }
    .product-details-tab .tab-content { 
        padding-top: 30px; 
        color: #444444; 
        line-height: 1.8; 
    }
</style>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Chi tiết sản phẩm</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <a href="products.php">Sản phẩm</a>
                        <span>Chi tiết</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="product-details-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="product-pic-zoom">
                    <img class="product-big-img" src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="width: 100%; max-height: 500px; object-fit: contain; border: 1px solid #f0f0f0; border-radius: 8px; padding: 10px;">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="product-details">
                    <div class="pd-title">
                        <span><?php echo $product['category_name'] ?? 'Chưa phân loại'; ?></span>
                        <h3><?php echo $product['name']; ?></h3>
                    </div>
                    <div class="product-rating-summary">
                        <div class="product-rating-score">
                            <span class="stars"><?php echo str_repeat('★', (int) round($rating)); ?><?php echo str_repeat('☆', max(0, 5 - (int) round($rating))); ?></span>
                            <span><?php echo number_format($rating, 1); ?>/5</span>
                        </div>
                        <span class="review-summary-pill"><i class="fa fa-comment-o"></i> <?php echo $review_count; ?> lượt đánh giá</span>
                    </div>
                    <?php if ($price_info['has_discount']): ?>
                    <div class="alert alert-info" style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 10px 15px; margin-bottom: 20px; color: #0c5460;">
                        <i class="fa fa-gift"></i> <strong>Hạng <?php echo $price_info['tier_name']; ?></strong> - 
                        Giảm ngay <?php echo number_format($price_info['discount_percent'], 0); ?>% 
                        (Tiết kiệm <?php echo number_format($price_info['discount_amount'], 0, ',', '.'); ?>đ)
                    </div>
                    <?php endif; ?>
                    <div class="pd-desc">
                        <?php if (!empty($short_description)): ?>
                            <p><strong>Mô tả ngắn:</strong> <?php echo htmlspecialchars($short_description); ?></p>
                        <?php endif; ?>
                        <?php if ($price_info['has_discount']): ?>
                            <h4 style="text-decoration: line-through; color: #999; font-size: 20px; margin-bottom: 5px;">
                                <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?> VNĐ
                            </h4>
                            <h4 style="color: #e7ab3c; font-size: 28px; font-weight: bold; margin-bottom: 15px;">
                                <?php echo number_format($price_info['final_price'], 0, ',', '.'); ?> VNĐ
                                <span style="background: #ff4444; color: white; padding: 5px 10px; border-radius: 3px; font-size: 16px; margin-left: 10px; vertical-align: middle;">
                                    -<?php echo number_format($price_info['discount_percent'], 0); ?>%
                                </span>
                            </h4>
                        <?php else: ?>
                            <h4 style="color: #e7ab3c; font-size: 28px; font-weight: bold; margin-bottom: 15px;">
                                <?php echo number_format($product['selling_price'], 0, ',', '.'); ?> VNĐ
                            </h4>
                        <?php endif; ?>
                        <p><strong>Đơn vị:</strong> <?php echo $unit; ?></p>
                        <p><strong>Còn lại:</strong> <?php echo $stock; ?> <?php echo $unit; ?></p>
                    </div>
                    <div class="quantity">
                        <div class="pro-qty">
                            <input type="number" value="1" min="1" max="<?php echo $stock; ?>" id="quantity">
                        </div>
                        <a href="#" class="primary-btn pd-cart" onclick="addToCart(<?php echo $product['id']; ?>); return false;">Thêm vào giỏ</a>
                    </div>
                    <ul class="pd-tags" style="list-style: none; padding-left: 0; margin-top: 20px; border-top: 1px solid #ebebeb; padding-top: 20px;">
                        <li style="margin-bottom: 10px;"><span>DANH MỤC</span>: <?php echo $product['category_name'] ?? 'Chưa phân loại'; ?></li>
                        <li><span>MÃ SẢN PHẨM</span>: #SP<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="product-details-tab mt-5">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab">Mô tả chi tiết</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabs-3" role="tab">Đánh giá (<?php echo $review_count; ?>)</a>
                </li>
            </ul>
            <div class="tab-content" style="background: #fff; padding: 20px; border: 1px solid #dee2e6; border-top: none;">
                <div class="tab-pane active" id="tabs-1" role="tabpanel">
                    <div class="product-content">
                        <?php if ($display_description !== ''): ?>
                            <p><strong>Mô tả ngắn:</strong> <?php echo nl2br(htmlspecialchars($display_description)); ?></p>
                        <?php endif; ?>
                        <?php if ($detail_description !== '' && $detail_description !== $display_description): ?>
                            <div style="margin-top: 16px;">
                                <h5 style="font-weight:700; color:#111; margin-bottom: 10px;">Mô tả chi tiết</h5>
                                <p><?php echo nl2br(htmlspecialchars($detail_description)); ?></p>
                            </div>
                        <?php endif; ?>
                        <?php if ($display_description === '' && $detail_description === ''): ?>
                            <p>Chưa có mô tả cho sản phẩm này.</p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="tab-pane" id="tabs-3" role="tabpanel">
                    <div class="product-content">
                        <div class="mb-3" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                            <h5 style="margin:0; font-weight:700; color:#111;">Đánh giá từ hội viên</h5>
                            <span class="review-summary-pill"><i class="fa fa-star"></i> <?php echo number_format($rating, 1); ?>/5</span>
                            <span class="review-summary-pill"><i class="fa fa-comment-o"></i> <?php echo $review_count; ?> lượt</span>
                        </div>
                        <?php if (!$hasReviewTable): ?>
                            <div class="review-empty">Hệ thống hiện chưa hỗ trợ đánh giá sản phẩm.</div>
                        <?php elseif (!empty($review_items)): ?>
                            <div class="review-list">
                                <?php foreach ($review_items as $review_item): ?>
                                    <div class="review-item">
                                        <div class="review-item-head">
                                            <div>
                                                <div class="reviewer-name"><?php echo htmlspecialchars($review_item['full_name']); ?></div>
                                                <div class="review-date"><?php echo !empty($review_item['created_at']) ? date('d/m/Y H:i', strtotime($review_item['created_at'])) : ''; ?></div>
                                            </div>
                                            <div class="review-stars"><?php echo str_repeat('★', (int) $review_item['rating']); ?><?php echo str_repeat('☆', max(0, 5 - (int) $review_item['rating'])); ?></div>
                                        </div>
                                        <div style="color:#444; line-height:1.7;">
                                            <?php echo nl2br(htmlspecialchars($review_item['content'])); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="review-empty">
                                Chưa có đánh giá nào cho sản phẩm này.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function addToCart(productId) {
    var qtyInput = document.getElementById('quantity');
    var quantity = parseInt(qtyInput.value, 10);
    var maxStock = parseInt(qtyInput.getAttribute('max'), 10); 

    if (isNaN(quantity) || quantity < 1) {
        alert('Vui lòng nhập số lượng hợp lệ. Số lượng phải lớn hơn hoặc bằng 1');
        qtyInput.value = 1;
        return; 
    }
    if (quantity > maxStock) {
        alert('Xin lỗi, sản phẩm này chỉ còn ' + maxStock + ' sản phẩm trong kho!');
        qtyInput.value = maxStock; 
        return; 
    }
    var formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    fetch('ajax/cart-add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === true) {
            if (confirm(data.message + "\nBạn có muốn chuyển đến Giỏ hàng để thanh toán không?")) {
                window.location.href = "cart.php";
            }
        } else {
            alert('Thông báo: ' + data.message);
            if (data.redirect) {
                window.location.href = data.redirect;
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra kết nối với máy chủ, vui lòng thử lại!');
    });
}
</script>

<?php include 'layout/footer.php'; ?>
