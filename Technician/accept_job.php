<?php
session_start();
include __DIR__ . "/../../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit();
}

if (!isset($_POST['id'])) {
    die("ไม่พบรหัสงานซ่อม");
}

$repair_id = intval($_POST['id']);
$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'ช่างเทคนิค';

$sql = "UPDATE repair_history
        SET status = 'กำลังซ่อม',
            username = ? 
        WHERE id = ? AND technician_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $username, $repair_id, $user_id);

if ($stmt->execute()) {
    header("Location: ../work_detail.php?id=" . $repair_id);
    exit();
} else {
    die("เกิดข้อผิดพลาดในการรับงาน");
}
