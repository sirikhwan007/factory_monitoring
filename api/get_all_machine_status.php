<?php
header("Content-Type: application/json");

include "../config.php";

// ดึงรายชื่อเครื่องทั้งหมด
$machines = $conn->query("SELECT machine_id FROM machines");

$active = 0;
$stop = 0;
$error = 0;

while ($m = $machines->fetch_assoc()) {

    $id = $m["machine_id"];

    $statusApi = file_get_contents("http://localhost/factory_monitoring/api/get_machine_status.php?id=$id");
    $statusData = json_decode($statusApi, true);

    if (!$statusData || !isset($statusData["status"])) continue;

    if ($statusData["status"] === "active") $active++;
    if ($statusData["status"] === "error")  $error++;
    if ($statusData["status"] === "stop")   $stop++;
}

echo json_encode([
    "active" => $active,
    "error" => $error,
    "stop" => $stop
]);
