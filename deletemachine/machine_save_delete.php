<?php
session_start();
include "../config.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("ไม่สามารถเข้าถึงหน้านี้ได้โดยตรง");
}

$machine_id = $_POST['machine_id'] ?? null;
$delete_reason = trim($_POST['delete_reason'] ?? '');

if (!$machine_id) {
    die("ไม่พบเครื่องจักรที่ต้องลบ");
}

// -----------------------------------------
// 1) ดึงข้อมูลเครื่องจักรเพื่อใช้ลบไฟล์
// -----------------------------------------
$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();
$stmt->close();

if (!$machine) {
    die("ไม่พบข้อมูลเครื่องจักร");
}

// ดึงไฟล์รูป
$photo_url = $machine['photo_url'];   // เช่น uploads/machines/xxx.png

// -----------------------------------------
// 2) ดึงข้อมูลไฟล์เอกสารจาก machine_documents
// -----------------------------------------
$stmt = $conn->prepare("SELECT file_path FROM machine_documents WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$res_doc = $stmt->get_result();
$doc = $res_doc->fetch_assoc();
$stmt->close();

$datasheet_path = $doc['file_path'] ?? null;

// -----------------------------------------
// เริ่ม Transaction
// -----------------------------------------
$conn->begin_transaction();

try {

    // -------------------------------------
    // 3) ลบไฟล์รูปจริง (.png, .jpg)
    // -------------------------------------
    if (!empty($photo_url) && file_exists("../" . $photo_url)) {
        unlink("../" . $photo_url);
    }

    // -------------------------------------
    // 4) ลบไฟล์เอกสารจริง (.pdf, .docx)
    // -------------------------------------
    if (!empty($datasheet_path) && file_exists("../" . $datasheet_path)) {
        unlink("../" . $datasheet_path);
    }

    // -------------------------------------
    // 5) ลบข้อมูลใน machine_documents
    // -------------------------------------
    $stmt = $conn->prepare("DELETE FROM machine_documents WHERE machine_id = ?");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    $stmt->close();

    // -------------------------------------
    // 6) ลบข้อมูลใน machines
    // -------------------------------------
    $stmt = $conn->prepare("DELETE FROM machines WHERE machine_id = ?");
    $stmt->bind_param("s", $machine_id);
    $stmt->execute();
    $stmt->close();

    // -------------------------------------
    // 7) บันทึก LOG
    // -------------------------------------
    $action = "DELETE";
    $user_id = $_SESSION['user_id'] ?? null;
    $role = $_SESSION['role'] ?? "Admin";

    $machine_json = json_encode($machine, JSON_UNESCAPED_UNICODE);
    $description = "ลบเครื่องจักร: $machine_json\nรายละเอียดเพิ่มเติม: $delete_reason";

    if ($user_id === null) {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description)
                                VALUES (NULL, ?, ?, ?)");
        $stmt->bind_param("sss", $role, $action, $description);
    } else {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, role, action, description)
                                VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user_id, $role, $action, $description);
    }

    $stmt->execute();
    $stmt->close();

    $conn->commit();

    header("Location: /factory_monitoring/admin/machines.php?msg=deleted");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    die("เกิดข้อผิดพลาด: " . $e->getMessage());
}
?>
