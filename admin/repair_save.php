<?php
include "../config/db.php";

$id = $_POST['id'];
$machine_id = $_POST['machine_id'];
$reporter = $_POST['reporter'];
$position = $_POST['position'];
$type = $_POST['type'];
$detail = $_POST['detail'];
$status = $_POST['status'];

if ($id) {
    // แก้ไข
    $sql = "UPDATE repair_history SET
            machine_id='$machine_id',
            reporter='$reporter',
            position='$position',
            type='$type',
            detail='$detail',
            status='$status',
            updated_at=NOW()
            WHERE id=$id";
} else {
    // เพิ่มใหม่
    $sql = "INSERT INTO repair_history
            (machine_id, reporter, position, type, detail, status, report_time, created_at)
            VALUES
            ('$machine_id', '$reporter', '$position', '$type', '$detail', '$status', NOW(), NOW())";
}

$conn->query($sql);
header("Location: reporthistory.php");
