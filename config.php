<?php
date_default_timezone_set("Asia/Bangkok");

$servername = "bft7bcehnrmpxwyzktj4-mysql.services.clever-cloud.com";
$username = "urqpet8hr9e140i5";
$password = "kHolbtlLF8xcItzwe1Qc";
$dbname = "bft7bcehnrmpxwyzktj4";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
