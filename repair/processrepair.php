<?php
include "../config.php"; // ../ หมายถึงขึ้นไป 1 ระดับโฟลเดอร์

$machine_id = mysqli_real_escape_string($conn, $_POST['machine_id']);
$reporter   = mysqli_real_escape_string($conn, $_POST['reporter']);
$position   = mysqli_real_escape_string($conn, $_POST['position']);
$type       = mysqli_real_escape_string($conn, $_POST['type']);
$detail     = mysqli_real_escape_string($conn, $_POST['detail']);

$report_time = date("Y-m-d H:i:s");

$sql = "INSERT INTO repair_history 
        (machine_id, reporter, position, type, detail, report_time, status)
        VALUES 
        ('$machine_id', '$reporter', '$position', '$type', '$detail', '$report_time', 'รอดำเนินการ')";

if(mysqli_query($conn, $sql)) {
    header("Location: reporthistory.php");
    exit();
} else {
    echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
}
?>
