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

// Tính giá sau giảm theo tier
$price_info = calculateDiscountedPrice($product['selling_price'], $user_id, $conn);

include 'layout/header.php'; 
?>

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
                    <img class="product-big-img" src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="width: 100%; max-height: 500px; object-fit: contain;">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="product-details">
                    <div class="pd-title">
                        <span><?php echo $product['category_name'] ?? 'Chưa phân loại'; ?></span>
                        <h3><?php echo $product['name']; ?></h3>
                    </div>
                    <?php if ($price_info['has_discount']): ?>
                    <div class="alert alert-info" style="background: #e7f3ff; border-left: 4px solid #2196F3; padding: 10px 15px; margin-bottom: 20px; color: #0066cc;">
                        <i class="fa fa-gift"></i> <strong>Hạng <?php echo $price_info['tier_name']; ?></strong> -
                        Giảm ngay <?php echo number_format($price_info['discount_percent'], 0); ?>%
                        (Tiết kiệm <?php echo number_format($price_info['discount_amount'], 0, ',', '.'); ?>đ)
                    </div>
                    <?php endif; ?>
                    <div class="pd-desc">
                        <?php if ($price_info['has_discount']): ?>
                            <h4 style="text-decoration: line-through; color: #999; font-size: 20px; margin-bottom: 5px;">
                                <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?> VNĐ
                            </h4>
                            <h4 style="color: #e7ab3c; font-size: 28px; font-weight: bold;">
                                <?php echo number_format($price_info['final_price'], 0, ',', '.'); ?> VNĐ
                                <span style="background: #ff4444; color: white; padding: 5px 10px; border-radius: 3px; font-size: 16px; margin-left: 10px;">
                                    -<?php echo number_format($price_info['discount_percent'], 0); ?>%
                                </span>
                            </h4>
                        <?php else: ?>
                            <h4 style="color: #e7ab3c; font-size: 28px; font-weight: bold;">
                                <?php echo number_format($product['selling_price'], 0, ',', '.'); ?> VNĐ
                            </h4>
                        <?php endif; ?>
                        <p style="color: #666; font-weight: 500;"><strong>Đơn vị:</strong> <?php echo $unit; ?></p>
                        <p style="color: #666; font-weight: 500;"><strong>Còn lại:</strong> <?php echo $stock; ?> <?php echo $unit; ?></p>
                    </div>
                    <div class="quantity">
                        <div class="pro-qty">
                            <input type="number" value="1" min="1" max="<?php echo $stock; ?>" id="quantity">
                        </div>
                        <a href="#" class="primary-btn pd-cart" onclick="addToCart(<?php echo $product['id']; ?>); return false;">Thêm vào giỏ</a>
                    </div>
                    <ul class="pd-tags">
                        <li style="color: #666;"><span style="color: #111; font-weight: 600;">DANH MỤC</span>: <?php echo $product['category_name'] ?? 'Chưa phân loại'; ?></li>
                        <li style="color: #666;"><span style="color: #111; font-weight: 600;">MÃ SẢN PHẨM</span>: #SP<?php echo str_pad($product['id'], 4, '0', STR_PAD_LEFT); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="product-details-tab">
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tabs-1" role="tab">Mô tả chi tiết</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabs-2" role="tab">Thông số kỹ thuật</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabs-3" role="tab">Đánh giá (5)</a>
                </li>
            </ul>
            <div class="tab-content">
                <div class="tab-pane active" id="tabs-1" role="tabpanel">
                    <div class="product-content">
                        <p style="color: #666;"><?php echo $product['description'] ?? 'Chưa có mô tả cho sản phẩm này.'; ?></p>
                    </div>
                </div>
                <div class="tab-pane" id="tabs-2" role="tabpanel">
                    <div class="product-content">
                        <p style="color: #666;">Đang cập nhật...</p>
                    </div>
                </div>
                <div class="tab-pane" id="tabs-3" role="tabpanel">
                    <div class="product-content">
                        <p style="color: #666;">Đang cập nhật...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<script>

function addToCart(productId) {
    var quantity = document.getElementById('quantity').value;
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