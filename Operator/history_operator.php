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
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" />
    <style>
        /* --- General & Typography --- */
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafd;
            color: #333;
            line-height: 1.6;
            margin: 0;
            overflow-x: hidden;
            /* ป้องกันสกรอลแนวนอนทั้งหน้า */
        }

        /* --- [แก้ไขส่วน Layout หลัก] --- */
        .main {
            display: flex;
            /* จัดเรียงซ้าย-ขวา */
            width: 100%;
            min-height: 100vh;
            /* ความสูงเต็มจอ */
            padding: 0 !important;
            /* ลบ padding เดิมออกเพื่อไม่ให้เบี้ยว */
        }

        /* --- [เพิ่ม Class สำหรับ Sidebar Wrapper] --- */
        .sidebar-wrapper {
            width: 250px;
            /* ความกว้างคงที่ของ Sidebar */
            min-width: 250px;
            /* ห้ามหดเล็กกว่านี้ */
            flex-shrink: 0;
            /* สำคัญ! ห้ามบีบ Sidebar เมื่อหน้าจอเล็ก */
            background: #fff;
            /* หรือสีเดียวกับ Sidebar ของคุณ */
            height: 100vh;
            /* สูงเต็มจอ */
            position: sticky;
            /* ให้ติดอยู่กับที่ */
            top: 0;
            overflow-y: auto;
            /* ถ้า Sidebar ยาวให้เลื่อนได้ */
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
            z-index: 1000;
            transition: all 0.3s;
        }

        /* --- [แก้ไขส่วน Content ด้านขวา] --- */
        .repair-history-container {
            flex-grow: 1;
            /* ให้กินพื้นที่ที่เหลือทั้งหมด */
            padding: 30px;
            width: calc(100% - 250px);
            /* ความกว้าง = 100% - ความกว้าง Sidebar */
            overflow-x: auto;
            /* ให้ตารางเลื่อนแนวนอนได้ในกรอบนี้ */
        }

        .page-title {
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 35px;
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 15px;
        }

        .page-title i {
            color: #3498db;
            font-size: 2.5rem;
        }

        /* --- Table Container --- */
        .table-wrapper {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
            padding: 30px;
            overflow-x: auto;
        }

        /* --- Table Styles (คงเดิม) --- */
        .repair-table {
            width: 100%;
            min-width: 1200px;
            /* ลดลงหน่อยเพื่อให้พอดีกับจอ Laptop ทั่วไป */
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .repair-table thead th {
            background-color: #e9f0f7;
            color: #4a6c8e;
            padding: 18px 20px;
            font-size: 14px;
            font-weight: 600;
            text-align: left;
            border: none;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            white-space: nowrap;
        }

        .repair-table thead th:first-child {
            border-top-left-radius: 10px;
            border-bottom-left-radius: 10px;
        }

        .repair-table thead th:last-child {
            border-top-right-radius: 10px;
            border-bottom-right-radius: 10px;
        }

        .repair-table tbody td {
            background-color: #fff;
            padding: 15px 20px;
            font-size: 14px;
            color: #555;
            vertical-align: middle;
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .repair-table tbody tr {
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
        }

        .repair-table tbody tr:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            z-index: 2;
        }

        .repair-table tbody tr td:first-child {
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .repair-table tbody tr td:last-child {
            border-top-right-radius: 8px;
            border-bottom-right-radius: 8px;
        }

        .repair-table th.text-center,
        .repair-table td.text-center {
            text-align: center;
        }

        .detail-cell {
            max-width: 250px;
            min-width: 150px;
            white-space: normal;
            word-wrap: break-word;
            line-height: 1.4;
            font-size: 0.9em;
            color: #666;
        }

        /* --- Badges & Buttons (คงเดิม) --- */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 7px 14px;
            border-radius: 25px;
            font-size: 13px;
            font-weight: 600;
            text-transform: capitalize;
            gap: 6px;
            white-space: nowrap;
        }

        .status-badge i {
            font-size: 12px;
        }

        .status-badge.success {
            background-color: #e6ffed;
            color: #28a745;
            border: 1px solid #28a745;
        }

        .status-badge.pending {
            background-color: #fff8e1;
            color: #ffc107;
            border: 1px solid #ffc107;
        }

        .status-badge.in-progress {
            background-color: #e0f2f7;
            color: #17a2b8;
            border: 1px solid #17a2b8;
        }

        .status-badge.failed {
            background-color: #ffe6e6;
            color: #dc3545;
            border: 1px solid #dc3545;
        }

        .type-tag {
            background-color: #f0f4f7;
            color: #6c757d;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
            white-space: nowrap;
        }

        .action-cell {
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }

        .btn-action {
            padding: 8px 15px;
            font-size: 13px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            white-space: nowrap;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .btn-action.btn-primary {
            background-color: #007bff;
            color: #fff;
        }

        .btn-action.btn-success {
            background-color: #28a745;
            color: #fff;
        }

        .datetime-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 14px;
            white-space: nowrap;
        }

        .datetime-date {
            font-weight: 500;
            color: #495057;
        }

        .datetime-time {
            font-size: 12px;
            color: #888;
        }

        .dashboard {
            flex: 1;
            overflow: auto;
            background: #f4f9fd;
            border-radius: 20px;
            padding: 30px;
            margin: 0px;
            margin-left: 250px;
        }

        /* --- Responsive Adjustments --- */
        /* สำหรับหน้าจอ Mobile (เล็กกว่า 992px) */
        @media (max-width: 992px) {
            .sidebar-wrapper {
                position: absolute;
                /* เปลี่ยนเป็นลอยเหนือเนื้อหา */
                left: -250px;
                /* ซ่อนไปทางซ้าย */
            }

            .sidebar-wrapper.active {
                left: 0;
                /* แสดงเมื่อมี class active (ต้องใช้ JS เปิดปิด) */
            }

            .repair-history-container {
                width: 100%;
                /* เนื้อหาเต็มจอ */
                padding: 15px;
            }

            .btn-hamburger {
                display: block;
                /* แสดงปุ่มเมนูบนมือถือ */
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1001;
                background: #fff;
                padding: 10px;
                border-radius: 5px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                cursor: pointer;
            }
        }

        /* ซ่อนปุ่ม hamburger บนจอใหญ่ */
        @media (min-width: 993px) {
            .btn-hamburger {
                display: none;
            }
        }
    </style>
</head>

<body>

    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active')">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main">

        <div class="sidebar-wrapper">

            <?php $activePage = 'history';
            include __DIR__ . '/SidebarOperator.php';
            ?>

        </div>

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
                                // ... (Logic สถานะ และ วันที่ เหมือนเดิม ไม่ต้องแก้) ...
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

                                // เตรียมข้อมูลวันที่ซ่อมเสร็จ
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
                                            <a href="edit_repairoperator.php?id=<?= $row['id'] ?>"
                                                class="btn btn-outline-primary btn-sm btn-action">
                                                <i class="fas fa-edit"></i>แก้ไข
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