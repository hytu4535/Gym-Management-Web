<?php 
require_once '../config/db.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

if ($category_id > 0) {
    $sql .= " AND p.category_id = " . $category_id;
}

$sql .= " ORDER BY p.id DESC";
$result = $conn->query($sql);

$cat_sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
$categories = $conn->query($cat_sql);

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
                        if ($categories && $categories->num_rows > 0) {
                            while($cat = $categories->fetch_assoc()) {
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
                    if ($result && $result->num_rows > 0) {
                        while($product = $result->fetch_assoc()) {
                            $imageFile = $product['img'] ?? null;
                            $imgPath = $imageFile ? "../assets/uploads/products/{$imageFile}" : "../assets/uploads/products/default-product.jpg";
                            $price = number_format($product['selling_price'] ?? 0, 0, ',', '.');
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="product-item">
                            <div class="pi-pic">
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="width: 100%; height: 250px; object-fit: cover;">
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
                                <p><?php echo $price; ?> VNĐ</p>
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