<?php
session_start();
include "../config.php"; 

$user_role = $_SESSION['role'] ?? 'Admin';

// เช็กล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}
// ตรวจสิทธิ์แอดมิน
if ($_SESSION['role'] !== 'Admin') {
    header("Location: /factory_monitoring/login.php");
    exit();
}
// ตั้งค่าหน้าปัจจุบัน
$page = 'dashboard';

/* -----------------------------------------------------
    MACHINE OVERVIEW
----------------------------------------------------- */

$total_machines  = $conn->query("SELECT COUNT(*) FROM machines")->fetch_row()[0];
$total_danger = $conn->query("SELECT COUNT(*) FROM machines WHERE status='อันตราย'")->fetch_row()[0];

/* -----------------------------------------------------
    USER OVERVIEW
----------------------------------------------------- */
$total_users     = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
$role_admin      = $conn->query("SELECT COUNT(*) FROM users WHERE role='Admin'")->fetch_row()[0];
$role_manager    = $conn->query("SELECT COUNT(*) FROM users WHERE role='Manager'")->fetch_row()[0];
$role_technician = $conn->query("SELECT COUNT(*) FROM users WHERE role='Technician'")->fetch_row()[0];
$role_operator   = $conn->query("SELECT COUNT(*) FROM users WHERE role='Operator'")->fetch_row()[0];

/* -----------------------------------------------------
   🔹 REPAIR REQUEST OVERVIEW (งานซ่อม / แจ้งซ่อม)
----------------------------------------------------- */
$sql_total = "SELECT COUNT(*) AS total FROM repair_requests";
$total_repair = $conn->query($sql_total)->fetch_assoc()['total'];
$total = $total_repair;

$sql_pending = "SELECT COUNT(*) AS pending FROM repair_requests WHERE status='pending'";
$pending = $conn->query($sql_pending)->fetch_assoc()['pending'];

$sql_in_progress = "SELECT COUNT(*) AS in_progress FROM repair_requests WHERE status='in_progress'";
$in_progress = $conn->query($sql_in_progress)->fetch_assoc()['in_progress'];

$sql_completed = "SELECT COUNT(*) AS completed FROM repair_requests WHERE status='completed'";
$completed = $conn->query($sql_completed)->fetch_assoc()['completed'];

$total_repair = $conn->query("SELECT COUNT(*) FROM repair_history")->fetch_row()[0];
$total = $total_repair;

$pending = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='รอดำเนินการ'")->fetch_row()[0];
$in_progress = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='กำลังซ่อม'")->fetch_row()[0];
$completed = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='สำเร็จ'")->fetch_row()[0];
$cancelled = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='ยกเลิก'")->fetch_row()[0];

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
];

// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Admin'];

$recent_logs = $conn->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 10");

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
    </style>
</head>

