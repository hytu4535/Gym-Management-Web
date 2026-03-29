<?php
session_start();

$page_title = 'Đăng ký khuôn mặt hội viên';

include '../includes/auth.php';
include '../includes/database.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_MEMBERS');

$db = getDB();
$stmt = $db->query("SELECT id, full_name, status, face_consent FROM members ORDER BY id DESC");
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Đăng ký khuôn mặt hội viên</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="index.php">Home</a></li>
						<li class="breadcrumb-item active">Face Enroll</li>
					</ol>
				</div>
			</div>
		</div>
	</div>

	<section class="content">
		<div class="container-fluid">
			<div id="alertBox" class="alert d-none" role="alert"></div>

			<div class="row">
				<div class="col-md-8">
					<div class="card card-primary">
						<div class="card-header">
							<h3 class="card-title">Camera</h3>
						</div>
						<div class="card-body text-center">
							<div class="camera-frame">
								<div id="scanStatusOverlay" class="scan-status-overlay" aria-live="polite"></div>
								<video id="video" autoplay playsinline style="width:100%;max-height:480px;background:#111;border-radius:8px;"></video>
							</div>
							<canvas id="canvas" width="640" height="480" class="d-none"></canvas>
							<div class="mt-3">
								<button id="startCameraBtn" type="button" class="btn btn-outline-primary mr-2">
									<i class="fas fa-video"></i> Bật camera
								</button>
								<button id="stopCameraBtn" type="button" class="btn btn-outline-danger mr-2" style="display:none;">
									<i class="fas fa-video-slash"></i> Tắt camera
								</button>
								<button id="captureBtn" type="button" class="btn btn-success">
									<i class="fas fa-camera"></i> Chụp & Đăng ký
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-4">
					<div class="card card-secondary">
						<div class="card-header">
							<h3 class="card-title">Thông tin đăng ký</h3>
						</div>
						<div class="card-body">
							<div class="form-group">
								<label for="memberId">Chọn hội viên</label>
								<select id="memberId" class="form-control select2bs4" style="width:100%;">
									<option value="">-- Chọn hội viên --</option>
									<?php foreach ($members as $member): ?>
										<option
											value="<?php echo (int) $member['id']; ?>"
											data-consent="<?php echo (int) ($member['face_consent'] ?? 0); ?>"
											<?php echo $member['status'] !== 'active' ? 'disabled' : ''; ?>
										>
											#<?php echo (int) $member['id']; ?> - <?php echo htmlspecialchars($member['full_name']); ?>
											<?php echo $member['status'] !== 'active' ? ' (không hoạt động)' : ''; ?>
										</option>
									<?php endforeach; ?>
								</select>
							</div>

							<div class="form-group form-check">
								<input type="checkbox" class="form-check-input" id="consentCheck">
								<label class="form-check-label" for="consentCheck">Tôi xác nhận hội viên đã đồng ý quét mặt</label>
							</div>

							<p class="text-muted mb-0">
								Lưu ý: hệ thống ưu tiên lưu vector đặc trưng, hạn chế lưu ảnh gốc khi không cần thiết.
							</p>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>
</div>

<style>
	.camera-frame {
		position: relative;
		width: 100%;
	}

	.scan-status-overlay {
		position: absolute;
		top: 16px;
		left: 50%;
		transform: translateX(-50%);
		z-index: 10;
		padding: 10px 14px;
		border-radius: 999px;
		font-weight: 700;
		font-size: 14px;
		letter-spacing: 0.3px;
		text-transform: uppercase;
		color: #fff;
		display: none;
	}

	.scan-status-overlay.show {
		display: inline-block;
	}

	.scan-status-overlay.info {
		background: rgba(23, 162, 184, 0.92);
	}

	.scan-status-overlay.success {
		background: rgba(40, 167, 69, 0.95);
	}

	.scan-status-overlay.warning {
		background: rgba(255, 193, 7, 0.95);
		color: #222;
	}

	.scan-status-overlay.danger {
		background: rgba(220, 53, 69, 0.95);
	}
</style>

