<?php
session_start();
include __DIR__ . "/../config.php";

// ===============================
// ตรวจสอบ Login + Role
// ===============================
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ไม่พบงานซ่อม");
}

$repair_id = intval($_GET['id']);
$user_id   = $_SESSION['user_id'];
$username  = $_SESSION['username'] ?? 'ช่างเทคนิค';

// ===============================
// บันทึกการซ่อม
// ===============================
$msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $repair_note = $_POST['repair_note'] ?? '';
    $status      = $_POST['status'] ?? 'กำลังซ่อม';

    // ถ้าสถานะเป็น "สำเร็จ" ให้ลงเวลาซ่อมเสร็จอัตโนมัติ
    if ($status === 'สำเร็จ') {
        $sql = "UPDATE repair_history
                SET technician_id = ?,
                    username = ?,
                    repair_note = ?,
                    status = ?,
                    repair_time = NOW()
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด SQL: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $stmt->bind_param(
                "isssi",
                $user_id,
                $username,
                $repair_note,
                $status,
                $repair_id
            );
            if ($stmt->execute()) {
                header("Location: work_orders.php?status=success");
                exit();
            } else {
                $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</div>';
            }
        }
    } else {
        $sql = "UPDATE repair_history
                SET technician_id = ?,
                    username = ?,
                    repair_note = ?,
                    status = ?
                WHERE id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด SQL: ' . htmlspecialchars($conn->error) . '</div>';
        } else {
            $stmt->bind_param(
                "isssi",
                $user_id,
                $username,
                $repair_note,
                $status,
                $repair_id
            );
            if ($stmt->execute()) {
                header("Location: work_orders.php?status=success");
                exit();
            } else {
                $msg = '<div class="alert alert-danger">เกิดข้อผิดพลาด: ' . htmlspecialchars($stmt->error) . '</div>';
            }
        }
    }
}

// ===============================
// ดึงข้อมูลงานซ่อม
// ===============================
$sql = "SELECT r.*, m.location FROM repair_history r LEFT JOIN machines m ON r.machine_id = m.machine_id WHERE r.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("ไม่พบข้อมูลงานซ่อม");
}

$row = $result->fetch_assoc();

// --- เตรียมข้อมูลสำหรับ Sidebar ---
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดงานซ่อม #<?= $repair_id ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/Technician/assets/css/sidebar_technician.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            <?php include __DIR__ . '/SidebarTechnician.php'; ?>
        </div>

        <div class="content-container">
            <div class="container-fluid p-0">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="header-title m-0"><i class="fas fa-tools"></i> รายละเอียดงานซ่อม ID: <?= $row['id'] ?></h3>
                </div>

                <?= $msg ?>

                <form method="POST">
                    <div class="row">
                        <div class="col-lg-5 mb-4">
                            <div class="card card-machine h-100">
                                <div class="machine-header">
                                    <h2 class="m-0"><i class="fas fa-cogs"></i> <?= htmlspecialchars($row['machine_id']) ?></h2>
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
                                            <td><i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($row['location'] ?? '-') ?></td>
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
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['type']) ?></span></td>
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
                                                <span class="badge bg-<?= $statusColor ?>"><?= htmlspecialchars($row['status']) ?></span>
                                            </td>
                                        </tr>
                                    </table>

                                    <hr>

                                    <h5 class="text-muted mb-3"><i class="fas fa-file-alt"></i> รายละเอียดแจ้งซ่อม</h5>
                                    <div class="p-3 bg-light rounded border mb-3">
                                        <?= nl2br(htmlspecialchars($row['detail'])) ?>
                                    </div>

                                    <?php if (!empty($row['repair_time'])): ?>
                                        <h5 class="text-muted mb-3"><i class="fas fa-calendar-check"></i> วันที่ซ่อมเสร็จ</h5>
                                        <div class="p-3 bg-success bg-opacity-10 rounded border border-success text-success">
                                            <strong><?= date('d/m/Y H:i', strtotime($row['repair_time'])) ?></strong>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7 mb-4">
                            <div class="card card-custom h-100">
                                <div class="card-header bg-success text-white">
                                    <strong><i class="fas fa-edit"></i> บันทึกการซ่อมและจัดสถานะ</strong>
                                </div>
                                <div class="card-body">

                                    <div class="mb-3">
                                        <label for="repair_note" class="form-label">รายละเอียดการซ่อม:</label>
                                        <textarea class="form-control" name="repair_note" id="repair_note" rows="5" placeholder="บันทึกงานซ่อมและรายละเอียดเพิ่มเติม..."><?= htmlspecialchars($row['repair_note'] ?? '') ?></textarea>
                                    </div>

                                    <hr>

                                    <div class="mb-3">
                                        <label for="status" class="form-label">อัปเดตสถานะงาน:</label>
                                        <select class="form-select" name="status" id="status" required>
                                            <option value="กำลังซ่อม" <?= $row['status'] == 'กำลังซ่อม' ? 'selected' : '' ?>>กำลังซ่อม</option>
                                            <option value="รออะไหล่" <?= $row['status'] == 'รออะไหล่' ? 'selected' : '' ?>>รออะไหล่</option>
                                            <option value="สำเร็จ" <?= $row['status'] == 'สำเร็จ' ? 'selected' : '' ?>>ซ่อมสำเร็จ (ปิดงาน)</option>
                                            <option value="ซ่อมไม่สำเร็จ" <?= $row['status'] == 'ซ่อมไม่สำเร็จ' ? 'selected' : '' ?>>ซ่อมไม่สำเร็จ</option>
                                        </select>
                                    </div>

                                    <hr>

                                    <div class="d-flex justify-content-end gap-2">
                                        <a href="work_orders.php" class="btn btn-light border">กลับ</a>

                                        <?php if ($row['status'] !== 'สำเร็จ' && $row['status'] !== 'ยกเลิก'): ?>
                                            <button type="submit" class="btn btn-success">
                                                <i class="fas fa-save"></i> บันทึกการซ่อม
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary" disabled>
                                                <i class="fas fa-lock"></i> งานนี้ถูก<?= $row['status'] ?>แล้ว (แก้ไขไม่ได้)
                                            </button>
                                        <?php endif; ?>
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
    <script src="/Technician/assets/js/SidebarOperator.js"></script>

</body>

</html>