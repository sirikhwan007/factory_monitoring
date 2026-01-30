<?php
session_start();
include __DIR__ . "/../config.php";

$user_role = $_SESSION['role'] ?? 'Operator';

$repair_id = $_GET['id'] ?? null;
$machine_id_from_dash = $_GET['machine_id'] ?? null;

// 2. ดึงข้อมูลรายชื่อช่าง (สำหรับ Dropdown)
$tech_sql = "SELECT user_id, username FROM users WHERE role = 'Technician' ORDER BY username ASC";
$tech_result = $conn->query($tech_sql);

$msg = "";

// 3. ตรวจสอบการบันทึกข้อมูล (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $detail = $_POST['detail'];
    $repair_note = $_POST['repair_note'] ?? '';
    $technician_id = !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : NULL;
    $m_id = $_POST['machine_id']; // รับค่า machine_id จาก hidden field

    if ($repair_id) {
        // --- กรณีแก้ไขรายการเดิม (UPDATE) ---
        $repair_time_sql = ($status === 'สำเร็จ') ? ", repair_time = NOW()" : "";
        $update_sql = "UPDATE repair_history SET 
                       status = ?, detail = ?, repair_note = ?, technician_id = ? $repair_time_sql
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("sssii", $status, $detail, $repair_note, $technician_id, $repair_id);
    } else {
        // --- กรณีแจ้งซ่อมใหม่ (INSERT) ---
        $reporter = $_SESSION['username'] ?? 'System';
        $insert_sql = "INSERT INTO repair_history (machine_id, reporter, detail, status, technician_id, report_time) 
                       VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ssssi", $m_id, $reporter, $detail, $status, $technician_id);
    }

    if ($stmt->execute()) {
        // ถ้าสำเร็จ ให้กลับไปหน้าประวัติของเครื่องจักรนั้นๆ
        header("Location: reporthistory.php?id=" . $m_id);
        exit;
    } else {
        $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</div>';
    }
}

