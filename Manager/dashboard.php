<?php
session_start();
require_once "../config.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ================= KPI ================= */
$totalMachine   = $conn->query("SELECT COUNT(*) c FROM machines")->fetch_assoc()['c'];
$onlineMachine  = $conn->query("SELECT COUNT(*) c FROM machines WHERE status='online'")->fetch_assoc()['c'];
$repairToday    = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE DATE(report_time)=CURDATE()")->fetch_assoc()['c'];
$pendingRepair  = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE status IN ('รอดำเนินการ','กำลังซ่อม')")->fetch_assoc()['c'];
$monthRepair    = $conn->query("
    SELECT COUNT(*) c
    FROM repair_history
    WHERE MONTH(report_time)=MONTH(CURDATE())
      AND YEAR(report_time)=YEAR(CURDATE())
")->fetch_assoc()['c'];

/* ================= GRAPH DATA ================= */
$statusLabels = $statusCounts = [];
$typeLabels   = $typeCounts   = [];

/* Status */
$statusData = $conn->query("SELECT status, COUNT(*) c FROM repair_history GROUP BY status");
while ($row = $statusData->fetch_assoc()) {
    $statusLabels[] = $row['status'];
    $statusCounts[] = $row['c'];
}

/* Type */
$typeData = $conn->query("SELECT type, COUNT(*) c FROM repair_history GROUP BY type");
while ($row = $typeData->fetch_assoc()) {
    $typeLabels[] = $row['type'];
    $typeCounts[] = $row['c'];
}

/* ================= DATA ================= */
$machines = $conn->query("SELECT machine_id, name, status FROM machines ORDER BY machine_id");
$repairs  = $conn->query("
    SELECT machine_id, type, detail, username, status, report_time
    FROM repair_history
    ORDER BY report_time DESC
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
    <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="layout-wrapper">
        <?php include __DIR__ . '/partials/SidebarManager.php'; ?>

        <section class="main p-4">


            <h3>Manager Control Panel</h3>
            <p class="text-muted">ภาพรวมระบบโรงงาน</p>

            <!-- KPI -->
            <div class="row g-4 mb-4">
                <?php
                $kpis = [
                    ['เครื่อง Online', "$onlineMachine / $totalMachine", 'success'],
                    ['งานค้าง', "$pendingRepair งาน", 'danger'],
                    ['แจ้งซ่อมวันนี้', "$repairToday งาน", 'warning'],
                    ['งานเดือนนี้', "$monthRepair งาน", 'info'],
                ];
                foreach ($kpis as [$title, $value, $color]): ?>
                    <div class="col-md-3">
                        <div class="card kpi <?= $color ?>">
                            <div class="card-body">
                                <small><?= $title ?></small>
                                <h3><?= $value ?></h3>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- GRAPH -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6>สถานะงานซ่อม</h6>
                            <canvas id="statusChart" height="180"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h6>ประเภทงานซ่อม</h6>
                            <canvas id="typeChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MACHINE TABLE -->
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
                            <?php while ($m = $machines->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $m['machine_id'] ?></td>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                    <td>
                                        <?= $m['status'] == 'online'
                                            ? '<span class="badge bg-success">Online</span>'
                                            : '<span class="badge bg-danger">Offline</span>' ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        </tbody>
                    </table>
                </div>
            </div>

            <!-- RECENT REPAIR -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5>การแจ้งซ่อมล่าสุด</h5>
                    <table class="table table-hover align-middle mt-3">
                        <thead>
                            <tr>
                                <th>เครื่อง</th>
                                <th>ประเภท</th>
                                <th>รายละเอียด</th>
                                <th>ผู้แจ้ง</th>
                                <th>สถานะ</th>
                                <th>เวลา</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $repairs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $r['machine_id'] ?></td>
                                    <td><?= htmlspecialchars($r['type']) ?></td>
                                    <td><?= htmlspecialchars($r['detail']) ?></td>
                                    <td><?= htmlspecialchars($r['username']) ?></td>
                                    <td>
                                        <span class="badge bg-<?=
                                                                $r['status'] == 'สำเร็จ' ? 'success' : ($r['status'] == 'กำลังซ่อม' ? 'warning' : 'danger')
                                                                ?>">
                                            <?= $r['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= date('d/m H:i', strtotime($r['report_time'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>

    <!-- CHART SCRIPT -->
    <script>
        new Chart(statusChart, {
            type: 'bar',
            data: {
                labels: <?= json_encode($statusLabels) ?>,
                datasets: [{
                    data: <?= json_encode($statusCounts) ?>,
                    backgroundColor: ['#dc3545', '#ffc107', '#198754'],
                    borderRadius: 8
                }]
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        new Chart(typeChart, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($typeLabels) ?>,
                datasets: [{
                    data: <?= json_encode($typeCounts) ?>
                }]
            },
            options: {
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                cutout: '65%'
            }
        });
    </script>

</body>

</html>