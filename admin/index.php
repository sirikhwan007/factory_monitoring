<?php
session_start();
include "../config.php"; // เชื่อมฐานข้อมูล

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
   🔹 MACHINE OVERVIEW
----------------------------------------------------- */
$total_machines  = $conn->query("SELECT COUNT(*) FROM machines")->fetch_row()[0];


/* -----------------------------------------------------
   🔹 USER OVERVIEW
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
$total = $total_repair; // <-- วางบรรทัดนี้ถ้าตอนนี้ HTML เรียก $total

$sql_pending = "SELECT COUNT(*) AS pending FROM repair_requests WHERE status='pending'";
$pending = $conn->query($sql_pending)->fetch_assoc()['pending'];

$sql_in_progress = "SELECT COUNT(*) AS in_progress FROM repair_requests WHERE status='in_progress'";
$in_progress = $conn->query($sql_in_progress)->fetch_assoc()['in_progress'];

$sql_completed = "SELECT COUNT(*) AS completed FROM repair_requests WHERE status='completed'";
$completed = $conn->query($sql_completed)->fetch_assoc()['completed'];

/* -----------------------------------------------------
   🔹 RECENT ACTIVITY (LOGS)
----------------------------------------------------- */
$recent_logs = $conn->query("SELECT * FROM logs ORDER BY created_at DESC LIMIT 10");

?>



<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>

    <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

    <section class="main">

        <?php include __DIR__ . '/SidebarAdmin.php'; ?>

        <div class="container-fluid">

            <div class="dashboard">

                
                <!-- Machine Overview -->
                <h4 class="mt-3 mb-3">ข้อมูลเครื่องจักร</h4>
                <div class="row g-3">

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>เครื่องจักรทั้งหมด</h5>
                            <div class="display-6 fw-bold text-primary"><?= $total_machines ?></div>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>ทำงานปกติ</h5>
                            <div class="display-6 fw-bold text-success" id="activeCount">0</div>

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>ผิดปกติ</h5>
                            <div class="display-6 fw-bold text-warning" id="errorCount">0</div>

                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>หยุดทำงาน</h5>
                            <div class="display-6 fw-bold text-danger" id="stopCount">0</div>

                        </div>
                    </div>

                </div>

                <h4 class="mt-4 mb-3">สถานะซ่อมบำรุง</h4>

                <div class="row g-3">

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>ทั้งหมด</h5>
                            <h2 class="text-primary"><?php echo $total; ?></h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>รอดำเนินการ</h5>
                            <h2 class="text-warning"><?php echo $pending; ?></h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>กำลังซ่อม</h5>
                            <h2 class="text-info"><?php echo $in_progress; ?></h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 text-center">
                            <h5>เสร็จสิ้น</h5>
                            <h2 class="text-success"><?php echo $completed; ?></h2>
                        </div>
                    </div>

                </div>


                <!-- USER OVERVIEW -->
                <h4 class="mt-4 mb-3">ข้อมูลผู้ใช้งานระบบ</h4>
                <div class="row g-3">

                    <div class="col-md-2">
                        <div class="card shadow-sm p-3 text-center">
                            <h6>ผู้ใช้ทั้งหมด</h6>
                            <div class="display-6 fw-bold text-primary"><?= $total_users ?></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card shadow-sm p-3 text-center">
                            <h6>Admin</h6>
                            <div class="display-6 fw-bold text-danger"><?= $role_admin ?></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card shadow-sm p-3 text-center">
                            <h6>Manager</h6>
                            <div class="display-6 fw-bold text-info"><?= $role_manager ?></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card shadow-sm p-3 text-center">
                            <h6>Technician</h6>
                            <div class="display-6 fw-bold text-success"><?= $role_technician ?></div>
                        </div>
                    </div>

                    <div class="col-md-2">
                        <div class="card shadow-sm p-3 text-center">
                            <h6>Operator</h6>
                            <div class="display-6 fw-bold text-warning"><?= $role_operator ?></div>
                        </div>
                    </div>

                </div>

                <!-- RECENT ACTIVITY -->
                <h4 class="mt-4 mb-3">กิจกรรมล่าสุด</h4>
                <div class="card shadow-sm p-3" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-group">

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
    <script>
$(document).ready(function () {

    function loadStatus() {
        $.ajax({
            url: "/factory_monitoring/api/get_all_machine_status.php",
            method: "GET",
            dataType: "json",
            success: function (res) {
                $("#activeCount").text(res.active);
                $("#errorCount").text(res.error);
                $("#stopCount").text(res.stop);
            }
        });
    }

    loadStatus();
    setInterval(loadStatus, 5000);
});
</script>

</body>

</html>