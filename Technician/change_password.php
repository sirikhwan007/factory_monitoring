<?php
session_start();
require_once "../config.php"; // ตรวจสอบ path ของ config ให้ถูกต้อง

/* ===== 1. Auth Guard (ตรวจสอบสิทธิ์) ===== */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* ===== 2. รับค่าจากฟอร์ม ===== */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 2.1 ตรวจสอบว่ากรอกข้อมูลครบหรือไม่
    if (empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = "กรุณากรอกข้อมูลให้ครบถ้วน";
        header("Location: profile.php"); // เปลี่ยนเป็นชื่อไฟล์หน้า Profile ของคุณ
        exit();
    }

    // 2.2 ตรวจสอบว่ารหัสผ่านตรงกันหรือไม่
    if ($password !== $confirm_password) {
        $_SESSION['error'] = "รหัสผ่านยืนยันไม่ตรงกัน";
        header("Location: profile.php");
        exit();
    }

    // 2.3 (Optional) ตรวจสอบความยาวรหัสผ่าน (เช่น ขั้นต่ำ 6 ตัว)
    if (strlen($password) < 6) {
        $_SESSION['error'] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
        header("Location: profile.php");
        exit();
    }

    /* ===== 3. อัปเดตลงฐานข้อมูล ===== */
    
    // ทำการ Hash รหัสผ่านเพื่อความปลอดภัย (ห้ามเก็บเป็น Plain Text)
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // เตรียม SQL (สมมติว่าชื่อ column ใน database คือ 'password')
    // **สำคัญ:** เช็คชื่อ column ใน Database ของคุณว่าเป็น 'password' หรือ 'password_hash' แล้วแก้ตรงนี้
    $sql = "UPDATE users SET password = ? WHERE user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $password_hash, $user_id);
        
        if ($stmt->execute()) {
            // สำเร็จ
            $_SESSION['success'] = "เปลี่ยนรหัสผ่านเรียบร้อยแล้ว";
        } else {
            // Error จาก Database
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "ไม่สามารถเชื่อมต่อฐานข้อมูลได้";
    }

    // ส่งกลับไปหน้าเดิม
    header("Location: profile.php");
    exit();

} else {
    // ถ้าเข้าไฟล์นี้โดยไม่ได้กด Submit
    header("Location: profile.php");
    exit();
}
?>