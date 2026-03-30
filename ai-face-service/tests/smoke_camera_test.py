import argparse
import sys
import time
from pathlib import Path


def _check_imports():
    targets = [
        ("cv2", "OpenCV"),
        ("mediapipe", "MediaPipe"),
        ("numpy", "NumPy"),
        ("flask", "Flask"),
        ("mysql.connector", "mysql-connector-python"),
    ]

    ok = True
    print("=== Import checks ===")
    for module_name, label in targets:
        try:
            __import__(module_name)
            print(f"[OK] {label}")
        except Exception as exc:
            ok = False
            print(f"[FAIL] {label}: {exc}")
    return ok


def _get_capture(cv2, camera_index):
    # CAP_DSHOW reduces open delay on many Windows machines.
    if hasattr(cv2, "CAP_DSHOW"):
        return cv2.VideoCapture(camera_index, cv2.CAP_DSHOW)
    return cv2.VideoCapture(camera_index)


def _test_camera(camera_index, warmup_frames=20):
    import cv2

    print("\n=== Camera check ===")
    cap = _get_capture(cv2, camera_index)
    if not cap.isOpened():
        print(f"[FAIL] Cannot open camera index {camera_index}")
        return False

    frame = None
    started = time.time()
    try:
        for _ in range(max(1, warmup_frames)):
            ret, frame = cap.read()
            if ret and frame is not None:
                break
            time.sleep(0.03)

        if frame is None:
            print("[FAIL] Camera opened but cannot read frame")
            return False

        h, w = frame.shape[:2]
        print(f"[OK] Camera read frame: {w}x{h}")

        root_dir = Path(__file__).resolve().parents[2]
        out_dir = root_dir / "storage" / "logs"
        out_dir.mkdir(parents=True, exist_ok=True)
        out_file = out_dir / f"camera_smoke_{int(started)}.jpg"
        cv2.imwrite(str(out_file), frame)
        print(f"[OK] Snapshot saved: {out_file}")
        return True
    except Exception as exc:
        print(f"[FAIL] Camera runtime error: {exc}")
        return False
    finally:
        cap.release()


def main():
    parser = argparse.ArgumentParser(
        description="Smoke test for AI service libraries and camera"
    )
    parser.add_argument("--camera-index", type=int, default=0)
    parser.add_argument("--skip-camera", action="store_true")
    args = parser.parse_args()

    imports_ok = _check_imports()
    camera_ok = True

    if not args.skip_camera:
        camera_ok = _test_camera(args.camera_index)

    all_ok = imports_ok and camera_ok
    print("\n=== Summary ===")
    print("PASS" if all_ok else "FAIL")
    return 0 if all_ok else 1


if __name__ == "__main__":
    sys.exit(main())
