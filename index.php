<?php
/**
 * Main Landing Page
 * Gym Management System
 */

require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome-free/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .hero-section {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 50px;
        }
        .feature-card {
            transition: transform 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-10px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero-section">
            <div class="text-center mb-5">
                <h1 class="display-3 font-weight-bold">
                    <i class="fas fa-dumbbell text-primary"></i>
                    Gym Management System
                </h1>
                <p class="lead text-muted">Hệ thống quản lý phòng tập Gym chuyên nghiệp</p>
                <hr class="my-4">
            </div>
            
            <div class="row mb-5">
                <div class="col-md-6 mb-3">
                    <a href="admin/index.php" class="text-decoration-none">
                        <div class="card border-primary feature-card">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-tachometer-alt fa-4x text-primary mb-3"></i>
                                <h4 class="card-title">Admin Panel</h4>
                                <p class="text-muted">Quản trị hệ thống</p>
                                <button class="btn btn-primary btn-lg">
                                    Truy cập <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="client/index.php" class="text-decoration-none">
                        <div class="card border-success feature-card">
                            <div class="card-body text-center p-4">
                                <i class="fas fa-home fa-4x text-success mb-3"></i>
                                <h4 class="card-title">Client Area</h4>
                                <p class="text-muted">Khu vực khách hàng</p>
                                <button class="btn btn-success btn-lg">
                                    Truy cập <i class="fas fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-users fa-3x text-info"></i>
                            </div>
                            <h5 class="card-title">Quản lý Hội viên</h5>
                            <p class="card-text text-muted">Đăng ký, theo dõi và quản lý thành viên</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-calendar-alt fa-3x text-warning"></i>
                            </div>
                            <h5 class="card-title">Lịch tập PT</h5>
                            <p class="card-text text-muted">Đặt lịch và quản lý buổi tập</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card border-0 shadow-sm feature-card">
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <i class="fas fa-shopping-cart fa-3x text-danger"></i>
                            </div>
                            <h5 class="card-title">Bán hàng</h5>
                            <p class="card-text text-muted">Quản lý sản phẩm và đơn hàng</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <p class="text-muted">
                    <i class="fas fa-code"></i> Developed by SGU Students © 2026
                </p>
                <p class="small text-muted">
                    Version <?php echo APP_VERSION; ?> | 
                    <a href="https://github.com/hytu4535/Gym-Management-Web" target="_blank">
                        <i class="fab fa-github"></i> GitHub
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script src="assets/plugins/jquery/jquery.min.js"></script>
    <script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
