<?php 
session_start();
require __DIR__ . '/../../config.php';

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
    $password = trim($_POST['password'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? '';

    if (!$user_id || !$username || !$password || !$email || !$role) {
        $response['error'] = 'กรุณากรอกข้อมูลให้ครบ';
        echo json_encode($response);
        exit;
    }

    // ตรวจสอบ user_id ซ้ำ
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id=?");
    $stmt->bind_param("s", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $response['error'] = 'User ID นี้มีอยู่แล้ว';
        echo json_encode($response);
        exit;
    }
    $stmt->close();

    // ตรวจสอบ username ซ้ำ
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && $res->num_rows > 0) {
        $response['error'] = 'ชื่อผู้ใช้นี้มีอยู่แล้ว';
        echo json_encode($response);
        exit;
    }
    $stmt->close();

    // เข้ารหัส password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // อัปโหลดรูป (ถ้ามี)
    $profile_image = 'default.png'; // default
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image = uniqid() . '.' . $ext;
        $upload_path = __DIR__ . '/../uploads/' . $profile_image;
        if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
            $response['error'] = 'ไม่สามารถอัปโหลดรูปได้';
            echo json_encode($response);
            exit;
        }
    }

    // เพิ่มผู้ใช้ใหม่พร้อมรูป
    $stmt = $conn->prepare("INSERT INTO users (user_id, username, password, email, phone, role, profile_image, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssssss", $user_id, $username, $hashed_password, $email, $phone, $role, $profile_image);


    if ($stmt->execute()) {
        $response['success'] = true;
    } else {
        $response['error'] = $stmt->error;
    }

    $stmt->close();
}

$conn->close();
echo json_encode($response);