// 4. การดึงข้อมูลมาแสดงผลใน Form
if ($repair_id) {
    // ดึงข้อมูลจาก ID งานซ่อม (Mode แก้ไข)
    $stmt = $conn->prepare("SELECT r.*, m.location FROM repair_history r LEFT JOIN machines m ON r.machine_id = m.machine_id WHERE r.id = ?");
    $stmt->bind_param("i", $repair_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
} elseif ($machine_id_from_dash) {
    // ดึงข้อมูลจาก Machine ID (Mode แจ้งใหม่)
    $stmt = $conn->prepare("SELECT machine_id, location FROM machines WHERE machine_id = ?");
    $stmt->bind_param("s", $machine_id_from_dash);
    $stmt->execute();
    $m_info = $stmt->get_result()->fetch_assoc();

    // สร้างข้อมูลจำลองสำหรับแสดงผลใน Form แจ้งใหม่
    $row = [
        'id' => 'ใหม่',
        'machine_id' => $m_info['machine_id'],
        'location' => $m_info['location'] ?? 'ไม่ระบุ',
        'reporter' => $_SESSION['username'] ?? 'ผู้ใช้งาน',
        'position' => $_SESSION['role'] ?? '-',
        'type' => 'แจ้งซ่อมทั่วไป',
        'report_time' => date('Y-m-d H:i:s'),
        'status' => 'รอดำเนินการ',
        'detail' => '',
        'technician_id' => null,
        'username' => '',
        'repair_note' => '',
        'repair_time' => null
    ];
} else {
    die("ไม่พบข้อมูลเครื่องจักรหรือรายการแจ้งซ่อม");
}

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'ผู้ใช้งาน';
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
    <link rel="stylesheet" href="/factory_monitoring/dashboard/dashboard.css">
    <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard {
            margin-left: 250px;
        }
    </style>
</head>

<body>

    <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

    <section class="main">
        <?php include $sidebar_file; ?>

        <div class="dashboard">

            <h2 class="dashboard-title">
                <i class="fas fa-tools"></i> แจ้งซ่อมเครื่องจักร
            </h2>

            <div class="repair-form-card">

                <form action="processrepair.php" method="POST">

                    <div class="machine-header">
                        <h2 class="m-0"><i class="fas fa-cogs"> id :</i>
                            <?= htmlspecialchars($row['machine_id']) ?></h2>
                        <small>รายละเอียดเครื่องจักร</small>
                    </div>

                    <div class="card-body p-4">
                        <h5 class="text-primary mb-3">ข้อมูลทั่วไป</h5>
                        <table class="table table-borderless">
                            <tr>
                                <th width="40%">Machine ID:</th>
                                <td><?= htmlspecialchars($row['machine_id']) ?></td>
                            </tr>
                            <tr>
                                <th>ที่ตั้ง:</th>
                                <td><i class="fas fa-map-marker-alt text-danger"></i>
                                    <?= htmlspecialchars($row['location'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th>ผู้แจ้ง:</th>
                                <td><?= htmlspecialchars($row['reporter']) ?></td>
                            </tr>
                            <tr>
                                <th>ตำแหน่ง:</th>
                                <td><?= htmlspecialchars($row['position'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th>ประเภท:</th>
                                <td><select class="form-select" name="type" required>
                                        <option value="">-- เลือกประเภท --</option>
                                        <option value="Preventive">Preventive (การบำรุงรักษาเชิงป้องกัน)</option>
                                        <option value="Predictive">Predictive (การบำรุงรักษาเชิงคาดการณ์)</option>
                                        <option value="Breakdown">Breakdown (การซ่อมแซมเมื่อเกิดการชำรุด)</option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>เวลาที่แจ้ง:</th>
                                <td><?= date('d/m/Y H:i', strtotime($row['report_time'])) ?></td>
                            </tr>

                        </table>

                        <hr>
                        <?php if (!empty($row['username'])): ?>
                            <h5 class="text-muted mb-2 mt-3">
                                <i class="fas fa-user-cog"></i> ช่างผู้รับผิดชอบ
                            </h5>

                            <div class="p-3 bg-light rounded border mb-3">
                                <?php if (!empty($row['username'])): ?>
                                    <?= htmlspecialchars($row['username']) ?>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">ยังไม่ได้มอบหมายช่าง</span>
                                <?php endif; ?>
                            </div>

                        <?php else: ?>
                            <div class="mt-2 text-muted fst-italic">
                                <i class="fas fa-user-slash"></i>
                                ยังไม่ได้มอบหมายช่าง
                            </div>
                        <?php endif; ?>
                        <div class="col-md-6 mb-3">
                            <label for="technician_id" class="form-label">ช่างผู้รับผิดชอบ:</label>
                            <select class="form-select" name="technician_id" id="technician_id">
                                <option value="">-- ยังไม่ระบุ --</option>
                                <?php
                                if ($tech_result->num_rows > 0) {
                                    $tech_result->data_seek(0);
                                    while ($tech = $tech_result->fetch_assoc()):
                                ?>
                                        <option value="<?= $tech['user_id'] ?>" <?= (isset($row['technician_id']) && $row['technician_id'] == $tech['user_id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($tech['username']) ?>
                                        </option>
                                <?php
                                    endwhile;
                                }
                                ?>
                            </select>
                        </div>

                        <?php if (!empty($row['repair_time'])): ?>
                            <h5 class="text-muted mb-3"><i class="fas fa-calendar-check"></i> วันที่ซ่อมเสร็จ
                            </h5>
                            <div
                                class="p-3 bg-success bg-opacity-10 rounded border border-success text-success">
                                <strong><?= date('d/m/Y H:i', strtotime($row['repair_time'])) ?></strong>
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label for="detail" class="form-label">รายละเอียดแจ้งซ่อม:</label>
                            <textarea class="form-control" name="detail" id="detail" rows="3"
                                placeholder="รายละเอียดของปัญหา..."><?= htmlspecialchars($row['detail'] ?? '') ?></textarea>
                        </div>
                        <input type="hidden" name="machine_id" value="<?= htmlspecialchars($row['machine_id']) ?>">
                        <input type="hidden" name="id" value="<?= htmlspecialchars($row['id'] ?? 'ใหม่') ?>">

                        <input type="hidden" name="reporter" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>">
                        <input type="hidden" name="position" value="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>">
                        <!-- ปุ่มส่ง -->
                        <button type="submit" class="btn btn-submit-repair w-100">
                            <i class="fas fa-paper-plane"></i> ส่งคำขอแจ้งซ่อม
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

</body>

</html>