<?php
session_start();
require_once "../config.php";

/* ===== AUTH GUARD ===== */
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

/* ===== LOAD USER DATA ===== */
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT username, email, phone, profile_image
    FROM users
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

/* ===== DATA GUARD ===== */
if ($result->num_rows === 0) {
    session_destroy();
    header("Location: /login.php");
    exit();
}

$op = $result->fetch_assoc();

/* ===== PROFILE IMAGE ===== */
$uploadPath = "/Manager/uploads/";
$profileImage = (!empty($op['profile_image']))
    ? $uploadPath . htmlspecialchars($op['profile_image'])
    : $uploadPath . "default_profile.png";
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>Manager Profile</title> <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/Operator/assets/css/profile.css">

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
          
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>

<div class="profile-container">

    <img src="<?= $profileImage ?>"
         class="profile-img"
         onerror="this.src='/Manager/uploads/default_profile.png'">

    <h2><?= htmlspecialchars($op['username']) ?></h2>
    <p class="role">Manager</p> <div class="info-box">
        <p><strong>Email:</strong> <?= htmlspecialchars($op['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($op['phone']) ?></p>
    </div>

    <button class="btn-edit" onclick="openPasswordModal()">
        <i class="fa-solid fa-key"></i> เปลี่ยนรหัสผ่าน
    </button>
</div>

<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closePasswordModal()">&times;</span>

        <h3>เปลี่ยนรหัสผ่าน</h3>

        <form action="change_password.php" method="POST">
            <label>รหัสผ่านใหม่</label>
            <input type="password" name="password" required>

            <label>ยืนยันรหัสผ่าน</label>
            <input type="password" name="confirm_password" required>

            <button type="submit" class="btn-save">
                <i class="fa-solid fa-save"></i> บันทึก
            </button>
        </form>
    </div>
</div>

<script>
    function openPasswordModal() {
        document.getElementById("passwordModal").style.display = "flex";
    }
    function closePasswordModal() {
        document.getElementById("passwordModal").style.display = "none";
    }
    window.onclick = function(e) {
        const modal = document.getElementById("passwordModal");
        if (e.target === modal) modal.style.display = "none";
    }
</script>

<?php if (isset($_SESSION['status'])): ?>
    <script>
        Swal.fire({
            icon: '<?= $_SESSION['status']; ?>',
            title: '<?= $_SESSION['status'] == "success" ? "สำเร็จ" : "แจ้งเตือน"; ?>',
            text: '<?= $_SESSION['message']; ?>',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#3085d6'
        }).then((result) => {
            // ถ้า Error ให้เด้ง Modal กลับมาเพื่อให้กรอกใหม่ได้ง่าย
            <?php if($_SESSION['status'] == 'error'): ?>
                openPasswordModal();
            <?php endif; ?>
        });
    </script>
    <?php 
    unset($_SESSION['status']);
    unset($_SESSION['message']);
    ?>
<?php endif; ?>

</body>
</html>