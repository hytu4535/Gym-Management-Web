import base64
from datetime import datetime
from pathlib import Path

import cv2
import numpy as np
from flask import Flask, jsonify, request

from core.database import FaceDatabase
from core.detector import FaceDetector
from core.embedder import FaceEmbedder, cosine_similarity


app = Flask(__name__)

detector = FaceDetector()
embedder = FaceEmbedder()
db = FaceDatabase()

PROJECT_ROOT = Path(__file__).resolve().parents[1]
FACE_TEMPLATE_DIR = PROJECT_ROOT / "storage" / "face_templates"
LOG_DIR = PROJECT_ROOT / "storage" / "logs"
FACE_TEMPLATE_DIR.mkdir(parents=True, exist_ok=True)
LOG_DIR.mkdir(parents=True, exist_ok=True)


def _decode_base64_image(image_base64):
	if not image_base64:
		return None

	if "," in image_base64:
		image_base64 = image_base64.split(",", 1)[1]

	try:
		image_bytes = base64.b64decode(image_base64)
		image_np = np.frombuffer(image_bytes, dtype=np.uint8)
		return cv2.imdecode(image_np, cv2.IMREAD_COLOR)
	except Exception:
		return None


@app.get("/health")
def health():
	return jsonify({"status": "ok"}), 200


@app.post("/enroll")
def enroll():
	payload = request.get_json(silent=True) or {}
	member_id = payload.get("member_id")
	image_base64 = payload.get("image_base64")

	if not member_id or not image_base64:
		return jsonify({"error": "member_id and image_base64 are required"}), 400

	image = _decode_base64_image(image_base64)
	if image is None:
		return jsonify({"error": "invalid image"}), 400

	face = detector.detect_first_face(image)
	if face is None:
		return jsonify({"error": "face not detected"}), 400

	embedding = embedder.create_embedding(face)
	if embedding is None:
		return jsonify({"error": "failed to extract embedding"}), 400

	filename = f"member_{member_id}_{datetime.now().strftime('%Y%m%d_%H%M%S')}.jpg"
	image_path = FACE_TEMPLATE_DIR / filename
	cv2.imwrite(str(image_path), image)

	db.upsert_face_profile(member_id=member_id, face_vector=embedding.tolist(), image_path=str(image_path))

	return jsonify({"success": True, "member_id": member_id}), 200


@app.post("/verify")
def verify():
	payload = request.get_json(silent=True) or {}
	image_base64 = payload.get("image_base64")
	threshold = float(payload.get("threshold", 0.85))

	if not image_base64:
		return jsonify({"error": "image_base64 is required"}), 400

	image = _decode_base64_image(image_base64)
	if image is None:
		return jsonify({"error": "invalid image"}), 400

	face = detector.detect_first_face(image)
	if face is None:
		return jsonify({"error": "face not detected"}), 400

	query_embedding = embedder.create_embedding(face)
	if query_embedding is None:
		return jsonify({"error": "failed to extract embedding"}), 400

	profiles = db.fetch_face_profiles()
	if not profiles:
		return jsonify({"verified": False, "reason": "no enrolled profiles"}), 200

	best_member_id = None
	best_score = 0.0

	for profile in profiles:
		ref_vector = np.array(profile["face_vector"], dtype=np.float32)
		score = cosine_similarity(query_embedding, ref_vector)
		if score > best_score:
			best_score = score
			best_member_id = profile["member_id"]

	is_success = best_score >= threshold
	log_filename = f"verify_{datetime.now().strftime('%Y%m%d_%H%M%S')}.jpg"
	log_path = LOG_DIR / log_filename

	if not is_success:
		cv2.imwrite(str(log_path), image)

	db.insert_checkin_log(
		member_id=best_member_id if is_success else None,
		confidence=best_score,
		is_success=is_success,
		image_path=str(log_path) if not is_success else None,
		note=None if is_success else "verify_failed",
	)

	return jsonify(
		{
			"verified": is_success,
			"member_id": best_member_id if is_success else None,
			"confidence": round(best_score, 4),
			"threshold": threshold,
		}
	), 200


@app.post("/unenroll")
def unenroll():
	"""Xóa face profile của hội viên"""
	payload = request.get_json(silent=True) or {}
	member_id = payload.get("member_id")

	if not member_id:
		return jsonify({"error": "member_id is required"}), 400

	try:
		db.delete_face_profile(member_id)
		return jsonify({"success": True, "message": "Face profile deleted successfully"}), 200
	except Exception as e:
		return jsonify({"error": str(e)}), 500


@app.get("/profiles")
def get_profiles():
	"""Lấy danh sách tất cả face profiles"""
	try:
		profiles = db.fetch_all_profiles()
		return jsonify({"success": True, "data": profiles}), 200
	except Exception as e:
		return jsonify({"error": str(e)}), 500


if __name__ == "__main__":
	app.run(host="0.0.0.0", port=8000, debug=True)
