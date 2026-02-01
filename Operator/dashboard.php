<?php
session_start();
include "../config.php";

// ตรวจสอบการ Login และสิทธิ์การใช้งาน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

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
    <title>Operator Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />

    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/dashboard.css">
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
</head>

<body>

    <div class="btn-hamburger">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main-operator">

        <?php include __DIR__ . '/SidebarOperator.php'; ?>

        <div class="dashboard">
            <div class="container-fluid">

                <div class="dashboard">
                    <h2 class="mb-4">Operator</h2>
                    <!-- Machine Overview -->
                    <h4 class="mt-3 mb-3">ข้อมูลเครื่องจักร</h4>
                    

                        <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 border-0 text-center" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=all'">
                            <h5 class="text-muted">เครื่องจักรทั้งหมด</h5>
                            <h2 class="fw-bold text-primary"><?= $total_machines ?></h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 border-0 text-center" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=กำลังทำงาน'">
                            <h5 class="text-muted text-success">กำลังทำงาน</h5>
                            <h2 class="fw-bold text-success" id="activeCount">0</h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 border-0 text-center" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=ผิดปกติ'">
                            <h5 class="text-muted text-warning">ผิดปกติ</h5>
                            <h2 class="fw-bold text-warning" id="errorCount">0</h2>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="card shadow-sm p-3 border-0 text-center" style="cursor:pointer;"
                            onclick="location.href='/factory_monitoring/machine_list/machine.php?status=หยุดทำงาน'">
                            <h5 class="text-muted text-danger">หยุดทำงาน</h5>
                            <h2 class="fw-bold text-danger" id="stopCount">0</h2>
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
                </div>
            </div>
        </div>

    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="SidebarOperator.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const sidebar = document.querySelector(".sidebar-operator");
            const btnHamburger = document.querySelector(".btn-hamburger");

            // Sidebar Toggle
            if (btnHamburger && sidebar) {
                btnHamburger.addEventListener("click", () => {
                    sidebar.classList.toggle("active");
                });
            }

            // Auto-active Menu ตาม URL ปัจจุบัน
            const currentUrl = window.location.href;
            const links = document.querySelectorAll(".op-ul a");

            links.forEach(a => {
                if (a.href === currentUrl) {
                    a.classList.add("active-menu");
                    // แนะนำให้ย้าย Style ไปไว้ใน CSS class .active-menu แทนการเขียน inline
                    Object.assign(a.style, {
                        background: "#8e44ad",
                        color: "#fff",
                        fontWeight: "bold"
                    });
                }
            });
        });
        $(document).ready(function() {

            function loadStatus() {
                $.ajax({
                    url: "/factory_monitoring/api/get_all_machine_status.php",
                    method: "GET",
                    dataType: "json",
                    success: function(res) {
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