import cv2
import numpy as np


class FaceEmbedder:
	def __init__(self, output_size=64):
		self.output_size = output_size

	def create_embedding(self, face_bgr):
		if face_bgr is None or face_bgr.size == 0:
			return None

		gray = cv2.cvtColor(face_bgr, cv2.COLOR_BGR2GRAY)
		resized = cv2.resize(gray, (self.output_size, self.output_size))
		vector = resized.astype(np.float32).flatten()

		norm = np.linalg.norm(vector)
		if norm == 0:
			return None

		return vector / norm


def cosine_similarity(vec1, vec2):
	if vec1 is None or vec2 is None:
		return 0.0

	denom = float(np.linalg.norm(vec1) * np.linalg.norm(vec2))
	if denom == 0:
		return 0.0

	return float(np.dot(vec1, vec2) / denom)
