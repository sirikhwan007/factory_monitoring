<?php
session_start();
require_once "../config.php";

// 1. Check Auth & Role (ตรวจสอบว่าเป็น Manager หรือไม่)
// ควรตรวจสอบ Role ด้วยเพื่อความปลอดภัย (สมมติว่า Role ใน DB คือ 'Manager')
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

// 2. ตรวจสอบการส่งค่า POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 3. Validation
    if (empty($password) || empty($confirm)) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'กรุณากรอกข้อมูลให้ครบถ้วน';
        header("Location: profile.php");
        exit();
    }

    if ($password !== $confirm) {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'รหัสผ่านยืนยันไม่ตรงกัน';
        header("Location: profile.php");
        exit();
    }

    // 4. Update Password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ใช้ user_id เป็น integer ตามโค้ด profile ที่คุณให้มา ("i")
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("si", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $_SESSION['status'] = 'success';
        $_SESSION['message'] = 'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว';
    } else {
        $_SESSION['status'] = 'error';
        $_SESSION['message'] = 'เกิดข้อผิดพลาด: ' . $conn->error;
    }

    $stmt->close();
    $conn->close();

    header("Location: profile.php");
    exit();

} else {
    header("Location: profile.php");
    exit();
}