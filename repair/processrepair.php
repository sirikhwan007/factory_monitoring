<?php
session_start();
include "../config.php";

// 1. รับค่าจาก POST
$machine_id    = $_POST['machine_id'] ?? '';
$reporter      = $_POST['reporter'] ?? '';
$position      = $_POST['position'] ?? '';
$type          = $_POST['type'] ?? '';
$detail        = $_POST['detail'] ?? '';

// --- เพิ่มบรรทัดนี้: รับค่า technician_id จากหน้า report.php ---
$technician_id = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : NULL;

$report_time = date("Y-m-d H:i:s");
$status      = 'รอดำเนินการ';

// 2. ใช้ Prepared Statement
$sql = "INSERT INTO repair_history 
        (machine_id, reporter, position, type, detail, report_time, status, technician_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

// --- แก้ไข bind_param ให้รองรับค่า INTEGER ของ technician_id ---
// เปลี่ยนจาก "ssssssss" เป็น "sssssssi" (i คือ integer ตัวสุดท้าย)
$stmt->bind_param("sssssssi", 
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