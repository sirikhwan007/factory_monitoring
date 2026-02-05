<?php
session_start();
require_once "../config.php"; // ตรวจสอบว่า path config ถูกต้อง (ถอยกลับ 1 ชั้น)

// 1. ตรวจสอบสิทธิ์ว่าเป็น Operator จริงหรือไม่
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Operator') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

// 2. ตรวจสอบการส่งค่า POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    // 3. ตรวจสอบความถูกต้องของข้อมูล
    if (empty($password) || empty($confirm)) {
        $_SESSION['status'] = "error";
        $_SESSION['message'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("Location: profile.php");
        exit();
    }

    if ($password !== $confirm) {
        $_SESSION['status'] = "error";
        $_SESSION['message'] = "รหัสผ่านยืนยันไม่ตรงกัน";
        header("Location: profile.php");
        exit();
    }

    // 4. บันทึกลงฐานข้อมูล
    // Hash รหัสผ่าน
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // เตรียม SQL Update
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $stmt->bind_param("ss", $hashed_password, $user_id);

    if ($stmt->execute()) {
        $_SESSION['status'] = "success";
        $_SESSION['message'] = "เปลี่ยนรหัสผ่านสำเร็จ!";
    } else {
        $_SESSION['status'] = "error";
        $_SESSION['message'] = "เกิดข้อผิดพลาดจากระบบ: " . $conn->error;
    }

    $stmt->close();
    $conn->close();

    // 5. ส่งกลับไปหน้า Profile
    header("Location: profile.php");
    exit();

} else {
    // ถ้าเข้าไฟล์นี้โดยตรงให้ดีดกลับ
    header("Location: profile.php");
    exit();
}