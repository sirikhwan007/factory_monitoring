<?php
include "../config.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

if ($_SESSION['role'] !== 'Manager') {
    header("Location: /factory_monitoring/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Manager Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="./assets/css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<div class="btn-hamburger">
    <i class="fa-solid fa-bars"></i>
</div>

<div class="layout-wrapper">


    <?php include __DIR__ . '/partials/SidebarManager.php'; ?>

    <section class="main">
        <div class="dashboard">
            <h2 class="dashboard-title">Manager Control Panel</h2>
            <p class="text-muted">ภาพรวมเชิงบริหารสำหรับตัดสินใจแบบ Real-time</p>

            <!-- KPI -->
            <div class="row g-4 mt-3">
                <div class="col-md-3">
                    <div class="card shadow-sm kpi-card">
                        <div class="card-body">
                            <h6>เครื่องจักรออนไลน์</h6>
                            <h3 class="text-success">18 / 20</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm kpi-card">
                        <div class="card-body">
                            <h6>Downtime วันนี้</h6>
                            <h3 class="text-danger">3.2%</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm kpi-card">
                        <div class="card-body">
                            <h6>ประวัติการแจ้งซ่อม</h6>
                            <h3 class="text-warning">5 งาน</h3>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card shadow-sm kpi-card">
                        <div class="card-body">
                            <h6>พนักงาน Online</h6>
                            <h3 class="text-primary">12 คน</h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ตารางสถานะเครื่อง -->
            <div class="card shadow-sm mt-4">
                <div class="card-body">
                    <h5>สถานะเครื่องจักรล่าสุด</h5>

                    <table class="table table-hover mt-3">
                        <thead>
                            <tr>
                                <th>Machine</th>
                                <th>สถานะ</th>
                                <th>อุณหภูมิ</th>
                                <th>อัปเดตล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>MC-01</td>
                                <td><span class="badge bg-success">Online</span></td>
                                <td>48°C</td>
                                <td>10:32</td>
                            </tr>
                            <tr>
                                <td>MC-04</td>
                                <td><span class="badge bg-danger">Offline</span></td>
                                <td>-</td>
                                <td>09:58</td>
                            </tr>
                        </tbody>
                    </table>

                </div>
            </div>

        </div>
    </section>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<!-- Sidebar JS (แก้ path สำคัญมาก) -->
<script src="/factory_monitoring/Manager/assets/js/SidebarManager.js"></script>

</body>
</html>
