<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';

// Load danh mục cho select box
$cat_result = $conn->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name ASC");
$categories = [];
if ($cat_result) {
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }
}

$init_keyword = isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : '';

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Tìm kiếm sản phẩm</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Tìm kiếm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Search Section Begin -->
<section class="search-section spad">
    <div class="container">

        <!-- Form tìm kiếm nâng cao -->
        <div class="row">
            <div class="col-lg-12">
                <div class="search-form" style="background:#f9f9f9; padding:25px; border-radius:8px; margin-bottom:30px;">
                    <h4 style="margin-bottom:20px;"><i class="fa fa-search"></i> Tìm kiếm </h4>
                    <form id="advanced-search-form">
                        <div class="row">
                            <!-- Ô tìm kiếm cơ bản có autocomplete -->
                            <div class="col-lg-4 col-md-6 mb-3" style="position:relative;">
                                <input type="text" id="search-keyword" name="keyword"
                                       placeholder="Tên sản phẩm..."
                                       value="<?php echo $init_keyword; ?>"
                                       autocomplete="off"
                                        class="form-control">
                                <!-- Dropdown gợi ý tìm kiếm cơ bản -->
                                <div id="basic-suggestions" style="
                                    display:none; position:absolute; top:100%; left:15px; right:15px;
                                    background:#fff; border:1px solid #ddd; border-top:none;
                                    border-radius:0 0 6px 6px; z-index:999;
                                    max-height:300px; overflow-y:auto; box-shadow:0 4px 12px rgba(0,0,0,0.1);
                                "></div>
                            </div>
                            <div class="col-lg-3 col-md-6 mb-3">
                                <select id="search-category" name="category" class="form-control">
                                    <option value="">-- Tất cả danh mục --</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <input type="number" id="search-min-price" name="min_price"
                                       placeholder="Giá từ (VNĐ)" class="form-control"
                                       min="0">
                            </div>
                            <div class="col-lg-2 col-md-4 mb-3">
                                <input type="number" id="search-max-price" name="max_price"
                                       placeholder="Giá đến (VNĐ)" class="form-control"
                                       min="0">
                            </div>
                            <div class="col-lg-1 col-md-4 mb-3">
                                <button type="submit" class="btn btn-primary w-100" style="padding:10px; background:#e7ab3c; border-color:#e7ab3c;">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>

                            <div class="col-12">
                                <div class="quick-price-wrap" id="quick-price-wrap">
                                    <span class="quick-price-label">Gợi ý giá:</span>
                                    <button type="button" class="quick-price-btn" data-min="" data-max="200000">Dưới 200k</button>
                                    <button type="button" class="quick-price-btn" data-min="200000" data-max="500000">200k - 500k</button>
                                    <button type="button" class="quick-price-btn" data-min="500000" data-max="1000000">500k - 1 triệu</button>
                                    <button type="button" class="quick-price-btn" data-min="1000000" data-max="">Trên 1 triệu</button>
                                    <button type="button" class="quick-price-btn clear" data-min="" data-max="">Bỏ lọc giá</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Khu vực hiển thị kết quả -->
        <div class="row">
            <div class="col-lg-12">
                <div id="search-results" style="display:none;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                        <h5 style="margin:0;">Kết quả tìm kiếm: <span id="result-count" style="color:#e7ab3c; font-weight:bold;">0</span> sản phẩm</h5>
                        <span id="page-info" style="font-size:14px; color:#888;"></span>
                    </div>
                    <div class="row" id="results-container"></div>
                </div>

                <!-- Thông báo không có kết quả -->
                <div id="no-results" style="display:none; text-align:center; padding:60px 0;">
                    <i class="fa fa-search" style="font-size:48px; color:#ccc; margin-bottom:15px;"></i>
                    <p style="font-size:18px; color:#888;">Không tìm thấy sản phẩm phù hợp.</p>
                </div>

                <!-- Trạng thái đang tải -->
                <div id="loading-state" style="display:none; text-align:center; padding:60px 0;">
                    <i class="fa fa-spinner fa-spin" style="font-size:36px; color:#e7ab3c;"></i>
                    <p style="margin-top:10px; color:#888;">Đang tìm kiếm...</p>
                </div>

                <!-- Màn hình mặc định khi chưa tìm kiếm -->
                <div id="default-state" style="text-align:center; padding:60px 0;">
                    <i class="fa fa-search" style="font-size:64px; color:#ddd; margin-bottom:20px;"></i>
                    <p style="font-size:16px; color:#aaa;">Nhập từ khóa hoặc chọn bộ lọc để tìm kiếm sản phẩm.</p>
                </div>
            </div>
        </div>

        <!-- Phân trang -->
        <div class="row">
            <div class="col-lg-12">
                <div class="pagination-wrap" id="pagination" style="margin-top:30px;"></div>
            </div>
        </div>

    </div>
