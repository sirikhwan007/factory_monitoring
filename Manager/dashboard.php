<?php
session_start();
require_once "../config.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ================= KPI ================= */

/* เครื่องจักร */
$totalMachine = $conn->query("
    SELECT COUNT(*) c FROM machines
")->fetch_assoc()['c'];

$onlineMachine = $conn->query("
    SELECT COUNT(*) c FROM machines WHERE status='online'
")->fetch_assoc()['c'];

/* งานซ่อมวันนี้ */
$maintenanceToday = $conn->query("
    SELECT COUNT(*) c 
    FROM maintenance_history 
    WHERE DATE(start_time)=CURDATE()
")->fetch_assoc()['c'];

/* งานค้าง */
$pendingRepair = $conn->query("
    SELECT COUNT(*) c
    FROM maintenance_history
    WHERE status IN ('PENDING','IN_PROGRESS')
")->fetch_assoc()['c'];

/* งานซ่อมเดือนนี้ */
$monthRepair = $conn->query("
    SELECT COUNT(*) c
    FROM maintenance_history
    WHERE MONTH(start_time)=MONTH(CURDATE())
      AND YEAR(start_time)=YEAR(CURDATE())
")->fetch_assoc()['c'];

/* ================= DATA ================= */

/* เครื่องจักร */
$machines = $conn->query("
    SELECT machine_id, name, status
    FROM machines
    ORDER BY machine_id
");

/* การแจ้งซ่อมล่าสุด */
$repairs = $conn->query("
    SELECT 
        machine_id,
        maintenance_type,
        maintenance_title,
        performer_name,
        status,
        start_time
    FROM maintenance_history
    ORDER BY start_time DESC
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Manager Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="./assets/css/dashboard.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="layout-wrapper">
<?php include __DIR__.'/partials/SidebarManager.php'; ?>

<section class="main p-4">

<h3>Manager Control Panel</h3>
<p class="text-muted">ภาพรวมระบบโรงงาน (ข้อมูลจริง)</p>

<!-- KPI -->
<div class="row g-4 mb-4">

    <div class="col-md-3">
        <div class="card kpi success">
            <div class="card-body">
                <small>เครื่อง Online</small>
                <h3><?= $onlineMachine ?> / <?= $totalMachine ?></h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card kpi danger">
            <div class="card-body">
                <small>งานค้าง</small>
                <h3><?= $pendingRepair ?> งาน</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card kpi warning">
            <div class="card-body">
                <small>Maintenance วันนี้</small>
                <h3><?= $maintenanceToday ?> งาน</h3>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card kpi info">
            <div class="card-body">
                <small>งานเดือนนี้</small>
                <h3><?= $monthRepair ?> งาน</h3>
            </div>
        </div>
    </div>

</div>

<!-- สถานะเครื่อง -->
<div class="card shadow-sm mb-4">
<div class="card-body">

<h5>สถานะเครื่องจักร</h5>

<table class="table table-hover align-middle mt-3">
<thead>
<tr>
    <th>ID</th>
    <th>ชื่อเครื่อง</th>
    <th>สถานะ</th>
</tr>
</thead>
<tbody>

<?php while($m = $machines->fetch_assoc()): ?>
<tr>
    <td><?= $m['machine_id'] ?></td>
    <td><?= htmlspecialchars($m['name']) ?></td>
    <td>
        <?php if ($m['status']=='online'): ?>
            <span class="badge bg-success">Online</span>
        <?php else: ?>
            <span class="badge bg-danger">Offline</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

<!-- การแจ้งซ่อมล่าสุด -->
<div class="card shadow-sm">
<div class="card-body">

<h5>การแจ้งซ่อมล่าสุด</h5>

<table class="table table-hover align-middle mt-3">
<thead>
<tr>
    <th>เครื่อง</th>
    <th>ประเภท</th>
    <th>งาน</th>
    <th>ผู้ดำเนินการ</th>
    <th>สถานะ</th>
    <th>เวลา</th>
</tr>
</thead>
<tbody>

<?php while($r = $repairs->fetch_assoc()): ?>
<tr>
    <td><?= $r['machine_id'] ?></td>
    <td><?= $r['maintenance_type'] ?></td>
    <td><?= htmlspecialchars($r['maintenance_title']) ?></td>
    <td><?= $r['performer_name'] ?></td>
    <td>
        <?php if($r['status']=='COMPLETED'): ?>
            <span class="badge bg-success">เสร็จแล้ว</span>
        <?php elseif($r['status']=='IN_PROGRESS'): ?>
            <span class="badge bg-warning">กำลังทำ</span>
        <?php else: ?>
            <span class="badge bg-danger">รอดำเนินการ</span>
        <?php endif; ?>
    </td>
    <td><?= date('d/m H:i', strtotime($r['start_time'])) ?></td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</div>

</section>
</div>

</body>
</html>
