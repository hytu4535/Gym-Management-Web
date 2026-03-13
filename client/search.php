<?php include 'layout/header.php'; ?>

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
        <div class="row">
            <div class="col-lg-12">
                <div class="search-form">
                    <h4>Tìm kiếm nâng cao</h4>
                    <form id="advanced-search-form">
                        <div class="row">
                            <div class="col-lg-4">
                                <input type="text" id="search-keyword" name="keyword" placeholder="Tên sản phẩm..." value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
                            </div>
                            <div class="col-lg-3">
                                <select id="search-category" name="category" class="form-control">
                                    <option value="">-- Chọn danh mục --</option>
                                    <!-- TODO: Load danh mục từ database -->
                                </select>
                            </div>
                            <div class="col-lg-2">
                                <input type="number" id="search-min-price" name="min_price" placeholder="Giá từ..." class="form-control">
                            </div>
                            <div class="col-lg-2">
                                <input type="number" id="search-max-price" name="max_price" placeholder="Giá đến..." class="form-control">
                            </div>
                            <div class="col-lg-1">
                                <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row mt-5">
            <div class="col-lg-12">
                <div class="search-results" id="search-results">
                    <!-- TODO: Hiển thị kết quả tìm kiếm -->
                    <h5>Kết quả tìm kiếm: <span id="result-count">0</span> sản phẩm</h5>
                    <div class="row" id="results-container">
                        <!-- Results will be loaded here via AJAX -->
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="pagination-wrap" id="pagination">
                    <!-- Pagination will be loaded here via AJAX -->
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Search Section End -->

<script>
// TODO: Implement AJAX tìm kiếm nâng cao
document.getElementById('advanced-search-form').addEventListener('submit', function(e) {
    e.preventDefault();
    performSearch();
});

// TODO: Implement AJAX tìm kiếm cơ bản (tìm theo tên)
document.getElementById('search-keyword').addEventListener('keyup', function() {
    // Debounce search
    clearTimeout(window.searchTimeout);
    window.searchTimeout = setTimeout(function() {
        performBasicSearch();
    }, 500);
});

function performBasicSearch() {
    // TODO: AJAX call to ajax/search-basic.php
    console.log('Performing basic search...');
}

function performSearch(page = 1) {
    // TODO: AJAX call to ajax/search-advanced.php
    var keyword = document.getElementById('search-keyword').value;
    var category = document.getElementById('search-category').value;
    var minPrice = document.getElementById('search-min-price').value;
    var maxPrice = document.getElementById('search-max-price').value;
    
    console.log('Searching with:', {keyword, category, minPrice, maxPrice, page});
    
    // AJAX implementation here
}

// Load initial results if keyword exists
<?php if(isset($_GET['keyword'])): ?>
    performSearch();
<?php endif; ?>
</script>

<?php include 'layout/footer.php'; ?>
