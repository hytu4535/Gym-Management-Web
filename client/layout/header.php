<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Lấy trang hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);

// TODO: Lấy số lượng sản phẩm trong giỏ hàng từ database
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    // TODO: Query database to get cart count
    // SELECT SUM(quantity) FROM carts WHERE member_id = ?
    $cart_count = 0; // Placeholder
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="description" content="Gym Management System">
    <meta name="keywords" content="Gym, fitness, training, workout">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Gym Management System</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css?family=Muli:300,400,500,600,700,800,900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700&display=swap" rel="stylesheet">

    <!-- Css Styles -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css" type="text/css">
    <link rel="stylesheet" href="assets/css/flaticon.css" type="text/css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css" type="text/css">
    <link rel="stylesheet" href="assets/css/barfiller.css" type="text/css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css" type="text/css">
    <link rel="stylesheet" href="assets/css/slicknav.min.css" type="text/css">
    <link rel="stylesheet" href="assets/css/style.css" type="text/css">
    <link rel="stylesheet" href="assets/css/custom-shop.css" type="text/css">
</head>

<body>
    <!-- Page Preloder -->
    <div id="preloder">
        <div class="loader"></div>
    </div>

    <!-- Offcanvas Menu Section Begin -->
    <div class="offcanvas-menu-overlay"></div>
    <div class="offcanvas-menu-wrapper">
        <div class="canvas-close">
            <i class="fa fa-close"></i>
        </div>
        <div class="canvas-search search-switch">
            <i class="fa fa-search"></i>
        </div>
        <nav class="canvas-menu mobile-menu">
            <ul>
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><a href="index.php">Trang chủ</a></li>
                <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"><a href="about.php">Về chúng tôi</a></li>
                <li class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>"><a href="classes.php">Lớp tập</a></li>
                <li class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"><a href="services.php">Dịch vụ</a></li>
                <li class="<?php echo ($current_page == 'trainers.php') ? 'active' : ''; ?>"><a href="trainers.php">Huấn luyện viên</a></li>
                <li class="<?php echo ($current_page == 'packages.php') ? 'active' : ''; ?>"><a href="packages.php">Gói tập</a></li>
                <li class="<?php echo ($current_page == 'products.php' || $current_page == 'product-detail.php') ? 'active' : ''; ?>"><a href="products.php">Sản phẩm</a></li>
                <li><a href="#">Khác</a>
                    <ul class="dropdown">
                        <li><a href="bmi-calculator.php">Tính BMI</a></li>
                        <li><a href="gallery.php">Thư viện</a></li>
                        <li><a href="blog.php">Tin tức</a></li>
                    </ul>
                </li>
                <li class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><a href="contact.php">Liên hệ</a></li>
            </ul>
        </nav>
        <div id="mobile-menu-wrap"></div>
        <div class="canvas-social">
            <a href="#"><i class="fa fa-facebook"></i></a>
            <a href="#"><i class="fa fa-twitter"></i></a>
            <a href="#"><i class="fa fa-youtube-play"></i></a>
            <a href="#"><i class="fa fa-instagram"></i></a>
        </div>
    </div>
    <!-- Offcanvas Menu Section End -->

    <!-- Header Section Begin -->
    <header class="header-section">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3">
                    <div class="logo">
                        <a href="index.php">
                            <img src="assets/img/logo.png" alt="">
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <nav class="nav-menu">
                        <ul>
                            <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><a href="index.php">Trang chủ</a></li>
                            <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"><a href="about.php">Về chúng tôi</a></li>
                            <li class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>"><a href="classes.php">Lớp tập</a></li>
                            <li class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"><a href="services.php">Dịch vụ</a></li>
                            <li class="<?php echo ($current_page == 'trainers.php') ? 'active' : ''; ?>"><a href="trainers.php">Huấn luyện viên</a></li>
                            <li class="<?php echo ($current_page == 'packages.php') ? 'active' : ''; ?>"><a href="packages.php">Gói tập</a></li>
                            <li class="<?php echo ($current_page == 'products.php' || $current_page == 'product-detail.php') ? 'active' : ''; ?>"><a href="products.php">Sản phẩm</a></li>
                            <li><a href="#">Khác</a>
                                <ul class="dropdown">
                                    <li><a href="bmi-calculator.php">Tính BMI</a></li>
                                    <li><a href="gallery.php">Thư viện</a></li>
                                    <li><a href="blog.php">Tin tức</a></li>
                                </ul>
                            </li>
                            <li class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><a href="contact.php">Liên hệ</a></li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3">
                    <div class="top-option">
                        <div class="to-search search-switch">
                            <a href="search.php" title="Tìm kiếm"><i class="fa fa-search"></i></a>
                        </div>
                        <div class="to-social">
                            <<a href="cart.php" title="Giỏ hàng" style="position: relative;">
                                <i class="fa fa-shopping-cart"></i>
                                <span class="cart-badge" style="position: absolute; top: -8px; right: -8px; background: #f36100; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; align-items: center; justify-content: center; display: <?php echo (!empty($cart_count) && $cart_count > 0) ? 'flex' : 'none'; ?>;">
                                    <?php echo $cart_count ?? 0; ?>
                                </span>
                            </a>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="profile.php" title="Tài khoản"><i class="fa fa-user"></i></a>
                            <?php else: ?>
                                <a href="login.php" title="Đăng nhập"><i class="fa fa-sign-in"></i></a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="canvas-open">
                <i class="fa fa-bars"></i>
            </div>
        </div>
    </header>
    <!-- Header End -->
