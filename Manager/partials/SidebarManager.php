<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'Manager';
$role = $_SESSION['role'] ?? 'Manager';
$uploadPath = '/factory_monitoring/admin/uploads/';


$currentPage = basename($_SERVER['PHP_SELF']);
?>

<link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">

<div class="sidebar">

    <div class="sidebar-top">

        <a href="/factory_monitoring/manager/profile.php" class="profile-btn">
            <div class="sb-logo">
                <img src="<?= $uploadPath . htmlspecialchars($profileImage) ?>" class="profile-img"
                    onerror="this.src='/factory_monitoring/assets/img/default_profile.png'">


                <div class="profile-info">
                    <span class="profile-name"><?= htmlspecialchars($username) ?></span>
                    <span class="profile-role"><?= htmlspecialchars($role) ?></span>
                </div>
            </div>
        </a>

        <ul class="sb-ul">

            <li>
                <a href="/factory_monitoring/manager/dashboard.php"
                    class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>หน้าหลัก</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/admin/machines.php"
                    class="<?= $currentPage === 'machines.php' ? 'active' : '' ?>">
                    <i class="fas fa-industry"></i>
                    <span>สถานะเครื่องจักร</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/manager/downtime.php"
                    class="<?= $currentPage === 'downtime.php' ? 'active' : '' ?>">
                    <i class="fas fa-stopwatch"></i>
                    <span>Downtime / OEE</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/manager/history_manager.php"
                    class="<?= $currentPage === 'history_manager.php' ? 'active' : '' ?>">
                    <i class="fas fa-screwdriver-wrench"></i>
                    <span>ประวัติการแจ้งซ่อม</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/manager/pm_schedule.php"
                    class="<?= $currentPage === 'pm_schedule.php' ? 'active' : '' ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>ตาราง PM</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/manager/workforce.php"
                    class="<?= $currentPage === 'workforce.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-clock"></i>
                    <span>พนักงานหน้างาน</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/manager/reports.php"
                    class="<?= $currentPage === 'reports.php' ? 'active' : '' ?>">
                    <i class="fas fa-file-lines"></i>
                    <span>rererrerer</span>
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