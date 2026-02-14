<?php
session_start();
require_once "../config.php";

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /login.php");
    exit();
}

/* ================= INPUT ================= */
$machine_id = $_POST['machine_id'] ?? null;
$type       = $_POST['maintenance_type'] ?? 'PM';

if (!$machine_id) {
    die("Invalid machine");
}

$conn->begin_transaction();

try {

    /* ===== MACHINE INFO ===== */
    $stmt = $conn->prepare("
        SELECT machine_id, machine_id AS machine_code, name
        FROM machines
        WHERE machine_id = ?
    ");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    $machine = $stmt->get_result()->fetch_assoc();

    if (!$machine) {
        throw new Exception("Machine not found");
    }

    /* ===== SCHEDULE ===== */
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
    $duration = 60;

    /* ===== INSERT HISTORY ===== */
    $stmt = $conn->prepare("
        INSERT INTO maintenance_history
        (
            machine_id,
            machine_code,
            machine_name,
            maintenance_type,
            maintenance_title,
            performer_name,
            start_time,
            end_time,
            duration_minutes,
            cost_actual,
            status,
            source
        )
        VALUES (?, ?, ?, ?, 'PM รายเดือน', ?, ?, ?, ?, 0, 'completed', 'manual')
    ");

    $stmt->bind_param(
        "sssssssi",
        $machine['machine_id'],
        $machine['machine_code'],
        $machine['name'],
        $type,
        $_SESSION['username'],
        $start,
        $end,
        $duration
    );
    $stmt->execute();

    /* ===== UPDATE NEXT ROUND ===== */
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
