<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

$memberHeight = '';
$memberWeight = '';

if (!empty($_SESSION['user_id'])) {
    $userId = (int) $_SESSION['user_id'];
    $stmtMember = $conn->prepare("SELECT height, weight FROM members WHERE users_id = ? LIMIT 1");
    if ($stmtMember) {
        $stmtMember->bind_param("i", $userId);
        $stmtMember->execute();
        $memberData = $stmtMember->get_result()->fetch_assoc();
        $stmtMember->close();

        if (!empty($memberData)) {
            $memberHeight = $memberData['height'] !== null ? $memberData['height'] : '';
            $memberWeight = $memberData['weight'] !== null ? $memberData['weight'] : '';
        }
    }
}

include 'layout/header.php'; ?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Tính chỉ số BMI</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Tính BMI</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<!-- BMI Calculator Section Begin -->
<section class="bmi-calculator-section spad">
    <div class="container">
        <div class="row">
            <div class="col-lg-6">
                <div class="section-title chart-title">
                    <span>Kiểm tra chỉ số cơ thể</span>
                    <h2>BIỂU ĐỒ CHỈ SỐ BMI</h2>
                </div>
                <div class="chart-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Chỉ số BMI</th>
                                <th>Cân nặng</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="point">Dưới 18.5</td>
                                <td>Thiếu cân</td>
                            </tr>
                            <tr>
                                <td class="point">18.5 - 24.9</td>
                                <td>Bình thường</td>
                            </tr>
                            <tr>
                                <td class="point">25.0 - 29.9</td>
                                <td>Thừa cân</td>
                            </tr>
                            <tr>
                                <td class="point">30.0 - 34.9</td>
                                <td>Béo phì cấp độ 1</td>
                            </tr>
                            <tr>
                                <td class="point">35.0 - 39.9</td>
                                <td>Béo phì cấp độ 2</td>
                            </tr>
                            <tr>
                                <td class="point">Trên 40</td>
                                <td>Béo phì cấp độ 3</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-title chart-calculate-title">
                    <span>Kiểm tra chỉ số cơ thể</span>
                    <h2>TÍNH CHỈ SỐ BMI CỦA BẠN</h2>
                </div>
                <div class="chart-calculate-form">
                    <p>Chỉ số khối cơ thể (BMI) là một chỉ số đơn giản về mối quan hệ giữa cân nặng và chiều cao thường được sử dụng để phân loại tình trạng cân nặng ở người trưởng thành.</p>
                    <form id="bmiForm">
                        <div class="row">
                            <div class="col-sm-6">
                                <input type="number" id="height" placeholder="Chiều cao / cm" required step="0.1" value="<?php echo htmlspecialchars((string) $memberHeight); ?>" <?php echo $memberHeight !== '' ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-sm-6">
                                <input type="number" id="weight" placeholder="Cân nặng / kg" required step="0.1" value="<?php echo htmlspecialchars((string) $memberWeight); ?>" <?php echo $memberWeight !== '' ? 'readonly' : ''; ?>>
                            </div>
                            <div class="col-sm-6">
                                <input type="number" id="age" placeholder="Tuổi" required min="0" step="1">
                                <small id="ageError" class="text-danger" style="display:none;"></small>
                            </div>
                            <div class="col-sm-6">
                                <select id="gender">
                                    <option value="">Giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                </select>
                            </div>
                            <div class="col-lg-12">
                                <button type="button" onclick="calculateBMI()" class="primary-btn">Tính BMI</button>
                            </div>
                        </div>
                    </form>
                    <div id="bmiResult" class="mt-4" style="display:none;">
                        <div class="alert alert-info">
                            <h4>Kết quả:</h4>
                            <p><strong>Chỉ số BMI: </strong><span id="bmiValue"></span></p>
                            <p><strong>Đánh giá: </strong><span id="bmiStatus"></span></p>
                            <p><strong>Lời khuyên: </strong><span id="bmiAdvice"></span></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- BMI Calculator Section End -->

<script>
function showAgeError(message) {
    const ageError = document.getElementById('ageError');
    ageError.textContent = message;
    ageError.style.display = message ? 'block' : 'none';
}

document.getElementById('age').addEventListener('input', function () {
    const ageValue = this.value.trim();
    if (ageValue === '') {
        showAgeError('');
        return;
    }

    if (Number(ageValue) < 0) {
        showAgeError('Tuổi không được âm.');
        return;
    }

    showAgeError('');
});

function calculateBMI() {
    const height = parseFloat(document.getElementById('height').value);
    const weight = parseFloat(document.getElementById('weight').value);
    const age = parseInt(document.getElementById('age').value);
    const gender = document.getElementById('gender').value;
    const ageInput = document.getElementById('age');
    
    if (!height || !weight || !age || !gender) {
        alert('Vui lòng điền đầy đủ thông tin!');
        return;
    }

    if (age < 0) {
        showAgeError('Tuổi không được âm.');
        ageInput.focus();
        return;
    }

    showAgeError('');
    
    // Chuyển đổi chiều cao từ cm sang m
    const heightInMeters = height / 100;
    const bmi = (weight / (heightInMeters * heightInMeters)).toFixed(2);
    
    let status = '';
    let advice = '';
    
    if (bmi < 18.5) {
        status = 'Thiếu cân';
        advice = 'Bạn nên tăng cân bằng cách ăn uống đủ chất dinh dưỡng và tập luyện đều đặn.';
    } else if (bmi >= 18.5 && bmi < 25) {
        status = 'Bình thường - Lý tưởng';
        advice = 'Bạn có cân nặng lý tưởng! Hãy duy trì lối sống lành mạnh.';
    } else if (bmi >= 25 && bmi < 30) {
        status = 'Thừa cân';
        advice = 'Bạn nên giảm cân bằng cách kết hợp chế độ ăn uống lành mạnh và tập luyện thường xuyên.';
    } else if (bmi >= 30 && bmi < 35) {
        status = 'Béo phì cấp độ 1';
        advice = 'Bạn cần giảm cân nghiêm túc. Hãy tham khảo ý kiến chuyên gia dinh dưỡng và huấn luyện viên.';
    } else if (bmi >= 35 && bmi < 40) {
        status = 'Béo phì cấp độ 2';
        advice = 'Tình trạng béo phì nghiêm trọng. Vui lòng tham khảo ý kiến bác sĩ.';
    } else {
        status = 'Béo phì cấp độ 3';
        advice = 'Tình trạng béo phì rất nghiêm trọng. Cần có sự tư vấn và theo dõi của bác sĩ.';
    }
    
    document.getElementById('bmiValue').textContent = bmi;
    document.getElementById('bmiStatus').textContent = status;
    document.getElementById('bmiAdvice').textContent = advice;
    document.getElementById('bmiResult').style.display = 'block';
}
</script>

<?php include 'layout/footer.php'; ?>
