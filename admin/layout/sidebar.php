  <!-- Main Sidebar Container -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="index.php" class="brand-link">
      <i class="fas fa-dumbbell brand-image"></i>
      <span class="brand-text font-weight-light">Gym</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <i class="fas fa-user-circle fa-2x text-white"></i>
        </div>
        <div class="info">
          <a href="#" class="d-block">Trung Kiên</a>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          
          <!-- Dashboard -->
          <li class="nav-item">
            <a href="index.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tachometer-alt"></i>
              <p>Dashboard</p>
            </a>
          </li>

          <!-- Quản lý tài khoản -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'roles.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['users.php', 'roles.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-users-cog"></i>
              <p>
                Quản lý tài khoản
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'users.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Người dùng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="roles.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'roles.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Vai trò</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Quản lý hội viên -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['members.php', 'staff.php', 'member-packages.php', 'member-tiers.php', 'bmi-devices.php', 'bmi-measurements.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['members.php', 'staff.php', 'member-packages.php', 'member-tiers.php', 'bmi-devices.php', 'bmi-measurements.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-user-friends"></i>
              <p>
                Quản lý hội viên
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="members.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'members.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Hội viên</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="member-packages.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'member-packages.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Gói tập HV</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="member-tiers.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'member-tiers.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Hạng hội viên</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="bmi-devices.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'bmi-devices.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Máy đo BMI</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="bmi-measurements.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'bmi-measurements.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Lịch sử đo BMI</p>
                </a>
              </li>
            </ul>
          </li>
          <li class="nav-item">
                <a href="staff.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'staff.php') ? 'active' : ''; ?>">
                  <i class="nav-icon fas fa-user-friends"></i>
                  <p>Quản lý nhân viên</p>
                </a>
              </li>
          <!-- Quản lý gói tập -->
          <li class="nav-item">
            <a href="packages.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'packages.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-box"></i>
              <p>
                Quản lý gói tập
              </p>
            </a>
          </li>
          <!-- Quản lý huấn luyện viên -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['trainers.php', 'training-schedules.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['trainers.php', 'training-schedules.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-chalkboard-teacher"></i>
              <p>
                Quản lý huấn luyện viên
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="trainers.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'trainers.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Huấn luyện viên</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="training-schedules.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'training-schedules.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Lịch tập</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- Quản lý Dịch vụ -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['services.php', 'nutrition-plans.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['services.php', 'nutrition-plans.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-utensils"></i>
              <p>
                Dịch vụ & Dinh dưỡng
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="services.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'services.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Dịch vụ</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="nutrition-plans.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'nutrition-plans.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Chế độ dinh dưỡng</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- Quản lý bán hàng -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'orders.php', 'order-items.php', 'payments.php', 'carts.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['categories.php', 'orders.php', 'order-items.php', 'payments.php', 'carts.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-shopping-cart"></i>
              <p>
                Quản lý bán hàng
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="products.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'active' : ''; ?>">
                 <i class="far fa-circle nav-icon"></i>
                  <p>Sản phẩm</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="categories.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'categories.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Danh mục</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="orders.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'orders.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Đơn hàng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="order-items.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'order-items.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Chi tiết đơn hàng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="carts.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'carts.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Giỏ hàng</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Quản lý kho -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['import-slips.php', 'suppliers.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['import-slips.php', 'suppliers.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-warehouse"></i>
              <p>
                Quản lý kho
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="import-slips.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'import-slips.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Phiếu nhập</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="suppliers.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'suppliers.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Nhà cung cấp</p>
                </a>
              </li>
            </ul>
          </li>
          
          <!-- Quản lý thiết bị -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['equipment.php', 'equipment-maintenance.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['equipment.php', 'equipment-maintenance.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-tools"></i>
              <p>
                Quản lý thiết bị
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="equipment.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'equipment.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Thiết bị</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="equipment-maintenance.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'equipment-maintenance.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Bảo trì thiết bị</p>
                </a>
              </li>
            </ul>
          </li>

          <!-- Phản hồi & Thông báo -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['feedback.php', 'notifications.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['feedback.php', 'notifications.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-comments"></i>
              <p>
                Phản hồi & Thông báo
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="feedback.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'feedback.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Phản hồi</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="notifications.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'notifications.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Thông báo</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- Quản lý ưu đãi -->
          <li class="nav-item <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['tier-promotions.php', 'promotion-usage.php'])) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo (in_array(basename($_SERVER['PHP_SELF']), ['tier-promotions.php', 'promotion-usage.php'])) ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-percent"></i>
              <p>
                Quản lý ưu đãi
                <i class="fas fa-angle-left right"></i>
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="tier-promotions.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'tier-promotions.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Khuyến mãi theo hạng</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="promotion-usage.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'promotion-usage.php') ? 'active' : ''; ?>">
                  <i class="far fa-circle nav-icon"></i>
                  <p>Lịch sử sử dụng</p>
                </a>
              </li>
            </ul>
          </li>
          
          <!-- Báo cáo thống kê -->
          <li class="nav-item">
            <a href="reports.php" class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'reports.php') ? 'active' : ''; ?>">
              <i class="nav-icon fas fa-chart-line"></i>
              <p>Báo cáo thống kê</p>
            </a>
          </li>
          <!-- Logout -->
          <li class="nav-item">
            <a href="logout.php" class="nav-link">
              <i class="nav-icon fas fa-sign-out-alt"></i>
              <p>Đăng xuất</p>
            </a>
          </li>

        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>