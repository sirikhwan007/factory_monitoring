<?php
session_start();
include "../config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // รับค่าจากฟอร์ม
    $machine_id_old = $_POST['machine_id_old'];
    $machine_id_new = $_POST['machine_id'];
    $mac_address    = $_POST['mac_address'];
    $name           = $_POST['name'];
    $model          = $_POST['model'];
    $installed_at   = $_POST['installed_at'];
    $location       = $_POST['location'];
    $amp            = $_POST['amp'];
    $hp             = $_POST['hp'];
    $rpm            = $_POST['rpm'];
    $old_photo      = $_POST['old_photo'];

    // ----------------------------
    // ดึงข้อมูลก่อนแก้ไข
    // ----------------------------
    $stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id=?");
    $stmt->bind_param("s", $machine_id_old);
    $stmt->execute();
    $result = $stmt->get_result();
    $old_data = $result->fetch_assoc();
    $stmt->close();

    if (!$old_data) {
        echo "<script>alert('❌ ไม่พบข้อมูลเครื่องจักรเดิม!'); history.back();</script>";
        exit();
    }

    // ----------------------------
    // ตรวจสอบ Machine ID / MAC ซ้ำ
    // ----------------------------
    $stmt = $conn->prepare("SELECT * FROM machines WHERE (machine_id=? OR mac_address=?) AND machine_id != ?");
    $stmt->bind_param("sss", $machine_id_new, $mac_address, $machine_id_old);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        echo "<script>alert('❌ Machine ID หรือ MAC Address ซ้ำในระบบ!'); history.back();</script>";
        exit();
    }
    $stmt->close();

    // ----------------------------
    // อัปโหลดรูปใหม่ (ถ้ามี)
    // ----------------------------
    $photo_url = $old_photo;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
        $uploadDir = "../uploads/machines/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $new_name = $machine_id_new . "_" . time() . "." . $extension;
        $target_file = $uploadDir . $new_name;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            if (!empty($old_photo) && file_exists("../" . $old_photo)) {
                unlink("../" . $old_photo);
            }
            $photo_url = "uploads/machines/" . $new_name;
        }
    }

    // ----------------------------
    // โหลดข้อมูลไฟล์ datasheet เดิม (เก็บไว้ใช้ใน LOG)
    // ----------------------------
    $old_doc = null;
    $old_datasheet_name = '-';
    $old_datasheet_path = '-';

    $stmt = $conn->prepare("SELECT file_name, file_path, file_type 
                        FROM machine_documents 
                        WHERE machine_id=?");
    $stmt->bind_param("s", $machine_id_old);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows > 0) {
        $old_doc = $res->fetch_assoc();

        // สำคัญ! เก็บชื่อไฟล์เดิมไว้ก่อน unlink
        $old_datasheet_name = basename($old_doc['file_path']);
        $old_datasheet_path = $old_doc['file_path'];
    }
    $stmt->close();


    // ----------------------------
    // Upload Datasheet ใหม่ (ถ้ามี)
    // ----------------------------
    $document_uploaded = false;
    $new_document_name = null;
    $new_document_url  = null;
    $new_document_type = null;

    if (!empty($_FILES["datasheet"]["name"])) {

        $target_dir = "../uploads/datasheets/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES["datasheet"]["name"], PATHINFO_EXTENSION));
        $allowed = ["pdf", "doc", "docx", "xlsx", "xls", "txt"];

        if (!in_array($ext, $allowed)) {
            echo "<script>alert('❌ ไฟล์ที่อนุญาต: PDF, DOCX, XLSX, TXT'); history.back();</script>";
            exit();
        }

        // สร้างชื่อใหม่ (ชื่อไฟล์จริง + machine_id)
        $base = pathinfo($_FILES["datasheet"]["name"], PATHINFO_FILENAME);
        $clean = preg_replace('/[^A-Za-z0-9_\-]/', '_', $base);
        $new_document_name = $machine_id_new . "_" . $clean . "." . $ext;

        $target_file = $target_dir . $new_document_name;

        if (move_uploaded_file($_FILES["datasheet"]["tmp_name"], $target_file)) {

            // ลบไฟล์เก่า
            if ($old_doc && file_exists("../" . $old_doc['file_path'])) {
                unlink("../" . $old_doc['file_path']);
            }

            $document_uploaded = true;
            $new_document_url  = "uploads/datasheets/" . $new_document_name;
            $new_document_type = $ext;
        }
    }
    // ----------------------------
    // UPDATE/INSERT Datasheet
    // ----------------------------
    if ($document_uploaded) {

        if ($old_doc) {
            // UPDATE ไฟล์ใหม่แทนเก่า
            $stmt = $conn->prepare("
        UPDATE machine_documents
        SET file_name=?, file_path=?, file_type=?
        WHERE machine_id=?
    ");
            $stmt->bind_param(
                "ssss",
                $new_document_name,
                $new_document_url,
                $new_document_type,
                $machine_id_old   // สำคัญ!!
            );
        } else {
            // หากไม่มีไฟล์เดิม → INSERT ใหม่
            $stmt = $conn->prepare("
                    INSERT INTO machine_documents (machine_id, file_name, file_path, file_type)
                    VALUES (?, ?, ?, ?)
                ");
            $stmt->bind_param(
                "ssss",
                $machine_id_new,
                $new_document_name,
                $new_document_url,
                $new_document_type
            );
        }

        $stmt->execute();
        $stmt->close();
    }


    // ============================
    // เริ่ม Transaction
    // ============================
    $conn->begin_transaction();

    try {

        // ----------------------------
        // UPDATE ข้อมูลเครื่องจักร
        // ----------------------------
        $stmt = $conn->prepare("
            UPDATE machines SET
                machine_id=?,
                mac_address=?,
                name=?,
                model=?,
                installed_at=?,
                location=?,
                amp=?,
                hp=?,
                rpm=?,
                photo_url=?
            WHERE machine_id=?
        ");

        $stmt->bind_param(
            "ssssssddsss",
            $machine_id_new,
            $mac_address,
            $name,
            $model,
            $installed_at,
            $location,
            $amp,
            $hp,
            $rpm,
            $photo_url,
            $machine_id_old
        );

        $stmt->execute();
        $stmt->close();



        // ----------------------------
        // LOG – บันทึกหลังแก้ไข
        // ----------------------------
        $action = "UPDATE";
        $user_id = $_SESSION['user_id'] ?? null;
        $role    = $_SESSION['role'] ?? 'Admin';

        // ใช้ข้อมูลไฟล์เก่าที่โหลดไว้ก่อนแล้ว (old_doc)
        $old_datasheet_name = $old_doc ? basename($old_doc['file_path']) : '-';

        // ถ้าอัปโหลดไฟล์ใหม่ → ใช้ชื่อใหม่
        // ถ้าไม่ได้อัปโหลด → ใช้ชื่อเดิม
        $new_datasheet_name = $document_uploaded
            ? basename($new_document_url)
            : ($old_doc ? basename($old_doc['file_path']) : '-');


        // กำหนดค่าเก่าและใหม่
        $old_photo_name     = !empty($old_data['photo_url']) ? basename($old_data['photo_url']) : '-';
        $new_photo_name     = !empty($photo_url) ? basename($photo_url) : '-';

        $old_datasheet_name = $old_datasheet_name; // ใช้ค่าที่เก็บไว้ก่อน unlink
        $new_datasheet_name = $document_uploaded
            ? basename($new_document_url)
            : $old_datasheet_name;


        $old_name     = !empty($old_data['name']) ? $old_data['name'] : '-';
        $new_name     = !empty($name) ? $name : '-';

        // สร้างข้อความ LOG
        $description = "แก้ไขข้อมูลเครื่องจักร\n\n" .
            "---- ก่อนแก้ไข ----\n" .
            "Machine ID: " . (!empty($old_data['machine_id']) ? $old_data['machine_id'] : '-') . "\n" .
            "MAC Address: " . (!empty($old_data['mac_address']) ? $old_data['mac_address'] : '-') . "\n" .
            "Name: " . (!empty($old_data['name']) ? $old_data['name'] : '-') . "\n" .
            "Model: " . (!empty($old_data['model']) ? $old_data['model'] : '-') . "\n" .
            "Installed At: " . (!empty($old_data['installed_at']) ? $old_data['installed_at'] : '-') . "\n" .
            "Location: " . (!empty($old_data['location']) ? $old_data['location'] : '-') . "\n" .
            "Amp: " . (!empty($old_data['amp']) ? $old_data['amp'] : '-') . "\n" .
            "HP: " . (!empty($old_data['hp']) ? $old_data['hp'] : '-') . "\n" .
            "RPM: " . (!empty($old_data['rpm']) ? $old_data['rpm'] : '-') . "\n" .
            "Photo: $old_photo_name\n" .
            "Datasheet: $old_datasheet_name\n\n" .
            "---- หลังแก้ไข ----\n" .
            "Machine ID: " . (!empty($machine_id_new) ? $machine_id_new : '-') . "\n" .
            "MAC Address: " . (!empty($mac_address) ? $mac_address : '-') . "\n" .
            "Name: " . (!empty($name) ? $name : '-') . "\n" .
            "Model: " . (!empty($model) ? $model : '-') . "\n" .
            "Installed At: " . (!empty($installed_at) ? $installed_at : '-') . "\n" .
            "Location: " . (!empty($location) ? $location : '-') . "\n" .
            "Amp: " . (!empty($amp) ? $amp : '-') . "\n" .
            "HP: " . (!empty($hp) ? $hp : '-') . "\n" .
            "RPM: " . (!empty($rpm) ? $rpm : '-') . "\n" .
            "Photo: $new_photo_name\n" .
            "Datasheet: $new_datasheet_name";

        // ----------------------------
        // บันทึกลง logs
        // ----------------------------
        if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description) VALUES (NULL, ?, ?, ?)");
            $stmt->bind_param("sss", $role, $action, $description);
        } else {
            $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $user_id, $role, $action, $description);
        }

        $stmt->execute();
        $stmt->close();

        // ============================
        // Commit
        // ============================
        $conn->commit();

        echo "<script>alert('✅ อัปเดตข้อมูลเครื่องจักรเรียบร้อย'); window.location='/factory_monitoring/admin/machines.php';</script>";
        exit();
    } catch (Exception $e) {

        $conn->rollback();
        echo "<script>alert('❌ เกิดข้อผิดพลาด: " . $e->getMessage() . "'); history.back();</script>";
        exit();
    }
}