</section>
<!-- Search Section End -->

<style>
.search-form .form-control {
    height: 50px;
    padding: 10px 14px;
    font-family: "Muli", sans-serif;
    font-size: 16px;
    line-height: 1.4;
}

.search-form select.form-control {
    padding-top: 0;
    padding-bottom: 0;
    padding-right: 36px;
    line-height: 48px;
}

.search-form select.form-control option {
    font-family: "Muli", sans-serif;
    font-size: 16px;
    line-height: 1.6;
}

.quick-price-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
    margin-top: 6px;
}

.quick-price-label {
    font-size: 13px;
    color: #666;
    margin-right: 4px;
}

.quick-price-btn {
    border: 1px solid #ddd;
    background: #fff;
    color: #555;
    border-radius: 20px;
    padding: 5px 12px;
    font-size: 13px;
    line-height: 1.2;
    cursor: pointer;
    transition: all 0.2s ease;
}

.quick-price-btn:hover,
.quick-price-btn.active {
    border-color: #e7ab3c;
    background: #e7ab3c;
    color: #fff;
}

.quick-price-btn.clear {
    border-style: dashed;
}

.search-product-card {
    background: #fff;
    border-radius: 6px;
    overflow: hidden;
    margin-bottom: 25px;
    transition: all 0.3s ease;
}
.search-product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}
.search-product-card .pi-pic {
    position: relative;
    overflow: hidden;
}
.search-product-card .pi-pic img {
    width: 100%;
    height: 220px;
    object-fit: cover;
    transition: transform 0.3s ease;
}
.search-product-card:hover .pi-pic img {
    transform: scale(1.05);
}
.search-product-card .search-links {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    display: flex;
    justify-content: center;
    gap: 10px;
    padding: 10px;
    background: rgba(0,0,0,0.5);
    transform: translateY(100%);
    transition: transform 0.3s ease;
}
.search-product-card:hover .search-links {
    transform: translateY(0);
}
.search-product-card .search-links a {
    width: 38px; height: 38px;
    background: #fff;
    color: #333;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px;
    text-decoration: none;
    transition: background 0.2s;
}
.search-product-card .search-links a:hover { background: #e7ab3c; color: #fff; }
.search-product-card .pi-text {
    padding: 12px 15px;
}
.search-product-card .catagory-name {
    font-size: 12px;
    color: #999;
    text-transform: uppercase;
    margin-bottom: 4px;
}
.search-product-card h6 {
    font-size: 15px;
    margin-bottom: 8px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.search-product-card .product-price {
    color: #e7ab3c;
    font-weight: bold;
    font-size: 16px;
}
/* Gợi ý tìm kiếm cơ bản */
#basic-suggestions .suggestion-item {
    display: flex;
    align-items: center;
    padding: 10px 15px;
    cursor: pointer;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.15s;
}
#basic-suggestions .suggestion-item:hover { background: #fafafa; }
#basic-suggestions .suggestion-item img {
    width: 40px; height: 40px; object-fit: cover;
    border-radius: 4px; margin-right: 12px; flex-shrink: 0;
}
#basic-suggestions .suggestion-info { overflow: hidden; }
#basic-suggestions .suggestion-name {
    font-size: 14px; font-weight: 500;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
#basic-suggestions .suggestion-meta {
    font-size: 12px; color: #999; margin-top: 2px;
}
#basic-suggestions .suggestion-price { color: #e7ab3c; font-weight: bold; }
/* Phân trang */
#pagination .page-link {
    color: #e7ab3c;
}
#pagination .page-item.active .page-link {
    background: #e7ab3c; border-color: #e7ab3c; color: #fff;
}
</style>

