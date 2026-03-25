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

<section class="breadcrumb-section set-bg" data-setbg="assets/img/breadcrumb-bg.jpg">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 text-center">
                <div class="breadcrumb-text">
                    <h2>Dinh dưỡng</h2>
                    <div class="bt-option">
                        <a href="index.php">Trang chủ</a>
                        <span>Kế hoạch dinh dưỡng</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

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
    .nutrition-desc-col {
        max-width: 120px;
        width: 120px;
        white-space: nowrap;
    }
    .desc-text {
        display: inline;
        word-break: break-word;
    }
    .result-card {
        border: 1px solid #353535;
        border-left: 3px solid #f36100;
        border-radius: 8px;
        padding: 10px 12px;
        background: #1b1b1b;
        margin-bottom: 10px;
    }
</style>

<section class="profile-section spad">
    <div class="container">
        <div class="portal-card">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="portal-headline">Kế hoạch dinh dưỡng</h3>
                    <?php if ($currentMember): ?>
                        <div class="portal-subline">Đang xem hội viên <strong><?php echo htmlspecialchars($currentMember['full_name']); ?></strong> (ID: <?php echo (int) $currentMember['id']; ?>)</div>
                    <?php else: ?>
                        <div class="portal-subline text-warning">Chưa có dữ liệu hội viên để hiển thị.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="alertBox" class="mt-3"></div>

            <h5 class="text-white mt-4 mb-3">Danh sách kế hoạch</h5>
            <div class="table-responsive">
                <table class="table portal-table table-bordered table-sm">
                    <thead>
                        <tr>
                            <th>Tên kế hoạch</th>
                            <th>Loại</th>
                            <th>Calo</th>
                            <th>BMI phù hợp</th>
                            <th class="nutrition-desc-col">Mô tả</th>
                            <th style="width: 200px;">Món ăn theo thực đơn</th>
                        </tr>
                    </thead>
                    <tbody id="nutritionTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="planMealsModal" tabindex="-1" role="dialog" aria-labelledby="planMealsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content" style="background:#151515;border:1px solid #2a2a2a;color:#fff;">
            <div class="modal-header" style="border-bottom:1px solid #2a2a2a;">
                <h5 class="modal-title" id="planMealsModalLabel">Món ăn theo thực đơn</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div><strong>Thực đơn:</strong> <span id="planMealsName">-</span></div>
                    <div><strong>Loại:</strong> <span id="planMealsType">-</span></div>
                    <div><strong>Calo:</strong> <span id="planMealsCalories">-</span></div>
                </div>

                <div class="table-responsive">
                    <table class="table portal-table table-bordered table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Món ăn</th>
                                <th>Khẩu phần</th>
                                <th>Bữa ăn</th>
                                <th>Suất/ngày</th>
                                <th>Calo</th>
                                <th>Protein</th>
                                <th>Carbs</th>
                                <th>Fat</th>
                                <th>Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody id="planMealsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer" style="border-top:1px solid #2a2a2a;">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="planDescriptionModal" tabindex="-1" role="dialog" aria-labelledby="planDescriptionModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content" style="background:#151515;border:1px solid #2a2a2a;color:#fff;">
            <div class="modal-header" style="border-bottom:1px solid #2a2a2a;">
                <h5 class="modal-title" id="planDescriptionModalLabel">Mô tả thực đơn</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><strong>Thực đơn:</strong> <span id="planDescriptionName">-</span></div>
                <div>
                    <strong>Nội dung mô tả:</strong>
                    <div id="planDescriptionContent" class="mt-2 p-2" style="background:#1f1f1f;border:1px solid #2f2f2f;border-radius:6px;white-space:pre-wrap;">-</div>
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
let nutritionPlanStore = {};

