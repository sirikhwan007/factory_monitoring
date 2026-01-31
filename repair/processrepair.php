<?php
session_start();
include "../config.php";

// 1. รับค่าจาก POST
$machine_id    = $_POST['machine_id'] ?? '';
$reporter      = $_POST['reporter'] ?? '';
$position      = $_POST['position'] ?? '';
$type          = $_POST['type'] ?? '';
$detail        = $_POST['detail'] ?? '';
// รับค่าช่าง ถ้าไม่มีให้เป็น null

$report_time = date("Y-m-d H:i:s");
$status      = 'รอดำเนินการ';

// 2. ใช้ Prepared Statement เพื่อความปลอดภัยและรองรับ ID ยาว
$sql = "INSERT INTO repair_history 
        (machine_id, reporter, position, type, detail, report_time, status, technician_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// "ssssssss" หมายถึงส่งค่าเป็น String ทั้งหมด 8 ตัว 
// ถึงแม้ ID จะเป็นตัวเลข แต่ส่งแบบ String ("s") จะปลอดภัยที่สุดสำหรับเลข 11 หลัก
$stmt->bind_param("ssssssss", 
    $machine_id, 
    $reporter, 
    $position, 
    $type, 
    $detail, 
    $report_time, 
    $status, 
    $technician_id
);

if($stmt->execute()) {
    header("Location: reporthistory.php?id=" . urlencode($machine_id));
    exit();
} else {
    echo "เกิดข้อผิดพลาดในการบันทึก: " . htmlspecialchars($stmt->error);
}
?>