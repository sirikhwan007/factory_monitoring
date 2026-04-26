<?php
// 1. Debug Mode
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 2. Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Config Path
require_once "../../config.php";

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'error' => ''];

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception("คุณยังไม่ได้ล็อกอิน");
    }

    // --- CASE 1: ดึงข้อมูล (GET) ---
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $u_id = isset($_GET['user_id']) ? trim($_GET['user_id']) : '';
        if (empty($u_id)) throw new Exception("ไม่พบรหัสผู้ใช้");

        $stmt = $conn->prepare("SELECT user_id, username, email, phone, role FROM users WHERE user_id = ?");
        $stmt->bind_param("s", $u_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        
        if (!$res) throw new Exception("ไม่พบข้อมูลผู้ใช้");
        
        echo json_encode($res);
        exit;

    // --- CASE 2: บันทึกข้อมูล (POST) ---
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user_id  = isset($_POST['user_id']) ? trim($_POST['user_id']) : '';
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
        $phone    = isset($_POST['phone']) ? trim($_POST['phone']) : '';
        $role     = isset($_POST['role']) ? trim($_POST['role']) : '';

        if (empty($user_id) || empty($username)) {
            throw new Exception("ข้อมูลไม่ครบถ้วน");
        }

        $conn->begin_transaction();

        // Update ข้อมูลพื้นฐาน
        $stmt = $conn->prepare("UPDATE users SET username=?, email=?, phone=?, role=? WHERE user_id=?");
        $stmt->bind_param("sssss", $username, $email, $phone, $role, $user_id);
        if (!$stmt->execute()) throw new Exception("Update Failed: " . $stmt->error);
        $stmt->close();

        // จัดการรูปภาพ (ถ้ามี)
        if (!empty($_FILES['profile_image']['name']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
            $fileData = file_get_contents($_FILES['profile_image']['tmp_name']);
            $type = $_FILES['profile_image']['type'];
            $base64 = base64_encode($fileData);
            $newImage = "data:$type;base64,$base64";

            $stmt_img = $conn->prepare("UPDATE users SET profile_image=? WHERE user_id=?");
            $stmt_img->bind_param("ss", $newImage, $user_id);
            $stmt_img->execute();
            $stmt_img->close();
        }

        $conn->commit();
        $response['success'] = true;

    } else {
        // ถ้าไม่ใช่ทั้ง GET และ POST
        throw new Exception("Invalid Request Method: " . $_SERVER['REQUEST_METHOD']);
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_errno == 0) $conn->rollback();
    $response['success'] = false;
    $response['error'] = $e->getMessage();
}

// ส่งผลลัพธ์สุดท้าย
echo json_encode($response);
exit;