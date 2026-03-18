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

    $stmt = $conn->prepare("SELECT machine_id FROM machines WHERE machine_id=?");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: /addmachine/machine.php?error=duplicate_id");
        exit();
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT mac_address FROM machines WHERE mac_address=?");
    $stmt->bind_param("s", $mac_address);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        header("Location: /addmachine/machine.php?error=duplicate_mac");
        exit();
    }
    $stmt->close();

    $photo_url = null; 

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

        $fileData = file_get_contents($_FILES['photo']['tmp_name']);

        $fileType = $_FILES['photo']['type'];

        $base64 = base64_encode($fileData);

        $photo_url = 'data:' . $fileType . ';base64,' . $base64;
    }

    $datasheet_uploaded = false;
    $datasheet_path = null;
    $datasheet_name = null;
    $datasheet_type = null;

    if (!empty($_FILES["datasheet"]["name"]) && $_FILES["datasheet"]["error"] === 0) {

        $fileData = file_get_contents($_FILES["datasheet"]["tmp_name"]);
        
        // 2. ข้อมูลไฟล์
        $mimeType = $_FILES["datasheet"]["type"];
        $originalName = $_FILES["datasheet"]["name"];

        $base64 = base64_encode($fileData);

        $datasheet_path = 'data:' . $mimeType . ';base64,' . $base64;

        $datasheet_name = $originalName; 
        $datasheet_type = pathinfo($originalName, PATHINFO_EXTENSION);
        
        $datasheet_uploaded = true;
    }

    $conn->begin_transaction();

    try {

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

        if ($datasheet_uploaded) {
            $stmt = $conn->prepare("
                INSERT INTO machine_documents (machine_id, file_name, file_path, file_type)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("ssss", $machine_id, $datasheet_name, $datasheet_path, $datasheet_type);
            $stmt->execute();
            $stmt->close();
        }

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

        header("Location: /machine_list/machine.php?status=success");
        exit;
    } catch (Exception $e) {
        $conn->rollback();

        header("Location: /addmachine/machine.php?status=error&message=" . urlencode($e->getMessage()));
        exit;
    }
}
