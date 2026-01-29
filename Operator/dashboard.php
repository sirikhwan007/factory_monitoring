<?php
session_start();

// ตรวจสอบการ Login และสิทธิ์การใช้งาน
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Dashboard | Factory Monitoring</title>

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

        <div class="dashboard p-4">
            <header class="mb-4">
                <h2 class="dashboard-title">แดชบอร์ด Operator</h2>
                <p class="text-muted">พื้นที่สำหรับติดตามสถานะเครื่องจักร แจ้งปัญหา และดูคำขอที่คุณส่งไว้</p>
            </header>

            <div class="card-grid">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card p-3 shadow-sm">
                            <h5>สถานะเครื่องจักร</h5>
                            <p>กำลังทำงานปกติ</p>
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
    </script>
</body>

</html>