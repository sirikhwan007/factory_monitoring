<?php
// เริ่ม session ถ้ายังไม่ได้เริ่ม
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// เตรียม statement
$stmt = $conn->prepare("SELECT user_id, username, role, email, phone, created_at, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $role, $email, $phone, $created_at, $profile_image);
$stmt->fetch();
$stmt->close();

// จัดข้อมูลให้อยู่ใน array
$user = [
    'user_id' => $id,
    'username' => $username ?? '',
    'role' => $role ?? '',
    'email' => $email ?? '',
    'phone' => $phone ?? '',
    'created_at' => $created_at ?? '',
    'profile_image' => $profile_image ?? 'default_profile.png'
];

$profileImage = $user['profile_image'];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ผู้ใช้</title>
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/profile.css">
</head>
<body>

<div class="profile-container">

    <img src="/factory_monitoring/admin/uploads/<?php echo htmlspecialchars($profileImage); ?>" class="profile-img-card">

    <h2><?php echo htmlspecialchars($user['username']); ?></h2>
    <p class="role"><?php echo htmlspecialchars($user['role']); ?></p>

    <div class="info-box">
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>สร้างเมื่อ:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
    </div>

    <button class="btn-edit" onclick="openEditModal()">แก้ไขข้อมูล</button>
</div>

<!-- Modal แก้ไขข้อมูล -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <form class="label" action="profile_update.php" method="post" enctype="multipart/form-data">
            
            <label>Username</label>
            <input type="text" name="username" 
                   value="<?php echo htmlspecialchars($user['username']); ?>" required>

            <label>Email</label>
            <input type="email" name="email" 
                   value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label>Phone</label>
            <input type="text" name="phone" 
                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>

            <!-- เพิ่มรหัสผ่าน -->
            <label>รหัสผ่านใหม่ (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label>
            <input type="password" name="password" placeholder="New Password">

            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" placeholder="Confirm Password">

            <label>Profile Image</label>
            <input type="file" name="profile_image">

            <button type="submit">บันทึก</button>
        </form>
    </div>
</div>

<script>
// เปิด modal
function openEditModal() {
    document.getElementById("editModal").style.display = "flex";
}
// ปิด modal
function closeEditModal() {
    document.getElementById("editModal").style.display = "none";
}
// ปิด modal เมื่อคลิกนอก modal-content
window.onclick = function(event) {
    const modal = document.getElementById("editModal");
    if (event.target == modal) {
        modal.style.display = "none";
    }
}
</script>

</body>
</html>
