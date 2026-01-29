<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role = $_SESSION['role'] ?? 'ไม่ทราบสิทธิ์';
?>

<div class="sidebar">
  <div class="sidebar-top">

    <a href="/factory_monitoring/admin/profile.php" class="profile-btn">
      <div class="sb-logo">
        <img src="/factory_monitoring/admin/uploads/<?php echo $profileImage; ?>" class="profile-img">

        <div class="profile-info">
          <span class="profile-name"><?php echo htmlspecialchars($username); ?></span>
          <span class="profile-role"><?php echo htmlspecialchars($role); ?></span>
        </div>
      </div>
    </a>

    <ul class="sb-ul">
      <li>
        <a href="/factory_monitoring/admin/index.php">
          <i class="fas fa-home"></i><span class="sb-text">หน้าหลัก</span>
        </a>
      </li>

      <li>
        <a href="/factory_monitoring/admin/machines.php">
          <i class="fas fa-industry"></i><span class="sb-text">เครื่องจักร</span>
        </a>
      </li>

      <li>
        <a href="/factory_monitoring/admin/users.php">
          <i class="fas fa-user"></i><span class="sb-text">ผู้ใช้</span>
        </a>
      </li>

      <li>
        <a href="/factory_monitoring/admin/report.php">
          <i class="fas fa-wrench"></i><span class="sb-text">แจ้งซ่อม</span>
        </a>
      </li>

      <li>
        <a href="/factory_monitoring/admin/reporthistory.php">
          <i class="fas fa-history"></i><span class="sb-text">ประวัติการแจ้งซ่อม</span>
        </a>
      </li>

      <li>
        <a href="/factory_monitoring/logs/logs.php">
          <i class="fas fa-clipboard-list"></i><span class="sb-text">ประวัติการเข้าใช้</span>
        </a>
      </li>
    </ul>
  </div>

  <div class="sidebar-bottom">
    <a href="/factory_monitoring/logout.php" class="btn btn-logout">
      <i class="fas fa-sign-out-alt"></i>
      <span class="sb-text">ออกจากระบบ</span>
    </a>
  </div>
</div>