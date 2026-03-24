<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileImage = $_SESSION['profile_image'] ?? 'default.png';
$username     = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role         = $_SESSION['role'] ?? 'Operator';


$activePage = $activePage ?? '';
?>

<div class="sidebar">

    <div class="op-top">
        <a href="/operator/profile.php" class="op-profile-btn">
            <div class="op-logo">
                <?php
                $showImg = (strpos($profileImage, 'data:') === 0)
                    ? $profileImage
                    : "/admin/uploads/" . $profileImage;
                ?>
                <img src="<?php echo $showImg; ?>" class="op-profile-img" alt="Profile">

                <div class="op-profile-info">
                    <span class="op-profile-name"><?= htmlspecialchars($username) ?></span>
                    <span class="op-profile-role"><?= htmlspecialchars($role) ?></span>
                </div>
            </div>
        </a>

        <ul class="op-ul">
            <li>
                <a href="/Operator/dashboard.php"
                    class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-home"></i>
                    <span class="sb-text">หน้าหลัก</span>
                </a>
            </li>
            <li>
                <a href="/machine_list/machine.php"
                    class="<?= $activePage === 'machines' ? 'active' : '' ?>">
                    <i class="fa-solid fa-industry"></i>
                    <span class="sb-text">รายการเครื่องจักร</span>
                </a>
            </li>

            <li>
                <a href="/repair/reporthistory.php"
                    class="<?= $activePage === 'history' ? 'active' : '' ?>">
                    <i class="fa-solid fa-clock"></i>
                    <span class="sb-text">รายการแจ้งซ่อม</span>
                </a>
            </li>

        </ul>
    </div>
    <div class="sidebar-bottom">
        <a href="/logout.php" class="btn-logout">
            <i class="fa-solid fa-sign-out-alt"></i>
            <span class="sb-text">ออกจากระบบ</span>
        </a>
    </div>

</div>