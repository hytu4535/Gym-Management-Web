import cv2


def main():
    # CAP_DSHOW usually improves camera open behavior on Windows.
    cap = cv2.VideoCapture(0, cv2.CAP_DSHOW)

    if not cap.isOpened():
        print("Khong mo duoc camera")
        return

    while True:
        ret, frame = cap.read()
        if not ret:
            print("Khong doc duoc frame tu camera")
            break

        cv2.imshow("Test Camera", frame)

        key = cv2.waitKey(1) & 0xFF
        if key == 27 or key == ord("q"):
            break

    cap.release()
    cv2.destroyAllWindows()


if __name__ == "__main__":
    main()
