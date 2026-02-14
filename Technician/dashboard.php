<?php
session_start();
require_once "../config.php";

/* =======================
   Auth Guard
======================= */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['username'];

/* =======================
   งานรอดำเนินการ
======================= */
$pending_count = 0;
$sql_pending = "SELECT COUNT(*) AS count 
                FROM repair_history 
                WHERE technician_id = ? AND status = 'รอดำเนินการ'";

$stmt = $conn->prepare($sql_pending);
if ($stmt) {
    // ใช้ $_SESSION['user_id'] ซึ่งเป็นตัวเลข ID ของช่างที่ Login อยู่
    $stmt->bind_param("i", $_SESSION['user_id']); 
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pending_count = $row['count'];
    }
    $stmt->close();
}

/* =======================
   งานที่เสร็จแล้ว
======================= */
$completed_count = 0;
$sql_completed = "SELECT COUNT(*) AS count 
                  FROM repair_history 
                  WHERE username = ? AND status = 'สำเร็จ'";
$stmt = $conn->prepare($sql_completed);
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $completed_count = $row['count'];
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Technician Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- CSS -->
    <link rel="stylesheet" href="/Technician/assets/css/sidebar_technician.css">
    <link rel="stylesheet" href="/Technician/assets/css/dashboard_technician.css">

    <!-- FontAwesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script src="/Technician/assets/js/sidebar_technician.js" defer></script>

    <style>
        .card-link {
            text-decoration: none;
            color: inherit;
        }
        .card-link .card {
            cursor: pointer;
            transition: .2s ease;
        }
        .card-link .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .job-count {
            font-size: 1.4rem;
            font-weight: bold;
            color: #0d6efd;
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<?php include "SidebarTechnician.php"; ?>

<!-- Content -->
<section class="main">
    <h1 class="dashboard-title">แดชบอร์ด Technician</h1>

    <p class="welcome-text">
        ยินดีต้อนรับ, <strong><?= htmlspecialchars($username) ?></strong>
    </p>

    <div class="card-grid">

        <!-- งานซ่อมที่ได้รับ -->
        <a href="work_orders.php" class="card-link">
            <div class="card">
                <i class="fa-solid fa-screwdriver-wrench"></i>
                <h3>งานซ่อมที่ได้รับ</h3>
                <p class="job-count"><?= $pending_count ?> งาน</p>
            </div>
        </a>

        <!-- งานที่เสร็จแล้ว -->
        <a href="history_technician.php" class="card-link">
            <div class="card">
                <i class="fa-solid fa-list-check"></i>
                <h3>งานที่เสร็จแล้ว</h3>
                <p class="job-count"><?= $completed_count ?> งาน</p>
            </div>
        </a>

        <!-- โปรไฟล์ -->
        <a href="profile.php" class="card-link">
            <div class="card">
                <i class="fa-solid fa-user"></i>
                <h3>โปรไฟล์</h3>
                <p>ข้อมูลส่วนตัว</p>
            </div>
        </a>

    </div>
</section>

</body>
</html>
