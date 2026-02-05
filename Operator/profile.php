<?php
session_start();
require_once "../config.php";

/* ===== Auth Guard ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ===== Load Operator Data ===== */
$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT username, email, phone, profile_image 
    FROM users 
    WHERE user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$op = $stmt->get_result()->fetch_assoc();

$profileImage = !empty($op['profile_image'])
    ? "/factory_monitoring/admin/uploads/" . $op['profile_image']
    : "/factory_monitoring/admin/uploads/default.png";
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Operator Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- CSS -->
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/profile.css">

    <!-- FontAwesome -->
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

    <div class="profile-container">

        <img src="<?= $profileImage ?>" class="profile-img">

        <h2><?= htmlspecialchars($op['username']) ?></h2>
        <p class="role">Operator</p>

        <div class="info-box">
            <p><strong>Email:</strong> <?= htmlspecialchars($op['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($op['phone']) ?></p>
        </div>

        <button class="btn-edit" onclick="openPasswordModal()">
            <i class="fa-solid fa-key"></i> เปลี่ยนรหัสผ่าน
        </button>
    </div>

    <!-- ===== Modal Change Password ===== -->
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            icon: '<?= $_SESSION['status']; ?>', // success หรือ error
            title: '<?= $_SESSION['status'] == "success" ? "สำเร็จ" : "ผิดพลาด"; ?>',
            text: '<?= $_SESSION['message']; ?>',
            confirmButtonText: 'ตกลง',
            confirmButtonColor: '#3085d6'
        }).then((result) => {
            // ถ้า error ให้เปิด Modal ค้างไว้เพื่อให้กรอกใหม่ได้เลย
            <?php if($_SESSION['status'] == 'error'): ?>
                openPasswordModal();
            <?php endif; ?>
        });
    </script>
    <?php 
    // เคลียร์ค่าทิ้ง เพื่อไม่ให้แสดงซ้ำเมื่อรีเฟรช
    unset($_SESSION['status']);
    unset($_SESSION['message']);
    ?>
<?php endif; ?>

</body>

</html>