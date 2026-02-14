<?php
session_start();
include __DIR__ . "/../config.php";

/* ===============================
   1. ตรวจสอบ ID
================================ */
if (!isset($_GET['id'])) {
    die("Error: ไม่พบ ID รายการแจ้งซ่อม");
}
$repair_id = (int) $_GET['id'];

/* ===============================
   2. ดึงข้อมูลใบแจ้งซ่อม + ชื่อช่าง
================================ */
$stmt = $conn->prepare("
    SELECT r.*, m.location, u.username
    FROM repair_history r
    LEFT JOIN machines m ON r.machine_id = m.machine_id
    LEFT JOIN users u ON r.technician_id = u.user_id
    WHERE r.id = ?
");
$stmt->bind_param("i", $repair_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    die("ไม่พบข้อมูลใบแจ้งซ่อม");
}

/* ===============================
   3. เตรียมข้อมูล Sidebar
================================ */
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'ช่างเทคนิค';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายละเอียดใบแจ้งซ่อม #<?= $row['id'] ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Sidebar Technician -->
    <link rel="stylesheet" href="/assets/css/sidebar_technician.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            background: #f4f6f9;
            font-family: 'Kanit', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        .main {
            display: flex;
            min-height: 100vh;
        }

        .sidebar-wrapper {
            width: 240px;
            min-width: 240px;
            flex-shrink: 0;
        }

        .content-container {
            flex: 1;
            padding: 30px;
        }

        .card-machine {
            border: none;
            border-radius: 16px;
            box-shadow: 0 6px 20px rgba(0,0,0,.08);
            overflow: hidden;
            background: #fff;
        }

        .machine-header {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: #fff;
            padding: 20px 24px;
        }

        .machine-header h2 {
            margin: 0;
            font-size: 1.6rem;
            font-weight: 700;
        }

        .card-body {
            padding: 24px;
        }

        .table th {
            width: 40%;
            font-weight: 600;
            color: #555;
        }

        .btn-hamburger {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 9999;
            background: #fff;
            padding: 10px 12px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,.2);
            cursor: pointer;
        }

        @media (max-width: 992px) {
            .sidebar-wrapper {
                position: fixed;
                left: -240px;
                top: 0;
                height: 100%;
                transition: .3s;
                z-index: 1000;
            }

            .sidebar-wrapper.active {
                left: 0;
            }

            .content-container {
                padding: 16px;
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
    <i class="fas fa-bars"></i>
</div>

<section class="main">

    <!-- Sidebar -->
    <div class="sidebar-wrapper">
        <?php include __DIR__ . '/SidebarTechnician.php'; ?>
    </div>

    <!-- Content -->
    <div class="content-container">
        <div class="container-fluid p-0">

            <h3 class="fw-bold mb-4">
                <i class="fas fa-file-alt text-primary"></i>
                รายละเอียดใบแจ้งซ่อม #<?= $row['id'] ?>
            </h3>

            <div class="row justify-content-center">
                <div class="col-xl-10 col-12">

                    <div class="card card-machine">
                        <div class="machine-header">
                            <h2>
                                <i class="fas fa-cogs"></i>
                                Machine ID: <?= htmlspecialchars($row['machine_id']) ?>
                            </h2>
                            <small>ข้อมูลเครื่องจักรและการแจ้งซ่อม</small>
                        </div>

                        <div class="card-body">

                            <h5 class="text-primary mb-3">ข้อมูลทั่วไป</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <th>Machine ID</th>
                                    <td><?= htmlspecialchars($row['machine_id']) ?></td>
                                </tr>
                                <tr>
                                    <th>ที่ตั้ง</th>
                                    <td><i class="fas fa-map-marker-alt text-danger"></i>
                                        <?= htmlspecialchars($row['location'] ?? '-') ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>ผู้แจ้ง</th>
                                    <td><?= htmlspecialchars($row['reporter']) ?></td>
                                </tr>
                                <tr>
                                    <th>ตำแหน่ง</th>
                                    <td><?= htmlspecialchars($row['position'] ?? '-') ?></td>
                                </tr>
                                <tr>
                                    <th>ประเภท</th>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($row['type']) ?></span></td>
                                </tr>
                                <tr>
                                    <th>เวลาที่แจ้ง</th>
                                    <td><?= date('d/m/Y H:i', strtotime($row['report_time'])) ?></td>
                                </tr>
                                <tr>
                                    <th>สถานะ</th>
                                    <td>
                                        <?php
                                        $statusColor =
                                            ($row['status'] === 'สำเร็จ') ? 'success' :
                                            (($row['status'] === 'กำลังซ่อม') ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $statusColor ?>">
                                            <?= htmlspecialchars($row['status']) ?>
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <hr>

                            <h5 class="text-muted mb-2">
                                <i class="fas fa-file-alt"></i> รายละเอียดแจ้งซ่อม
                            </h5>
                            <div class="p-3 bg-light rounded border mb-3">
                                <?= nl2br(htmlspecialchars($row['detail'])) ?>
                            </div>

                            <h5 class="text-muted mb-2 mt-3">
                                <i class="fas fa-user-cog"></i> ช่างผู้รับผิดชอบ
                            </h5>
                            <div class="p-3 bg-light rounded border mb-3">
                                <?= !empty($row['username'])
                                    ? htmlspecialchars($row['username'])
                                    : '<span class="text-muted fst-italic">ยังไม่ได้มอบหมายช่าง</span>'; ?>
                            </div>

                            <?php if (!empty($row['repair_time'])): ?>
                                <h5 class="text-muted mb-2 mt-4">
                                    <i class="fas fa-calendar-check"></i> วันที่ซ่อมเสร็จ
                                </h5>
                                <div class="p-3 bg-success bg-opacity-10 rounded border border-success text-success">
                                    <strong><?= date('d/m/Y H:i', strtotime($row['repair_time'])) ?></strong>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($row['repair_note'])): ?>
                                <h5 class="text-muted mb-2 mt-4">
                                    <i class="fas fa-wrench"></i> รายละเอียดการซ่อม
                                </h5>
                                <div class="p-3 bg-info bg-opacity-10 rounded border border-info">
                                    <?= nl2br(htmlspecialchars($row['repair_note'])) ?>
                                </div>
                            <?php endif; ?>

                            <!-- ปุ่มกลับ -->
                            <hr>
                            <div class="mt-4">
                                <a href="javascript:history.back()" class="btn btn-secondary px-4">
                                    <i class="fas fa-arrow-left me-2"></i> กลับ
                                </a>
                            </div>

                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>

</section>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
