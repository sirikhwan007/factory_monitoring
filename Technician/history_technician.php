<?php
// ... (ส่วน PHP ด้านบนเหมือนเดิม ไม่ต้องแก้) ...
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../config.php";

$result = $conn->query("SELECT * FROM repair_history ORDER BY report_time DESC");

$tech_sql = "SELECT user_id, username FROM users WHERE role = 'Technician'";
$tech_result = $conn->query($tech_sql);
$technicians = [];
if ($tech_result->num_rows > 0) {
    while ($tech = $tech_result->fetch_assoc()) {
        $technicians[] = $tech;
    }
}
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role = $_SESSION['role'] ?? 'ไม่ทราบสิทธิ์';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการแจ้งซ่อม</title>
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

        .repair-history-container {
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px;
            transition: all .3s ease;
        }

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

        .table-wrapper {
            background: #ffffff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            overflow-x: auto;
        }

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

        .detail-cell {
            max-width: 250px;
            min-width: 150px;
            word-wrap: break-word;
            white-space: normal;
            line-height: 1.4;
            font-size: 13px;
            color: #666;
        }

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

        @media (max-width: 992px) {

            .sidebar-wrapper {
                position: fixed;
                top: 0;
                left: -260px;
                width: 250px;
                height: 100vh;
                z-index: 2000;
                background-color: #fff;
                box-shadow: 2px 0 10px rgba(0, 0, 0, 0.2);
                transition: all 0.3s ease-in-out;
            }

            .sidebar-wrapper.active {
                left: 0;
            }

            .sidebar-wrapper .sidebar {
                transform: translateX(0) !important;
                position: relative !important;
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                padding-top: 60px;
            }

            .repair-history-container {
                margin-left: 0;
                width: 100%;
                padding: 60px;
            }

            .btn-hamburger {
                display: flex;
                position: fixed;
                top: 15px;
                left: 15px;
                width: 35px;
                height: 35px;
                align-items: center;
                justify-content: center;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
                z-index: 3000;
                font-size: 20px;
                cursor: pointer;
            }

            .sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1900;
            }

            .sidebar-overlay.active {
                display: block;
            }
        }
    </style>
</head>

<body>

    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active'); document.querySelector('.sidebar-overlay').classList.toggle('active');">
        <i class="fa-solid fa-bars"></i>
    </div>
    <div class="sidebar-overlay" onclick="document.querySelector('.sidebar-wrapper').classList.remove('active'); this.classList.remove('active')"></div>
    <div class="sidebar-wrapper">
        <?php include __DIR__ . "/SidebarTechnician.php"; ?>
    </div>

    <section class="main">

        <div class="repair-history-container">
            <h2 class="page-title"><i class="fas fa-history"></i> ประวัติการแจ้งซ่อม</h2>

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
                            <th class="text-center"><i class="fas fa-search"></i> ตรวจสอบเครื่องจักร</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()):
                                $status_class = '';
                                $status_icon = '';
                                switch ($row['status']) {
                                    case 'สำเร็จ':
                                        $status_class = 'success';
                                        $status_icon = '<i class="fas fa-check-circle"></i>';
                                        break;
                                    case 'รอดำเนินการ':
                                        $status_class = 'pending';
                                        $status_icon = '<i class="fas fa-hourglass-half"></i>';
                                        break;
                                    case 'กำลังซ่อม':
                                        $status_class = 'in-progress';
                                        $status_icon = '<i class="fas fa-tools"></i>';
                                        break;
                                    case 'ซ่อมไม่สำเร็จ':
                                        $status_class = 'failed';
                                        $status_icon = '<i class="fas fa-exclamation-triangle"></i>';
                                        break;
                                }
                                $report_datetime = new DateTime($row['report_time']);
                                $report_date = $report_datetime->format('d/m/Y');
                                $report_time = $report_datetime->format('H:i');

                                $repair_complete_date = '-';
                                $repair_complete_time = '';
                                if (!empty($row['repair_time']) && $row['repair_time'] !== '0000-00-00 00:00:00') {
                                    $repair_datetime = new DateTime($row['repair_time']);
                                    $repair_complete_date = $repair_datetime->format('d/m/Y');
                                    $repair_complete_time = $repair_datetime->format('H:i');
                                }
                        ?>
                                <tr>
                                    <td class="text-center"><strong><?= $no++ ?></strong></td>
                                    <td><?= htmlspecialchars($row['machine_id']) ?></td>
                                    <td><?= htmlspecialchars($row['reporter']) ?></td>
                                    <td><?= htmlspecialchars($row['position']) ?></td>
                                    <td><span class="type-tag"><?= htmlspecialchars($row['type']) ?></span></td>
                                    <td class="detail-cell"><?= htmlspecialchars($row['detail'] ?? '-') ?></td>

                                    <td class="text-center">
                                        <div class="datetime-display">
                                            <span class="datetime-date"><?= $report_date ?></span>
                                            <span class="datetime-time"><?= $report_time ?> น.</span>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <div class="datetime-display">
                                            <span class="datetime-date"><?= $repair_complete_date ?></span>
                                            <?php if (!empty($repair_complete_time)): ?>
                                                <span class="datetime-time"><?= $repair_complete_time ?> น.</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_icon ?> <?= $row['status'] ?>
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="action-cell">
                                            <a href="edit_repairtech.php?id=<?= $row['id'] ?>"
                                                class="btn btn-outline-primary btn-sm btn-action">
                                                <i class="fas fa-magnifying-glass"></i>ตรวจสอบ
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                        } else { ?>
                            <tr>
                                <td colspan="10" class="text-center py-4">ไม่มีข้อมูล</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="SidebarAdmin.js"></script>

    <script>
        $(document).ready(function() {
            $('.btn-assign').click(function() {
                var repairId = $(this).data('id');
                $('#modal_repair_id').val(repairId);
            });
        });
    </script>

</body>

</html>