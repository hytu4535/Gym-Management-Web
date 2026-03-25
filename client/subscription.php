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

include 'layout/header.php';
?>

<!-- Breadcrumb Section Begin -->
<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Thông tin cá nhân</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Hồ sơ</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- Breadcrumb Section End -->

<style>
    .portal-card {
        background: #151515;
        border: 1px solid #2a2a2a;
        border-radius: 10px;
        padding: 24px;
    }
    .portal-headline {
        color: #ffffff;
        margin-bottom: 6px;
    }
    .portal-subline {
        color: #b3b3b3;
        font-size: 14px;
    }
    .portal-search .form-control {
        background: #1f1f1f;
        border-color: #363636;
        color: #fff;
        height: 46px;
    }
    .portal-tabs.nav-tabs {
        border-bottom: 1px solid #2f2f2f;
        margin-bottom: 20px;
    }
    .portal-tabs .nav-link {
        color: #b3b3b3;
        border: 0;
        border-bottom: 2px solid transparent;
        background: transparent;
        padding: 10px 14px;
    }
    .portal-tabs .nav-link.active {
        color: #f36100;
        border-bottom-color: #f36100;
        background: transparent;
    }
    .portal-table {
        color: #ddd;
        margin-bottom: 0;
    }
    .portal-table thead th {
        border-color: #2f2f2f;
        color: #fff;
        font-weight: 600;
    }
    .portal-table td {
        border-color: #2b2b2b;
        vertical-align: middle;
    }
    .portal-card .form-control,
    .portal-card .custom-select,
    .portal-card textarea {
        background: #1f1f1f;
        border-color: #363636;
        color: #fff;
    }
    .result-card {
        border: 1px solid #353535;
        border-left: 3px solid #f36100;
        border-radius: 8px;
        padding: 10px 12px;
        background: #1b1b1b;
        margin-bottom: 10px;
    }
    .feedback-submit {
        min-width: 170px;
    }
</style>

<!-- Profile Section Begin -->
<section class="profile-section spad">
    <div class="container">
        <div class="portal-card">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="portal-headline">Trung tâm hội viên</h3>
                    <?php if ($currentMember): ?>
                        <div class="portal-subline">Đang xem hội viên <strong><?php echo htmlspecialchars($currentMember['full_name']); ?></strong> (ID: <?php echo (int) $currentMember['id']; ?>)</div>
                    <?php else: ?>
                        <div class="portal-subline text-warning">Chưa có dữ liệu hội viên để hiển thị.</div>
                    <?php endif; ?>
                </div>
                <div class="col-lg-4">
                    <div class="input-group portal-search">
                        <input type="text" id="globalSearch" class="form-control" placeholder="Tìm ưu đãi...">
                        <div class="input-group-append">
                            <button class="btn btn-warning" id="searchBtn" type="button"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="alertBox" class="mt-3"></div>

            <div id="searchResultsCard" class="mt-3" style="display:none;">
                <h5 class="text-white mb-2">Kết quả tìm kiếm</h5>
                <div id="searchResults"></div>
            </div>

            <ul class="nav nav-tabs portal-tabs mt-4" id="memberTabs" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-toggle="tab" href="#tab-promotions" role="tab">Ưu đãi</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-feedback" role="tab">Feedback</a></li>
                <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#tab-notifications" role="tab">Thông báo</a></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-promotions" role="tabpanel">
                    <h5 class="text-white mb-3">Ưu đãi theo hạng thành viên</h5>
                    <div class="table-responsive">
                        <table class="table portal-table table-bordered table-sm">
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

                <div class="tab-pane fade" id="tab-feedback" role="tabpanel">
                    <h5 class="text-white mb-3">Gửi Feedback</h5>
                    <form id="feedbackForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="text-light" for="feedbackRating">Đánh giá</label>
                                    <select class="custom-select" id="feedbackRating" required>
                                        <option value="5">5 - Rất tốt</option>
                                        <option value="4">4 - Tốt</option>
                                        <option value="3">3 - Bình thường</option>
                                        <option value="2">2 - Chưa tốt</option>
                                        <option value="1">1 - Kém</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-group">
                                    <label class="text-light" for="feedbackContent">Nội dung</label>
                                    <textarea class="form-control" id="feedbackContent" rows="3" required></textarea>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="primary-btn feedback-submit">Gửi Feedback</button>
                            </div>
                        </div>
                    </form>

                    <h6 class="text-white mb-3">Lịch sử feedback</h6>
                    <div class="table-responsive">
                        <table class="table portal-table table-bordered table-sm">
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

                <div class="tab-pane fade" id="tab-notifications" role="tabpanel">
                    <h5 class="text-white mb-3">Thông báo của bạn</h5>
                    <div class="table-responsive">
                        <table class="table portal-table table-bordered table-sm">
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
</section>
<!-- Profile Section End -->

