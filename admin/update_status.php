<?php
include __DIR__ . "/../config.php";

if(isset($_POST['id']) && isset($_POST['status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];

    if($status === 'สำเร็จ') {
        $repair_time = date("Y-m-d H:i:s");
        $sql = "UPDATE repair_history SET status='$status', repair_time='$repair_time' WHERE id=$id";
    } else {
        $sql = "UPDATE repair_history SET status='$status' WHERE id=$id";
    }

    if(mysqli_query($conn, $sql)) {
        header("Location: reporthistory.php");
        exit();
    } else {
        echo "เกิดข้อผิดพลาด: " . mysqli_error($conn);
    }
} else {
    header("Location: reporthistory.php");
    exit();
}
?>
