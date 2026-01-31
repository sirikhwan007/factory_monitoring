<?php
session_start();
require_once "../config.php";

/* ===== AUTH ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ===== INPUT ===== */
$machine_id = $_POST['machine_id'] ?? null;
$type       = $_POST['maintenance_type'] ?? 'PM';

if (!$machine_id) {
    die("Invalid machine");
}

$conn->begin_transaction();

try {

    /* ===============================
       1) ตรวจว่า machine มีจริง
       =============================== */
    $stmt = $conn->prepare("
        SELECT machine_id 
        FROM machines 
        WHERE machine_id = ?
    ");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Machine not found");
    }

    /* ===============================
       2) ดึง schedule
       =============================== */
    $stmt = $conn->prepare("
        SELECT next_maintenance_date 
        FROM maintenance_schedule
        WHERE machine_id = ? AND maintenance_type = ?
    ");
    $stmt->bind_param("ss", $machine_id, $type);
    $stmt->execute();
    $schedule = $stmt->get_result()->fetch_assoc();

    if (!$schedule) {
        throw new Exception("Schedule not found");
    }

    $start = $schedule['next_maintenance_date'] . " 09:00:00";
    $end   = $schedule['next_maintenance_date'] . " 10:00:00";

    /* ===============================
       3) INSERT → maintenance_history
       =============================== */
    $stmt = $conn->prepare("
        INSERT INTO maintenance_history
        (
            machine_id,
            maintenance_type,
            maintenance_title,
            performer_name,
            start_time,
            end_time,
            duration_minutes,
            status,
            cost_actual
        )
        VALUES (?, ?, 'PM รายเดือน', ?, ?, ?, 60, 'COMPLETED', 0)
    ");

    $stmt->bind_param(
        "sssss",
        $machine_id,
        $type,
        $_SESSION['username'],
        $start,
        $end
    );
    $stmt->execute();

    /* ===============================
       4) UPDATE รอบถัดไป (+1 เดือน)
       =============================== */
    $stmt = $conn->prepare("
        UPDATE maintenance_schedule
        SET 
            last_maintenance_date = next_maintenance_date,
            next_maintenance_date = DATE_ADD(next_maintenance_date, INTERVAL 1 MONTH)
        WHERE machine_id = ? AND maintenance_type = ?
    ");
    $stmt->bind_param("ss", $machine_id, $type);
    $stmt->execute();

    $conn->commit();

    header("Location: maintenance_schedule.php?success=1");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    echo "Error: " . $e->getMessage();
}
