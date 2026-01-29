<?php
session_start();
require __DIR__ . '/../../config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

if(!isset($_SESSION['user_id'])){
    $response['error'] = 'คุณยังไม่ได้ล็อกอิน';
    echo json_encode($response);
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $user_id = trim($_POST['user_id'] ?? '');

    if($user_id !== ''){
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("s", $user_id);

        if($stmt->execute()){
            $response['success'] = $stmt->affected_rows > 0;
            if(!$response['success']){
                $response['error'] = 'ไม่พบผู้ใช้หรือไม่สามารถลบได้';
            }
        }else{
            $response['error'] = $stmt->error;
        }

        $stmt->close();
    }else{
        $response['error'] = 'ไม่มีรหัสผู้ใช้';
    }
}

$conn->close();
echo json_encode($response);
