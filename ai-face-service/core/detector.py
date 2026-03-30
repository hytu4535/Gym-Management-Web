import cv2
import mediapipe as mp


class FaceDetector:
	def __init__(self, min_detection_confidence=0.6):
		self._detector = mp.solutions.face_detection.FaceDetection(
			model_selection=0,
			min_detection_confidence=min_detection_confidence,
		)

	def detect_first_face(self, image_bgr):
		if image_bgr is None or image_bgr.size == 0:
			return None

		height, width = image_bgr.shape[:2]
		image_rgb = cv2.cvtColor(image_bgr, cv2.COLOR_BGR2RGB)
		result = self._detector.process(image_rgb)

		if not result.detections:
			return None

		bbox = result.detections[0].location_data.relative_bounding_box
		x1 = max(int(bbox.xmin * width), 0)
		y1 = max(int(bbox.ymin * height), 0)
		x2 = min(int((bbox.xmin + bbox.width) * width), width)
		y2 = min(int((bbox.ymin + bbox.height) * height), height)

		if x2 <= x1 or y2 <= y1:
			return None

		return image_bgr[y1:y2, x1:x2]
