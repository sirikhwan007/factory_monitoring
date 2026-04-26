<?php
include "../../config.php";

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id = $_POST['user_id'] ?? '';

    if (empty($user_id)) {
        echo json_encode([
            "success" => false,
            "error" => "ไม่พบ user_id"
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE users
        SET status = 'rejected'
        WHERE user_id = ?
    ");

    $stmt->bind_param("s", $user_id);

    if ($stmt->execute()) {
        echo json_encode([
            "success" => true
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "error" => "ไม่สามารถอัปเดตข้อมูลได้"
        ]);
    }
}
?>