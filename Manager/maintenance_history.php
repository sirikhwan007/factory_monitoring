<?php
session_start();

// ===== AUTH =====
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

if ($_SESSION['role'] !== 'Manager') {
    header("Location: /login.php");
    exit();
}

// ===== DB =====
include __DIR__ . '/../config.php';   // ต้องมี $conn (mysqli)

// =======================
// FILTER
// =======================
$type = $_GET['type'] ?? 'ALL';   // ALL | PM | CM | PdM

$where = "";
if ($type !== 'ALL') {
    if ($type == 'PM') $where = "WHERE type='Preventive'";
    if ($type == 'CM') $where = "WHERE type='Breakdown'";
    if ($type == 'PdM') $where = "WHERE type='Predictive'";
}

// =======================
// QUERY (repair_history)
// =======================
$sql = "
SELECT 
    id,
    machine_id,
    reporter,
    username,
    position,
    type,
    detail,
    repair_note,
    status,
    report_time,
    repair_time
FROM repair_history
$where
ORDER BY report_time DESC
";

$result = $conn->query($sql);
if(!$result){
    die("SQL ERROR: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Maintenance History</title>

<style>
@import url("https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600&display=swap");

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Sarabun",sans-serif;
}

/* ===== Layout ===== */
body{ background:#f4f6f9; }

.sidebar-wrapper{
    position:fixed;
    left:0;
    top:0;
    height:100vh;
    z-index:1000;
}

.main-content{
    margin-left:260px;
    padding:25px;
}

/* ===== Header ===== */
h2{
    font-size:22px;
    font-weight:600;
    margin-bottom:15px;
    color:#333;
}

/* ===== Filter ===== */
.filter-box{
    margin:15px 0 20px 0;
}

.filter-box a{
    text-decoration:none;
    padding:6px 14px;
    margin-right:6px;
    border-radius:20px;
    font-size:13px;
    background:#ecf0f1;
    color:#333;
    transition:0.2s;
}

.filter-box a:hover{
    background:#6f1e51;
    color:#fff;
}

/* ===== Table ===== */
.table-container{
    background:#fff;
    padding:20px;
    border-radius:14px;
    box-shadow:0 4px 12px rgba(0,0,0,0.05);
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:12px 10px;
    border-bottom:1px solid #eee;
    font-size:14px;
    text-align:left;
}

th{
    background:#f8f9fa;
    font-weight:600;
    color:#444;
}

tr:hover{
    background:#fafafa;
}

/* ===== Badge TYPE ===== */
.badge{
    padding:4px 12px;
    border-radius:20px;
    font-size:12px;
    color:#fff;
    font-weight:500;
}

.PM{ background:#27ae60;}     /* Preventive */
.CM{ background:#c0392b;}     /* Breakdown */
.PdM{ background:#2980b9;}    /* Predictive */
.DEFAULT{ background:#7f8c8d;}/* ไม่ระบุ */

/* ===== STATUS COLOR ===== */
.status-รอดำเนินการ{ color:#e67e22; font-weight:600; }
.status-กำลังซ่อม{ color:#2980b9; font-weight:600; }
.status-สำเร็จ{ color:#27ae60; font-weight:600; }
.status-ซ่อมไม่ได้{ color:#c0392b; font-weight:600; }

/* ===== Responsive ===== */
@media(max-width:992px){
    .main-content{
        margin-left:0;
        padding:15px;
    }
}
</style>
</head>

<body>

<div class="sidebar-wrapper">
    <?php include __DIR__ . '/partials/SidebarManager.php'; ?>
</div>

<div class="main-content">

<h2>ประวัติการบำรุงรักษา (Maintenance History)</h2>

<!-- FILTER -->
<div class="filter-box">
    <a href="?type=ALL">ทั้งหมด</a>
    <a href="?type=PM">PM</a>
    <a href="?type=CM">แจ้งซ่อม (CM)</a>
    <a href="?type=PdM">Predictive (PdM)</a>
</div>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Machine</th>
            <th>ผู้แจ้ง</th>
            <th>ช่าง</th>
            <th>ตำแหน่ง</th>
            <th>ประเภท</th>
            <th>รายละเอียด</th>
            <th>สถานะ</th>
            <th>แจ้งเมื่อ</th>
            <th>ซ่อมเสร็จ</th>
        </tr>
    </thead>
    <tbody>

<?php while($row = $result->fetch_assoc()): ?>

<?php
// ===== TYPE MAPPING =====
$dbType = trim($row['type']);
$typeMap = [
    'Breakdown'  => 'CM',
    'Preventive' => 'PM',
    'Predictive' => 'PdM',
    ''           => 'DEFAULT',
    null         => 'DEFAULT'
];
$typeClass = $typeMap[$dbType] ?? 'DEFAULT';
?>

<tr>
    <td><?= $row['id'] ?></td>
    <td><?= htmlspecialchars($row['machine_id']) ?></td>
    <td><?= htmlspecialchars($row['reporter']) ?></td>
    <td><?= $row['username'] ? htmlspecialchars($row['username']) : '-' ?></td>
    <td><?= htmlspecialchars($row['position']) ?></td>

    <!-- TYPE -->
    <td>
        <span class="badge <?= $typeClass ?>">
            <?= $dbType != '' ? $dbType : 'ไม่ระบุ' ?>
        </span>
    </td>

    <td><?= htmlspecialchars($row['detail']) ?></td>

    <!-- STATUS -->
    <td class="status-<?= $row['status'] ?>">
        <?= $row['status'] ?>
    </td>

    <td><?= $row['report_time'] ?></td>
    <td><?= $row['repair_time'] ? $row['repair_time'] : '-' ?></td>
</tr>

<?php endwhile; ?>

    </tbody>
</table>
</div>

</div>
</body>
</html>
