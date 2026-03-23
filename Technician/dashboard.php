<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: /login.php");
    exit();
}

$username = $_SESSION['username'];

$pending_count = 0;
$sql_pending = "SELECT COUNT(*) AS count 
                FROM repair_history 
                WHERE technician_id = ? AND status = 'รอดำเนินการ'";

$stmt = $conn->prepare($sql_pending);
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $pending_count = $row['count'];
    }
    $stmt->close();
}

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
    <link rel="stylesheet" href="/factory_monitoring/Technician/assets/css/sidebar_technician.css">
    <link rel="stylesheet" href="/factory_monitoring/Technician/assets/css/dashboard_technician.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .job-count {
            font-size: 1.4rem;
            font-weight: bold;
            color: #0d6efd;
        }

        .btn-hamburger {
            display: none;
        }

        @media (max-width: 992px) {
            .main {
                margin-left: 0;
                padding-top: 60px;
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
        <?php include __DIR__ . "/SidebarTechnician.php"; ?>
    </div>

    <section class="main">
        <h1 class="dashboard-title">แดชบอร์ด Technician</h1>
        <p class="welcome-text">
            ยินดีต้อนรับ <strong><?= htmlspecialchars($username) ?></strong>
        </p>

        <div class="card-grid">

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
    <script src="/factory_monitoring/Technician/assets/js/sidebar_technician.js" defer></script>
</body>

</html>