<?php
session_start();
include __DIR__ . "/../config.php"; // ตรวจสอบ Path config ให้ถูกต้อง

// 1. ตรวจสอบ ID ที่ส่งมา
if (!isset($_GET['id'])) {
    die("Error: ไม่พบ ID รายการแจ้งซ่อม");
}
$repair_id = $_GET['id'];

// 2. ดึงข้อมูลรายชื่อช่าง (สำหรับ Dropdown)
$tech_sql = "SELECT user_id, username FROM users WHERE role = 'Technician' ORDER BY username ASC";
$tech_result = $conn->query($tech_sql);

// 3. ตรวจสอบการบันทึกข้อมูล (POST Request)
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $detail = $_POST['detail'];
    $repair_note = !empty($_POST['repair_note']) ? $_POST['repair_note'] : '';
    $technician_id = !empty($_POST['technician_id']) ? (int) $_POST['technician_id'] : NULL;

    // ถ้าสถานะเป็น "สำเร็จ" ให้ลงเวลาซ่อมเสร็จอัตโนมัติ (ถ้ายังไม่มี)
    if ($status === 'สำเร็จ') {
        $update_sql = "UPDATE repair_history SET 
                       status = ?, 
                       detail = ?,
                       repair_note = ?,
                       technician_id = ?,
                       repair_time = NOW()
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด SQL: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $stmt->bind_param("sssii", $status, $detail, $repair_note, $technician_id, $repair_id);
            if ($stmt->execute()) {
                header('Location: reporthistory.php?id=' . htmlspecialchars($row['machine_id']));
                exit;
            } else {
                $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</div>';
            }
        }
    } else {
        $update_sql = "UPDATE repair_history SET 
                       status = ?, 
                       detail = ?,
                       repair_note = ?,
                       technician_id = ?
                       WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        if (!$stmt) {
            $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด SQL: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $stmt->bind_param("sssii", $status, $detail, $repair_note, $technician_id, $repair_id);
            if ($stmt->execute()) {
                header('Location: reporthistory.php?id=' . htmlspecialchars($row['machine_id']));
                exit;
            } else {
                $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</div>';
            }
        }
    }
}

// 4. ดึงข้อมูลใบแจ้งซ่อมล่าสุดมาแสดง (เพื่อให้ข้อมูลอัปเดตหลังกดบันทึก)
$stmt = $conn->prepare("SELECT r.*, m.location FROM repair_history r LEFT JOIN machines m ON r.machine_id = m.machine_id WHERE r.id = ?");
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("ไม่พบข้อมูล");
}

