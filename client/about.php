<?php
require_once '../config/db.php';

$equipmentImages = [];

$equipmentImageDir = realpath(__DIR__ . '/../assets/uploads/equipment');
if ($equipmentImageDir !== false && is_dir($equipmentImageDir)) {
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $files = scandir($equipmentImageDir);
    if ($files !== false) {
        foreach ($files as $fileName) {
            if ($fileName === '.' || $fileName === '..') {
                continue;
            }

            $fullPath = $equipmentImageDir . DIRECTORY_SEPARATOR . $fileName;
            if (!is_file($fullPath)) {
                continue;
            }

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (in_array($ext, $allowedExt, true)) {
                $equipmentImages[] = '../assets/uploads/equipment/' . rawurlencode($fileName);
            }
        }

        sort($equipmentImages);
    }
}

include 'layout/header.php';
?>

<style>
.about-feature-card {
    background: #11151b;
    border: 1px solid #2a313b;
    border-radius: 10px;
    padding: 22px;
    height: 100%;
}

.about-feature-card h5 {
    color: #ffffff;
    margin-bottom: 10px;
}

.about-feature-card p {
    margin-bottom: 0;
}

.equipment-slider .owl-stage-outer {
    padding: 5px 0;
}

.equipment-slide-thumb {
    width: 100%;
    height: 320px;
    object-fit: cover;
    display: block;
    border-radius: 12px;
    border: 1px solid #2a313b;
}

