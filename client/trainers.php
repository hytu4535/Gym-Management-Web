<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../config/db.php';

// Query để lấy danh sách huấn luyện viên đang hoạt động
$sql = "SELECT * FROM trainers WHERE status = 'hoạt động' ORDER BY id ASC";
$trainers_result = $conn->query($sql);

include 'layout/header.php'; 
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Huấn luyện viên</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Huấn luyện viên</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- Team Section Begin -->
<section class="team-section team-page spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Đội ngũ của chúng tôi</span>
                    <h2>LUYỆN TẬP CÙNG CHUYÊN GIA</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <?php 
            // Styles for hover icons (ensure icons appear on hover)
            echo "<style>.ts-item{position:relative;overflow:hidden}.ts_text{position:absolute;left:0;right:0;bottom:0;padding:20px;background:rgba(0,0,0,0.45);color:#fff;transform:translateY(100%);transition:transform .25s ease}.ts-item:hover .ts_text{transform:translateY(0)}.tt_social a{color:#fff;margin-right:8px;display:inline-block;width:34px;height:34px;line-height:34px;text-align:center;background:rgba(0,0,0,0.35);border-radius:50%}</style>";

            if ($trainers_result && $trainers_result->num_rows > 0) {
                while($trainer = $trainers_result->fetch_assoc()) {
                    // Normalize image path to client/assets/img/team
                    $rawImg = trim($trainer['image'] ?? '');
                    if ($rawImg !== '') {
                        if (preg_match('/^https?:\/\//', $rawImg)) {
                            $imgPath = $rawImg;
                        } else {
                            // take filename portion and build client-relative path
                            $filename = basename($rawImg);
                            $imgPath = 'assets/img/team/' . $filename;
                        }
                        // if file not found locally, try mapping by id
                        if (!preg_match('/^https?:\/\//', $imgPath) && !file_exists(__DIR__ . '/' . $imgPath)) {
                            $candidate = 'assets/img/team/team-' . intval($trainer['id']) . '.jpg';
                            if (file_exists(__DIR__ . '/' . $candidate)) {
                                $imgPath = $candidate;
                            } else {
                                $idx = (intval($trainer['id']) - 1) % 6 + 1;
                                $imgPath = 'assets/img/team/team-' . $idx . '.jpg';
                            }
                        }
                    } else {
                        $candidate = 'assets/img/team/team-' . intval($trainer['id']) . '.jpg';
                        if (file_exists(__DIR__ . '/' . $candidate)) {
                            $imgPath = $candidate;
                        } else {
                            $idx = (intval($trainer['id']) - 1) % 6 + 1;
                            $imgPath = 'assets/img/team/team-' . $idx . '.jpg';
                        }
                    }
            ?>
            <div class="col-lg-4 col-sm-6">
                <div class="ts-item set-bg" data-setbg="<?php echo htmlspecialchars($imgPath); ?>">
                    <div class="ts_text">
                        <h4><?php echo htmlspecialchars($trainer['full_name']); ?></h4>
                        <span><?php echo htmlspecialchars($trainer['specialty'] ?? 'Huấn luyện viên'); ?></span>
                        <?php
                        $facebook = $trainer['facebook'] ?? '#';
                        $instagram = $trainer['instagram'] ?? '#';
                        // Force specific links for trainer Trương Trung Kiên
                        if (trim($trainer['full_name']) === 'Trương Trung Kiên') {
                            $facebook = 'https://www.facebook.com/l14925';
                            $instagram = 'https://www.instagram.com/truongchungkin?fbclid=IwY2xjawQHjOpleHRuA2FlbQIxMABicmlkETJ1eG5EbThPR3BISlpzQXpPc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHv3H1cB8v2JK6jh0tpCDOJiUPhLrSJ-Hw_pTmMn8K0taVDUXd4ItxkerE8jo_aem_Htg1W4aBbqfG076q56VyZw';
                        } elseif (trim($trainer['full_name']) === 'Nguyễn Tường Huy') {
                            $facebook = 'https://www.facebook.com/hytulasthope';
                            $instagram = 'https://www.instagram.com/hytulasthope/?fbclid=IwY2xjawQHjOpleHRuA2FlbQIxMABicmlkETJ1eG5EbThPR3BISlpzQXpPc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHv3H1cB8v2JK6jh0tpCDOJiUPhLrSJ-Hw_pTmMn8K0taVDUXd4ItxkerE8jo_aem_Htg1W4aBbqfG076q56VyZw';
                        }
                        elseif (trim($trainer['full_name']) === 'Nguyễn Thị Ý') {
                            $facebook = 'https://www.facebook.com/nguyen.y.548494';
                            $instagram = 'https://www.instagram.com/nguyen.y.548494/?fbclid=IwY2xjawQHjOpleHRuA2FlbQIxMABicmlkETJ1eG5EbThPR3BISlpzQXpPc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHv3H1cB8v2JK6jh0tpCDOJiUPhLrSJ-Hw_pTmMn8K0taVDUXd4ItxkerE8jo_aem_Htg1W4aBbqfG076q56VyZw';
                        }
                        elseif (trim($trainer['full_name']) === 'Nguyễn Nguyên Bảo') {
                            $facebook = 'https://www.facebook.com/nguyen.beo.340861';
                            $instagram = 'https://www.instagram.com/nguyenbeo_/?fbclid=IwY2xjawQHjOpleHRuA2FlbQIxMABicmlkETJ1eG5EbThPR3BISlpzQXpPc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHv3H1cB8v2JK6jh0tpCDOJiUPhLrSJ-Hw_pTmMn8K0taVDUXd4ItxkerE8jo_aem_Htg1W4aBbqfG076q56VyZw';
                        }
                        ?>
                        <div class="tt_social">
                            <a href="<?php echo htmlspecialchars($facebook); ?>" target="_blank"><i class="fa fa-facebook"></i></a>
                            <a href="<?php echo htmlspecialchars($instagram); ?>" target="_blank"><i class="fa fa-instagram"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else {
            ?>
            <div class="col-lg-12 text-center">
                <p>Hiện tại chưa có huấn luyện viên nào.</p>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
<!-- Team Section End -->

<!-- Banner Section Begin -->
<section class="banner-section set-bg" data-setbg="assets/img/banner-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="bs-text">
                    <h2>Đăng ký ngay để được tư vấn miễn phí</h2>
                    <div class="bt-tips">Huấn luyện viên cá nhân chuyên nghiệp.</div>
                    <a href="contact.php" class="primary-btn btn-normal">Liên hệ ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End -->

<?php 
// Đóng kết nối database
$conn->close();
?>

<?php include 'layout/footer.php'; ?>
