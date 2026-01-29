<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileImage = isset($_SESSION['profile_image']) ? $_SESSION['profile_image'] : 'default_profile.png';
$username     = isset($_SESSION['username']) ? $_SESSION['username'] : 'ผู้ใช้งาน';
$role         = isset($_SESSION['role']) ? $_SESSION['role'] : 'Technician';

/* ป้องกัน error ถ้าไม่ได้ส่ง activePage มา */
$activePage = $activePage ?? '';
?>

<div class="sidebar">

    <!-- ===== TOP ===== -->
    <div class="sidebar-top">

        <a href="/factory_monitoring/Technician/profile.php" class="profile-btn">
            <div class="sb-logo">

                <img src="/factory_monitoring/admin/uploads/<?php echo htmlspecialchars($profileImage); ?>"
                     class="profile-img"
                     alt="Profile">

                <div class="profile-info">
                    <span class="profile-name">
                        <?php echo htmlspecialchars($username); ?>
                    </span>
                    <span class="profile-role">
                        <?php echo htmlspecialchars($role); ?>
                    </span>
                </div>

            </div>
        </a>

        <!-- ===== MENU ===== -->
        <ul class="sb-ul">

            <li>
                <a href="/factory_monitoring/Technician/dashboard.php"
                   class="<?php echo ($activePage === 'dashboard') ? 'sb-ul-active' : ''; ?>">
                    <i class="fas fa-home fontawesome"></i>
                    <span class="sb-text">หน้าหลัก</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/Technician/work_orders.php"
                   class="<?php echo ($activePage === 'work_orders') ? 'sb-ul-active' : ''; ?>">
                    <i class="fas fa-wrench fontawesome"></i>
                    <span class="sb-text">งานซ่อม</span>
                </a>
            </li>

            <li>
                <a href="/factory_monitoring/Technician/history_technician.php"
                   class="<?php echo ($activePage === 'history') ? 'sb-ul-active' : ''; ?>">
                    <i class="fas fa-clock fontawesome"></i>
                    <span class="sb-text">ประวัติการแจ้งซ่อม</span>
                </a>
            </li>

        </ul>

    </div>

    <!-- ===== BOTTOM ===== -->
    <div class="sidebar-bottom">
        <a href="/factory_monitoring/logout.php" class="btn btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span class="sb-text">ออกจากระบบ</span>
        </a>
    </div>

</div>
