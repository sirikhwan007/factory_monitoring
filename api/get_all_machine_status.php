<?php
header('Content-Type: application/json');
include "../config.php"; 

$res = $conn->query("SELECT mac_address FROM machines");
$machines = $res->fetch_all(MYSQLI_ASSOC);

$active = 0;
$error = 0;
$danger = 0; // 1. เพิ่มตัวแปรนับสถานะอันตราย
$stop = 0;

$api_base = "https://factory-monitoring.onrender.com/api/latest/";

foreach ($machines as $m) {
    $mac = $m['mac_address'];
    
    $json = @file_get_contents($api_base . $mac);
    if ($json) {
        $data = json_decode($json, true);
        
        $temp  = $data['temperature'] ?? 0;
        $vib   = $data['vibration'] ?? 0;
        $cur   = $data['current'] ?? 0;
        $volt  = $data['voltage'] ?? 0;
        $power = $data['power'] ?? 0;

        // เกณฑ์การวัด
        $isDanger = ($temp >= 35 || $vib >= 15 || $cur >= 8 || $volt >= 300 || $power >= 20);
        $isWarning = ($temp >= 34 || $vib >= 5 || $cur >= 5 || $volt >= 250 || $power >= 15);
        $isRunning = ($power > 0.5);

        // 2. แยก Logic การนับให้ชัดเจน (ต้องเช็ก Danger ก่อนเสมอ)
        if ($isDanger) {
            $danger++;
        } elseif ($isWarning) {
            $error++;
        } elseif ($isRunning) {
            $active++;
        } else {
            $stop++;
        }
    } else {
        $stop++; 
    }
}

// 3. ส่งค่า danger กลับไปด้วย
echo json_encode([
    "active" => $active,
    "error"  => $error,
    "danger" => $danger,
    "stop"   => $stop
]);