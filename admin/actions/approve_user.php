<?php
include "../../config.php";

header('Content-Type: application/json; charset=utf-8');

if (!$conn) {
    echo json_encode([
        "success" => false,
        "error" => "DB connection failed"
    ]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode([
        "success" => false,
        "error" => "Invalid request method"
    ]);
    exit;
}

$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    echo json_encode([
        "success" => false,
        "error" => "ไม่พบ user_id"
    ]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE users
    SET status = 'approved'
    WHERE user_id = ?
");

if (!$stmt) {
    echo json_encode([
        "success" => false,
        "error" => "Prepare failed: " . $conn->error
    ]);
    exit;
}

$stmt->bind_param("s", $user_id);

if ($stmt->execute()) {

    echo json_encode([
        "success" => true,
        "user_id" => $user_id,
        "affected_rows" => $stmt->affected_rows
    ]);
} else {

    echo json_encode([
        "success" => false,
        "error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
