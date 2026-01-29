<?php
session_start();
include 'config.php';

$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // ดึงข้อมูลผู้ใช้โดย username
    $stmt = $conn->prepare(
        "SELECT user_id, username, password, role, profile_image 
         FROM users 
         WHERE username = ? LIMIT 1"
    );
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res && $res->num_rows === 1) {
        $user = $res->fetch_assoc();

        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $user['password'])) {

            // สร้าง SESSION
            $_SESSION['user_id']       = $user['user_id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['profile_image'] = $user['profile_image'];

            // Redirect ตามสิทธิ์
            switch ($user['role']) {
                case 'Admin':
                    header("Location: /factory_monitoring/admin/index.php");
                    break;
                case 'Manager':
                    header("Location: /factory_monitoring/manager/dashboard.php");
                    break;
                case 'Technician':
                    header("Location: /factory_monitoring/technician/dashboard.php");
                    break;
                case 'Operator':
                    header("Location: /factory_monitoring/operator/dashboard.php");
                    break;
                default:
                    header("Location: /factory_monitoring/login.php");
            }
            exit;

        } else {
            $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
        }

    } else {
        $error = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login</title>
<style>
body { font-family: Kanit, sans-serif; display:flex; justify-content:center; align-items:center; height:100vh; background:#f7f7f7; }
.login-box { background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); width:300px; }
input { width:100%; padding:10px; margin:10px 0; border-radius:5px; border:1px solid #ccc; }
button { width:100%; padding:10px; background:#6f1e51; color:#fff; border:none; border-radius:5px; cursor:pointer; }
.error { color:red; margin-bottom:10px; }
</style>
</head>
<body>
<div class="login-box">
    <h2>Login</h2>
    <?php if($error) echo '<div class="error">'.$error.'</div>'; ?>
    <form method="post">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">เข้าสู่ระบบ</button>
    </form>
</div>
</body>
</html>
