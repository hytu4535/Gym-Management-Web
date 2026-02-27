<?php 
require_once '../config/db.php';

// Query để lấy sản phẩm - CHỈ LẤY STATUS = 'active'
$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active' 
        ORDER BY p.id DESC";
$result = $conn->query($sql);

// Query danh mục
$cat_sql = "SELECT * FROM categories ORDER BY name ASC";
$categories = $conn->query($cat_sql);

include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
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
<!-- Breadcrumb Section End -->

<!-- Products Section Begin -->
<section class="products-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 col-md-4">
                <div class="filter-widget">
                    <h4 class="fw-title">Danh mục</h4>
                    <ul class="filter-catagories">
                        <li><a href="products.php">Tất cả sản phẩm</a></li>
                        <?php 
                        if ($categories && $categories->num_rows > 0) {
                            while($cat = $categories->fetch_assoc()) {
                                echo "<li><a href='products.php?category_id={$cat['id']}'>{$cat['name']}</a></li>";
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
                            $imgPath = $product['img'] ? "../assets/uploads/products/{$product['img']}" : "../assets/uploads/products/default-product.jpg";
                            $price = number_format($product['selling_price'], 0, ',', '.');
                    ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="product-item">
                            <div class="pi-pic">
                                <img src="<?php echo $imgPath; ?>" alt="<?php echo $product['name']; ?>" style="width: 100%; height: 250px; object-fit: cover;">
                                <div class="pi-links">
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="add-card">
                                        <i class="fa fa-shopping-cart"></i><span>Thêm vào giỏ</span>
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
                        echo '<div class="col-12"><p class="text-center">Không có sản phẩm nào.</p></div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Products Section End -->

<?php include 'layout/footer.php'; ?>
