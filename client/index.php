<?php
require_once '../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db = getDB();

function resolveCurrentMember(PDO $db)
{
    $sessionUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;

    if ($sessionUserId > 0) {
        $stmt = $db->prepare("SELECT * FROM members WHERE users_id = ? LIMIT 1");
        $stmt->execute([$sessionUserId]);
        $member = $stmt->fetch();
        if ($member) {
            return $member;
        }
    }

    $memberIdFromQuery = isset($_GET['member_id']) ? intval($_GET['member_id']) : 0;
    if ($memberIdFromQuery > 0) {
        $stmt = $db->prepare("SELECT * FROM members WHERE id = ? LIMIT 1");
        $stmt->execute([$memberIdFromQuery]);
        $member = $stmt->fetch();
        if ($member) {
            return $member;
        }
    }

    $fallbackStmt = $db->query("SELECT * FROM members ORDER BY id ASC LIMIT 1");
    return $fallbackStmt->fetch();
}

$currentMember = resolveCurrentMember($db);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Portal - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../assets/plugins/fontawesome-free/css/all.min.css">
    <style>
        .card-tools-inline {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        .small-muted {
            font-size: 12px;
            color: #6c757d;
        }
        .result-card {
            border-left: 4px solid #007bff;
        }
    </style>
</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand-md navbar-light navbar-white">
        <div class="container">
            <a href="index.php" class="navbar-brand">
                <i class="fas fa-dumbbell text-primary"></i>
                <span class="brand-text font-weight-light">Gym User Portal</span>
            </a>
            <ul class="order-1 order-md-3 navbar-nav navbar-no-expand ml-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Trang chủ</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="content-wrapper">
        <div class="content-header">
            <div class="container">
                <div class="row mb-2">
                    <div class="col-sm-8">
                        <h1 class="m-0">Service & Package System</h1>
                        <?php if ($currentMember): ?>
                            <small class="small-muted">Đang xem với hội viên: <strong><?php echo htmlspecialchars($currentMember['full_name']); ?></strong> (ID: <?php echo (int) $currentMember['id']; ?>)</small>
                        <?php else: ?>
                            <small class="small-muted text-danger">Chưa có dữ liệu hội viên. Vui lòng tạo hội viên trước.</small>
                        <?php endif; ?>
                    </div>
                    <div class="col-sm-4">
                        <div class="input-group input-group-sm float-sm-right">
                            <input type="text" id="globalSearch" class="form-control" placeholder="Search gói tập, dịch vụ, dinh dưỡng, ưu đãi...">
                            <div class="input-group-append">
                                <button class="btn btn-primary" id="searchBtn"><i class="fas fa-search"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="content">
            <div class="container">
                <div id="alertBox"></div>

                <div class="card card-primary card-outline" id="searchResultsCard" style="display:none;">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-search"></i> Kết quả Search</h3>
                    </div>
                    <div class="card-body" id="searchResults"></div>
                </div>

                <div class="card card-outline card-secondary">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="userTabs" role="tablist">
                            <li class="nav-item"><a class="nav-link active" id="packages-tab" data-toggle="pill" href="#packages" role="tab">Gói tập</a></li>
                            <li class="nav-item"><a class="nav-link" id="services-tab" data-toggle="pill" href="#services" role="tab">Dịch vụ</a></li>
                            <li class="nav-item"><a class="nav-link" id="nutrition-tab" data-toggle="pill" href="#nutrition" role="tab">Dinh dưỡng</a></li>
                            <li class="nav-item"><a class="nav-link" id="promotions-tab" data-toggle="pill" href="#promotions" role="tab">Ưu đãi cá nhân</a></li>
                            <li class="nav-item"><a class="nav-link" id="feedback-tab" data-toggle="pill" href="#feedback" role="tab">Feedback</a></li>
                            <li class="nav-item"><a class="nav-link" id="notifications-tab" data-toggle="pill" href="#notifications" role="tab">Thông báo</a></li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="userTabsContent">
                            <div class="tab-pane fade show active" id="packages" role="tabpanel">
                                <h5>Danh sách gói tập</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên gói</th>
                                                <th>Thời hạn</th>
                                                <th>Giá</th>
                                                <th>Mô tả</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="packageTableBody"></tbody>
                                    </table>
                                </div>
                                <hr>
                                <h6>Gói đã đăng ký</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Gói</th>
                                                <th>Bắt đầu</th>
                                                <th>Kết thúc</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="registeredPackageTableBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="services" role="tabpanel">
                                <h5>Danh sách dịch vụ</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên dịch vụ</th>
                                                <th>Loại</th>
                                                <th>Giá</th>
                                                <th>Mô tả</th>
                                            </tr>
                                        </thead>
                                        <tbody id="serviceTableBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="nutrition" role="tabpanel">
                                <h5>Kế hoạch dinh dưỡng</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên kế hoạch</th>
                                                <th>Loại</th>
                                                <th>Calo</th>
                                                <th>BMI phù hợp</th>
                                                <th>Giá</th>
                                                <th>Mô tả</th>
                                            </tr>
                                        </thead>
                                        <tbody id="nutritionTableBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="promotions" role="tabpanel">
                                <h5>Ưu đãi cá nhân</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tên ưu đãi</th>
                                                <th>Loại giảm</th>
                                                <th>Giá trị</th>
                                                <th>Thời gian áp dụng</th>
                                                <th>Lượt dùng tối đa</th>
                                            </tr>
                                        </thead>
                                        <tbody id="promotionTableBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="feedback" role="tabpanel">
                                <h5>Gửi Feedback</h5>
                                <form id="feedbackForm">
                                    <div class="form-group">
                                        <label for="feedbackRating">Đánh giá (1-5)</label>
                                        <select class="form-control" id="feedbackRating" required>
                                            <option value="5">5 - Rất tốt</option>
                                            <option value="4">4 - Tốt</option>
                                            <option value="3">3 - Bình thường</option>
                                            <option value="2">2 - Chưa tốt</option>
                                            <option value="1">1 - Kém</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="feedbackContent">Nội dung</label>
                                        <textarea class="form-control" id="feedbackContent" rows="4" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Gửi Feedback</button>
                                </form>
                                <hr>
                                <h6>Lịch sử feedback</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead>
                                            <tr>
                                                <th>Thời gian</th>
                                                <th>Đánh giá</th>
                                                <th>Nội dung</th>
                                                <th>Trạng thái</th>
                                            </tr>
                                        </thead>
                                        <tbody id="feedbackTableBody"></tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="notifications" role="tabpanel">
                                <h5>Thông báo của bạn</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead>
                                            <tr>
                                                <th>Tiêu đề</th>
                                                <th>Nội dung</th>
                                                <th>Thời gian</th>
                                                <th>Trạng thái</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody id="notificationTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../assets/plugins/jquery/jquery.min.js"></script>
<script src="../assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
const currentMemberId = <?php echo $currentMember ? (int) $currentMember['id'] : 0; ?>;

function showAlert(type, message) {
    const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>`;
    $('#alertBox').html(html);
}

function money(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' VNĐ';
}

function renderDashboard(data) {
    const packageRows = (data.packages || []).map(item => {
        const disabled = item.already_registered ? 'disabled' : '';
        const btnText = item.already_registered ? 'Đã đăng ký' : 'Đăng ký';
        const badge = item.already_registered ? '<span class="badge badge-success">Đã đăng ký</span>' : '';
        return `<tr>
            <td>${item.package_name} ${badge}</td>
            <td>${item.duration_months} tháng</td>
            <td>${money(item.price)}</td>
            <td>${item.description || ''}</td>
            <td><button class="btn btn-primary btn-sm register-package-btn" data-id="${item.id}" ${disabled}><i class="fas fa-plus"></i> ${btnText}</button></td>
        </tr>`;
    }).join('');
    $('#packageTableBody').html(packageRows || '<tr><td colspan="5" class="text-center">Chưa có gói tập</td></tr>');

    const regRows = (data.member_packages || []).map(item => `<tr>
        <td>${item.package_name}</td>
        <td>${item.start_date}</td>
        <td>${item.end_date}</td>
        <td><span class="badge badge-${item.status === 'active' ? 'success' : 'secondary'}">${item.status}</span></td>
        <td>${item.status === 'active' ? `<button class="btn btn-danger btn-sm cancel-package-btn" data-member-package-id="${item.member_package_id || ''}" data-package-id="${item.package_id || ''}"><i class="fas fa-times"></i> Huỷ đăng ký</button>` : ''}</td>
    </tr>`).join('');
    $('#registeredPackageTableBody').html(regRows || '<tr><td colspan="5" class="text-center">Bạn chưa đăng ký gói tập nào</td></tr>');

    const serviceRows = (data.services || []).map(item => `<tr>
        <td>${item.name}</td><td>${item.type}</td><td>${money(item.price)}</td><td>${item.description || ''}</td>
    </tr>`).join('');
    $('#serviceTableBody').html(serviceRows || '<tr><td colspan="4" class="text-center">Chưa có dịch vụ</td></tr>');

    const nutritionRows = (data.nutrition_plans || []).map(item => `<tr>
        <td>${item.name}</td><td>${item.type}</td><td>${item.calories || '-'}</td><td>${item.bmi_range || '-'}</td><td>${money(item.price || 0)}</td><td>${item.description || ''}</td>
    </tr>`).join('');
    $('#nutritionTableBody').html(nutritionRows || '<tr><td colspan="6" class="text-center">Chưa có kế hoạch dinh dưỡng</td></tr>');

    const promoRows = (data.promotions || []).map(item => `<tr>
        <td>${item.name}</td><td>${item.discount_type}</td><td>${item.discount_value}</td><td>${item.start_date} - ${item.end_date}</td><td>${item.usage_limit || 'Không giới hạn'}</td>
    </tr>`).join('');
    $('#promotionTableBody').html(promoRows || '<tr><td colspan="5" class="text-center">Hiện chưa có ưu đãi cá nhân</td></tr>');

    const feedbackRows = (data.feedbacks || []).map(item => `<tr>
        <td>${item.created_at}</td><td>${item.rating}</td><td>${item.content}</td><td><span class="badge badge-info">${item.status}</span></td>
    </tr>`).join('');
    $('#feedbackTableBody').html(feedbackRows || '<tr><td colspan="4" class="text-center">Bạn chưa gửi feedback nào</td></tr>');

    const notiRows = (data.notifications || []).map(item => `<tr>
        <td>${item.title}</td><td>${item.content}</td><td>${item.created_at}</td>
        <td>${item.is_read == 1 ? '<span class="badge badge-success">Đã đọc</span>' : '<span class="badge badge-warning">Chưa đọc</span>'}</td>
        <td>${item.is_read == 1 ? '' : `<button class="btn btn-info btn-sm mark-read-btn" data-id="${item.id}"><i class="fas fa-eye"></i> Đánh dấu đã đọc</button>`}</td>
    </tr>`).join('');
    $('#notificationTableBody').html(notiRows || '<tr><td colspan="5" class="text-center">Chưa có thông báo</td></tr>');
}

function loadDashboard() {
    if (!currentMemberId) {
        showAlert('warning', 'Không tìm thấy hội viên để tải dữ liệu.');
        return;
    }
    $.getJSON('api.php', { action: 'dashboard', member_id: currentMemberId }, function(response) {
        if (!response.success) {
            showAlert('danger', response.message || 'Không tải được dữ liệu');
            return;
        }
        renderDashboard(response.data);
    }).fail(function() {
        showAlert('danger', 'Lỗi kết nối khi tải dữ liệu user.');
    });
}

function performSearch() {
    const keyword = $('#globalSearch').val().trim();
    if (!keyword) {
        $('#searchResultsCard').hide();
        return;
    }
    $.getJSON('api.php', { action: 'search', member_id: currentMemberId, q: keyword }, function(response) {
        if (!response.success) {
            showAlert('danger', response.message || 'Search thất bại');
            return;
        }
        const data = response.data || {};
        let html = '';
        const blocks = [
            { key: 'packages', label: 'Gói tập' },
            { key: 'services', label: 'Dịch vụ' },
            { key: 'nutrition_plans', label: 'Dinh dưỡng' },
            { key: 'promotions', label: 'Ưu đãi cá nhân' }
        ];

        blocks.forEach(block => {
            const list = data[block.key] || [];
            html += `<div class="mb-3"><h6>${block.label} (${list.length})</h6>`;
            if (!list.length) {
                html += '<p class="small-muted">Không có kết quả</p></div>';
                return;
            }
            list.forEach(item => {
                html += `<div class="p-2 mb-2 border rounded result-card"><strong>${item.title || item.name || item.package_name}</strong><br><small>${item.subtitle || item.description || ''}</small></div>`;
            });
            html += '</div>';
        });
        $('#searchResults').html(html);
        $('#searchResultsCard').show();
    }).fail(function() {
        showAlert('danger', 'Lỗi kết nối khi search.');
    });
}

$(document).on('click', '.register-package-btn', function() {
    const packageId = $(this).data('id');
    $.post('api.php', { action: 'register_package', member_id: currentMemberId, package_id: packageId }, function(response) {
        if (response.success) {
            showAlert('success', response.message || 'Đăng ký gói tập thành công');
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể đăng ký gói tập');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Lỗi kết nối khi đăng ký gói tập');
    });
});

$(document).on('click', '.cancel-package-btn', function() {
    const memberPackageId = $(this).data('member-package-id');
    const packageId = $(this).data('package-id');

    if (!confirm('Bạn có chắc muốn huỷ đăng ký gói tập này?')) {
        return;
    }

    $.post('api.php', {
        action: 'cancel_package',
        member_id: currentMemberId,
        member_package_id: memberPackageId,
        package_id: packageId
    }, function(response) {
        if (response.success) {
            showAlert('success', response.message || 'Huỷ đăng ký gói tập thành công');
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể huỷ đăng ký gói tập');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Lỗi kết nối khi huỷ đăng ký gói tập');
    });
});

$(document).on('click', '.mark-read-btn', function() {
    const notificationId = $(this).data('id');
    $.post('api.php', { action: 'mark_notification_read', member_id: currentMemberId, notification_id: notificationId }, function(response) {
        if (response.success) {
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể cập nhật thông báo');
        }
    }, 'json');
});

$('#feedbackForm').on('submit', function(e) {
    e.preventDefault();
    const rating = $('#feedbackRating').val();
    const content = $('#feedbackContent').val().trim();
    if (!content) {
        showAlert('warning', 'Vui lòng nhập nội dung feedback');
        return;
    }
    $.post('api.php', { action: 'submit_feedback', member_id: currentMemberId, rating: rating, content: content }, function(response) {
        if (response.success) {
            showAlert('success', response.message || 'Gửi feedback thành công');
            $('#feedbackContent').val('');
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể gửi feedback');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Lỗi kết nối khi gửi feedback');
    });
});

$('#searchBtn').on('click', performSearch);
$('#globalSearch').on('keypress', function(e) {
    if (e.which === 13) {
        performSearch();
    }
});

loadDashboard();
</script>
</body>
</html>
