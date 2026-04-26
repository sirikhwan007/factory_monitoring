<?php
session_start();
include 'config.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $user_id  = trim($_POST['user_id']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $email    = trim($_POST['email']);
    $phone    = trim($_POST['phone']);
    $role     = trim($_POST['role']);

    // ตรวจสอบ username ซ้ำ
    $check = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? OR username = ? LIMIT 1");
    $check->bind_param("ss", $user_id, $username);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        $error = "Username นี้ถูกใช้งานแล้ว";
    } else {

        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $defaultProfile = 'default.png';

        $stmt = $conn->prepare("
            INSERT INTO users
            (user_id, username, password, role, email, phone, profile_image, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");

        $stmt->bind_param(
            "sssssss",
            $user_id,
            $username,
            $hashedPassword,
            $role,
            $email,
            $phone,
            $defaultProfile
        );

        if ($stmt->execute()) {
            $message = "สมัครสมาชิกสำเร็จ กรุณารอผู้ดูแลระบบอนุมัติบัญชี";

            
        } else {
            $error = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Factory Monitoring</title>

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Kanit', sans-serif;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background:
                radial-gradient(circle at top left, #e0e7ff 0%, transparent 40%),
                radial-gradient(circle at bottom right, #f0f4ff 0%, transparent 40%),
                #176edf;
        }

        .container {
            width: 500px;
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow:
                0 24px 48px rgba(0, 0, 0, 0.10),
                0 8px 16px rgba(0, 0, 0, 0.06);
        }

        h1 {
            margin: 0 0 8px;
            font-weight: 600;
        }

        .subtitle {
            margin-bottom: 30px;
            font-size: 14px;
            opacity: 0.7;
        }

        .success {
            margin-bottom: 18px;
            padding: 16px;
            border-radius: 8px;
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #b7dfb9;
            font-size: 14px;
            text-align: center;
            font-weight: 500;
        }

        .success-icon {
            font-size: 28px;
            margin-bottom: 8px;
            display: block;
        }

        .error {
            margin-bottom: 18px;
            padding: 12px;
            border-radius: 6px;
            background: #ffe5e5;
            color: #c62828;
            border: 1px solid #f5b5b5;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-size: 13px;
        }

        input,
        select {
            width: 100%;
            padding: 12px;
            border: 1px solid #dcdcdc;
            border-radius: 6px;
            outline: none;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #1e5eff;
            color: white;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }

        button:hover {
            background: #1748c5;
        }

        .back-login {
            margin-top: 20px;
            text-align: center;
        }

        .back-login a {
            text-decoration: none;
            color: #1e5eff;
            font-weight: 600;
        }

        .back-login a:hover {
            color: #1748c5;
        }
    </style>
</head>

<body>

    <div class="container">

        <h1>Register</h1>
        <div class="subtitle">
            สมัครสมาชิกเพื่อเข้าใช้งานระบบตรวจสอบเครื่องจักร
        </div>

        <?php if ($message): ?>
            <div class="success">
                <span class="success-icon">✓</span>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" id="registerForm">

            <div class="form-group">
                <label>Employee ID</label>
                <input type="text" name="user_id" required>
            </div>

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required>
            </div>

            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" required>
            </div>

            <div class="form-group">
                <label>Role</label>
                <select name="role" required>
                    <option value="">-- เลือกตำแหน่ง --</option>
                    <option value="Operator">Operator</option>
                    <option value="Technician">Technician</option>
                    <option value="Manager">Manager</option>
                </select>
            </div>

            <button type="submit">Create Account</button>

        </form>

        <div class="back-login">
            <a href="login.php">← กลับไปหน้า Login</a>
        </div>

    </div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
document.getElementById('registerForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('register.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {

        if (data.includes("สมัครสมาชิกสำเร็จ")) {

            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'สมัครสมาชิกสำเร็จ กรุณารอผู้ดูแลระบบอนุมัติบัญชี',
                showConfirmButton: false,
                timer: 3000
            }).then(() => {
                window.location.href = "login.php";
            });

        } else if (data.includes("ถูกใช้งานแล้ว")) {

            Swal.fire({
                icon: 'error',
                title: 'สมัครไม่สำเร็จ',
                text: 'Username หรือ Employee ID ถูกใช้งานแล้ว'
            });

        } else {

            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: 'กรุณาลองใหม่อีกครั้ง'
            });

        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'ระบบผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้'
        });
        console.error(err);
    });
});
</script>
</body>

</html>