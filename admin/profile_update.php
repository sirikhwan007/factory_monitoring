<?php
session_start();
require_once "../config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// รับค่าจากฟอร์ม
$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';
$phone = $_POST['phone'] ?? '';
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$profile_image = null;


// =======================================================
//       1) ดึงข้อมูลก่อนแก้ไข (สำคัญที่สุด)
// =======================================================
$oldQuery = $conn->prepare("
    SELECT username, email, phone, profile_image
    FROM users 
    WHERE user_id = ?
");
$oldQuery->bind_param("i", $user_id);
$oldQuery->execute();
$old_result = $oldQuery->get_result();
$oldData = $old_result->fetch_assoc();
$oldQuery->close();

if (!$oldData) {
    die("❌ ไม่พบข้อมูลผู้ใช้งาน!");
}


// =======================================================
//       2) ตรวจสอบรหัสผ่านใหม่ (ถ้ามีการกรอก)
// =======================================================
if (!empty($password)) {
    if ($password !== $confirm_password) {
        die("❌ รหัสผ่านไม่ตรงกัน");
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
}



// =======================================================
//       3) อัปโหลดรูปโปรไฟล์ใหม่ (ถ้ามี)
// =======================================================
if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {

    $uploadDir = __DIR__ . "/uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES['profile_image']['name']);
    $targetFile = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetFile)) {
        $profile_image = $fileName;
    }
}



// =======================================================
//       4) สร้าง SQL UPDATE ตามกรณีต่างๆ
// =======================================================
if ($profile_image && !empty($password)) {

    $sql = "UPDATE users 
            SET username=?, email=?, phone=?, password=?, profile_image=? 
            WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $username, $email, $phone, $hashedPassword, $profile_image, $user_id);

} elseif (!empty($password)) {

    $sql = "UPDATE users 
            SET username=?, email=?, phone=?, password=? 
            WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $email, $phone, $hashedPassword, $user_id);

} elseif ($profile_image) {

    $sql = "UPDATE users 
            SET username=?, email=?, phone=?, profile_image=? 
            WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $username, $email, $phone, $profile_image, $user_id);

} else {

    $sql = "UPDATE users 
            SET username=?, email=?, phone=? 
            WHERE user_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $username, $email, $phone, $user_id);
}



// =======================================================
//       5) Execute UPDATE Users
// =======================================================
if ($stmt->execute()) {

    // อัปเดต SESSION
    $_SESSION['username'] = $username;
    if ($profile_image) {
        $_SESSION['profile_image'] = $profile_image;
    }

    // เตรียมข้อมูลใหม่
    $new_data = [
        "username"  => $username,
        "email"     => $email,
        "phone"     => $phone,
        "photo_url" => $profile_image ?? $oldData['profile_image']
    ];

    // log message
    $old_data = [
        "username"  => $oldData['username'],
        "email"     => $oldData['email'],
        "phone"     => $oldData['phone'],
        "photo_url" => $oldData['profile_image']
    ];

    $description = "แก้ไขข้อมูลโปรไฟล์:\nเดิม: " .
                    json_encode($old_data, JSON_UNESCAPED_UNICODE) .
                    "\nใหม่: " .
                    json_encode($new_data, JSON_UNESCAPED_UNICODE);

    $action = "UPDATE_PROFILE";
    $role = $_SESSION['role'] ?? 'User';


    // =======================================================
    //       6) Insert Log
    // =======================================================
    if (!empty($user_id)) {

        $log_stmt = $conn->prepare("
            INSERT INTO logs (user_id, role, action, description)
            VALUES (?, ?, ?, ?)
        ");
        $log_stmt->bind_param("ssss", $user_id, $role, $action, $description);

    } else {

        $log_stmt = $conn->prepare("
            INSERT INTO logs (user_id, role, action, description)
            VALUES (NULL, ?, ?, ?)
        ");
        $log_stmt->bind_param("sss", $role, $action, $description);
    }

    $log_stmt->execute();
    $log_stmt->close();

    header("Location: profile.php");
    exit;

} else {
    echo "เกิดข้อผิดพลาด: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
