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
        echo "<script>alert('❌ Machine ID นี้มีอยู่แล้วในระบบ!'); history.back();</script>";
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
        echo "<script>alert('❌ MAC Address นี้ถูกใช้แล้ว!'); history.back();</script>";
        exit();
    }
    $stmt->close();

    // ----------------------------
    // Upload รูปภาพ
    // ----------------------------
    $photo_url = null;
    if (!empty($_FILES["photo"]["name"])) {

        $target_dir = "../uploads/machines/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $new_name = $machine_id . "_" . time() . "." . $extension;
        $target_file = $target_dir . $new_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_url = "uploads/machines/" . $new_name;
        }
    }

    // ----------------------------
    // Upload Datasheet (PDF/Doc)
    // ----------------------------
    $datasheet_uploaded = false;
    $datasheet_path = null;
    $datasheet_name = null;
    $datasheet_type = null;

    if (!empty($_FILES["datasheet"]["name"])) {

        $target_dir = "../uploads/datasheets/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $ds_extension = strtolower(pathinfo($_FILES["datasheet"]["name"], PATHINFO_EXTENSION));

        // อนุญาตไฟล์
        $allowed = ["pdf", "doc", "docx", "xls", "xlsx", "txt"];

        if (!in_array($ds_extension, $allowed)) {
            echo "<script>alert('❌ อัปโหลดได้เฉพาะไฟล์ PDF, DOCX, XLSX เท่านั้น'); history.back();</script>";
            exit();
        }

        // ใช้ชื่อไฟล์จริง
        $original_name = pathinfo($_FILES["datasheet"]["name"], PATHINFO_FILENAME);

        // ลบอักขระที่ไม่ปลอดภัย เช่น ช่องว่าง, เครื่องหมายพิเศษ
        $clean_name = preg_replace('/[^A-Za-z0-9_\-]/', '_', $original_name);

        // กำหนดชื่อใหม่โดยใช้ชื่อไฟล์จริง + machine_id
        $new_ds_name = $machine_id . "_" . $clean_name . "." . $ds_extension;

        
        $target_ds_file = $target_dir . $new_ds_name;

        if (move_uploaded_file($_FILES["datasheet"]["tmp_name"], $target_ds_file)) {
            $datasheet_uploaded = true;

            // สำหรับบันทึกลง DB
            $datasheet_name = $new_ds_name;
            $datasheet_path = "uploads/datasheets/" . $new_ds_name;
            $datasheet_type = $ds_extension;
        }
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

        echo "<script>alert('✅ เพิ่มเครื่องจักรพร้อม Datasheet สำเร็จ!'); window.location='/factory_monitoring/admin/machines.php';</script>";
        exit;
    } catch (Exception $e) {

        $conn->rollback();
        echo "<script>alert('❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "'); history.back();</script>";
        exit;
    }
}
