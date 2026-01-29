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
            if (!empty($_FILES['profile_image']['name']) 
                && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

                // เอารูปเก่าออกก่อน
                $old = $conn->query("SELECT profile_image FROM users WHERE user_id='$user_id'")
                            ->fetch_assoc()['profile_image'];

                if ($old && $old !== 'default.png') {
                    $oldPath = $uploadDir . $old;
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                // อัปโหลดรูปใหม่
                $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                $filename = uniqid('user_') . "." . $ext;
                $targetPath = $uploadDir . $filename;

                if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $targetPath)) {
                    throw new Exception("อัปโหลดรูปไม่สำเร็จ");
                }

                // อัปเดตชื่อไฟล์ใน DB
                $stmt = $conn->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
                $stmt->bind_param("ss", $filename, $user_id);
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
