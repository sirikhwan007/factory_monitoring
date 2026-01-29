<?php
session_start();
include __DIR__ . "/../config.php";

/* ===============================
   1. ตรวจสอบ Login + Role
================================ */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Technician') {
    header("Location: ../login.php");
    exit();
}

$current_user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'ช่างเทคนิค';
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';

/* ===============================
   2. ดึงงานซ่อม
================================ */
$sql = "SELECT * FROM repair_history
        WHERE technician_id IS NULL OR technician_id = ?
        ORDER BY 
            CASE WHEN status = 'กำลังซ่อม' THEN 0 ELSE 1 END,
            report_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>งานซ่อมที่มี</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/Technician/assets/css/sidebar_technician.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafd;
            color: #333;
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
        }

        /* ================= Sidebar ================= */
        .sidebar {
            width: 250px;
            min-width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            background: #ffffff;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all .3s ease;
            overflow-y: auto;
        }

        /* ================= Content ================= */
        .repair-history-container {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px;
            transition: all .3s ease;
        }

        /* ================= Title ================= */
        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .page-title i {
            color: #3498db;
            font-size: 2.5rem;
        }

        /* ================= Table Wrapper ================= */
        .table-wrapper {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

        /* ================= Table ================= */
        .repair-table {
            width: 100%;
            min-width: 1200px;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .repair-table thead th {
            background-color: #e9f0f7;
            color: #4a6c8e;
            padding: 18px 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .repair-table thead th:first-child {
            border-radius: 10px 0 0 10px;
        }

        .repair-table thead th:last-child {
            border-radius: 0 10px 10px 0;
        }

        /* ================= Table Body ================= */
        .repair-table tbody td {
            background: #fff;
            padding: 15px 20px;
            font-size: 14px;
            color: #555;
            vertical-align: middle;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .05);
            transition: all .2s ease;
        }

        .repair-table tbody tr {
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .repair-table tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, .1);
            z-index: 2;
        }

        .repair-table tbody tr td:first-child {
            border-radius: 8px 0 0 8px;
        }

        .repair-table tbody tr td:last-child {
            border-radius: 0 8px 8px 0;
        }

        /* ================= Detail ================= */
        .detail-cell {
            max-width: 250px;
            min-width: 150px;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
            font-size: 13px;
            color: #666;
        }

        /* ================= Status Badge ================= */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .status-badge.success {
            background: #e6ffed;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-badge.in-progress {
            background: #e0f2f7;
            color: #17a2b8;
            border: 1px solid #17a2b8;
        }

        .status-badge.pending {
            background: #fff8e1;
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .status-badge.failed {
            background: #ffe6e6;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        /* ================= Action Button ================= */
        .action-cell {
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .btn-action {
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .05);
            transition: all .2s ease;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, .1);
        }

        /* ================= Mobile ================= */
        @media (max-width: 992px) {
            .sidebar {
                left: -250px;
            }

            .sidebar.active {
                left: 0;
            }

            .repair-history-container {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .btn-hamburger {
                display: block;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1100;
                background: #fff;
                padding: 10px;
                border-radius: 6px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, .1);
                cursor: pointer;
            }
        }

        @media (min-width: 993px) {
            .btn-hamburger {
                display: none;
            }
        }
    </style>

</head>

<body>

    <button class="btn btn-light btn-hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <?php include __DIR__ . '/SidebarTechnician.php'; ?>

    <!-- Content -->
    <div class="repair-history-container">
        <h2 class="page-title">
            <i class="fas fa-tools text-primary"></i> งานซ่อมที่มี
        </h2>

        <div class="table-wrapper">
            <table class="repair-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> </th>
                        <th><i class="fas fa-microchip"></i> ID เครื่องจักร</th>
                        <th><i class="fas fa-user"></i> ชื่อผู้แจ้ง</th>
                        <th><i class="fas fa-location-dot"></i> ตำแหน่ง</th>
                        <th><i class="fas fa-tags"></i> ประเภท</th>
                        <th><i class="fas fa-file-lines"></i> รายละเอียด</th>
                        <th class="text-center"><i class="fas fa-calendar-plus"></i> วันที่แจ้ง</th>
                        <th class="text-center"><i class="fas fa-calendar-check"></i> วันที่ซ่อมเสร็จ</th>
                        <th class="text-center"><i class="fas fa-circle-info"></i> สถานะ</th>
                        <th class="text-center"><i class="fas fa-check-circle"></i> รับงาน</th>
                    </tr>
                </thead>

                <tbody>
                    <?php
                    $no = 1;
                    while ($row = $result->fetch_assoc()):
                        $statusClass = match ($row['status']) {
                            'สำเร็จ' => 'success',
                            'กำลังซ่อม' => 'in-progress',
                            'ซ่อมไม่สำเร็จ' => 'failed',
                            default => 'pending'
                        };
                        $isAssigned = ($row['technician_id'] == $current_user_id);
                    ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['machine_id']) ?></td>
                            <td><?= htmlspecialchars($row['reporter']) ?></td>
                            <td><?= htmlspecialchars($row['position'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['type']) ?></td>
                            <td class="detail-cell"><?= htmlspecialchars($row['detail']) ?></td>
                            <td class="text-center"><?= date('d/m/Y H:i', strtotime($row['report_time'])) ?></td>
                            <td class="text-center">
                                <?= $row['repair_time'] && $row['repair_time'] != '0000-00-00 00:00:00'
                                    ? date('d/m/Y H:i', strtotime($row['repair_time'])) : '-' ?>
                            </td>
                            <td class="text-center">
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $row['status'] ?>
                                </span>
                            </td>
                            <td class="text-center">
                                <?php if (!$isAssigned): ?>
                                    <form method="post" action="actions/accept_job.php">
                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                        <button class="btn btn-success btn-sm" onclick="return confirm('ยืนยันรับงาน?')">
                                            ✔ รับงาน
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-success">รับแล้ว</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>