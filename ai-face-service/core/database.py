import json
import os
from pathlib import Path

import mysql.connector


def _load_env_file():
	env_path = Path(__file__).resolve().parents[1] / ".env"
	if not env_path.exists():
		return

	for line in env_path.read_text(encoding="utf-8").splitlines():
		line = line.strip()
		if not line or line.startswith("#") or "=" not in line:
			continue
		key, value = line.split("=", 1)
		os.environ.setdefault(key.strip(), value.strip())


_load_env_file()


class FaceDatabase:
	def __init__(self):
		self.host = os.getenv("DB_HOST", "localhost")
		self.port = int(os.getenv("DB_PORT", "3306"))
		self.database = os.getenv("DB_NAME", "gym_management")
		self.user = os.getenv("DB_USER", "root")
		self.password = os.getenv("DB_PASSWORD", "")

	def _connect(self):
		return mysql.connector.connect(
			host=self.host,
			port=self.port,
			user=self.user,
			password=self.password,
			database=self.database,
		)

	def upsert_face_profile(self, member_id, face_vector, image_path=None):
		vector_json = json.dumps(face_vector)
		query = (
			"INSERT INTO face_profiles (member_id, face_vector, image_path, status) "
			"VALUES (%s, %s, %s, 'active') "
			"ON DUPLICATE KEY UPDATE "
			"face_vector = VALUES(face_vector), "
			"image_path = VALUES(image_path), "
			"status = 'active', "
			"updated_at = CURRENT_TIMESTAMP"
		)

		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query, (member_id, vector_json, image_path))
			conn.commit()
		finally:
			conn.close()

	def fetch_face_profiles(self):
		query = "SELECT member_id, face_vector FROM face_profiles"
		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query)
			rows = cursor.fetchall()
			return [
				{"member_id": row[0], "face_vector": json.loads(row[1])}
				for row in rows
			]
		finally:
			conn.close()

	def delete_face_profile(self, member_id):
		"""Xóa face profile của hội viên (đánh dấu là deleted)"""
		query = "UPDATE face_profiles SET status = 'deleted' WHERE member_id = %s"
		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query, (member_id,))
			conn.commit()
		finally:
			conn.close()

	def fetch_face_profiles(self):
		query = "SELECT member_id, face_vector FROM face_profiles WHERE status = 'active'"
		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query)
			rows = cursor.fetchall()
			return [
				{"member_id": row[0], "face_vector": json.loads(row[1])}
				for row in rows
			]
		finally:
			conn.close()

	def fetch_all_profiles(self):
		"""Lấy tất cả face profiles (bao gồm inactive/deleted)"""
		query = "SELECT member_id, status FROM face_profiles"
		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query)
			rows = cursor.fetchall()
			return [
				{"member_id": row[0], "status": row[1]}
				for row in rows
			]
		finally:
			conn.close()

	def insert_checkin_log(self, member_id, confidence, is_success, image_path=None, note=None):
		query = (
			"INSERT INTO face_checkin_logs "
			"(member_id, confidence, is_success, captured_image_path, note) "
			"VALUES (%s, %s, %s, %s, %s)"
		)
		conn = self._connect()
		try:
			cursor = conn.cursor()
			cursor.execute(query, (member_id, confidence, int(is_success), image_path, note))
			conn.commit()
		finally:
			conn.close()
