<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once '../config/db.php';

$user_id = $_SESSION['user_id'];

// Lấy thông tin hội viên và hạng hiện tại
$query = "SELECT m.*, mt.name as tier_name, mt.level as tier_level, 
          mt.min_spent, mt.base_discount, mt.status as tier_status
          FROM members m
          LEFT JOIN member_tiers mt ON m.tier_id = mt.id
          WHERE m.users_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();

if (!$member) {
    echo "Không tìm thấy thông tin hội viên!";
    exit;
}

$member_id = $member['id'];
$tier_id = $member['tier_id'];

// Lấy BMI mới nhất
$bmi_query = "SELECT bm.*, bd.device_code, bd.location
              FROM bmi_measurements bm
              LEFT JOIN bmi_devices bd ON bm.device_id = bd.id
              WHERE bm.member_id = ?
              ORDER BY bm.measured_at DESC
              LIMIT 1";
$bmi_stmt = $conn->prepare($bmi_query);
$bmi_stmt->bind_param("i", $member_id);
$bmi_stmt->execute();
$latest_bmi = $bmi_stmt->get_result()->fetch_assoc();

// Tính BMI từ height/weight hiện tại nếu không có đo lường
$current_bmi = null;
$current_body_type = 'Chưa xác định';
if ($member['height'] && $member['weight']) {
    $height_m = $member['height'] / 100;
    $current_bmi = $member['weight'] / ($height_m * $height_m);
    
    if ($current_bmi < 18.5) {
        $current_body_type = 'Gầy';
    } elseif ($current_bmi < 25) {
        $current_body_type = 'Bình thường';
    } elseif ($current_bmi < 30) {
        $current_body_type = 'Thừa cân';
    } else {
        $current_body_type = 'Béo phì';
    }
}

// Lấy danh sách các hạng để hiển thị tiến trình
$tiers_query = "SELECT * FROM member_tiers WHERE status = 'active' ORDER BY level ASC";
$tiers_result = $conn->query($tiers_query);
$all_tiers = [];
while ($tier = $tiers_result->fetch_assoc()) {
    $all_tiers[] = $tier;
}

// Lấy quyền lợi theo hạng hiện tại
$promotions_query = "SELECT tp.*, mt.name as tier_name
                     FROM tier_promotions tp
                     JOIN member_tiers mt ON tp.tier_id = mt.id
                     WHERE tp.tier_id = ? AND tp.status = 'active'
                     AND CURDATE() BETWEEN tp.start_date AND tp.end_date
                     ORDER BY tp.name ASC";
$promo_stmt = $conn->prepare($promotions_query);
$promo_stmt->bind_param("i", $tier_id);
$promo_stmt->execute();
$promotions = $promo_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Tính % tiến độ đến hạng tiếp theo
$next_tier = null;
$progress_percent = 0;
foreach ($all_tiers as $tier) {
    if ($tier['level'] > $member['tier_level']) {
        $next_tier = $tier;
        break;
    }
}

if ($next_tier) {
    $current_spent = $member['total_spent'];
    $required_spent = $next_tier['min_spent'];
    if ($required_spent > 0) {
        $progress_percent = min(100, ($current_spent / $required_spent) * 100);
    }
}

// Hàm format tiền
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . ' đ';
}

// Hàm chuyển đổi enum body_type
function getBodyTypeText($type) {
    $types = [
        'gay' => 'Gầy',
        'binh thuong' => 'Bình thường',
        'thua can' => 'Thừa cân',
        'beo phi' => 'Béo phì'
    ];
    return $types[$type] ?? ucfirst($type);
}

// Hàm chuyển đổi discount_type
function getDiscountTypeText($type) {
    $types = [
        'percentage' => 'Giảm giá %',
        'fixed' => 'Giảm tiền cố định',
        'package' => 'Tặng gói'
    ];
    return $types[$type] ?? $type;
}

// Hàm lấy màu badge theo hạng
function getTierColor($level) {
    $colors = [
        1 => '#cd7f32', // Đồng - Bronze
        2 => '#c0c0c0', // Bạc - Silver
        3 => '#ffd700', // Vàng - Gold
        4 => '#E5E4E2', // Bạch Kim - Platinum
        5 => '#B9F2FF'  // Kim Cương - Diamond
    ];
    return $colors[$level] ?? '#6c757d';
}