<div class="modal fade" id="viewNotificationModal" tabindex="-1" role="dialog" aria-labelledby="viewNotificationModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background:#151515;border:1px solid #2a2a2a;color:#fff;">
            <div class="modal-header" style="border-bottom:1px solid #2a2a2a;">
                <h5 class="modal-title" id="viewNotificationModalLabel">Chi tiết thông báo</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Tiêu đề:</strong> <span id="viewNotificationTitle">-</span></div>
                <div class="mb-2"><strong>Thời gian:</strong> <span id="viewNotificationTime">-</span></div>
                <div class="mb-3"><strong>Trạng thái:</strong> <span id="viewNotificationStatus">-</span></div>
                <div>
                    <strong>Nội dung:</strong>
                    <div id="viewNotificationContent" class="mt-2 p-2" style="background:#1f1f1f;border:1px solid #2f2f2f;border-radius:6px;white-space:pre-wrap;">-</div>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #2a2a2a;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<script>
const currentMemberId = <?php echo $currentMember ? (int) $currentMember['id'] : 0; ?>;
let notificationStore = {};

window.addEventListener('load', function() {
    if (typeof window.jQuery === 'undefined') {
        console.error('jQuery chưa được nạp. Không thể khởi tạo trang hồ sơ.');
        return;
    }

function showAlert(type, message) {
    const html = `<div class="alert alert-${type} alert-dismissible fade show" role="alert">${message}<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button></div>`;
    $('#alertBox').html(html);
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

function money(value) {
    return new Intl.NumberFormat('vi-VN').format(value || 0) + ' VNĐ';
}

function truncateText(value, maxLength = 15) {
    const text = String(value ?? '');
    if (text.length <= maxLength) {
        return text;
    }

    return text.slice(0, maxLength) + '...';
}

function emptyRow(colspan, title, hint = '') {
    const hintHtml = hint ? `<div class="small text-muted mt-1">${escapeHtml(hint)}</div>` : '';
    return `<tr><td colspan="${colspan}" class="text-center py-4"><strong>${escapeHtml(title)}</strong>${hintHtml}</td></tr>`;
}

function formatDate(value) {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);
    if (Number.isNaN(parsed.getTime())) {
        return escapeHtml(value);
    }

    return parsed.toLocaleDateString('vi-VN');
}

function promoValueText(item) {
    const type = String(item.discount_type || '').toLowerCase();
    const value = item.discount_value;

    if (type === 'percentage') {
        return `${Number(value || 0)}%`;
    }

    if (type === 'fixed') {
        return money(value || 0);
    }

    return escapeHtml(value);
}

function renderDashboard(data) {
    const hasAnyData = ['promotions', 'feedbacks', 'notifications']
        .some(key => Array.isArray(data[key]) && data[key].length > 0);

    if (!hasAnyData) {
        showAlert('info', 'Hiện chưa có dữ liệu cho hội viên này. Vui lòng thêm ưu đãi hoặc thông báo trong hệ thống để hiển thị tại đây.');
    }

    const promoRows = (data.promotions || []).map(item => `<tr>
        <td>${escapeHtml(item.name)}</td><td>${escapeHtml(item.discount_type)}</td><td>${promoValueText(item)}</td><td>${formatDate(item.start_date)} - ${formatDate(item.end_date)}</td><td>${item.usage_limit || 'Không giới hạn'}</td>
    </tr>`).join('');
    $('#promotionTableBody').html(promoRows || emptyRow(5, 'Hiện chưa có ưu đãi cá nhân', 'Ưu đãi chỉ xuất hiện khi còn hạn và đúng hạng hội viên của bạn.'));

    const feedbackRows = (data.feedbacks || []).map(item => `<tr>
        <td>${escapeHtml(item.created_at)}</td><td>${escapeHtml(item.rating)}</td><td>${escapeHtml(item.content)}</td><td><span class="badge badge-info">${escapeHtml(item.status)}</span></td>
    </tr>`).join('');
    $('#feedbackTableBody').html(feedbackRows || emptyRow(4, 'Bạn chưa gửi feedback nào', 'Hãy gửi đánh giá để bộ phận chăm sóc hội viên hỗ trợ tốt hơn.'));

    notificationStore = {};
    (data.notifications || []).forEach(item => {
        notificationStore[String(item.id)] = item;
    });

    const notiRows = (data.notifications || []).map(item => `<tr>
        <td>${escapeHtml(truncateText(item.title, 15))}</td><td>${escapeHtml(truncateText(item.content, 15))}</td><td>${escapeHtml(item.created_at)}</td>
        <td>${item.is_read == 1 ? '<span class="badge badge-success">Đã đọc</span>' : '<span class="badge badge-warning">Chưa đọc</span>'}</td>
        <td>
            <button class="btn btn-primary btn-sm view-noti-btn" data-id="${item.id}"><i class="fa fa-eye"></i> Xem</button>
            ${item.is_read == 1 ? '' : `<button class="btn btn-info btn-sm mark-read-btn" data-id="${item.id}"><i class="fa fa-check"></i> Đánh dấu đã đọc</button>`}
            ${item.is_read == 1 ? `<button class="btn btn-danger btn-sm delete-noti-btn" data-id="${item.id}"><i class="fa fa-trash"></i> Xoá</button>` : ''}
        </td>
    </tr>`).join('');
    $('#notificationTableBody').html(notiRows || emptyRow(5, 'Chưa có thông báo nào', 'Thông báo mới sẽ xuất hiện tại đây khi hệ thống gửi đến tài khoản của bạn.'));
}

function openNotificationModal(notification) {
    if (!notification) {
        return;
    }

    $('#viewNotificationTitle').text(notification.title || '-');
    $('#viewNotificationTime').text(notification.created_at || '-');
    $('#viewNotificationStatus').html(notification.is_read == 1
        ? '<span class="badge badge-success">Đã đọc</span>'
        : '<span class="badge badge-warning">Chưa đọc</span>');
    $('#viewNotificationContent').text(notification.content || '-');
    $('#viewNotificationModal').modal('show');
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
            { key: 'promotions', label: 'Ưu đãi cá nhân' }
        ];

        blocks.forEach(block => {
            const list = data[block.key] || [];
            html += `<div class="mb-3"><h6 class="text-white">${block.label} (${list.length})</h6>`;
            if (!list.length) {
                html += '<p class="small text-muted">Không có kết quả</p></div>';
                return;
            }
            list.forEach(item => {
                html += `<div class="result-card"><strong>${escapeHtml(item.title || item.name || item.package_name)}</strong><br><small>${escapeHtml(item.subtitle || item.description || '')}</small></div>`;
            });
            html += '</div>';
        });
        $('#searchResults').html(html);
        $('#searchResultsCard').show();
    }).fail(function() {
        showAlert('danger', 'Lỗi kết nối khi search.');
    });
}

