<?php
require_once "../../config.php";
header('Content-Type: application/json');

$user_id = $_GET['user_id'] ?? '';

if (!$user_id) {
    echo json_encode(["error" => "ไม่มี user_id"]);
    exit;
}

$stmt = $conn->prepare("SELECT user_id, username, email, phone, role FROM users WHERE user_id=?");
$stmt->bind_param("s", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$data = $result->fetch_assoc();

if (!$data) {
    echo json_encode(["error" => "ไม่พบ user"]);
    exit;
}

echo json_encode($data);