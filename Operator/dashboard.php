<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

// ตรวจสิทธิ์ Operator
if ($_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Operator Dashboard</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/dashboard.css">
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
</head>

<body>
    <script>
// ปุ่มเปิด sidebar สำหรับ mobile (ถ้าเพื่อนเพิ่มปุ่มใน header)
document.addEventListener("DOMContentLoaded", () => {

    const sidebar = document.querySelector(".sidebar-operator");
    const btnHamburger = document.querySelector(".btn-hamburger");

    if (btnHamburger) {
        btnHamburger.addEventListener("click", () => {
            sidebar.classList.toggle("active");
        });
    }

    // ระบบ active menu อัตโนมัติตาม URL
    const links = document.querySelectorAll(".op-ul a");
    links.forEach(a => {
        if (a.href === window.location.href) {
            a.style.background = "#8e44ad";
            a.style.color = "#fff";
            a.style.fontWeight = "bold";
        }
    });

});
</script>


    <!-- ปุ่มแฮมเบอร์เกอร์ -->
    <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

    <section class="main-operator">

    <?php include __DIR__ . '/SidebarOperator.php'; ?>

    <div class="dashboard">
        <h2 class="dashboard-title">แดชบอร์ด Operator</h2>
        <p>พื้นที่สำหรับติดตามสถานะเครื่องจักร แจ้งปัญหา และดูคำขอที่คุณส่งไว้</p>

        <!-- card grid -->
        <div class="card-grid">
            ...
        </div>
    </div>

</section>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- เรียก JS ของ Operator -->
    <script src="SidebarOperator.js"></script>

</body>

</html>
