<?php include 'layout/header.php'; ?>

    <!-- Breadcrumb Section Begin -->
    <section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <div class="breadcrumb-text">
                        <h2>Tính BMI</h2>
                        <div class="bt-option">
                            <a href="./index.php">Trang chủ</a>
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
                        <h2>TÍNH CHỈ SỐ BMI CỦA BẠN</h2>
                    </div>
                    <div class="chart-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>BMI</th>
                                    <th>Phân loại cân nặng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="point">Dưới 18.5</td>
                                    <td>Thiếu cân</td>
                                </tr>
                                <tr>
                                    <td class="point">18.5 - 24.9</td>
                                    <td>Cân nặng lý tưởng</td>
                                </tr>
                                <tr>
                                    <td class="point">25.0 - 29.9</td>
                                    <td>Thừa cân</td>
                                </tr>
                                <tr>
                                    <td class="point">30.0 - 34.9</td>
                                    <td>Béo phì độ 1</td>
                                </tr>
                                <tr>
                                    <td class="point">35.0 - 39.9</td>
                                    <td>Béo phì độ 2</td>
                                </tr>
                                <tr>
                                    <td class="point">Trên 40</td>
                                    <td>Béo phì độ 3</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="section-title chart-calculate-title">
                        <span>Kiểm tra chỉ số cơ thể</span>
                        <h2>TÍNH TOÁN CHỈ SỐ BMI</h2>
                    </div>
                    <div class="chart-calculate-form">
                        <p>Chỉ số khối cơ thể (BMI) là thước đo mức độ mỡ trong cơ thể dựa trên chiều cao và cân nặng.</p>
                        <form action="#">
                            <div class="row">
                                <div class="col-sm-6">
                                    <input type="text" placeholder="Chiều cao / cm">
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" placeholder="Cân nặng / kg">
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" placeholder="Tuổi">
                                </div>
                                <div class="col-sm-6">
                                    <input type="text" placeholder="Giới tính">
                                </div>
                                <div class="col-lg-12">
                                    <button type="submit">Tính toán</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- BMI Calculator Section End -->

<?php include 'layout/footer.php'; ?>
