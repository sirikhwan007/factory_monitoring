<?php
session_start();
require_once "../../config.php"; // <<<<<< ต้องมี !!!

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

if (!isset($_SESSION['user_id'])) {
    $response['error'] = 'คุณยังไม่ได้ล็อกอิน';
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id = trim($_POST['user_id'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($user_id && $username && $email && $phone && $role) {

        // ใช้โฟลเดอร์เดียวกับหน้าอื่น
        $uploadDir = __DIR__ . '/../uploads/';

        $conn->begin_transaction();

        try {
            // อัปเดตข้อมูล user
            $stmt = $conn->prepare("UPDATE users 
                                    SET username=?, email=?, phone=?, role=? 
                                    WHERE user_id=?");
            $stmt->bind_param("sssss", $username, $email, $phone, $role, $user_id);
            if (!$stmt->execute()) throw new Exception($stmt->error);
            $stmt->close();

            // ถ้ามีรูปใหม่
            // ถ้ามีการอัปโหลดรูปใหม่
            if (!empty($_FILES['profile_image']['name']) 
                && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

                // 1. อ่านไฟล์รูปภาพออกมา
                $fileData = file_get_contents($_FILES['profile_image']['tmp_name']);
                $type = $_FILES['profile_image']['type'];
                
                // 2. แปลงเป็น Base64
                $base64 = base64_encode($fileData);
                $newImage = 'data:' . $type . ';base64,' . $base64;

                // 3. อัปเดตลง Database (ทับข้อมูลเดิมไปเลย ไม่ต้องสั่งลบไฟล์เก่า)
                $stmt = $conn->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
                $stmt->bind_param("ss", $newImage, $user_id);
                
                if (!$stmt->execute()) throw new Exception($stmt->error);
                $stmt->close();
            }

            $conn->commit();
            $response['success'] = true;

        } catch (Exception $e) {
            $conn->rollback();
            $response['error'] = $e->getMessage();
        }

    } else {
        $response['error'] = "ข้อมูลไม่ครบ";
    }
}

$conn->close();
echo json_encode($response);
