<?php
session_start();
include 'config.php'; // เชื่อมฐานข้อมูล

// ใช้สำหรับทดสอบ: login ของ Admin
$username = "Natthanan Krokrathok"; // จากฐานข้อมูล
$password = "0908324556";           // จากฐานข้อมูล

// ดึงข้อมูล user
$stmt = $conn->prepare("SELECT user_id, username, role, profile_image FROM users WHERE username=? AND password=?");
$stmt->bind_param("ss", $username, $password);
$stmt->execute();
$res = $stmt->get_result();

if ($res && $res->num_rows === 1) {
    $user = $res->fetch_assoc();

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['profile_image'] = $user['profile_image'];
} else {
    die("Login failed");
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test Dashboard</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<section class="main">
  <div class="sidebar">
    <div class="sidebar-top">
      <div class="sb-logo">
          <img src="uploads/<?php echo $_SESSION['profile_image']; ?>" alt="Profile Image" class="profile-img">
          <div class="profile-info">
              <span class="profile-name"><?php echo $_SESSION['username']; ?></span>
              <span class="profile-role"><?php echo $_SESSION['role']; ?></span>
          </div>
      </div>
    </div>
  </div>

  <div class="dashboard">
    <h2 class="dashboard-title">แดชบอร์ดหลัก</h2>
    <p>ทดสอบแสดงชื่อ, สิทธิ์, และรูป profile จากฐานข้อมูล</p>
  </div>
</section>

</body>
</html>
