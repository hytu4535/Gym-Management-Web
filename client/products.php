<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/db.php';
require_once '../includes/discount_helper.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$keyword = trim($_GET['keyword'] ?? '');
$min_price = (isset($_GET['min_price']) && is_numeric($_GET['min_price'])) ? (float)$_GET['min_price'] : null;
$max_price = (isset($_GET['max_price']) && is_numeric($_GET['max_price'])) ? (float)$_GET['max_price'] : null;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

$sql = "SELECT p.*, c.name AS category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.status = 'active'";

if ($category_id > 0) {
    $sql .= " AND p.category_id = " . $category_id;
}

if ($keyword !== '') {
    $escapedKeyword = $conn->real_escape_string($keyword);
    $sql .= " AND p.name LIKE '%{$escapedKeyword}%'";
}

if ($min_price !== null) {
    $sql .= " AND p.selling_price >= " . $min_price;
}

if ($max_price !== null) {
    $sql .= " AND p.selling_price <= " . $max_price;
}

$sql .= " ORDER BY p.id DESC";
$products_result = $conn->query($sql);

$cat_sql = "SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC";
$categories_result = $conn->query($cat_sql);
$categories = [];
if ($categories_result && $categories_result->num_rows > 0) {
    while ($cat = $categories_result->fetch_assoc()) {
        $categories[] = $cat;
    }
}

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
        <style>
        .search-on-products { position: relative; }
        .search-on-products .form-control {
            background: #1e1e1e;
            border: 1px solid #333;
            color: #e0e0e0;
            border-radius: 6px;
        }
        .search-on-products .form-control::placeholder { color: #666; }
        .search-on-products .form-control:focus {
            background: #252525;
            border-color: #f36100;
            color: #fff;
            box-shadow: 0 0 0 2px rgba(243,97,0,.2);
        }
        .search-on-products select.form-control option { background: #1e1e1e; color: #e0e0e0; }
        .keyword-suggest-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: #1a1a1a;
            border: 1px solid #333;
            border-top: none;
            border-radius: 0 0 8px 8px;
            z-index: 999;
            max-height: 220px;
            overflow-y: auto;
            box-shadow: 0 6px 20px rgba(0,0,0,.5);
            display: none;
        }
        .keyword-suggest-list li {
            list-style: none;
            padding: 9px 14px;
            cursor: pointer;
            font-size: 14px;
            color: #ccc;
            border-bottom: 1px solid #2a2a2a;
        }
        .keyword-suggest-list li:last-child { border-bottom: none; }
        .keyword-suggest-list li:hover, .keyword-suggest-list li.active { background: #2a2a2a; color: #f36100; }
        .quick-price-btns { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
        .quick-price-btns .qp-btn {
            font-size: 12px;
            padding: 3px 11px;
            border: 1px solid #f36100;
            background: transparent;
            color: #f36100;
            border-radius: 20px;
            cursor: pointer;
            transition: background .18s, color .18s;
        }
        .quick-price-btns .qp-btn:hover { background: #f36100; color: #fff; }
        </style>
        <div class="search-on-products" style="background:#111; border-radius:10px; padding:18px; margin-bottom:20px; border:1px solid #2a2a2a;">
            <h5 style="margin-bottom:14px; color:#f36100;"><i class="fa fa-search"></i> Tìm kiếm</h5>
            <form id="products-search-form" action="products.php" method="GET">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-2" style="position:relative;">
                        <input type="text" id="products-keyword" name="keyword" class="form-control" placeholder="Tên sản phẩm..."
                               value="<?php echo htmlspecialchars($keyword); ?>" autocomplete="off">
                        <ul class="keyword-suggest-list" id="keyword-suggest-list"></ul>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-2">
                        <select name="category_id" class="form-control">
                            <option value="">-- Tất cả danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat['id']; ?>" <?php echo ($category_id == (int)$cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <input type="number" id="products-min-price" name="min_price" class="form-control" min="0" placeholder="Giá từ (VNĐ)"
                               value="<?php echo ($min_price !== null) ? (int)$min_price : ''; ?>">
                    </div>
                    <div class="col-lg-2 col-md-4 mb-2">
                        <input type="number" id="products-max-price" name="max_price" class="form-control" min="0" placeholder="Giá đến (VNĐ)"
                               value="<?php echo ($max_price !== null) ? (int)$max_price : ''; ?>">
                    </div>
                    <div class="col-lg-1 col-md-4 mb-2">
                        <button type="submit" class="btn btn-warning w-100"><i class="fa fa-search"></i></button>
                    </div>
                </div>
                <div class="quick-price-btns">
                    <span style="font-size:12px;color:#666;align-self:center;">Gợi ý giá:</span>
                    <button type="button" class="qp-btn" data-min="" data-max="200000">Dưới 200K</button>
                    <button type="button" class="qp-btn" data-min="200000" data-max="500000">200K – 500K</button>
                    <button type="button" class="qp-btn" data-min="500000" data-max="1000000">500K – 1TR</button>
                    <button type="button" class="qp-btn" data-min="1000000" data-max="3000000">1TR – 3TR</button>
                    <button type="button" class="qp-btn" data-min="3000000" data-max="">Trên 3TR</button>
                </div>
            </form>
        </div>
        <script>
        (function(){
            var $input   = document.getElementById('products-keyword');
            var $list    = document.getElementById('keyword-suggest-list');
            var $minP    = document.getElementById('products-min-price');
            var $maxP    = document.getElementById('products-max-price');
            var $form    = document.getElementById('products-search-form');
            var debounce, activeIdx = -1;

            /* ---- autocomplete ---- */
            $input.addEventListener('input', function(){
                clearTimeout(debounce);
                var q = this.value.trim();
                if (q.length < 1) { hideList(); return; }
                debounce = setTimeout(function(){ fetchSuggestions(q); }, 220);
            });

            $input.addEventListener('keydown', function(e){
                var items = $list.querySelectorAll('li');
                if (!items.length || $list.style.display === 'none') return;
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    activeIdx = Math.min(activeIdx + 1, items.length - 1);
                    highlightItem(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    activeIdx = Math.max(activeIdx - 1, -1);
                    highlightItem(items);
                } else if (e.key === 'Enter' && activeIdx >= 0) {
                    e.preventDefault();
                    $input.value = items[activeIdx].textContent;
                    hideList();
                    $form.submit();
                } else if (e.key === 'Escape') {
                    hideList();
                }
            });

            document.addEventListener('click', function(e){
                if (e.target !== $input) hideList();
            });

            function fetchSuggestions(q) {
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'ajax/search-basic.php?keyword=' + encodeURIComponent(q), true);
                xhr.onload = function(){
                    if (xhr.status !== 200) return;
                    var res; try { res = JSON.parse(xhr.responseText); } catch(e){ return; }
                    if (!res.success || !res.data.length) { hideList(); return; }
                    renderList(res.data);
                };
                xhr.send();
            }

            function renderList(items) {
                $list.innerHTML = '';
                activeIdx = -1;
                items.forEach(function(item){
                    var li = document.createElement('li');
                    li.textContent = item.name;
                    li.addEventListener('mousedown', function(e){
                        e.preventDefault();
                        $input.value = item.name;
                        hideList();
                        $form.submit();
                    });
                    $list.appendChild(li);
                });
                $list.style.display = 'block';
            }

            function highlightItem(items) {
                Array.prototype.forEach.call(items, function(li, i){
                    li.classList.toggle('active', i === activeIdx);
                });
                if (activeIdx >= 0) $input.value = items[activeIdx].textContent;
            }

            function hideList() { $list.style.display = 'none'; $list.innerHTML = ''; activeIdx = -1; }

            /* ---- quick price buttons ---- */
            document.querySelectorAll('.qp-btn').forEach(function(btn){
                btn.addEventListener('click', function(){
                    $minP.value = this.dataset.min;
                    $maxP.value = this.dataset.max;
                    // highlight active
                    document.querySelectorAll('.qp-btn').forEach(function(b){ b.style.background=''; b.style.color=''; });
                    this.style.background = '#e7ab3c';
                    this.style.color = '#fff';
                });
            });
        })();
        </script>

        <?php if ($keyword !== '' || $category_id > 0 || $min_price !== null || $max_price !== null): ?>
        <div class="alert alert-info" style="border-left: 4px solid #e7ab3c; background: #fffdf8; color: #444; margin-bottom: 20px;">
            <strong>Bộ lọc hiện tại:</strong>
            <?php if ($keyword !== ''): ?>
                <span style="margin-left:8px;">Từ khóa: <strong><?php echo htmlspecialchars($keyword); ?></strong></span>
            <?php endif; ?>
            <?php if ($category_id > 0):
                $selected_cat_name = '';
                foreach ($categories as $c) { if ((int)$c['id'] === $category_id) { $selected_cat_name = $c['name']; break; } }
            ?>
                <span style="margin-left:8px;">Danh mục: <strong><?php echo htmlspecialchars($selected_cat_name ?: $category_id); ?></strong></span>
            <?php endif; ?>
            <?php if ($min_price !== null): ?>
                <span style="margin-left:8px;">Giá từ: <strong><?php echo number_format($min_price, 0, ',', '.'); ?> VNĐ</strong></span>
            <?php endif; ?>
            <?php if ($max_price !== null): ?>
                <span style="margin-left:8px;">Giá đến: <strong><?php echo number_format($max_price, 0, ',', '.'); ?> VNĐ</strong></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
            <div class="col-lg-12">
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