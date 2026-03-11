<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/discount_helper.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

if ($category_id > 0) {
    $sql .= " AND p.category_id = " . $category_id;
}

$sql .= " ORDER BY p.id DESC";
$products_result = $conn->query($sql);

$cat_sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
$categories_result = $conn->query($cat_sql);

include 'layout/header.php'; 
?>

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Sản phẩm</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Sản phẩm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="products-section spad">
    <div class="container">
        <?php if ($user_id > 0): 
            $tier_info = getMemberTierDiscount($user_id, $conn);
            if ($tier_info['base_discount'] > 0):
        ?>
        <div class="alert alert-success" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; padding: 15px 20px; margin-bottom: 30px;">
            <i class="fa fa-star"></i> 
            <strong>Hạng <?php echo $tier_info['tier_name']; ?></strong> - 
            Bạn đang được hưởng giảm giá <strong><?php echo number_format($tier_info['base_discount'], 0); ?>%</strong> cho tất cả sản phẩm!
        </div>
        <?php endif; endif; ?>
        
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div class="filter-widget">
                    <h4 class="fw-title">Danh mục</h4>
                    <ul class="filter-catagories">
                        <?php 
                        $active_all = ($category_id == 0) ? 'class="active-cat"' : ''; 
                        ?>
                        <li><a href="products.php" <?php echo $active_all; ?>>Tất cả sản phẩm</a></li>
                        
                        <?php 
                        if ($categories_result && $categories_result->num_rows > 0) {
                            while($cat = $categories_result->fetch_assoc()) {
                                $active_cat = ($category_id == $cat['id']) ? 'class="active-cat"' : '';
                                echo "<li><a href='products.php?category_id={$cat['id']}' {$active_cat}>{$cat['name']}</a></li>";
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
            
            <div class="col-lg-9 col-md-8">
                <div class="row" id="products-container">
                    <?php 
                    if ($products_result && $products_result->num_rows > 0) {
                        while($product = $products_result->fetch_assoc()) {
                            $imageFile = $product['img'] ?? null;
                            $imgPath = $imageFile ? "../assets/uploads/products/{$imageFile}" : "../assets/uploads/products/default-product.jpg";
                            
                            // Tính giá sau giảm theo tier
                            $price_info = calculateDiscountedPrice($product['selling_price'], $user_id, $conn);
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="product-item">
                            <div class="pi-pic">
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="width: 100%; height: 250px; object-fit: cover;">
                                <?php if ($price_info['has_discount']): ?>
                                <div class="sale-label" style="position: absolute; top: 10px; left: 10px; background: #ff4444; color: white; padding: 5px 10px; border-radius: 3px; font-weight: bold;">
                                    -<?php echo number_format($price_info['discount_percent'], 0); ?>%
                                </div>
                                <?php endif; ?>
                                <div class="pi-links">
                                    <a href="#" class="add-card" onclick="addToCart(<?php echo $product['id']; ?>); return false;">
                                        <i class="fa fa-shopping-cart"></i>
                                    </a>
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="wishlist-btn">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                            <div class="pi-text">
                                <div class="catagory-name"><?php echo $product['category_name'] ?? 'Chưa phân loại'; ?></div>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>">
                                    <h6><?php echo $product['name']; ?></h6>
                                </a>
                                <div class="product-price">
                                    <?php if ($price_info['has_discount']): ?>
                                        <span style="text-decoration: line-through !important; color: #999; font-size: 14px; margin-right: 5px;">
                                            <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?> VNĐ
                                        </span>
                                        <br>
                                        <span style="text-decoration: none !important; color: #e7ab3c; font-weight: bold; font-size: 16px;">
                                            <?php echo number_format($price_info['final_price'], 0, ',', '.'); ?> VNĐ
                                        </span>
                                    <?php else: ?>
                                        <span style="text-decoration: none !important; color: #e7ab3c; font-weight: bold; font-size: 16px;">
                                            <?php echo number_format($price_info['original_price'], 0, ',', '.'); ?> VNĐ
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        }
                    } else {
                        echo '<div class="col-12"><p class="text-center" style="margin-top: 50px; font-size: 18px; color: #666;">Không tìm thấy sản phẩm nào trong danh mục này.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
.filter-catagories li a {
    color: #333;
    transition: all 0.3s ease;
    display: inline-block;
}
.filter-catagories li a:hover {
    color: #e7ab3c;
    transform: translateX(5px);
}
.filter-catagories li a.active-cat {
    color: #e7ab3c;
    font-weight: bold;
}
.product-item {
    transition: all 0.3s ease;
    border-radius: 5px;
    background: #fff;
    padding-bottom: 10px;
}
.product-item:hover {
    transform: translateY(-5px);
    box-shadow: 0px 10px 20px rgba(0,0,0,0.08);
}
</style>

<script>
function addToCart(productId) {
    var formData = new FormData();
    formData.append('product_id', productId);
    formData.append('quantity', 1);

    fetch('ajax/cart-add.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === true) {
            var cartCountElements = document.querySelectorAll('.cart-count, #cart-count'); 
            cartCountElements.forEach(function(el) {
                el.innerText = data.cart_count;
            });
            
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
        alert('Có lỗi xảy ra kết nối với máy chủ!');
    });
}
</script>

<?php include 'layout/footer.php'; ?>