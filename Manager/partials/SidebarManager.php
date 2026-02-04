<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ===== SESSION DATA ===== */
$username = $_SESSION['username'] ?? 'Manager';
$role     = $_SESSION['role'] ?? 'Manager';

/* ===== PROFILE IMAGE (จาก SESSION) ===== */
$uploadPath = "/factory_monitoring/Manager/uploads/";
$profileImage = !empty($_SESSION['profile_image'])
    ? $uploadPath . $_SESSION['profile_image']
    : $uploadPath . "default_profile.png";

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Sidebar</title>

    <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        /* ===== TOGGLE BUTTON ===== */
        .toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 2000;
            background: #6f1e51;
            color: #fff;
            border: none;
            padding: 10px 12px;
            border-radius: 8px;
            cursor: pointer;
            display: none;
        }

        /* SHOW TOGGLE ON MOBILE */
        @media (max-width: 992px) {
            .toggle-btn {
                display: block;
            }
        }
    </style>
</head>

<body>

    <button class="toggle-btn" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar active">

        <div class="sidebar-top">

            <a href="/factory_monitoring/Manager/profile.php" class="profile-btn">
                <div class="sb-logo">
                    <img src="<?= $profileImage ?>"
                        class="profile-img"
                        onerror="this.src='/factory_monitoring/Manager/uploads/default_profile.png'">

                    <div class="profile-info">
                        <span class="profile-name"><?= htmlspecialchars($username) ?></span>
                        <span class="profile-role"><?= htmlspecialchars($role) ?></span>
                    </div>
                </div>
            </a>

            <ul class="sb-ul">

                <li>
                    <a href="/factory_monitoring/Manager/dashboard.php"
                        class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                        <i class="fas fa-chart-line"></i>
                        <span>หน้าหลัก</span>
                    </a>
                </li>

                <li>
                    <a href="/factory_monitoring/Machine_list/machine.php"
                        class="<?= $currentPage === 'machine.php' ? 'active' : '' ?>">
                        <i class="fas fa-industry"></i>
                        <span>สถานะเครื่องจักร</span>
                    </a>
                </li>

                <li>
                    <a href="/factory_monitoring/Manager/user_roles.php"
                        class="<?= $currentPage === 'user_roles.php' ? 'active' : '' ?>">
                        <i class="fas fa-user-clock"></i>
                        <span>พนักงานหน้างาน</span>
                    </a>
                </li>
           <li>
                    <a href="/factory_monitoring/repair/reporthistory.php"
                        class="<?= $currentPage === 'reporthistory.php' ? 'active' : '' ?>">
                        <i class="fas fa-screwdriver-wrench"></i>
                        <span>ประวัติการแจ้งซ่อม</span>
                    </a>
                </li> 

            </ul>
        </div>

        <div class="sidebar-bottom">
            <a href="/factory_monitoring/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i>
                <span>ออกจากระบบ</span>
            </a>
        </div>

    </div>

    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('active');
        }
    </script>

</body>

</html>