$(document).on('click', '.mark-read-btn', function() {
    const notificationId = $(this).data('id');
    $.post('api.php', { action: 'mark_notification_read', member_id: currentMemberId, notification_id: notificationId }, function(response) {
        if (response.success) {
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể cập nhật thông báo');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Lỗi kết nối khi cập nhật thông báo');
    });
});

$(document).on('click', '.view-noti-btn', function() {
    const notificationId = String($(this).data('id'));
    const notification = notificationStore[notificationId];

    if (!notification) {
        showAlert('warning', 'Không tìm thấy thông báo để hiển thị');
        return;
    }

    openNotificationModal(notification);

    if (Number(notification.is_read) !== 1) {
        $.post('api.php', { action: 'mark_notification_read', member_id: currentMemberId, notification_id: notificationId }, function(response) {
            if (response.success) {
                notification.is_read = 1;
                $('#viewNotificationStatus').html('<span class="badge badge-success">Đã đọc</span>');
                loadDashboard();
            }
        }, 'json');
    }
});

$(document).on('click', '.delete-noti-btn', function() {
    if (!confirm('Xoá thông báo này?')) return;
    const notificationId = $(this).data('id');
    $.post('api.php', { action: 'delete_notification', member_id: currentMemberId, notification_id: notificationId }, function(response) {
        if (response.success) {
            loadDashboard();
        } else {
            showAlert('warning', response.message || 'Không thể xoá thông báo');
        }
    }, 'json').fail(function() {
        showAlert('danger', 'Lỗi kết nối khi xoá thông báo');
    });
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
});
</script>

<?php include 'layout/footer.php'; ?>
