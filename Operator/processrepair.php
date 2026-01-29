<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

include __DIR__ . "/../config.php";

// ===============================
// รับค่าจากฟอร์ม
// ===============================
$reporter   = $_POST['reporter']   ?? '';
$position   = $_POST['position']   ?? '';
$type       = $_POST['type']       ?? '';
$machine_id = $_POST['machine_id'] ?? '';
$detail     = $_POST['detail']     ?? '';

// ===============================
// ตรวจสอบข้อมูล
// ===============================
if (
    empty($reporter) ||
    empty($position) ||
    empty($type) ||
    empty($machine_id) ||
    empty($detail)
) {
    die("ข้อมูลไม่ครบถ้วน");
}

// ===============================
// ค่าเริ่มต้น (Operator)
// ===============================
$technician_id = null;   // ✅ ยังไม่มอบหมาย
$username      = null;   // ✅ ยังไม่ระบุช่าง
$status        = 'รอดำเนินการ';

// ===============================
// SQL Insert
// ===============================
$sql = "INSERT INTO repair_history
(
    machine_id,
    reporter,
    technician_id,
    username,
    position,
    type,
    detail,
    status,
    report_time,
    created_at
)
VALUES
(
    ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW()
)";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// ===============================
// bind_param (รองรับ NULL)
// ===============================
$stmt->bind_param(
    "ssisssss",
    $machine_id,
    $reporter,
    $technician_id,
    $username,
    $position,
    $type,
    $detail,
    $status
);

// ===============================
// Execute
// ===============================
if ($stmt->execute()) {
    header("Location: history_operator.php?success=1");
    exit();
} else {
    echo "Execute error: " . $stmt->error;
}

$stmt->close();
$conn->close();
