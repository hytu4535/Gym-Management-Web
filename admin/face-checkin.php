<?php
session_start();

$page_title = 'Điểm danh bằng khuôn mặt';

include '../includes/auth.php';
include '../includes/auth_permission.php';

checkPermission('MANAGE_MEMBERS');

include 'layout/header.php';
include 'layout/sidebar.php';
?>

<div class="content-wrapper">
	<div class="content-header">
		<div class="container-fluid">
			<div class="row mb-2">
				<div class="col-sm-6">
					<h1 class="m-0">Điểm danh khuôn mặt hội viên</h1>
				</div>
				<div class="col-sm-6">
					<ol class="breadcrumb float-sm-right">
						<li class="breadcrumb-item"><a href="index.php">Home</a></li>
						<li class="breadcrumb-item active">Face Check-in</li>
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
							<h3 class="card-title">Camera check-in</h3>
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
								<button id="verifyBtn" type="button" class="btn btn-success">
									<i class="fas fa-user-check"></i> Quét điểm danh
								</button>
							</div>
						</div>
					</div>
				</div>

				<div class="col-md-4">
					<div class="card card-secondary">
						<div class="card-header">
							<h3 class="card-title">Kết quả nhận diện</h3>
						</div>
						<div class="card-body">
							<div class="form-group">
								<label for="thresholdInput">Ngưỡng so khớp (0-1)</label>
								<input id="thresholdInput" type="number" min="0.1" max="1" step="0.01" value="0.85" class="form-control">
							</div>

							<div class="border rounded p-3" style="min-height:160px;">
								<p class="mb-1"><strong>Hội viên:</strong> <span id="resultMember">-</span></p>
								<p class="mb-1"><strong>Mã hội viên:</strong> <span id="resultMemberId">-</span></p>
								<p class="mb-1"><strong>Độ tin cậy:</strong> <span id="resultConfidence">-</span></p>
								<p class="mb-0"><strong>Trạng thái:</strong> <span id="resultStatus">Chưa quét</span></p>
							</div>
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
		max-width: 92%;
		white-space: nowrap;
		overflow: hidden;
		text-overflow: ellipsis;
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
	const verifyBtn = document.getElementById('verifyBtn');
	const thresholdInput = document.getElementById('thresholdInput');
	const alertBox = document.getElementById('alertBox');
	const scanStatusOverlay = document.getElementById('scanStatusOverlay');

	const resultMember = document.getElementById('resultMember');
	const resultMemberId = document.getElementById('resultMemberId');
	const resultConfidence = document.getElementById('resultConfidence');
	const resultStatus = document.getElementById('resultStatus');

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

	function setResult(data) {
		resultMember.textContent = data.member_name || '-';
		resultMemberId.textContent = data.member_id || '-';
		resultConfidence.textContent = typeof data.confidence === 'number' ? data.confidence.toFixed(4) : '-';
		resultStatus.textContent = data.status || '-';
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

	async function verifyFace() {
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

		const threshold = parseFloat(thresholdInput.value || '0.85');
		verifyBtn.disabled = true;
		showAlert('info', 'Đang xác thực khuôn mặt...');
		showScanStatus('Đang quét...', 'info', 0);

		try {
			const res = await fetch('../api/face/verify.php', {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					image_base64: captureImageBase64(),
					threshold: threshold
				})
			});

			const raw = await res.text();
			let payload = null;
			try {
				payload = JSON.parse(raw);
			} catch (parseErr) {
				throw new Error('API không trả JSON hợp lệ. HTTP ' + res.status + '. Nội dung: ' + raw.substring(0, 160));
			}

			if (!res.ok) {
				throw new Error(payload.message || ('API lỗi HTTP ' + res.status));
			}

			if (!payload.success) {
				throw new Error(payload.message || 'Xác thực thất bại.');
			}

			const data = payload.data || {};
			if (payload.verified) {
				const memberName = data.member_name || 'Không rõ tên';
				setResult({
					member_name: data.member_name,
					member_id: data.member_id,
					confidence: data.confidence,
					status: 'Thành công'
				});
				showAlert('success', payload.message || 'Điểm danh thành công.');
				if (data.package_warning) {
					showAlert('warning', data.package_warning);
					showScanStatus('Quét thành công - ' + memberName + ' - Sắp hết hạn gói', 'warning', 4200);
				} else {
					showScanStatus('Quét thành công - ' + memberName, 'success', 3200);
				}
			} else if (payload.blocked_due_to_package) {
				setResult({
					member_name: data.member_name,
					member_id: data.member_id,
					confidence: data.confidence,
					status: 'Hết hạn gói tập'
				});
				showAlert('danger', payload.message || 'Gói tập đã hết hạn, vui lòng gia hạn.');
				showScanStatus('Gói tập đã hết hạn - Yêu cầu gia hạn', 'danger', 4500);
			} else {
				setResult({
					member_name: null,
					member_id: null,
					confidence: data.confidence,
					status: 'Không khớp'
				});
				showAlert('warning', payload.message || 'Không nhận diện được khuôn mặt phù hợp.');
				showScanStatus('Quét thất bại', 'danger', 3200);
			}
		} catch (err) {
			showAlert('danger', err.message);
			showScanStatus('Quét thất bại', 'danger', 3200);
			setResult({ member_name: null, member_id: null, confidence: null, status: 'Lỗi' });
		} finally {
			verifyBtn.disabled = false;
		}
	}

	startCameraBtn.addEventListener('click', startCamera);
	stopCameraBtn.addEventListener('click', stopCamera);
	verifyBtn.addEventListener('click', verifyFace);

	if (location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1') {
		showAlert('warning', 'Camera chỉ hoạt động ổn định trên https hoặc localhost.');
	}
})();
</script>

<?php include 'layout/footer.php'; ?>