// --- เตรียมข้อมูลสำหรับส่งไปให้ SidebarAdmin.php ---
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'ผู้ใช้งาน';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการงานซ่อม #<?= $repair_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* --- Layout Styles --- */
        body {
            background-color: #f8fafd;
            font-family: 'Kanit', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .main {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar-wrapper {
            width: 250px;
            min-width: 250px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #eee;
            z-index: 1000;
            transition: 0.3s;
        }

        /* Content Styling */
        .content-container {
            flex-grow: 1;
            padding: 30px;
            width: calc(100% - 250px);
        }

        /* Custom Styles for Edit Page */
        .card-custom {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }

        .header-title {
            color: #2c3e50;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .form-label {
            font-weight: 600;
            color: #555;
        }

        .info-label {
            color: #888;
            font-size: 0.9rem;
        }

        .info-value {
            font-weight: 500;
            color: #333;
            font-size: 1.05rem;
        }

        /* Machine Detail Card Styles */
        .card-machine {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            background: white;
        }

        .machine-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 20px;
        }

        /* Mobile Hamburger */
        .btn-hamburger {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 9999;
            background: #fff;
            padding: 10px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .sidebar-wrapper {
                position: fixed;
                left: -250px;
                height: 100vh;
            }

            .sidebar-wrapper.active {
                left: 0;
            }

            .content-container {
                width: 100%;
                padding: 15px;
                padding-top: 60px;
                /* เว้นที่ให้ปุ่ม Hamburger */
            }

            .btn-hamburger {
                display: block;
            }
        }
    </style>
</head>

<body>

    <div class="btn-hamburger">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main">
        <div class="sidebar-wrapper">
            <?php include __DIR__ . '/SidebarAdmin.php'; ?>
        </div>

        <div class="content-container">
            <div class="container-fluid p-0">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="header-title m-0"><i class="fas fa-edit"></i> จัดการใบแจ้งซ่อม ID: <?= $row['id'] ?></h3>
                </div>

                <?= $msg ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-lg-5 mb-4">
                            <div class="card card-machine h-100">
                                <div class="machine-header">
                                    <h2 class="m-0"><i class="fas fa-cogs"></i>
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
                                            <td><span
                                                    class="badge bg-secondary"><?= htmlspecialchars($row['type']) ?></span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <th>เวลาที่แจ้ง:</th>
                                            <td><?= date('d/m/Y H:i', strtotime($row['report_time'])) ?></td>
                                        </tr>
                                        <tr>
                                            <th>สถานะ:</th>
                                            <td>
                                                <?php
                                                $statusColor = ($row['status'] == 'สำเร็จ') ? 'success' : (($row['status'] == 'กำลังซ่อม') ? 'warning' : 'danger');
                                                ?>
                                                <span
                                                    class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($row['status']) ?></span>
                                            </td>
                                        </tr>
                                    </table>

                                    <hr>

                                    <h5 class="text-muted mb-3"><i class="fas fa-file-alt"></i> รายละเอียดแจ้งซ่อม</h5>
                                    <div class="p-3 bg-light rounded border mb-3">
                                        <?= nl2br(htmlspecialchars($row['detail'])) ?>
                                    </div>

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


                                    <?php if (!empty($row['repair_time'])): ?>
                                        <h5 class="text-muted mb-3"><i class="fas fa-calendar-check"></i> วันที่ซ่อมเสร็จ
                                        </h5>
                                        <div
                                            class="p-3 bg-success bg-opacity-10 rounded border border-success text-success">
                                            <strong><?= date('d/m/Y H:i', strtotime($row['repair_time'])) ?></strong>
                                        </div>
                                    <?php endif; ?>

                                    <?php if (!empty($row['repair_note'])): ?>
                                        <h5 class="text-muted mb-3 mt-4"><i class="fas fa-wrench"></i> รายละเอียดการซ่อม
                                        </h5>
                                        <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                                            <?= nl2br(htmlspecialchars($row['repair_note'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 mb-4">
                            <div class="card card-custom h-100">
                                <div class="card-header bg-primary text-white">
                                    <strong><i class="fas fa-tools"></i> ส่วนการจัดการ / มอบหมายงาน</strong>
                                </div>
                                <div class="card-body">

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="status" class="form-label">อัปเดตสถานะ:</label>
                                            <select class="form-select" name="status" id="status" required>
                                                <option value="รอดำเนินการ" <?= $row['status'] == 'รอดำเนินการ' ? 'selected' : '' ?>>รอดำเนินการ</option>
                                                <option value="กำลังซ่อม" <?= $row['status'] == 'กำลังซ่อม' ? 'selected' : '' ?>>กำลังซ่อม (รับงาน)</option>
                                                <option value="รออะไหล่" <?= $row['status'] == 'รออะไหล่' ? 'selected' : '' ?>>รออะไหล่</option>
                                                <option value="สำเร็จ" <?= $row['status'] == 'สำเร็จ' ? 'selected' : '' ?>>
                                                    ซ่อมสำเร็จ (ปิดงาน)</option>
                                                <option value="ซ่อมไม่สำเร็จ" <?= $row['status'] == 'ซ่อมไม่สำเร็จ' ? 'selected' : '' ?>>ซ่อมไม่สำเร็จ</option>
                                            </select>
                                        </div>

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
                                    </div>

                                    <div class="mb-3">
                                        <label for="detail" class="form-label">รายละเอียดแจ้งซ่อม:</label>
                                        <textarea class="form-control" name="detail" id="detail" rows="3"
                                            placeholder="รายละเอียดของปัญหา..."><?= htmlspecialchars($row['detail'] ?? '') ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="reporthistory.php?id=<?= htmlspecialchars($row['machine_id']) ?>"
                                            class="btn btn-light border">ยกเลิก</a>
                                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i>
                                            บันทึกข้อมูล</button>
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>
            </div>
            </form>

        </div>
        </div>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="SidebarAdmin.js"></script>

</body>

</html>