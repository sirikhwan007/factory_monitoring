<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /login.php");
    exit();
}

$totalMachine   = $conn->query("SELECT COUNT(*) c FROM machines")->fetch_assoc()['c'];

$repairToday    = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE DATE(report_time)=CURDATE()")->fetch_assoc()['c'];
$pendingRepair  = $conn->query("SELECT COUNT(*) c FROM repair_history WHERE status IN ('รอดำเนินการ','กำลังซ่อม')")->fetch_assoc()['c'];
$monthRepair    = $conn->query("
    SELECT COUNT(*) c
    FROM repair_history
    WHERE MONTH(report_time)=MONTH(CURDATE())
      AND YEAR(report_time)=YEAR(CURDATE())
")->fetch_assoc()['c'];
$unassignedPlanCount = $conn->query("SELECT COUNT(*) c FROM maintenance_plan WHERE technician_id IS NULL OR technician_id = ''")->fetch_assoc()['c'];
$machines_sql = $conn->query("SELECT machine_id, name, mac_address FROM machines ORDER BY machine_id");

$statusLabels = $statusCounts = [];
$typeLabels   = $typeCounts   = [];

$statusData = $conn->query("SELECT status, COUNT(*) c FROM repair_history GROUP BY status");
while ($row = $statusData->fetch_assoc()) {
    $statusLabels[] = $row['status'];
    $statusCounts[] = $row['c'];
}

$typeData = $conn->query("SELECT type, COUNT(*) c FROM repair_history GROUP BY type");
while ($row = $typeData->fetch_assoc()) {
    $typeLabels[] = $row['type'];
    $typeCounts[] = $row['c'];
}

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/Manager/assets/css/dashboard.css">
    <link rel="stylesheet" href="/Manager/assets/css/Sidebar.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @keyframes bell-ring {

            0%,
            100% {
                transform: rotate(0);
            }

            20%,
            60% {
                transform: rotate(15deg);
            }

            40%,
            80% {
                transform: rotate(-15deg);
            }
        }

        .ring-active {
            animation: bell-ring 0.5s infinite;
            color: #dc3545 !important;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-top: 80px;
            }

            .sidebar-wrapper {
                position: fixed;
                top: 0;
                left: -260px;
                width: 250px;
                height: 100vh;
                z-index: 2000;
                background-color: #fff;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease-in-out;
            }

            .sidebar-wrapper.active {
                left: 0;
            }

            .sidebar-wrapper .sidebar {
                transform: translateX(0) !important;
                position: relative !important;
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                padding-top: 60px;
            }

            .btn-hamburger {
                display: flex;
                position: fixed;
                top: 15px;
                left: 15px;
                width: 35px;
                height: 35px;
                align-items: center;
                justify-content: center;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
                z-index: 3000;
                font-size: 20px;
                cursor: pointer;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1900;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>

<body>
    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active'); document.querySelector('.sidebar-overlay').classList.toggle('active');">
        <i class="fa-solid fa-bars"></i>
    </div>
    <div class="sidebar-overlay" onclick="document.querySelector('.sidebar-wrapper').classList.remove('active'); this.classList.remove('active')"></div>
    <div class="sidebar-wrapper">
        <?php include 'partials/SidebarManager.php'; ?>
    </div>

    <section class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3>Manager Control Panel</h3>
                <p class="text-muted mb-0">ภาพรวมระบบโรงงาน</p>
            </div>
            
            <div id="maintenance-alert" class="position-relative" style="cursor: pointer; font-size: 1.8rem;" 
                 title="มีแผนซ่อมบำรุง <?= $unassignedPlanCount ?> รายการที่ยังไม่มีผู้รับผิดชอบ!" 
                 onclick="location.href='/machine_list/machine.php'">
                
                <i class="fa-solid fas fa-user-circle <?= $unassignedPlanCount > 0 ? 'ring-active' : 'text-secondary' ?>"></i>
                
                <?php if ($unassignedPlanCount > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?= $unassignedPlanCount ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <?php
            $kpis = [
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
                <div class="table-responsive">
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
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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

        const thresholdsCache = {};

        setInterval(() => {
            for (let mac in thresholdsCache) {
                delete thresholdsCache[mac];
            }
        }, 60000);

        async function updateAllStatuses() {
            let onlineCount = 0;
            const rows = document.querySelectorAll('.machine-row');

            for (let row of rows) {
                const mac = row.getAttribute('data-mac');
                const id = row.getAttribute('data-id');
                const statusCell = row.querySelector('.status-cell');

                // ข้ามถ้าไม่มี MAC Address
                if (!mac || mac === "") {
                    statusCell.innerHTML = `<span class="badge bg-secondary">No MAC</span>`;
                    continue;
                }
                try {
                    const res = await fetch(`${API_BASE}/api/latest/${mac}`);
                    if (!res.ok) throw new Error("Network response was not ok");
                    const data = await res.json();

                    if (!data || Object.keys(data).length === 0) throw new Error("No data");

                    let t = thresholdsCache[mac];
                    if (!t) {
                        const thRes = await fetch(`${API_BASE}/api/thresholds/${mac}`);
                        if (thRes.ok) {
                            t = await thRes.json();
                            thresholdsCache[mac] = t;
                        } else {
                            throw new Error("Cannot fetch threshold");
                        }
                    }

                    const temp = Number(data.temperature) || 0;
                    const vib = Number(data.vibration) || 0;
                    const cur = Number(data.current) || 0;
                    const volt = Number(data.voltage) || 0;
                    const pow = Number(data.power) || 0;
                    const energy = Number(data.energy) || 0;

                    const isDanger = (
                        temp >= t.danger_temp ||
                        vib >= t.danger_vib ||
                        cur >= t.danger_cur ||
                        volt >= t.danger_volt ||
                        pow >= t.danger_power ||
                        energy >= t.danger_energy
                    );

                    const isWarning = (
                        temp >= t.warn_temp ||
                        vib >= t.warn_vib ||
                        cur >= t.warn_cur ||
                        volt >= t.warn_volt ||
                        pow >= t.warn_power ||
                        energy >= t.warn_energy
                    );

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
                        onlineCount++;
                    } else {
                        statusText = "หยุดทำงาน";
                        badgeClass = "bg-secondary";
                    }

                    statusCell.innerHTML = `<span class="badge ${badgeClass}">${statusText}</span>`;

                } catch (error) {
                    statusCell.innerHTML = `<span class="badge bg-dark">Offline</span>`;
                }
            }
            document.getElementById('onlineCount').innerText = onlineCount;
        }
        $(document).ready(function() {
            updateAllStatuses();
            setInterval(updateAllStatuses, 5000); // ดึงข้อมูลทุก 5 วินาที
        });
    </script>

</body>

</html>