<script>
(function () {
    var currentPage = 1;
    var currentSuggestions = [];
    var defaultProductImage = '../assets/uploads/products/default-product.jpg';

    function buildProductImageUrl(imgName) {
        if (!imgName) return defaultProductImage;
        if (imgName.indexOf('http://') === 0 || imgName.indexOf('https://') === 0) return imgName;
        return '../assets/uploads/products/' + String(imgName).replace(/^\/+/, '');
    }

    // ---- Tìm kiếm cơ bản (autocomplete gợi ý) ----
    var basicTimeout = null;
    document.getElementById('search-keyword').addEventListener('input', function () {
        clearTimeout(basicTimeout);
        var keyword = this.value.trim();
        if (keyword.length < 1) {
            hideSuggestions();
            return;
        }
        basicTimeout = setTimeout(function () {
            performBasicSearch(keyword);
        }, 350);
    });

    // Ẩn gợi ý khi click ra ngoài
    document.addEventListener('click', function (e) {
        if (!document.getElementById('search-keyword').contains(e.target) &&
            !document.getElementById('basic-suggestions').contains(e.target)) {
            hideSuggestions();
        }
    });

    function performBasicSearch(keyword) {
        var formData = new FormData();
        formData.append('keyword', keyword);

        fetch('ajax/search-basic.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.data.length > 0) {
                    renderSuggestions(data.data);
                } else {
                    hideSuggestions();
                }
            })
            .catch(function () { hideSuggestions(); });
    }

    function renderSuggestions(items) {
        var box = document.getElementById('basic-suggestions');
        currentSuggestions = items || [];
        var html = '';
        currentSuggestions.forEach(function (p, idx) {
            var imgSrc = buildProductImageUrl(p.img);
            var price = new Intl.NumberFormat('vi-VN').format(p.selling_price);
            html += '<div class="suggestion-item" onclick="selectSuggestion(' + idx + ')">' +
                        '<img src="' + imgSrc + '" alt="" onerror="this.onerror=null;this.src=\'' + defaultProductImage + '\'">' +
                        '<div class="suggestion-info">' +
                            '<div class="suggestion-name">' + escHtml(p.name) + '</div>' +
                            '<div class="suggestion-meta">' +
                                escHtml(p.category_name) +
                                ' &nbsp;|&nbsp; <span class="suggestion-price">' + price + ' VNĐ</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>';
        });
        box.innerHTML = html;
        box.style.display = 'block';
    }

    function hideSuggestions() {
        var box = document.getElementById('basic-suggestions');
        box.style.display = 'none';
        box.innerHTML = '';
    }

    function setCategoryByName(categoryName) {
        var categorySelect = document.getElementById('search-category');
        var normalizedTarget = String(categoryName || '').trim().toLowerCase();
        if (!normalizedTarget) {
            categorySelect.value = '';
            return;
        }

        for (var i = 0; i < categorySelect.options.length; i++) {
            var opt = categorySelect.options[i];
            if (String(opt.text || '').trim().toLowerCase() === normalizedTarget) {
                categorySelect.value = opt.value;
                return;
            }
        }

        categorySelect.value = '';
    }

    window.selectSuggestion = function (idx) {
        var selected = currentSuggestions[idx];
        if (!selected) return;

        document.getElementById('search-keyword').value = selected.name || '';
        setCategoryByName(selected.category_name || '');
        hideSuggestions();
        currentPage = 1;
        performSearch(1);
    };

    // ---- Tìm kiếm nâng cao (form submit) ----
    document.getElementById('advanced-search-form').addEventListener('submit', function (e) {
        e.preventDefault();
        hideSuggestions();
        currentPage = 1;
        performSearch(1);
    });

    document.getElementById('quick-price-wrap').addEventListener('click', function (e) {
        if (!e.target.classList.contains('quick-price-btn')) return;

        var min = e.target.getAttribute('data-min');
        var max = e.target.getAttribute('data-max');
        document.getElementById('search-min-price').value = min;
        document.getElementById('search-max-price').value = max;

        document.querySelectorAll('.quick-price-btn').forEach(function (btn) {
            btn.classList.remove('active');
        });
        e.target.classList.add('active');

        currentPage = 1;
        performSearch(1);
    });

    function performSearch(page) {
        page = page || 1;
        currentPage = page;

        var keyword  = document.getElementById('search-keyword').value.trim();
        var category = document.getElementById('search-category').value;
        var minPrice = document.getElementById('search-min-price').value;
        var maxPrice = document.getElementById('search-max-price').value;

        showLoading();

        var formData = new FormData();
        formData.append('keyword',   keyword);
        formData.append('category',  category);
        formData.append('min_price', minPrice);
        formData.append('max_price', maxPrice);
        formData.append('page',      page);

        fetch('ajax/search-advanced.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                hideLoading();
                if (data.success) {
                    renderResults(data);
                } else {
                    showNoResults();
                }
            })
            .catch(function () {
                hideLoading();
                showNoResults();
            });
    }

    function renderResults(data) {
        var container   = document.getElementById('results-container');
        var countEl     = document.getElementById('result-count');
        var pageInfoEl  = document.getElementById('page-info');
        var resultsWrap = document.getElementById('search-results');
        var noResults   = document.getElementById('no-results');
        var defaultState = document.getElementById('default-state');

        defaultState.style.display = 'none';
        noResults.style.display    = 'none';

        if (data.total === 0) {
            showNoResults();
            return;
        }

        countEl.textContent    = data.total;
        pageInfoEl.textContent = 'Trang ' + data.page + ' / ' + data.total_pages;
        resultsWrap.style.display = 'block';

        var html = '';
        data.data.forEach(function (p) {
            var imgSrc = buildProductImageUrl(p.img);
            var price = new Intl.NumberFormat('vi-VN').format(p.selling_price);

            html += '<div class="col-lg-4 col-md-6">' +
                '<div class="search-product-card">' +
                    '<div class="pi-pic">' +
                        '<img src="' + imgSrc + '" alt="' + escHtml(p.name) + '" onerror="this.onerror=null;this.src=\'' + defaultProductImage + '\'">' +
                        '<div class="search-links">' +
                            '<a href="#" onclick="addToCart(' + p.id + '); return false;" title="Thêm vào giỏ">' +
                                '<i class="fa fa-shopping-cart"></i>' +
                            '</a>' +
                            '<a href="product-detail.php?id=' + p.id + '" title="Xem chi tiết">' +
                                '<i class="fa fa-eye"></i>' +
                            '</a>' +
                        '</div>' +
                    '</div>' +
                    '<div class="pi-text">' +
                        '<div class="catagory-name">' + escHtml(p.category_name) + '</div>' +
                        '<a href="product-detail.php?id=' + p.id + '"><h6>' + escHtml(p.name) + '</h6></a>' +
                        '<div class="product-price">' + price + ' VNĐ</div>' +
                    '</div>' +
                '</div>' +
            '</div>';
        });
        container.innerHTML = html;

        renderPagination(data.page, data.total_pages);
    }

    function renderPagination(page, totalPages) {
        var pag = document.getElementById('pagination');
        if (totalPages <= 1) { pag.innerHTML = ''; return; }

        var html = '<ul class="pagination justify-content-center">';

        // Nút Trước
        html += '<li class="page-item' + (page <= 1 ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" onclick="changePage(' + (page - 1) + '); return false;">&laquo;</a></li>';

        // Số trang
        var start = Math.max(1, page - 2);
        var end   = Math.min(totalPages, page + 2);
        if (start > 1) html += '<li class="page-item disabled"><a class="page-link">...</a></li>';
        for (var i = start; i <= end; i++) {
            html += '<li class="page-item' + (i === page ? ' active' : '') + '">' +
                '<a class="page-link" href="#" onclick="changePage(' + i + '); return false;">' + i + '</a></li>';
        }
        if (end < totalPages) html += '<li class="page-item disabled"><a class="page-link">...</a></li>';

        // Nút Sau
        html += '<li class="page-item' + (page >= totalPages ? ' disabled' : '') + '">' +
            '<a class="page-link" href="#" onclick="changePage(' + (page + 1) + '); return false;">&raquo;</a></li>';

        html += '</ul>';
        pag.innerHTML = html;
    }

    window.changePage = function (page) {
        performSearch(page);
        window.scrollTo({ top: 300, behavior: 'smooth' });
    };

    // ---- Thêm vào giỏ hàng ----
    window.addToCart = function (productId) {
        var formData = new FormData();
        formData.append('product_id', productId);
        formData.append('quantity', 1);

        fetch('ajax/cart-add.php', { method: 'POST', body: formData })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    document.querySelectorAll('.cart-count, #cart-count').forEach(function (el) {
                        el.innerText = data.cart_count;
                    });
                    if (confirm(data.message + '\nBạn có muốn chuyển đến Giỏ hàng để thanh toán không?')) {
                        window.location.href = 'cart.php';
                    }
                } else {
                    alert('Thông báo: ' + data.message);
                    if (data.redirect) window.location.href = data.redirect;
                }
            })
            .catch(function () { alert('Có lỗi khi thêm vào giỏ hàng.'); });
    };

    // ---- Tiện ích ----
    function showLoading() {
        document.getElementById('default-state').style.display   = 'none';
        document.getElementById('no-results').style.display      = 'none';
        document.getElementById('search-results').style.display  = 'none';
        document.getElementById('pagination').innerHTML           = '';
        document.getElementById('loading-state').style.display   = 'block';
    }
    function hideLoading() {
        document.getElementById('loading-state').style.display = 'none';
    }
    function showNoResults() {
        document.getElementById('search-results').style.display = 'none';
        document.getElementById('pagination').innerHTML         = '';
        document.getElementById('no-results').style.display    = 'block';
    }
    function escHtml(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    // Tự động tìm nếu có keyword từ URL
    <?php if ($init_keyword !== ''): ?>
    performSearch(1);
    <?php endif; ?>
}());
</script>

<?php include 'layout/footer.php'; ?>
