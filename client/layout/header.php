<?php
if (session_status() === PHP_SESSION_NONE) session_start();
// Lấy trang hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);

// Lấy số lượng sản phẩm trong giỏ hàng từ database
$cart_count = 0;
$unread_notification_count = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../config/db.php';
    
    // Query database to get cart count
    // Từ users_id lấy member_id, rồi lấy cart_id và tổng quantity
    $cart_stmt = $conn->prepare("
        SELECT COALESCE(SUM(ci.quantity), 0) as total_items
        FROM members m
        LEFT JOIN carts c ON c.member_id = m.id AND c.status = 'active'
        LEFT JOIN cart_items ci ON ci.cart_id = c.id
        WHERE m.users_id = ?
    ");
    $cart_stmt->bind_param("i", $_SESSION['user_id']);
    $cart_stmt->execute();
    $cart_result = $cart_stmt->get_result();
    if ($cart_row = $cart_result->fetch_assoc()) {
        $cart_count = (int)$cart_row['total_items'];
    }
    $cart_stmt->close();

    $noti_stmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $noti_stmt->bind_param("i", $_SESSION['user_id']);
    $noti_stmt->execute();
    $noti_result = $noti_stmt->get_result();
    if ($noti_row = $noti_result->fetch_assoc()) {
        $unread_notification_count = (int)$noti_row['unread_count'];
    }
    $noti_stmt->close();
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
    <link rel="stylesheet" href="assets/css/style.css?v=2.0" type="text/css">
    <link rel="stylesheet" href="assets/css/custom-shop.css" type="text/css">
    <link rel="stylesheet" href="assets/css/chatbot-faq.css" type="text/css">
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
        
        <div class="mobile-user-options" style="display: flex; justify-content: center; align-items: center; gap: 20px; margin-bottom: 20px; padding-top: 20px;">
            <div class="search-switch" style="cursor: pointer;">
                <i class="fa fa-search" style="font-size: 20px; color: #111;"></i>
            </div>
            
            <a href="cart.php" title="Giỏ hàng" style="position: relative;">
                <i class="fa fa-shopping-cart" style="font-size: 20px; color: #111;"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="cart-badge" style="position: absolute; top: -8px; right: -12px; background: #f36100; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </a>

            <?php if(isset($_SESSION['user_id'])): ?>
                <a href="subscription.php#tab-notifications" title="Thông báo" style="position: relative;">
                    <i class="fa fa-bell" style="font-size: 20px; color: #111;"></i>
                    <span class="mobile-notification-badge" style="position: absolute; top: -8px; right: -12px; background: #f36100; color: white; border-radius: 50%; min-width: 18px; height: 18px; padding: 0 4px; font-size: 10px; display: <?php echo $unread_notification_count > 0 ? 'flex' : 'none'; ?>; align-items: center; justify-content: center;"><?php echo $unread_notification_count; ?></span>
                </a>
                <a href="profile.php" title="Tài khoản">
                    <i class="fa fa-user" style="font-size: 20px; color: #111;"></i>
                </a>
                <a href="logout.php" title="Đăng xuất">
                    <i class="fa fa-sign-out" style="font-size: 20px; color: #e74c3c;"></i>
                </a>
            <?php else: ?>
                <a href="login.php" title="Đăng nhập">
                    <i class="fa fa-sign-in" style="font-size: 20px; color: #111;"></i>
                </a>
            <?php endif; ?>
        </div>
        <nav class="canvas-menu mobile-menu">
            <ul>
                <li class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>"><a href="index.php">Trang chủ</a></li>
                <li class="<?php echo ($current_page == 'about.php') ? 'active' : ''; ?>"><a href="about.php">Về chúng tôi</a></li>
                <li class="<?php echo ($current_page == 'classes.php') ? 'active' : ''; ?>"><a href="classes.php">Lớp tập</a></li>
                <li class="<?php echo ($current_page == 'services.php') ? 'active' : ''; ?>"><a href="services.php">Dịch vụ</a></li>
                <li class="<?php echo ($current_page == 'trainers.php') ? 'active' : ''; ?>"><a href="trainers.php">Huấn luyện viên</a></li>
                <li class="<?php echo ($current_page == 'pt-booking.php') ? 'active' : ''; ?>"><a href="pt-booking.php">Lịch tập với PT</a></li>
                <li class="<?php echo ($current_page == 'packages.php') ? 'active' : ''; ?>"><a href="packages.php">Gói tập</a></li>
                <li class="<?php echo ($current_page == 'nutrition.php') ? 'active' : ''; ?>"><a href="nutrition.php">Dinh dưỡng</a></li>
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
                            <li class="<?php echo ($current_page == 'pt-booking.php') ? 'active' : ''; ?>"><a href="pt-booking.php">Lịch tập với PT</a></li>
                            <li class="<?php echo ($current_page == 'packages.php') ? 'active' : ''; ?>"><a href="packages.php">Gói tập</a></li>
                            <li class="<?php echo ($current_page == 'nutrition.php') ? 'active' : ''; ?>"><a href="nutrition.php">Dinh dưỡng</a></li>
                            <li class="<?php echo ($current_page == 'products.php' || $current_page == 'product-detail.php') ? 'active' : ''; ?>"><a href="products.php">Sản phẩm</a></li>
                            <li class="<?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>"><a href="contact.php">Liên hệ</a></li>
                            <li class="<?php echo ($current_page == 'subscription.php') ? 'active' : ''; ?>"><a href="subscription.php">Đánh giá & thông báo</a></li>
                            <li><a href="#">Khác</a>
                                <ul class="dropdown">
                                    <li><a href="bmi-calculator.php">Tính BMI</a></li>
                                    <li><a href="gallery.php">Thư viện</a></li>
                                    <li><a href="blog.php">Tin tức</a></li>
                                </ul>
                            </li>
                        </ul>
                    </nav>
                </div>
                <div class="col-lg-3">
                    <div class="top-option">
                        <div class="to-search">
                            <a href="#" id="global-search-toggle" title="Tìm kiếm toàn bộ" onclick="return false;"><i class="fa fa-search"></i></a>
                        </div>
                        <div class="to-social">
                            <a href="cart.php" title="Giỏ hàng" style="position: relative;">
                                <i class="fa fa-shopping-cart"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span class="cart-badge" style="position: absolute; top: -8px; right: -8px; background: #f36100; color: white; border-radius: 50%; width: 18px; height: 18px; font-size: 10px; display: flex; align-items: center; justify-content: center;"><?php echo $cart_count; ?></span>
                                <?php endif; ?>
                            </a>
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <a href="subscription.php#tab-notifications" title="Thông báo" style="position: relative;">
                                    <i class="fa fa-bell"></i>
                                    <span id="notification-badge" style="position: absolute; top: -8px; right: -8px; background: #f36100; color: white; border-radius: 50%; min-width: 18px; height: 18px; padding: 0 4px; font-size: 10px; display: <?php echo $unread_notification_count > 0 ? 'flex' : 'none'; ?>; align-items: center; justify-content: center;"><?php echo $unread_notification_count; ?></span>
                                </a>
                                <div class="user-dropdown" style="position:relative;display:inline-block;">
                                    <a href="#" class="user-dropdown-toggle" title="Tài khoản" style="position:relative;">
                                        <i class="fa fa-user"></i>
                                    </a>
                                    <div class="user-dropdown-menu" style="
                                        display:none;position:absolute;right:0;top:130%;
                                        background:#1a1a1a;min-width:200px;border-radius:6px;
                                        box-shadow:0 5px 20px rgba(0,0,0,.4);z-index:9999;
                                        border-top:3px solid #f36100;padding:8px 0;">
                                        <div style="padding:10px 16px 8px;border-bottom:1px solid #333;">
                                            <strong style="color:#fff;font-size:13px;">
                                                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hội viên'); ?>
                                            </strong>
                                        </div>
                                        <a href="profile.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-user-o" style="width:18px;color:#f36100;"></i> Thông tin cá nhân
                                        </a>
                                        <a href="my-membership.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-star-o" style="width:18px;color:#f36100;"></i> Thông tin hội viên
                                        </a>
                                        <a href="my-packages.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-ticket" style="width:18px;color:#f36100;"></i> Gói tập của tôi
                                        </a>
                                        <a href="my-schedules.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-calendar" style="width:18px;color:#f36100;"></i> Lịch tập của tôi
                                        </a>
                                        <a href="pt-booking.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-handshake-o" style="width:18px;color:#f36100;"></i> Đặt lịch PT 1-1
                                        </a>
                                        <a href="order-history.php" style="display:block;padding:9px 16px;color:#ccc;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-shopping-bag" style="width:18px;color:#f36100;"></i> Lịch sử mua hàng
                                        </a>
                                        <div style="border-top:1px solid #333;margin-top:4px;"></div>
                                        <a href="logout.php" style="display:block;padding:9px 16px;color:#e74c3c;text-decoration:none;font-size:13px;">
                                            <i class="fa fa-sign-out" style="width:18px;"></i> Đăng xuất
                                        </a>
                                    </div>
                                </div>
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
<script>
(function(){
    var toggle = document.querySelector('.user-dropdown-toggle');
    var menu   = document.querySelector('.user-dropdown-menu');
    if (!toggle || !menu) return;
    toggle.addEventListener('click', function(e){
        e.preventDefault();
        menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
    });
    document.addEventListener('click', function(e){
        if (!e.target.closest('.user-dropdown')) menu.style.display = 'none';
    });
})();

(function () {
    function updateNotificationBadge() {
        fetch('api.php?action=unread_notification_count', {
            method: 'GET',
            credentials: 'same-origin'
        })
        .then(function (response) { return response.json(); })
        .then(function (result) {
            if (!result || !result.success || !result.data) {
                return;
            }

            var unread = Number(result.data.unread_count || 0);
            
            var badges = document.querySelectorAll('#notification-badge, .mobile-notification-badge');
            badges.forEach(function(badge) {
                badge.textContent = unread;
                badge.style.display = unread > 0 ? 'flex' : 'none';
            });
        })
        .catch(function () {
        });
    }

    updateNotificationBadge();
    setInterval(updateNotificationBadge, 15000);

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            updateNotificationBadge();
        }
    });
})();
</script>