include 'layout/header.php'; 
?>

<style>
.membership-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.membership-card {
    background: white;
    border-radius: 10px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
}

.membership-card h3 {
    color: #f36100;
    margin-bottom: 20px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
}

.membership-card h3 i {
    font-size: 28px;
}

.bmi-display {
    display: flex;
    justify-content: space-around;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.bmi-circle {
    width: 180px;
    height: 180px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.bmi-circle .bmi-value {
    font-size: 48px;
    font-weight: 700;
    line-height: 1;
}

.bmi-circle .bmi-label {
    font-size: 14px;
    margin-top: 8px;
    opacity: 0.9;
}

.bmi-info {
    flex: 1;
    min-width: 250px;
}

.bmi-info-item {
    display: flex;
    justify-content: space-between;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 5px;
    margin-bottom: 10px;
}

.bmi-info-item strong {
    color: #333;
}

.bmi-status {
    display: inline-block;
    padding: 6px 15px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 14px;
}

.status-underweight { background: #ffc107; color: #000; }
.status-normal { background: #28a745; color: #fff; }
.status-overweight { background: #ff9800; color: #fff; }
.status-obese { background: #dc3545; color: #fff; }

.tier-current {
    text-align: center;
    padding: 30px;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border-radius: 15px;
    color: white;
    margin-bottom: 30px;
}

.tier-badge {
    display: inline-block;
    padding: 15px 40px;
    border-radius: 50px;
    font-size: 32px;
    font-weight: 700;
    margin: 20px 0;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
}

.tier-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.tier-stat {
    text-align: center;
}

.tier-stat .value {
    font-size: 28px;
    font-weight: 700;
    display: block;
}

.tier-stat .label {
    font-size: 14px;
    opacity: 0.9;
}

.tier-progress {
    margin-top: 30px;
}

.tier-progress h5 {
    color: #333;
    margin-bottom: 15px;
}

.progress {
    height: 30px;
    border-radius: 15px;
    background: #e9ecef;
    overflow: visible;
}

.progress-bar {
    background: linear-gradient(90deg, #f36100 0%, #ff8c42 100%);
    border-radius: 15px;
    font-weight: 600;
    position: relative;
}

.tier-list {
    margin-top: 30px;
}

.tier-item {
    display: flex;
    align-items: center;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 15px;
    transition: all 0.3s;
}

.tier-item:hover {
    background: #e9ecef;
    transform: translateX(5px);
}

.tier-item.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3);
}

.tier-item .tier-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    font-weight: 700;
    margin-right: 20px;
}

.tier-item .tier-details {
    flex: 1;
}

.tier-item .tier-name {
    font-weight: 700;
    font-size: 20px;
    margin-bottom: 5px;
}

.tier-item .tier-requirement {
    font-size: 14px;
    opacity: 0.8;
}

.benefits-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.benefit-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    transition: transform 0.3s;
}

.benefit-card:hover {
    transform: translateY(-5px);
}

.benefit-card .benefit-icon {
    width: 50px;
    height: 50px;
    background: rgba(255,255,255,0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
}

.benefit-card .benefit-icon i {
    font-size: 24px;
}

.benefit-card h5 {
    color: white;
    font-weight: 700;
    margin-bottom: 10px;
}

.benefit-card .benefit-value {
    font-size: 28px;
    font-weight: 700;
    margin: 10px 0;
}

.benefit-card .benefit-type {
    font-size: 13px;
    opacity: 0.9;
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 15px;
    display: inline-block;
}

.no-benefits {
    text-align: center;
    padding: 40px;
    color: #6c757d;
}

.no-benefits i {
    font-size: 64px;
    margin-bottom: 15px;
    opacity: 0.3;
}

.alert-warning {
    background: #fff3cd;
    border: 1px solid #ffc107;
    border-radius: 8px;
    padding: 15px;
    margin-top: 20px;
}
</style>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Thông Tin Hội Viên</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Hội viên</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Membership Section Begin -->
<section class="membership-section">
    <div class="container">
        
        <!-- BMI Card -->
        <div class="membership-card">
            <h3>
                <i class="fa fa-heartbeat"></i>
                Chỉ Số BMI Của Bạn
            </h3>
            
            <?php if ($latest_bmi): ?>
                <div class="bmi-display">
                    <div class="bmi-circle">
                        <div class="bmi-value"><?= number_format($latest_bmi['bmi'], 1) ?></div>
                        <div class="bmi-label">CHỈ SỐ BMI</div>
                    </div>
                    
                    <div class="bmi-info">
                        <div class="bmi-info-item">
                            <strong>Chiều cao:</strong>
                            <span><?= number_format($latest_bmi['height'], 0) ?> cm</span>
                        </div>
                        <div class="bmi-info-item">
                            <strong>Cân nặng:</strong>
                            <span><?= number_format($latest_bmi['weight'], 1) ?> kg</span>
                        </div>
                        <div class="bmi-info-item">
                            <strong>Phân loại:</strong>
                            <span class="bmi-status status-<?= strtolower(str_replace(' ', '', getBodyTypeText($latest_bmi['body_type']))) ?>">
                                <?= getBodyTypeText($latest_bmi['body_type']) ?>
                            </span>
                        </div>
                        <div class="bmi-info-item">
                            <strong>Ngày đo:</strong>
                            <span><?= date('d/m/Y H:i', strtotime($latest_bmi['measured_at'])) ?></span>
                        </div>
                        <?php if ($latest_bmi['device_code']): ?>
                        <div class="bmi-info-item">
                            <strong>Thiết bị:</strong>
                            <span><?= $latest_bmi['device_code'] ?> - <?= $latest_bmi['location'] ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php elseif ($current_bmi): ?>
                <div class="bmi-display">
                    <div class="bmi-circle">
                        <div class="bmi-value"><?= number_format($current_bmi, 1) ?></div>
                        <div class="bmi-label">CHỈ SỐ BMI (Ước tính)</div>
                    </div>
                    
                    <div class="bmi-info">
                        <div class="bmi-info-item">
                            <strong>Chiều cao:</strong>
                            <span><?= number_format($member['height'], 0) ?> cm</span>
                        </div>
                        <div class="bmi-info-item">
                            <strong>Cân nặng:</strong>
                            <span><?= number_format($member['weight'], 1) ?> kg</span>
                        </div>
                        <div class="bmi-info-item">
                            <strong>Phân loại:</strong>
                            <span class="bmi-status"><?= $current_body_type ?></span>
                        </div>
                    </div>
                </div>
                <div class="alert alert-warning mt-3">
                    <i class="fa fa-info-circle"></i> Chỉ số BMI này được tính từ thông tin chiều cao và cân nặng trong hồ sơ. 
                    Hãy đến phòng gym để đo BMI chính xác với thiết bị chuyên dụng!
                </div>
            <?php else: ?>
                <div class="no-benefits">
                    <i class="fa fa-chart-line"></i>
                    <h5>Chưa có thông tin BMI</h5>
                    <p>Vui lòng cập nhật chiều cao và cân nặng trong hồ sơ hoặc đến phòng gym để đo BMI.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tier Card -->
        <div class="membership-card">
            <h3>
                <i class="fa fa-crown"></i>
                Hạng Hội Viên Hiện Tại
            </h3>
            
            <div class="tier-current">
                <h4>HẠNG CỦA BẠN</h4>
                <div class="tier-badge" style="background-color: <?= getTierColor($member['tier_level']) ?>; color: <?= $member['tier_level'] >= 4 ? '#333' : '#fff' ?>;">
                    <?= strtoupper($member['tier_name']) ?>
                </div>
                
                <div class="tier-stats">
                    <div class="tier-stat">
                        <span class="value"><?= formatCurrency($member['total_spent']) ?></span>
                        <span class="label">Tổng Chi Tiêu</span>
                    </div>
                    <div class="tier-stat">
                        <span class="value"><?= number_format($member['base_discount'], 0) ?>%</span>
                        <span class="label">Giảm Giá</span>
                    </div>
                    <div class="tier-stat">
                        <span class="value">Level <?= $member['tier_level'] ?></span>
                        <span class="label">Cấp Độ</span>
                    </div>
                </div>
            </div>
            
            <?php if ($next_tier): ?>
            <div class="tier-progress">
                <h5>Tiến Độ Lên Hạng <?= $next_tier['name'] ?></h5>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: <?= $progress_percent ?>%">
                        <?= number_format($progress_percent, 1) ?>%
                    </div>
                </div>
                <p class="mt-2 text-muted">
                    Còn <?= formatCurrency($next_tier['min_spent'] - $member['total_spent']) ?> nữa để lên hạng <?= $next_tier['name'] ?>
                </p>
            </div>
            <?php else: ?>
            <div class="alert alert-success mt-3">
                <i class="fa fa-trophy"></i> Chúc mừng! Bạn đã đạt hạng cao nhất trong hệ thống!
            </div>
            <?php endif; ?>
            
            <div class="tier-list">
                <h5>Tất Cả Các Hạng Hội Viên</h5>
                <?php foreach ($all_tiers as $tier): ?>
                <div class="tier-item <?= $tier['id'] == $tier_id ? 'active' : '' ?>">
                    <div class="tier-icon" style="background-color: <?= getTierColor($tier['level']) ?>; color: <?= $tier['level'] >= 4 ? '#333' : '#fff' ?>;">
                        <?= $tier['level'] ?>
                    </div>
                    <div class="tier-details">
                        <div class="tier-name"><?= $tier['name'] ?></div>
                        <div class="tier-requirement">
                            <?php if ($tier['min_spent'] > 0): ?>
                                Chi tiêu từ <?= formatCurrency($tier['min_spent']) ?> | Giảm giá <?= $tier['base_discount'] ?>%
                            <?php else: ?>
                                Hạng mặc định | Giảm giá <?= $tier['base_discount'] ?>%
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($tier['id'] == $tier_id): ?>
                    <div class="badge badge-light">Hạng hiện tại</div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Benefits Card -->
        <div class="membership-card">
            <h3>
                <i class="fa fa-gift"></i>
                Quyền Lợi Hạng <?= $member['tier_name'] ?>
            </h3>
            
            <p class="text-muted mb-4">
                Các ưu đãi và khuyến mãi dành riêng cho hội viên hạng <strong><?= $member['tier_name'] ?></strong>
            </p>
            
            <?php if (count($promotions) > 0): ?>
                <div class="benefits-list">
                    <?php foreach ($promotions as $promo): ?>
                    <div class="benefit-card">
                        <div class="benefit-icon">
                            <i class="fa fa-<?= $promo['discount_type'] == 'percentage' ? 'percent' : ($promo['discount_type'] == 'fixed' ? 'dollar-sign' : 'gift') ?>"></i>
                        </div>
                        <h5><?= htmlspecialchars($promo['name']) ?></h5>
                        <div class="benefit-value">
                            <?php if ($promo['discount_type'] == 'percentage'): ?>
                                <?= number_format($promo['discount_value'], 0) ?>%
                            <?php elseif ($promo['discount_type'] == 'fixed'): ?>
                                <?= formatCurrency($promo['discount_value']) ?>
                            <?php else: ?>
                                x<?= number_format($promo['discount_value'], 0) ?>
                            <?php endif; ?>
                        </div>
                        <span class="benefit-type"><?= getDiscountTypeText($promo['discount_type']) ?></span>
                        <p class="mt-3 mb-0" style="font-size: 13px;">
                            <i class="fa fa-calendar"></i> 
                            <?= date('d/m/Y', strtotime($promo['start_date'])) ?> - <?= date('d/m/Y', strtotime($promo['end_date'])) ?>
                        </p>
                        <?php if ($promo['usage_limit']): ?>
                        <p class="mt-1 mb-0" style="font-size: 13px;">
                            <i class="fa fa-ticket-alt"></i> Giới hạn: <?= $promo['usage_limit'] ?> lần
                        </p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-benefits">
                    <i class="fa fa-gift"></i>
                    <h5>Chưa có ưu đãi nào</h5>
                    <p>Hiện tại chưa có ưu đãi đặc biệt cho hạng <?= $member['tier_name'] ?>. Hãy theo dõi để cập nhật các ưu đãi mới!</p>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-info mt-4">
                <i class="fa fa-info-circle"></i> 
                <strong>Ghi chú:</strong> Các ưu đãi có thể thay đổi theo thời gian. Vui lòng liên hệ nhân viên để biết thêm chi tiết!
            </div>
        </div>
        
    </div>
</section>
<!-- Membership Section End -->

<?php include 'layout/footer.php'; ?>
