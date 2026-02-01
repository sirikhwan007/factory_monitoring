<?php
date_default_timezone_set("Asia/Bangkok");

$servername = "sql308.infinityfree.com";
$username = "if0_40984053";
$password = "0935160117";
$dbname = "if0_40984053_factory_monitoring";

// สร้างการเชื่อมต่อ
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>