.plan-card {
    background: #141922;
    border-left: 4px solid #f36100;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}

.plan-card h5 {
    color: #fff;
    margin-bottom: 8px;
}

.question-box {
    background: #181d24;
    border-radius: 10px;
    border: 1px solid #2a313b;
    padding: 18px;
    margin-bottom: 15px;
}

.question-box h6 {
    color: #fff;
    margin-bottom: 10px;
}

.question-box p {
    margin-bottom: 0;
}

.nutrition-badge {
    display: inline-block;
    background: #f36100;
    color: #fff;
    font-size: 12px;
    padding: 4px 10px;
    border-radius: 30px;
    margin-bottom: 8px;
}

.nutrition-overview {
    max-width: 1100px;
    margin: 0 auto;
    font-size: 28px;
    line-height: 1.8;
    font-weight: 500;
    color: #c7cbd1;
}

.nutrition-overview p {
    font-size: 20px !important;
    line-height: 1.8 !important;
    font-weight: 500;
    margin-bottom: 20px;
}

.equipment-overview {
    max-width: 1100px;
    margin: 24px auto 0;
    font-size: 20px;
    line-height: 1.9;
    color: #c7cbd1;
    text-align: center;
}

.training-overview {
   max-width: 1100px;
    margin: 24px auto 0;
    font-size: 20px;
    line-height: 1.9;
    color: #c7cbd1;
    text-align: center;
}

.training-overview p {
    font-size: 20px !important;
    line-height: 1.8 !important;
    font-weight: 500;
    margin-bottom: 20px;
}

.feature-link {
    display: block;
    text-decoration: none;
}

.feature-link .cs-item {
    cursor: pointer;
    transition: transform 0.25s ease;
}

.feature-link .cs-item:hover {
    transform: translateY(-6px);
}
</style>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Về chúng tôi</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Về chúng tôi</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- ChoseUs Section Begin -->
<section class="choseus-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Tại sao chọn chúng tôi?</span>
                    <h2>ĐẨY GIỚI HẠN CỦA BẠN LÊN CAO HƠN</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-sm-6">
                <a class="feature-link" href="#equipment-showcase">
                    <div class="cs-item">
                        <span class="flaticon-034-stationary-bike"></span>
                        <h4>Thiết bị hiện đại</h4>
                        <p>Trang bị đầy đủ các thiết bị tập luyện hiện đại, chất lượng cao từ các thương hiệu hàng đầu.</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-sm-6">
                <a class="feature-link" href="#nutrition-showcase">
                    <div class="cs-item">
                        <span class="flaticon-033-juice"></span>
                        <h4>Chế độ dinh dưỡng</h4>
                        <p>Kế hoạch dinh dưỡng khoa học, phù hợp với từng mục tiêu tập luyện của bạn.</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-sm-6">
                <a class="feature-link" href="#training-showcase">
                    <div class="cs-item">
                        <span class="flaticon-002-dumbell"></span>
                        <h4>Kế hoạch tập chuyên nghiệp</h4>
                        <p>Lộ trình tập luyện được thiết kế bởi các huấn luyện viên chuyên nghiệp có chứng chỉ quốc tế.</p>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-sm-6">
                <a class="feature-link" href="#personalized-showcase">
                    <div class="cs-item">
                        <span class="flaticon-014-heart-beat"></span>
                        <h4>Phù hợp với nhu cầu</h4>
                        <p>Chương trình tập luyện được cá nhân hóa theo sức khỏe và mục tiêu của từng học viên.</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>
<!-- ChoseUs Section End -->

<!-- About US Section Begin -->
<section class="aboutus-section">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-6 p-0">
                <div class="about-video set-bg" data-setbg="assets/img/about-us.jpg">
                    <a href="https://www.youtube.com/watch?v=EzKkl64rRbM" class="play-btn video-popup"><i
                            class="fa fa-caret-right"></i></a>
                </div>
            </div>
            <div class="col-lg-6 p-0">
                <div class="about-text">
                    <div class="section-title">
                        <span>Về chúng tôi</span>
                        <h2>Những gì chúng tôi đã làm</h2>
                    </div>
                    <div class="at-desc">
                        <p>Chúng tôi là hệ thống phòng tập gym hiện đại với hơn 10 năm kinh nghiệm trong lĩnh vực thể hình và sức khỏe. Với đội ngũ huấn luyện viên chuyên nghiệp và trang thiết bị tiên tiến, chúng tôi cam kết mang đến trải nghiệm tập luyện tốt nhất cho khách hàng.</p>
                    </div>
                    <div class="about-bar">
                        <div class="ab-item">
                            <p>Thể hình</p>
                            <div id="bar1" class="barfiller">
                                <span class="fill" data-percentage="80"></span>
                                <div class="tipWrap">
                                    <span class="tip"></span>
                                </div>
                            </div>
                        </div>
                        <div class="ab-item">
                            <p>Huấn luyện</p>
                            <div id="bar2" class="barfiller">
                                <span class="fill" data-percentage="85"></span>
                                <div class="tipWrap">
                                    <span class="tip"></span>
                                </div>
                            </div>
                        </div>
                        <div class="ab-item">
                            <p>Fitness</p>
                            <div id="bar3" class="barfiller">
                                <span class="fill" data-percentage="75"></span>
                                <div class="tipWrap">
                                    <span class="tip"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- About US Section End -->

<!-- About Intro Extend Begin -->
<section class="spad" style="background:#101318;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Tổng quan dịch vụ</span>
                    <h2>Thiết bị, dinh dưỡng, kế hoạch tập luyện phù hợp nhu cầu</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="about-feature-card">
                    <h5>Thiết bị theo khu vực</h5>
                    <p>Mỗi khu tập được bố trí theo mục tiêu rõ ràng: sức mạnh, giảm mỡ, phục hồi và cardio cường độ cao.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="about-feature-card">
                    <h5>Dinh dưỡng có định hướng</h5>
                    <p>Gợi ý thực đơn theo nhu cầu tăng cơ, giảm mỡ, duy trì thể lực và điều chỉnh theo chỉ số cơ thể hiện tại.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="about-feature-card">
                    <h5>Lịch tập có cấu trúc</h5>
                    <p>Áp dụng các giáo án cơ bản như PUSH PULL LEG giúp bạn dễ theo dõi tiến độ và tăng hiệu quả tập luyện.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-4">
                <div class="about-feature-card">
                    <h5>Phù hợp từng học viên</h5>
                    <p>Mỗi người có mục tiêu khác nhau, hệ thống luôn ưu tiên tư vấn cá nhân hóa thay vì áp dụng một mẫu chung.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- About Intro Extend End -->

<!-- Equipment Showcase Begin -->
<section id="equipment-showcase" class="spad" style="background:#151a22;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Giới thiệu thiết bị</span>
                    <h2>Danh sách thiết bị hiện có</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <?php if (!empty($equipmentImages)): ?>
                <div class="col-lg-12">
                    <div class="equipment-slider owl-carousel">
                        <?php foreach ($equipmentImages as $idx => $imagePath): ?>
                            <div>
                                <img class="equipment-slide-thumb"
                                     src="<?= htmlspecialchars($imagePath) ?>"
                                     alt="Thiết bị phòng gym <?= $idx + 1 ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <p class="equipment-overview">
                        - Thiết bị phòng gym được đầu tư đồng bộ cho nhiều mục tiêu tập luyện như tăng cơ, giảm mỡ,
                        cải thiện sức bền và phục hồi vận động. Tất cả máy móc đều được kiểm tra định kỳ để đảm bảo
                        an toàn, vận hành ổn định và mang lại trải nghiệm tập luyện hiệu quả cho hội viên.
                    </p>
                </div>
            <?php else: ?>
                <div class="col-lg-12">
                    <p class="text-center mb-0">Hiện chưa có ảnh thiết bị trong thư mục uploads/equipment để hiển thị.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<!-- Equipment Showcase End -->

<!-- Nutrition Plans Begin -->
<section id="nutrition-showcase" class="spad" style="background:#101318;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Định hướng dinh dưỡng</span>
                    <h2>Gợi ý kế hoạch dinh dưỡng theo mục tiêu</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="nutrition-overview">
                    <p>
                        - Dinh dưỡng đóng vai trò quan trọng trong quá trình tập luyện và cải thiện sức khỏe. Một chế độ ăn hợp lý,
                        cân đối giữa protein, tinh bột và chất béo sẽ giúp cơ thể có đủ năng lượng để tập luyện, đồng thời hỗ trợ
                        phát triển cơ bắp và giảm mỡ hiệu quả. Bên cạnh đó, việc bổ sung đủ nước và các vitamin cần thiết cũng giúp
                        cơ thể phục hồi nhanh hơn sau mỗi buổi tập, mang lại kết quả rõ rệt và bền vững theo thời gian.
                    </p>
                    <p>
                        - Hiểu được điều đó, bên chúng tôi xây dựng chế độ dinh dưỡng cá nhân hóa dựa trên thể trạng, mục tiêu và thói
                        quen sinh hoạt của từng người, giúp bạn không cần phải tự tìm hiểu hay thử sai. Đặc biệt, khi đăng ký huấn
                        luyện viên cá nhân (PT), bạn sẽ được theo dõi sát sao và liên tục điều chỉnh chế độ ăn uống phù hợp với tiến
                        độ tập luyện, đảm bảo bạn luôn đi đúng hướng và đạt được kết quả nhanh chóng, hiệu quả nhất.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Nutrition Plans End -->

<!-- Basic Training Plan Begin -->
<section id="training-showcase" class="spad" style="background:#151a22;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Giáo án</span>
                    <h2>LỊCH TẬP THAM KHẢO</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="training-overview">
                    <p>
                        - Chúng tôi cung cấp nhiều giáo án tập luyện được xây dựng theo từng mục tiêu cụ thể như tăng cơ,
                        giảm mỡ, cải thiện sức bền và nâng cao thể lực. Các chương trình được thiết kế linh hoạt với nhiều
                        hình thức tập luyện như lịch 6 buổi Push - Pull - Leg hoặc 3 buổi Full Body, phù hợp với quỹ thời
                        gian và nhu cầu của từng người.
                    </p>
                    <p>
                        - Tuy nhiên, để đạt hiệu quả tối ưu, giáo án cần được điều chỉnh theo thể trạng và khả năng riêng của
                        từng hội viên. Vì vậy, khi đăng ký tập luyện hoặc sử dụng dịch vụ huấn luyện viên cá nhân (PT), bạn
                        sẽ được tư vấn và xây dựng lộ trình phù hợp nhất - chi tiết và chính xác hơn so với các giáo án chung.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Basic Training Plan End -->

<!-- Quick Question Begin -->
<section id="personalized-showcase" class="spad" style="background:#101318;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="section-title">
                    <span>Câu hỏi định hướng</span>
                    <h2>Bạn nên tự trả lời trước khi bắt đầu</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-6">
                <div class="question-box">
                    <h6>1. Mục tiêu chính của bạn là gì?</h6>
                    <p>Tăng cơ, giảm mỡ, cải thiện sức bền hay chỉ duy trì sức khỏe? Mục tiêu rõ ràng giúp chọn đúng chương trình.</p>
                </div>
                <div class="question-box">
                    <h6>2. Bạn có thể tập bao lâu mỗi buổi?</h6>
                    <p>30, 45 hay 60 phút? Quỹ thời gian quyết định số bài tập và cường độ phù hợp để không bị quá tải.</p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="question-box">
                    <h6>3. Bạn tập được bao nhiêu buổi mỗi tuần?</h6>
                    <p>Với 3 buổi/tuần, mô hình PPL xoay vòng là lựa chọn cân bằng giữa hiệu quả và phục hồi.</p>
                </div>
                <div class="question-box">
                    <h6>4. Bạn có tiền sử chấn thương không?</h6>
                    <p>Nếu có, hãy báo với huấn luyện viên để điều chỉnh bài và biên độ vận động an toàn hơn.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Quick Question End -->

<!-- Team Section Begin -->
<section class="team-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="team-title">
                    <div class="section-title">
                        <span>Đội ngũ của chúng tôi</span>
                        <h2>LUYỆN TẬP CÙNG CHUYÊN GIA</h2>
                    </div>
                    <a href="trainers.php" class="primary-btn btn-normal appoinment-btn">Xem tất cả</a>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="ts-slider owl-carousel">
                <div class="col-lg-4">
                    <div class="ts-item set-bg" data-setbg="assets/img/team/team-1.jpg">
                        <div class="ts_text">
                            <h4>John Doe</h4>
                            <span>Huấn luyện viên Gym</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="ts-item set-bg" data-setbg="assets/img/team/team-2.jpg">
                        <div class="ts_text">
                            <h4>Jane Smith</h4>
                            <span>Huấn luyện viên Yoga</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="ts-item set-bg" data-setbg="assets/img/team/team-3.jpg">
                        <div class="ts_text">
                            <h4>Mike Johnson</h4>
                            <span>Huấn luyện viên Cardio</span>
                        </div>
                    </div>
                </div>
            </div>
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
                    <h2>Đăng ký ngay để nhận thêm ưu đãi</h2>
                    <div class="bt-tips">Nơi sức khỏe, sắc đẹp và thể hình gặp nhau.</div>
                    <a href="contact.php" class="primary-btn btn-normal">Liên hệ ngay</a>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Banner Section End -->

<?php include 'layout/footer.php'; ?>
