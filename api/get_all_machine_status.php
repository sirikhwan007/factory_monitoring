<?php
header('Content-Type: application/json');
include "../config.php"; 


$res = $conn->query("SELECT mac_address FROM machines");
$machines = $res->fetch_all(MYSQLI_ASSOC);

$th_res = $conn->query("SELECT * FROM thresholds");
$thresholds = [];
if ($th_res) {
    while ($row = $th_res->fetch_assoc()) {
        $thresholds[$row['mac_address']] = $row;
    }
}

$default_th = $thresholds['default'] ?? [
    'warn_temp' => 45, 'danger_temp' => 55,
    'warn_vib' => 60, 'danger_vib' => 80,
    'warn_cur' => 4, 'danger_cur' => 8,
    'warn_volt' => 230, 'danger_volt' => 280,
    'warn_power' => 800, 'danger_power' => 1700,
    'warn_energy' => 2500, 'danger_energy' => 3000
];

$active = 0;
$error = 0;
$danger = 0;
$stop = 0;

$api_base = "https://factory-monitoring.onrender.com/api/latest/";

foreach ($machines as $m) {
    $mac = $m['mac_address'];

    $t = $thresholds[$mac] ?? $default_th;
    
    $json = @file_get_contents($api_base . $mac);
    if ($json) {
        $data = json_decode($json, true);
        
        $temp   = $data['temperature'] ?? 0;
        $vib    = $data['vibration'] ?? 0;
        $cur    = $data['current'] ?? 0;
        $volt   = $data['voltage'] ?? 0;
        $power  = $data['power'] ?? 0;
        $energy = $data['energy'] ?? 0;

        $isDanger = (
            $temp >= $t['danger_temp'] || 
            $vib >= $t['danger_vib'] || 
            $cur >= $t['danger_cur'] || 
            $volt >= $t['danger_volt'] || 
            $power >= $t['danger_power'] ||
            $energy >= $t['danger_energy']
        );
        
        $isWarning = (
            $temp >= $t['warn_temp'] || 
            $vib >= $t['warn_vib'] || 
            $cur >= $t['warn_cur'] || 
            $volt >= $t['warn_volt'] || 
            $power >= $t['warn_power'] ||
            $energy >= $t['warn_energy']
        );
        
        $isRunning = ($power > 0.5);

        // จัดกลุ่มสถานะ
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

echo json_encode([
    "active" => $active,
    "error"  => $error,
    "danger" => $danger,
    "stop"   => $stop
]);
?>