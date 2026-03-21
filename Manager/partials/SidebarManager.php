<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== SESSION DATA ===== */
$username = $_SESSION['username'] ?? 'Manager';
$role     = $_SESSION['role'] ?? 'Manager';


$sessImg = $_SESSION['profile_image'] ?? 'default_profile.png';
$profileImage = (strpos($sessImg, 'data:') === 0)
    ? $sessImg
    : "/Manager/uploads/" . $sessImg;

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar">
    <div class="sidebar-top">
        <a href="/Manager/profile.php" class="profile-btn">
            <div class="sb-logo">
                <img src="<?= $profileImage ?>"
                    class="profile-img"
                    onerror="this.src='/Manager/uploads/default_profile.png'">

                <div class="profile-info">
                    <span class="profile-name"><?= htmlspecialchars($username) ?></span>
                    <span class="profile-role"><?= htmlspecialchars($role) ?></span>
                </div>
            </div>
        </a>

        <ul class="sb-ul">

            <li>
                <a href="/Manager/dashboard.php"
                    class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>หน้าหลัก</span>
                </a>
            </li>

            <li>
                <a href="/machine_list/machine.php"
                    class="<?= $currentPage === 'machine.php' ? 'active' : '' ?>">
                    <i class="fas fa-industry"></i>
                    <span>สถานะเครื่องจักร</span>
                </a>
            </li>

            <li>
                <a href="/Manager/user_roles.php"
                    class="<?= $currentPage === 'user_roles.php' ? 'active' : '' ?>">
                    <i class="fas fa-user-clock"></i>
                    <span>พนักงานหน้างาน</span>
                </a>
            </li>
            <li>
                <a href="/repair/reporthistory.php"
                    class="<?= $currentPage === 'reporthistory.php' ? 'active' : '' ?>">
                    <i class="fas fa-screwdriver-wrench"></i>
                    <span>ประวัติการแจ้งซ่อม</span>
                </a>
            </li>

        </ul>
    </div>

    <div class="sidebar-bottom">
        <a href="/logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>ออกจากระบบ</span>
        </a>
    </div>
</div>