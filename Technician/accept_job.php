<?php
session_start();
include __DIR__ . "/../../config.php";

// ===============================
// ตรวจสอบการ Login
// ===============================
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

// ===============================
// รับงานซ่อม
// ===============================
$sql = "UPDATE repair_history
        SET status = 'กำลังซ่อม',
            username = ? 
        WHERE id = ? AND technician_id = ?"; // เช็คทั้ง ID งาน และ ID ช่าง

$stmt = $conn->prepare($sql);
$stmt->bind_param("sii", $username, $repair_id, $user_id);

if ($stmt->execute()) {
    // รับงานสำเร็จ → ไปหน้ารายละเอียดงาน
    header("Location: ../work_detail.php?id=" . $repair_id);
    exit();
} else {
    die("เกิดข้อผิดพลาดในการรับงาน");
}