<script>
(() => {
	const video = document.getElementById('video');
	const canvas = document.getElementById('canvas');
	const startCameraBtn = document.getElementById('startCameraBtn');
	const stopCameraBtn = document.getElementById('stopCameraBtn');
	const captureBtn = document.getElementById('captureBtn');
	const memberIdEl = document.getElementById('memberId');
	const consentCheck = document.getElementById('consentCheck');
	const alertBox = document.getElementById('alertBox');
	const scanStatusOverlay = document.getElementById('scanStatusOverlay');

	let stream = null;
	let overlayTimer = null;

	function showAlert(type, message) {
		alertBox.className = 'alert alert-' + type;
		alertBox.textContent = message;
		alertBox.classList.remove('d-none');
	}

	function isVideoReady() {
		return video.videoWidth > 0 && video.videoHeight > 0;
	}

	function showScanStatus(message, type = 'info', autoHideMs = 2200) {
		if (!scanStatusOverlay) {
			return;
		}

		scanStatusOverlay.className = 'scan-status-overlay show ' + type;
		scanStatusOverlay.textContent = message;

		if (overlayTimer) {
			clearTimeout(overlayTimer);
			overlayTimer = null;
		}

		if (autoHideMs > 0) {
			overlayTimer = setTimeout(() => {
				scanStatusOverlay.classList.remove('show');
			}, autoHideMs);
		}
	}

	async function startCamera() {
		if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
			showAlert('danger', 'Trình duyệt không hỗ trợ camera hoặc trang chưa ở ngữ cảnh an toàn (https/localhost).');
			showScanStatus('Không hỗ trợ camera', 'danger', 2600);
			return;
		}

		try {
			stream = await navigator.mediaDevices.getUserMedia({ video: { width: 640, height: 480 } });
			video.srcObject = stream;
			await video.play();
			showAlert('info', 'Camera đã sẵn sàng.');
			showScanStatus('Camera sẵn sàng', 'info', 1200);
			startCameraBtn.style.display = 'none';
			stopCameraBtn.style.display = 'inline-block';
		} catch (err) {
			showAlert('danger', 'Không thể mở camera: ' + err.message);
			showScanStatus('Mở camera thất bại', 'danger', 2600);
		}
	}

	function stopCamera() {
		if (stream) {
			stream.getTracks().forEach(track => track.stop());
			stream = null;
		}
		video.srcObject = null;
		showAlert('info', 'Camera đã tắt.');
		showScanStatus('Camera đã tắt', 'warning', 1500);
		startCameraBtn.style.display = 'inline-block';
		stopCameraBtn.style.display = 'none';
	}

	function captureImageBase64() {
		const ctx = canvas.getContext('2d');
		canvas.width = video.videoWidth || 640;
		canvas.height = video.videoHeight || 480;
		ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
		return canvas.toDataURL('image/jpeg', 0.9);
	}

	async function enrollFace() {
		const memberId = parseInt(memberIdEl.value, 10);
		if (!memberId) {
			showAlert('warning', 'Vui lòng chọn hội viên.');
			showScanStatus('Chưa chọn hội viên', 'warning', 1800);
			return;
		}

		if (!stream) {
			showAlert('warning', 'Vui lòng bật camera trước.');
			showScanStatus('Hãy bật camera trước', 'warning', 1800);
			return;
		}

		if (!isVideoReady()) {
			showAlert('warning', 'Camera chưa sẵn sàng khung hình. Chờ 1-2 giây rồi thử lại.');
			showScanStatus('Camera chưa sẵn sàng', 'warning', 1800);
			return;
		}

		const selected = memberIdEl.options[memberIdEl.selectedIndex];
		const hasConsent = Number(selected.getAttribute('data-consent')) === 1;
		if (!hasConsent && !consentCheck.checked) {
			showAlert('warning', 'Cần xác nhận đồng ý quét mặt trước khi đăng ký.');
			showScanStatus('Thiếu xác nhận đồng ý', 'warning', 1800);
			return;
		}

		captureBtn.disabled = true;
		showAlert('info', 'Đang gửi ảnh đăng ký...');
		showScanStatus('Đang quét...', 'info', 0);

		try {
			const res = await fetch('../api/face/enroll.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					member_id: memberId,
					image_base64: captureImageBase64(),
					consent: consentCheck.checked
				})
			});

			const raw = await res.text();
			let data = null;
			try {
				data = JSON.parse(raw);
			} catch (parseErr) {
				throw new Error('API không trả JSON hợp lệ. HTTP ' + res.status + '. Nội dung: ' + raw.substring(0, 160));
			}

			if (!res.ok) {
				throw new Error(data.message || ('API lỗi HTTP ' + res.status));
			}

			if (!data.success) {
				throw new Error(data.message || 'Đăng ký thất bại');
			}

			showAlert('success', data.message || 'Đăng ký khuôn mặt thành công.');
			showScanStatus('Quét thành công', 'success', 2800);
			consentCheck.checked = false;
		} catch (err) {
			showAlert('danger', err.message);
			showScanStatus('Quét thất bại', 'danger', 3200);
		} finally {
			captureBtn.disabled = false;
		}
	}

	startCameraBtn.addEventListener('click', startCamera);
	stopCameraBtn.addEventListener('click', stopCamera);
	captureBtn.addEventListener('click', enrollFace);

	if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
		showAlert('warning', 'Camera chỉ hoạt động ổn định trên https hoặc localhost.');
	}
})();
</script>

<?php include 'layout/footer.php'; ?>

