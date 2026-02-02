<?php
header('Content-Type: application/json');
include "../config.php"; //

$res = $conn->query("SELECT mac_address FROM machines");
$machines = $res->fetch_all(MYSQLI_ASSOC);

$active = 0;
$error = 0;
$stop = 0;

// URL ของ API ภายนอกที่เก็บข้อมูล Real-time
$api_base = "https://factory-monitoring.onrender.com/api/latest/";

foreach ($machines as $m) {
    $mac = $m['mac_address'];
    
    // ดึงข้อมูลล่าสุดของแต่ละเครื่อง
    $json = @file_get_contents($api_base . $mac);
    if ($json) {
        $data = json_decode($json, true);
        
        $temp  = $data['temperature'] ?? 0;
        $vib   = $data['vibration'] ?? 0;
        $cur   = $data['current'] ?? 0;
        $volt  = $data['voltage'] ?? 0;
        $power = $data['power'] ?? 0;

        // ใช้เกณฑ์เดียวกับ machine.js และ machine_detail.js
        $isDanger = ($temp >= 35 || $vib >= 15 || $cur >= 8 || $volt >= 300 || $power >= 20);
        $isWarning = ($temp >= 34 || $vib >= 5 || $cur >= 5 || $volt >= 250 || $power >= 15);
        $isRunning = ($power > 0.5);

        if ($isDanger || $isWarning) {
            $error++;
        } elseif ($isRunning) {
            $active++;
        } else {
            $stop++;
        }
    } else {
        $stop++; // ถ้าดึงข้อมูลไม่ได้ ให้ถือว่าหยุดทำงาน/ออฟไลน์
    }
}

echo json_encode([
    "active" => $active,
    "error"  => $error,
    "stop"   => $stop
]);