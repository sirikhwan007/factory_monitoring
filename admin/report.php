<?php
// ================================
// เริ่ม Session (ห้ามมี output ก่อน)
// ================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ================================
// เช็กการล็อกอิน
// ================================
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

// ================================
// เชื่อมต่อฐานข้อมูล
// ================================
include __DIR__ . "/../config.php";

// ================================
// รับ machine_id จาก Dashboard
// ================================
$machine_id = $_GET['machine_id'] ?? null;

if (!$machine_id) {
    die("ไม่พบเครื่องจักรที่เลือก");
}

// ================================
// ดึงข้อมูลเครื่องจักร
// ================================
$stmt = $conn->prepare("SELECT machine_id, name FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("ไม่พบข้อมูลเครื่องจักร");
}

$machine = $result->fetch_assoc();

$stmt->close();
$conn->close();

// ================================
// ข้อมูลผู้ใช้ (Sidebar)
// ================================
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username     = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role         = $_SESSION['role'] ?? 'ไม่ทราบสิทธิ์';
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมเครื่องจักร</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="/factory_monitoring/repair/css/report.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

<section class="main">
    <?php include __DIR__ . '/../admin/SidebarAdmin.php'; ?>

    <div class="dashboard">

        <h2 class="dashboard-title">
            <i class="fas fa-tools"></i> แจ้งซ่อมเครื่องจักร
        </h2>

        <div class="repair-form-card">

            <form action="processrepair.php" method="POST">
                

                <!-- ชื่อผู้แจ้ง -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-user"></i> ชื่อผู้แจ้ง
                    </label>
                    <input type="text"
                           class="form-control"
                           name="reporter"
                           value="<?= htmlspecialchars($username) ?>"
                           readonly>
                </div>

                <!-- ตำแหน่ง -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-briefcase"></i> ตำแหน่ง
                    </label>
                    <input type="text"
                           class="form-control"
                           name="position"
                           value="<?= htmlspecialchars($role) ?>"
                           readonly>
                </div>

                <!-- ประเภทการซ่อม -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-wrench"></i> ประเภทการซ่อม
                    </label>
                    <select class="form-select" name="type" required>
                        <option value="">-- เลือกประเภท --</option>
                        <option value="Preventive">Preventive (การบำรุงรักษาเชิงป้องกัน)</option>
                        <option value="Predictive">Predictive (การบำรุงรักษาเชิงคาดการณ์)</option>
                        <option value="Breakdown">Breakdown (การซ่อมแซมเมื่อเกิดการชำรุด)</option>
                    </select>
                </div>

                <!-- เครื่องจักร -->
                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-microchip"></i> เครื่องจักร
                    </label>
                    <input type="text"
                           class="form-control"
                           value="<?= htmlspecialchars($machine['machine_id'] . ' - ' . $machine['name']) ?>"
                           readonly>

                    <!-- ส่ง machine_id ไปจริง -->
                    <input type="hidden"
                           name="machine_id"
                           value="<?= htmlspecialchars($machine['machine_id']) ?>">
                </div>

                <!-- รายละเอียดปัญหา -->
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-comment-dots"></i> รายละเอียดปัญหา
                    </label>
                    <textarea class="form-control"
                              name="detail"
                              rows="4"
                              placeholder="กรุณาระบุรายละเอียดปัญหาที่พบ เช่น เสียงดัง, เครื่องไม่ทำงาน"
                              required></textarea>
                </div>

                <!-- ปุ่มส่ง -->
                <button type="submit" class="btn btn-submit-repair w-100">
                    <i class="fas fa-paper-plane"></i> ส่งคำขอแจ้งซ่อม
                </button>

            </form>

        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

</body>
</html>
