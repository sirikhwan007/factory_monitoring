<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("SELECT user_id, username, role, email, phone, created_at, profile_image FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$stmt->bind_result($id, $username, $role, $email, $phone, $created_at, $profile_image);
$stmt->fetch();
$stmt->close();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้</title>
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/profile.css">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .profile-container {
            background: white;
            width: 400px;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid #ececec;
            margin-bottom: 15px;
        }

        h2 {
            margin: 10px 0 5px;
            color: #333;
        }

        .role {
            color: #777;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }

        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: left;
            margin-bottom: 20px;
            font-size: 0.95rem;
        }

        .info-box p {
            margin: 8px 0;
            color: #555;
        }

        .info-box strong {
            color: #333;
            min-width: 80px;
            display: inline-block;
        }

        .btn-edit {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 1rem;
            transition: 0.3s;
        }

        .btn-edit:hover {
            background-color: #2980b9;
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #aaa;
        }

        .close:hover {
            color: #000;
        }

        form label {
            display: block;
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
            font-size: 0.9rem;
        }

        form input {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }

        form button {
            width: 100%;
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 12px;
            margin-top: 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
        }

        form button:hover {
            background-color: #27ae60;
        }
    </style>
</head>

<body>

    <div class="profile-container">

        <?php
        $showImg = (strpos($profileImage, 'data:') === 0)
            ? $profileImage
            : "/admin/uploads/" . $profileImage;
        ?>
        <img src="<?php echo $showImg; ?>" class="profile-img">

        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
        <p class="role"><?php echo htmlspecialchars($user['role']); ?></p>

        <div class="info-box">
            <p><strong>อีเมล์:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>เบอร์โทร:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <p><strong>สร้างเมื่อ:</strong> <?php echo htmlspecialchars($user['created_at']); ?></p>
        </div>

        <button class="btn-edit" onclick="openEditModal()">แก้ไขข้อมูล</button>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <form class="label" action="profile_update.php" method="post" enctype="multipart/form-data">

                <label>ชื่อผู้ใช้</label>
                <input type="text" name="username"
                    value="<?php echo htmlspecialchars($user['username']); ?>" required>

                <label>อีเมล์</label>
                <input type="email" name="email"
                    value="<?php echo htmlspecialchars($user['email']); ?>" required>

                <label>เบอร์โทร</label>
                <input type="text" name="phone"
                    value="<?php echo htmlspecialchars($user['phone']); ?>" required>

                <label>รหัสผ่านใหม่ (ถ้าไม่เปลี่ยนให้เว้นว่าง)</label>
                <input type="password" name="password" placeholder="New Password">

                <label>ยืนยันรหัสผ่าน</label>
                <input type="password" name="confirm_password" placeholder="Confirm Password">

                <label>รูปโปรไฟล์</label>
                <input type="file" name="profile_image">

                <button type="submit">บันทึก</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal() {
            document.getElementById("editModal").style.display = "flex";
        }

        function closeEditModal() {
            document.getElementById("editModal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("editModal");
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>

</html>