window.addEventListener('load', function() {
    if (typeof window.jQuery === 'undefined') {
        console.error('jQuery chưa được nạp. Không thể khởi tạo trang dinh dưỡng.');
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

function emptyRow(colspan, title, hint = '') {
    const hintHtml = hint ? `<div class="small text-muted mt-1">${escapeHtml(hint)}</div>` : '';
    return `<tr><td colspan="${colspan}" class="text-center py-4"><strong>${escapeHtml(title)}</strong>${hintHtml}</td></tr>`;
}

function truncateText(value, maxLength = 60) {
    const text = String(value ?? '');
    if (text.length <= maxLength) {
        return text;
    }

    return text.slice(0, maxLength) + '...';
}

function renderNutrition(items) {
    nutritionPlanStore = {};

    const rows = (items || []).map(item => `<tr>
        <td>${escapeHtml(item.name)}</td>
        <td>${escapeHtml(item.type)}</td>
        <td>${escapeHtml(item.calories || '-')}</td>
        <td>${escapeHtml(item.bmi_range || '-')}</td>
        <td class="nutrition-desc-col">
            ${(() => {
                const planId = Number(item.id) || 0;
                const planName = String(item.name || '');
                const fullDescription = String(item.description || '');

                nutritionPlanStore[String(planId)] = {
                    name: planName,
                    description: fullDescription,
                };

                if (!fullDescription) {
                    return '-';
                }

                return `<button type="button" class="btn btn-info btn-sm view-desc-btn" data-plan-id="${planId}">Xem</button>`;
            })()}
        </td>
        <td>
            <button type="button" class="btn btn-warning btn-sm view-meals-btn" data-plan-id="${Number(item.id) || 0}">
                Danh sách món ăn
            </button>
        </td>
    </tr>`).join('');

    $('#nutritionTableBody').html(rows || emptyRow(6, 'Chưa có kế hoạch dinh dưỡng', 'Kế hoạch sẽ hiển thị khi được tạo và kích hoạt trong hệ thống.'));
}

function loadNutrition() {
    if (!currentMemberId) {
        showAlert('warning', 'Không tìm thấy hội viên để tải dữ liệu.');
        return;
    }

    $.getJSON('api.php', { action: 'dashboard', member_id: currentMemberId }, function(response) {
        if (!response.success) {
            showAlert('danger', response.message || 'Không tải được dữ liệu dinh dưỡng');
            return;
        }

        const nutritionPlans = (response.data && response.data.nutrition_plans) || [];
        renderNutrition(nutritionPlans);
    }).fail(function() {
        showAlert('danger', 'Lỗi kết nối khi tải dữ liệu dinh dưỡng.');
    });
}

function renderPlanMeals(data) {
    const plan = data.plan || {};
    const items = data.items || [];

    $('#planMealsName').text(plan.name || '-');
    $('#planMealsType').text(plan.type || '-');
    $('#planMealsCalories').text(plan.calories || '-');

    const rows = items.map(item => `<tr>
        <td>${escapeHtml(item.name)}</td>
        <td>${escapeHtml(item.serving_desc || '-')}</td>
        <td>${escapeHtml(item.meal_time || '-')}</td>
        <td>${escapeHtml(item.servings_per_day || '-')}</td>
        <td>${escapeHtml(item.calories ?? '-')}</td>
        <td>${escapeHtml(item.protein ?? '-')}</td>
        <td>${escapeHtml(item.carbs ?? '-')}</td>
        <td>${escapeHtml(item.fat ?? '-')}</td>
        <td>${escapeHtml(item.note || '')}</td>
    </tr>`).join('');

    $('#planMealsBody').html(rows || emptyRow(9, 'Thực đơn này chưa có món ăn', 'Hãy thêm dữ liệu trong nutrition_plan_items để hiển thị tại đây.'));
}

function loadPlanMeals(planId) {
    if (!currentMemberId) {
        showAlert('warning', 'Không tìm thấy hội viên để tải dữ liệu.');
        return;
    }

    $.getJSON('api.php', {
        action: 'nutrition_plan_items',
        member_id: currentMemberId,
        nutrition_plan_id: planId
    }, function(response) {
        if (!response.success) {
            showAlert('warning', response.message || 'Không tải được món ăn theo thực đơn');
            return;
        }

        renderPlanMeals(response.data || {});
        $('#planMealsModal').modal('show');
    }).fail(function() {
        showAlert('danger', 'Lỗi kết nối khi tải món ăn theo thực đơn.');
    });
}

$(document).on('click', '.view-meals-btn', function() {
    const planId = Number($(this).data('plan-id')) || 0;
    if (planId <= 0) {
        showAlert('warning', 'Kế hoạch dinh dưỡng không hợp lệ.');
        return;
    }

    loadPlanMeals(planId);
});

$(document).on('click', '.view-desc-btn', function() {
    const planId = String($(this).data('plan-id'));
    const planData = nutritionPlanStore[planId] || null;

    if (!planData) {
        showAlert('warning', 'Không tìm thấy dữ liệu mô tả cho thực đơn này.');
        return;
    }

    $('#planDescriptionName').text(planData.name || '-');
    $('#planDescriptionContent').text(planData.description || '-');
    $('#planDescriptionModal').modal('show');
});

loadNutrition();
});
</script>

<?php include 'layout/footer.php'; ?>
