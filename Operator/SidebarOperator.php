<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$profileImage = $_SESSION['profile_image'] ?? 'default.png';
$username     = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role         = $_SESSION['role'] ?? 'Operator';

/* ป้องกัน error ถ้าไม่ได้กำหนด */
$activePage = $activePage ?? '';
?>

<div class="sidebar-operator">

    <div class="op-top">
        <a href="/operator/profile.php" class="op-profile-btn">
            <div class="op-logo">
                <?php
                // เช็คว่าเป็น Base64 หรือไม่ ถ้าใช่ให้แสดงเลย ถ้าไม่ใช่ให้เติม Path
                $showImg = (strpos($profileImage, 'data:') === 0)
                    ? $profileImage
                    : "/admin/uploads/" . $profileImage;
                ?>
                <img src="<?php echo $showImg; ?>" class="profile-img" alt="Profile">

                <div class="op-profile-info">
                    <span class="op-profile-name"><?= htmlspecialchars($username) ?></span>
                    <span class="op-profile-role"><?= htmlspecialchars($role) ?></span>
                </div>
            </div>
        </a>

        <ul class="op-ul">
            <!-- Dashboard -->
            <li>
                <a href="/Operator/dashboard.php"
                    class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                    <i class="fa-solid fa-home"></i>
                    <span class="sb-text">หน้าหลัก</span>
                </a>
            </li>
            <!-- Machines -->
            <li>
                <a href="/machine_list/machine.php"
                    class="<?= $activePage === 'machines' ? 'active' : '' ?>">
                    <i class="fa-solid fa-industry"></i>
                    <span class="sb-text">เครื่องจักรทั้งหมด</span>
                </a>
            </li>

            <!-- History -->
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