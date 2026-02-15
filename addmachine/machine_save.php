<?php
session_start();
include "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $machine_id   = $_POST['machine_id'];
    $mac_address  = $_POST['mac_address'];
    $name         = $_POST['name'];
    $model        = $_POST['model'];
    $installed_at = $_POST['installed_at'];
    $location     = $_POST['location'];
    $amp          = $_POST['amp'];
    $hp           = $_POST['hp'];
    $rpm          = $_POST['rpm'];
    $status       = "Active";

    // ----------------------------
    // เช็ค Machine ID ซ้ำ
    // ----------------------------
    $stmt = $conn->prepare("SELECT machine_id FROM machines WHERE machine_id=?");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: /addmachine/machine.php?error=duplicate_id");
        exit();
    }
    $stmt->close();

    // ----------------------------
    // เช็ค MAC ซ้ำ
    // ----------------------------
    $stmt = $conn->prepare("SELECT mac_address FROM machines WHERE mac_address=?");
    $stmt->bind_param("s", $mac_address);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: /addmachine/machine.php?error=duplicate_mac");
        exit();
    }
    $stmt->close();

    
    // Upload รูปภาพแบบ Base64 ลง Database
    $photo_url = null; 
    
    // เช็คว่ามีการเลือกไฟล์และไม่มี Error
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        // 1. อ่านไฟล์ออกมาเป็น Binary Data
        $fileData = file_get_contents($_FILES['photo']['tmp_name']);
        
        // 2. ดึงประเภทไฟล์ (เช่น image/jpeg, image/png)
        $fileType = $_FILES['photo']['type'];
        
        // 3. แปลงเป็น Base64
        $base64 = base64_encode($fileData);
        
        // 4. สร้าง string พร้อมใช้ (Data URI)
        $photo_url = 'data:' . $fileType . ';base64,' . $base64;
    }

    // ----------------------------
    // Upload Datasheet (PDF/Doc)
    // ----------------------------
    $datasheet_uploaded = false;
    $datasheet_path = null;
    $datasheet_name = null;
    $datasheet_type = null;

    if (!empty($_FILES["datasheet"]["name"]) && $_FILES["datasheet"]["error"] === 0) {

        // 1. อ่านไฟล์เป็น Binary
        $fileData = file_get_contents($_FILES["datasheet"]["tmp_name"]);
        
        // 2. ข้อมูลไฟล์
        $mimeType = $_FILES["datasheet"]["type"]; // เช่น application/pdf
        $originalName = $_FILES["datasheet"]["name"];
        
        // 3. แปลงเป็น Base64
        $base64 = base64_encode($fileData);
        
        // 4. สร้าง String ที่พร้อมใช้งาน (Data URI)
        $datasheet_path = 'data:' . $mimeType . ';base64,' . $base64;
        
        // เก็บชื่อและนามสกุลไว้เหมือนเดิม
        $datasheet_name = $originalName; 
        $datasheet_type = pathinfo($originalName, PATHINFO_EXTENSION);
        
        $datasheet_uploaded = true;
    }

    // ----------------------------
    // Transaction
    // ----------------------------
    $conn->begin_transaction();

    try {

        // ----------------------------
        // INSERT machines
        // ----------------------------
        $stmt = $conn->prepare("
            INSERT INTO machines
            (machine_id, mac_address, name, model, status, location, amp, hp, rpm, photo_url, installed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "ssssssddsss",
            $machine_id,
            $mac_address,
            $name,
            $model,
            $status,
            $location,
            $amp,
            $hp,
            $rpm,
            $photo_url,
            $installed_at
        );

        $stmt->execute();
        $stmt->close();

        // ----------------------------
        // INSERT DATASHEET → machine_documents
        // ----------------------------
        if ($datasheet_uploaded) {
            $stmt = $conn->prepare("
                INSERT INTO machine_documents (machine_id, file_name, file_path, file_type)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $machine_id, $datasheet_name, $datasheet_path, $datasheet_type);
            $stmt->execute();
            $stmt->close();
        }

        // ----------------------------
        // บันทึก LOGS
        // ----------------------------
        $action = "INSERT";
        $user_id = $_SESSION['user_id'] ?? null;
        $role = $_SESSION['role'] ?? 'Admin';

        $machine_data = [
            "machine_id" => $machine_id,
            "mac_address" => $mac_address,
            "name" => $name,
            "model" => $model,
            "status" => $status,
            "location" => $location,
            "amp" => $amp,
            "hp" => $hp,
            "rpm" => $rpm,
            "photo_url" => $photo_url,
            "installed_at" => $installed_at,
            "datasheet" => $datasheet_path
        ];

        $description = "เพิ่มเครื่องจักรใหม่: " . json_encode($machine_data, JSON_UNESCAPED_UNICODE);

        if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description) VALUES (NULL, ?, ?, ?)");
            $stmt->bind_param("sss", $role, $action, $description);
        } else {
            $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $role, $action, $description);
        }

        $stmt->execute();
        $stmt->close();

        // Commit
        $conn->commit();

        // ส่งกลับไปที่หน้า machine.php พร้อมแนบสถานะ success ไปทาง URL
        header("Location: /machine_list/machine.php?status=success");
        exit;
    } catch (Exception $e) {
        $conn->rollback();

        // ส่งกลับไปหน้าเดิมพร้อมแจ้ง Error (หรือจะใช้ die แบบเดิมที่คุณเคยใช้ก็ได้ครับ)
        header("Location: /addmachine/machine.php?status=error&message=" . urlencode($e->getMessage()));
        exit;
    }
}
