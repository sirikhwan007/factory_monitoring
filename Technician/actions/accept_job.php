<?php
session_start();
include __DIR__ . "/../../config.php";

// ตรวจสอบ Login + Role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: ../../login.php");
    exit();
}

// ตรวจสอบ POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    header("Location: ../work_orders.php");
    exit();
}

$repair_id = (int)$_POST['id'];
$current_user_id = $_SESSION['user_id'];

// อัปเดต technician_id และเปลี่ยนสถานะเป็น 'กำลังซ่อม'
$sql = "UPDATE repair_history 
        SET technician_id = ?, 
            status = 'กำลังซ่อม'
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $current_user_id, $repair_id);

if ($stmt->execute()) {
    // กลับไปที่หน้าแก้ไขงาน
    header("Location: ../work_detail.php?id=" . $repair_id . "&status=success");
} else {
    header("Location: ../work_orders.php?status=error&msg=เกิดข้อผิดพลาด");
}

$stmt->close();
$conn->close();
?>
