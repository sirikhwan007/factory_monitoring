<?php
session_start();
require_once "../config.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /login.php");
    exit();
}

/* ================= KPI ================= */
$totalMachine   = $conn->query("SELECT COUNT(*) c FROM machines")->fetch_assoc()['c'];

$repairToday    = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE DATE(report_time)=CURDATE()")->fetch_assoc()['c'];
$pendingRepair  = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE status IN ('รอดำเนินการ','กำลังซ่อม')")->fetch_assoc()['c'];
$monthRepair    = $conn->query("
    SELECT COUNT(*) c
    FROM repair_history
    WHERE MONTH(report_time)=MONTH(CURDATE())
      AND YEAR(report_time)=YEAR(CURDATE())
")->fetch_assoc()['c'];
$machines_sql = $conn->query("SELECT machine_id, name, mac_address FROM machines ORDER BY machine_id");

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
    <link rel="stylesheet" href="/Manager/assets/css/dashboard.css">
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
                    // แก้ไขบรรทัดนี้: ใส่โครงสร้าง HTML ที่มี ID เพื่อให้ JS เข้าไปเขียนค่าได้
                    ['เครื่อง Online', '<span id="onlineCount">0</span> / ' . $totalMachine, 'success'],
                    ['งานค้าง', $pendingRepair . " งาน", 'danger'],
                    ['แจ้งซ่อมวันนี้', $repairToday . " งาน", 'warning'],
                    ['งานเดือนนี้', $monthRepair . " งาน", 'info'],
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
                            <?php while ($m = $machines_sql->fetch_assoc()): ?>
                                <tr class="machine-row" data-mac="<?= htmlspecialchars($m['mac_address']) ?>" data-id="<?= $m['machine_id'] ?>">
                                    <td><?= $m['machine_id'] ?></td>
                                    <td><?= htmlspecialchars($m['name']) ?></td>
                                    <td class="status-cell">
                                        <span class="badge bg-secondary">กำลังโหลด...</span>
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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


        const API_BASE = "https://factory-monitoring.onrender.com";

        async function updateAllStatuses() {
            let onlineCount = 0;
            const rows = document.querySelectorAll('.machine-row');

            for (let row of rows) {
                const mac = row.getAttribute('data-mac');
                const id = row.getAttribute('data-id');
                const statusCell = row.querySelector('.status-cell');

                try {
                    const res = await fetch(`${API_BASE}/api/latest/${mac}`);
                    const data = await res.json();

                    if (!data || Object.keys(data).length === 0) throw new Error();

                    const temp = Number(data.temperature) || 0;
                    const vib = Number(data.vibration) || 0;
                    const cur = Number(data.current) || 0;
                    const volt = Number(data.voltage) || 0;
                    const pow = Number(data.power) || 0;

                    // Logic การตัดสินใจเดียวกับ Dashboard/Machine List
                    const isDanger = (temp >= 35 || vib >= 15 || cur >= 8 || volt >= 300 || pow >= 20);
                    const isWarning = (temp >= 34 || vib >= 5 || cur >= 5 || volt >= 250 || pow >= 15);
                    const isRunning = (pow > 0.5);

                    let statusText = "";
                    let badgeClass = "";

                    if (isDanger) {
                        statusText = "อันตราย";
                        badgeClass = "bg-danger";
                    } else if (isWarning) {
                        statusText = "ผิดปกติ";
                        badgeClass = "bg-warning text-dark";
                    } else if (isRunning) {
                        statusText = "กำลังทำงาน";
                        badgeClass = "bg-success";
                        onlineCount++; // นับเฉพาะเครื่องที่กำลังทำงานปกติ
                    } else {
                        statusText = "หยุดทำงาน";
                        badgeClass = "bg-secondary";
                    }

                    statusCell.innerHTML = `<span class="badge ${badgeClass}">${statusText}</span>`;

                } catch (error) {
                    statusCell.innerHTML = `<span class="badge bg-dark">Offline</span>`;
                }
            }

            // อัปเดตตัวเลข KPI ด้านบน
            document.getElementById('onlineCount').innerText = onlineCount;
        }

        // อัปเดตครั้งแรกและตั้งเวลาทุก 5 วินาที
        $(document).ready(function() {
            updateAllStatuses();
            setInterval(updateAllStatuses, 5000);
        });
    </script>

</body>

</html>