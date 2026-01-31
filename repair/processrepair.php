<?php
session_start();
include "../config.php";

// 1. รับค่าที่จำเป็น (machine_id ห้ามว่าง)
$repair_id   = $_POST['id'] ?? null;
$machine_id  = $_POST['machine_id'] ?? null;
$detail      = $_POST['detail'] ?? '';
$type        = $_POST['type'] ?? 'Preventive';
$tech_id     = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null;
$repair_note = $_POST['repair_note'] ?? '';

// ตรวจสอบเบื้องต้น
if (!$machine_id) {
    die("Error: ไม่พบ Machine ID");
}

if ($repair_id && $repair_id !== 'ใหม่') {
    // --- กรณีแก้ไข (UPDATE) ---
    // ใช้สถานะเดิมจากฐานข้อมูล หรือกำหนด Logic ใหม่ที่นี่
    $sql = "UPDATE repair_history SET 
            detail = ?, repair_note = ?, technician_id = ?, type = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisi", $detail, $repair_note, $tech_id, $type, $repair_id);
} else {
    // --- กรณีแจ้งใหม่ (INSERT) ---
    $reporter = $_SESSION['username'] ?? 'System';
    $pos      = $_SESSION['role'] ?? '-';
    $default_status = 'รอดำเนินการ';

    $sql = "INSERT INTO repair_history (machine_id, reporter, position, type, detail, status, technician_id, report_time) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ssssssi", $machine_id, $reporter, $pos, $type, $detail, $default_status, $tech_id);
}

if ($stmt->execute()) {
    header("Location: reporthistory.php?id=" . $machine_id);
    exit();
} else {
    echo "เกิดข้อผิดพลาด: " . $stmt->error;
}