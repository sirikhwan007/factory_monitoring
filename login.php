<?php
session_start();
include 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("
        SELECT user_id, username, password, role, profile_image 
        FROM users 
        WHERE username = ? LIMIT 1
    ");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            switch ($user['role']) {
                case 'Admin':
                    header("Location: /admin/index.php");
                    break;
                case 'Manager':
                    header("Location: /Manager/dashboard.php");
                    break;
                case 'Technician':
                    header("Location: /Technician/dashboard.php");
                    break;
                case 'Operator':
                    header("Location: /Operator/dashboard.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit;
        }
    }
    $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factory Monitoring | Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
            font-family: 'Kanit', sans-serif;
        }

        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #eef2f7;
            /* สีรอง ไม่แย่งสายตา */
        }

        .container {
            width: 1227px;
            height: 917px;

            background-color: #ffffff;
            background-image:
                radial-gradient(#d5dbea 0.8px, transparent 0.8px);
            background-size: 20px 20px;

            border-radius: 20px;
            display: flex;
            overflow: hidden;

            box-shadow:
                0 30px 60px rgba(0, 0, 0, 0.12),
                0 10px 20px rgba(0, 0, 0, 0.08);
        }

        /* ERROR */
        .error {
            margin-bottom: 18px;
            padding: 12px 14px;
            border-radius: 6px;

            background: #ffe5e5;
            color: #c62828;
            border: 1px solid #f5b5b5;

            font-size: 14px;
            font-weight: 500;
        }


        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;

            background:
                radial-gradient(circle at 15% 20%, #e3ebff 0%, transparent 40%),
                radial-gradient(circle at 85% 80%, #f0f4ff 0%, transparent 45%),
                linear-gradient(135deg, #eef2f7, #f6f8fc);
        }

        .container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.08),
                0 6px 12px rgba(0, 0, 0, 0.05);
        }


        body {
            background:
                radial-gradient(circle at top left, #e0e7ff 0%, transparent 40%),
                radial-gradient(circle at bottom right, #f0f4ff 0%, transparent 40%),
                #176edf;
        }


        .container {
            width: 900px;
            height: 520px;
            background: #ffffff;
            border-radius: 16px;
            display: flex;
            overflow: hidden;

            box-shadow:
                0 24px 48px rgba(0, 0, 0, 0.10),
                0 8px 16px rgba(0, 0, 0, 0.06);
        }


        /* LEFT */
        .left {
            flex: 1.2;
            background: #f5f7fa;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;

            padding: 30px;
            /* ลดจาก 40 */
            gap: 12px;
            /* คุมระยะทุก element */
        }

        /* IMAGE */
        .left img {
            max-width: 85%;
            /* ไม่ให้รูปใหญ่เกิน */
            margin-bottom: 6px;
            /* ระยะเล็กๆ ใต้รูป */
        }

        /* TITLE */
        .left h3 {
            margin: 0;
            /* ตัด margin เดิมทิ้ง */
            font-weight: 600;
            text-align: center;
        }

        /* DESCRIPTION */
        .left p {
            margin: 0;
            /* สำคัญ */
            font-size: 14px;
            opacity: 0.7;
            text-align: center;
            max-width: 360px;
        }

        .right {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            /* ⭐ ดันลงกลางแนวตั้ง */
        }

        /* HEADER */
        .login-header {

            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 50px;
        }

        .right h1 {
            margin: 0;
            font-weight: 600;
        }

        .right p {
            margin: 0;
            font-size: 13px;
            opacity: 0.65;
        }

        /* ERROR */
        .error {
            margin-bottom: 18px;
        }

        /* FORM */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 18px;
            /* คุม rhythm ทั้งฟอร์ม */
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 13px;
        }

        .form-group input {
            padding: 12px;
            border-radius: 6px;
            border: 1px solid #dcdcdc;
            outline: none;
        }

        /* BUTTON */
        button {
            margin-top: 6px;
            padding: 12px;
            border-radius: 6px;
            background: #1e5eff;
            color: #fff;
            font-weight: 600;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background: #1748c5;
        }
    </style>
</head>

<body>

    <div class="container">

        <div class="left">
            <img src="/uploads/login-illustration.png" alt="Login Illustration">

            <h3>Industrial Motor Machine Monitoring System</h3>
            <p>ระบบตรวจสอบการทำงานของมอเตอร์เครื่องจักรอุตสาหกรรม</p>
        </div>

        <div class="right">

            <div class="login-header">
                <h1>Sign in</h1>
                <p>เข้าสู่ระบบเพื่อจัดการและตรวจสอบสถานะเครื่องจักร</p>
            </div>

            <?php if ($error): ?>
                <div class="error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
                </div>

                <button type="submit">Sign in</button>
            </form>

        </div>


</body>

</html>