<body>

    <div class="btn-hamburger">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main">

        <?php include $sidebar_file; ?>

        <div class="container-fluid">

            <div class="dashboard">

                <!-- Machine Overview -->
                <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                    <h4>ข้อมูลเครื่องจักร</h4>
                    <div id="notification-bell" class="position-relative" style="cursor: pointer; font-size: 1.5rem;">
                        <i class="fa-solid fa-bell text-secondary"></i>
                        <span id="alert-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">
                            !
                        </span>
                    </div>
                </div>
                <div class="row mb-4 g-3">
                    <div class="col-lg col-md-4 col-6">
                        <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=all'">
                            <h5 class="text-muted">เครื่องจักรทั้งหมด</h5>
                            <h2 class="fw-bold text-primary"><?= $total_machines ?></h2>
                        </div>
                    </div>

                    <div class="col-lg col-md-4 col-6">
                        <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=กำลังทำงาน'">
                            <h5 class="text-muted text-success">กำลังทำงาน</h5>
                            <h2 class="fw-bold text-success" id="activeCount">0</h2>
                        </div>
                    </div>

                    <div class="col-lg col-md-4 col-6">
                        <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=ผิดปกติ'">
                            <h5 class="text-muted text-warning">ผิดปกติ</h5>
                            <h2 class="fw-bold text-warning" id="errorCount">0</h2>
                        </div>
                    </div>

                    <div class="col-lg col-md-6 col-6">
                        <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer; "
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=อันตราย'">
                            <h5 class="text-muted">อันตราย</h5>
                            <h2 class="fw-bold" style="color: #fd7e14;" id="dangerCount">0</h2>
                        </div>
                    </div>

                    <div class="col-lg col-md-6 col-12">
                        <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=หยุดทำงาน'">
                            <h5 class="text-muted text-danger">หยุดทำงาน</h5>
                            <h2 class="fw-bold text-danger" id="stopCount">0</h2>
                        </div>
                    </div>
                </div>

                <h4 class="mt-4 mb-3">สถานะซ่อมบำรุง</h4>

                <div class="row g-3 row-cols-1 row-cols-md-3 row-cols-lg-5">

                    <div class="col">
                        <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/repair/reporthistory.php?status=all'">
                            <h6 class="text-muted">ทั้งหมด</h6>
                            <h2 class="fw-bold text-primary"><?= $total ?></h2>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/repair/reporthistory.php?status=รอดำเนินการ'">
                            <h6 class="text-muted">รอดำเนินการ</h6>
                            <h2 class="fw-bold text-warning"><?= $pending ?></h2>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/repair/reporthistory.php?status=กำลังซ่อม'">
                            <h6 class="text-muted">กำลังซ่อม</h6>
                            <h2 class="fw-bold text-info"><?= $in_progress ?></h2>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/repair/reporthistory.php?status=สำเร็จ'">
                            <h6 class="text-muted">เสร็จสิ้น</h6>
                            <h2 class="fw-bold text-success"><?= $completed ?></h2>
                        </div>
                    </div>

                    <div class="col">
                        <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/repair/reporthistory.php?status=ยกเลิก'">
                            <h6 class="text-muted">ยกเลิก</h6>
                            <h2 class="fw-bold text-danger"><?= $cancelled ?></h2>
                        </div>
                    </div>

                </div>


                <!-- USER OVERVIEW -->
                <h4 class="mt-4 mb-3">ข้อมูลผู้ใช้งานระบบ</h4>

                <div class="row g-3">

                    <div class="col">
                        <a href="/factory_monitoring/admin/users.php?role=all" class="text-decoration-none">
                            <div class="card shadow-sm p-3 text-center h-100">
                                <h6>ทั้งหมด</h6>
                                <div class="display-6 fw-bold text-primary"><?= $total_users ?></div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="/factory_monitoring/admin/users.php?role=Admin" class="text-decoration-none">
                            <div class="card shadow-sm p-3 text-center h-100">
                                <h6>Admin</h6>
                                <div class="display-6 fw-bold text-danger"><?= $role_admin ?></div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="/factory_monitoring/admin/users.php?role=Manager" class="text-decoration-none">
                            <div class="card shadow-sm p-3 text-center h-100">
                                <h6>Manager</h6>
                                <div class="display-6 fw-bold text-info"><?= $role_manager ?></div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="/factory_monitoring/admin/users.php?role=Technician" class="text-decoration-none">
                            <div class="card shadow-sm p-3 text-center h-100">
                                <h6>Technician</h6>
                                <div class="display-6 fw-bold text-success"><?= $role_technician ?></div>
                            </div>
                        </a>
                    </div>

                    <div class="col">
                        <a href="/factory_monitoring/admin/users.php?role=Operator" class="text-decoration-none">
                            <div class="card shadow-sm p-3 text-center h-100">
                                <h6>Operator</h6>
                                <div class="display-6 fw-bold text-warning"><?= $role_operator ?></div>
                            </div>
                        </a>
                    </div>

                </div>

                <!-- RECENT ACTIVITY -->
                <h4 class="mt-4 mb-3">กิจกรรมล่าสุด</h4>
                <div class="card shadow-sm p-3" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group" style="cursor:pointer;" onclick="location.href='/factory_monitoring/logs/logs.php'">
                        <?php while ($log = $recent_logs->fetch_assoc()): ?>
                            <li class="list-group-item">
                                <strong><?= $log['role'] ?></strong> : <?= $log['action'] ?>
                                <br>
                                <small class="text-muted"><?= $log['created_at'] ?></small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/factory_monitoring/admin/assets/js/SidebarAdmin.js"></script>
    <script src="/factory_monitoring/admin/assets/js/indexadmin.js"></script>

</body>

</html>