<?php
header("Content-Type: application/json");

$machine_id = $_GET["id"] ?? null;

if (!$machine_id) {
    echo json_encode(["error" => "missing id"]);
    exit;
}

/* เชื่อม InfluxDB */
$host = "https://influxdb-tcesenior.as2.pitunnel.net";
$token = "mpiI63Hli-vbbRMj_GZk7sahDnsa2_fce8Gqb-sNzkSD1ibrPefDGfjsRJoxEphrORn9knZf0A59XqUivWLmTQ==";
$org = "b79809a86d9bbee5";
$bucket = "Motor-Monitoring";

$query = "from(bucket: \"$bucket\")
  |> range(start: -1m)
  |> filter(fn: (r) => r.machine_id == \"$machine_id\")";

$data = @file_get_contents("$host/api/v2/query?org=$org", false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Authorization: Token $token\r\nContent-Type: application/vnd.flux\r\n",
        'content' => $query
    ]
]));

if (!$data) {
    echo json_encode(["status" => "offline"]);
    exit;
}

// ตัวอย่าง Logic ประเมินสถานะ
$status = "active";
$temp = 0;
$vibration = 0;

preg_match_all('/_value\":([\d\.]+)/', $data, $matches);

if (count($matches[1]) >= 2) {
    $temp = floatval($matches[1][0]);
    $vibration = floatval($matches[1][1]);
}

// กำหนดกฎสถานะ
if ($temp > 70 || $vibration > 5) {
    $status = "error";
}
elseif ($temp < 5) {
    $status = "stop";
}

echo json_encode([
    "status" => $status,
    "temp" => $temp,
    "vibration" => $vibration
]);
