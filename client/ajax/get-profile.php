<?php
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success'=>false,'message'=>'Chưa đăng nhập']);
    exit();
}

$user_id = $_SESSION['user_id'];
$sql = "SELECT u.email, m.full_name, m.phone, m.birthdate, m.gender, m.address
        FROM users u
        JOIN members m ON u.id = m.users_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

echo json_encode(['success'=>true,'data'=>$user]);
