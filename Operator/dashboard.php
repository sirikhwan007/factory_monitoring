<?php
session_start();
include "../config.php";

// ตรวจสอบการ Login และสิทธิ์การใช้งาน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /login.php");
    exit();
}

$page = 'dashboard';

$total_machines  = $conn->query("SELECT COUNT(*) FROM machines")->fetch_row()[0];
$total_danger = $conn->query("SELECT COUNT(*) FROM machines WHERE status='อันตราย'")->fetch_row()[0];


$total = $conn->query("SELECT COUNT(*) FROM repair_history")->fetch_row()[0];
$pending = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='รอดำเนินการ'")->fetch_row()[0];
$in_progress = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='กำลังซ่อม'")->fetch_row()[0];
$completed = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='สำเร็จ'")->fetch_row()[0];
$cancelled = $conn->query("SELECT COUNT(*) FROM repair_history WHERE status='ยกเลิก'")->fetch_row()[0];
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
    <link rel="stylesheet" href="/Operator/assets/css/dashboard.css">
    <link rel="stylesheet" href="/Operator/assets/css/SidebarOperator.css">
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

        .sidebar-operator img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 10px;
        }

        .main-operator img.profile-img {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>

<body>

    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active')">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main-operator">
        <div class="sidebar-wrapper">
            <?php include __DIR__ . '/../Operator/SidebarOperator.php'; ?>
        </div>
        <div class="dashboard">
            <div class="container-fluid">

                <div class="dashboard">
                    <h2 class="mb-4">Operator</h2>
                    <!-- Machine Overview -->
                    <div class="d-flex justify-content-between align-items-center mt-3 mb-3">
                        <h4>ข้อมูลเครื่องจักร</h4>
                        <div id="notification-bell" class="position-relative" style="cursor: pointer; font-size: 1.5rem;">
                            <i class="fa-solid fa-bell text-secondary"></i>
                            <span id="alert-badge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none">!</span>
                        </div>
                    </div>

                    <div class="row mb-4 g-3">
                        <div class="col-lg col-md-4 col-6">
                            <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;" onclick="location.href='/machine_list/machine.php?status=all'">
                                <h5 class="text-muted">เครื่องจักรทั้งหมด</h5>
                                <h2 class="fw-bold text-primary"><?= $total_machines ?></h2>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6">
                            <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;" onclick="location.href='/machine_list/machine.php?status=กำลังทำงาน'">
                                <h5 class="text-muted text-success">กำลังทำงาน</h5>
                                <h2 class="fw-bold text-success" id="activeCount">0</h2>
                            </div>
                        </div>
                        <div class="col-lg col-md-4 col-6">
                            <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;" onclick="location.href='/machine_list/machine.php?status=ผิดปกติ'">
                                <h5 class="text-muted text-warning">ผิดปกติ</h5>
                                <h2 class="fw-bold text-warning" id="errorCount">0</h2>
                            </div>
                        </div>
                        <div class="col-lg col-md-6 col-6">
                            <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;" onclick="location.href='/machine_list/machine.php?status=อันตราย'">
                                <h5 class="text-muted">อันตราย</h5>
                                <h2 class="fw-bold" style="color: #fd7e14;" id="dangerCount">0</h2>
                            </div>
                        </div>
                        <div class="col-lg col-md-6 col-12">
                            <div class="card shadow-sm p-3 border-0 text-center h-100" style="cursor:pointer;" onclick="location.href='/machine_list/machine.php?status=หยุดทำงาน'">
                                <h5 class="text-muted text-danger">หยุดทำงาน</h5>
                                <h2 class="fw-bold text-danger" id="stopCount">0</h2>
                            </div>
                        </div>
                    </div>
                    <!-- Repair Request Overview -->
                    <h4 class="mt-4 mb-3">สถานะซ่อมบำรุง</h4>

                    <div class="row g-3 row-cols-1 row-cols-md-3 row-cols-lg-5">

                        <div class="col">
                            <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                                onclick="location.href='/repair/reporthistory.php?status=all'">
                                <h6 class="text-muted">ทั้งหมด</h6>
                                <h2 class="fw-bold text-primary"><?= $total ?></h2>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                                onclick="location.href='/repair/reporthistory.php?status=รอดำเนินการ'">
                                <h6 class="text-muted">รอดำเนินการ</h6>
                                <h2 class="fw-bold text-warning"><?= $pending ?></h2>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                                onclick="location.href='/repair/reporthistory.php?status=กำลังซ่อม'">
                                <h6 class="text-muted">กำลังซ่อม</h6>
                                <h2 class="fw-bold text-info"><?= $in_progress ?></h2>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                                onclick="location.href='/repair/reporthistory.php?status=สำเร็จ'">
                                <h6 class="text-muted">เสร็จสิ้น</h6>
                                <h2 class="fw-bold text-success"><?= $completed ?></h2>
                            </div>
                        </div>

                        <div class="col">
                            <div class="card shadow-sm p-3 text-center h-100" style="cursor:pointer;"
                                onclick="location.href='/repair/reporthistory.php?status=ยกเลิก'">
                                <h6 class="text-muted">ยกเลิก</h6>
                                <h2 class="fw-bold text-danger"><?= $cancelled ?></h2>
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
            let alertCounter = 0;
            let currentIssue = 'all';

            function loadStatus() {
                $.ajax({
                    url: "/api/get_all_machine_status.php",
                    method: "GET",
                    dataType: "json",
                    success: function(res) {
                        // 1. อัปเดตตัวเลขบนหน้าจอ
                        $("#activeCount").text(res.active);
                        $("#errorCount").text(res.error);
                        $("#dangerCount").text(res.danger);
                        $("#stopCount").text(res.stop);

                        // 2. ตรวจสอบสถานะเพื่อแจ้งเตือน
                        if (parseInt(res.stop) > 0) {
                            currentIssue = 'หยุดทำงาน';
                            alertCounter += 5;
                        } else if (parseInt(res.danger) > 0) {
                            currentIssue = 'อันตราย';
                            alertCounter += 5;
                        } else if (parseInt(res.error) > 0) {
                            currentIssue = 'ผิดปกติ';
                            alertCounter += 5;
                        } else {
                            alertCounter = 0;
                            currentIssue = 'all';
                            resetBell();
                        }

                        // 3. แจ้งเตือนเมื่อพบปัญหาเกิน 10 วินาที
                        if (alertCounter >= 10) {
                            triggerBell();
                        }
                    }
                });
            }

            function triggerBell() {
                $("#notification-bell i").addClass("ring-active");
                $("#alert-badge").removeClass("d-none");
            }

            function resetBell() {
                $("#notification-bell i").removeClass("ring-active");
                $("#alert-badge").addClass("d-none");
            }

            // คลิกกระดิ่งเพื่อไปหน้าเครื่องจักรตามสถานะที่มีปัญหา
            $("#notification-bell").on("click", function() {
                window.location.href = "/machine_list/machine.php?status=" + encodeURIComponent(currentIssue);
            });

            loadStatus();
            setInterval(loadStatus, 5000); // อัปเดตทุก 5 วินาที
        });
    </script>
</body>

</html>