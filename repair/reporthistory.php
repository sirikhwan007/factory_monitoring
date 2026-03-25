<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include __DIR__ . "/../config.php";

$user_role = $_SESSION['role'] ?? 'Operator';

$result = $conn->query("SELECT * FROM repair_history ORDER BY report_time DESC");

$tech_sql = "SELECT user_id, username FROM users WHERE role = 'Technician'";
$tech_result = $conn->query($tech_sql);
$technicians = [];
if ($tech_result->num_rows > 0) {
    while ($tech = $tech_result->fetch_assoc()) {
        $technicians[] = $tech;
    }
}

$status_filter = $_GET['status'] ?? 'all';

if ($status_filter !== 'all') {
    $stmt = $conn->prepare("SELECT * FROM repair_history WHERE status = ? ORDER BY report_time DESC");
    $stmt->bind_param("s", $status_filter);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query("SELECT * FROM repair_history ORDER BY report_time DESC");
}

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

$sidebar_css_paths = [
    'Admin'      => '/factory_monitoring/admin/assets/css/index.css',
    'Manager'    => '/factory_monitoring/Manager/assets/css/Sidebar.css',
    'Operator'   => '/factory_monitoring/Operator/assets/css/SidebarOperator.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];


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
    <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
    <link rel="stylesheet" href="/factory_monitoring/repair/css/reporthistory.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .btn-hamburger {
            display: none;
        }

        @media (max-width: 992px) {
            .main {
                flex-direction: column;
                margin-left: 0;
                padding: 15px;
                border-radius: 0;
                padding-top: 10px;
            }

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

            .repair-history-container {
                width: 100%;
                padding: 60px 15px 15px;
            }

            .sidebar-wrapper .sidebar {
                transform: translateX(0) !important;
                position: relative !important;
                width: 100% !important;
                max-width: 100% !important;
                display: flex !important;
                padding-top: 60px;
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

    <section class="main">
        <div class="sidebar-wrapper">
            <?php include $sidebar_file; ?>
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
                                    case 'ยกเลิก': // เพิ่มเคสนี้
                                        $status_class = 'cancelled';
                                        $status_icon = '<i class="fas fa-times-circle"></i>';
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
                                            <?php if ($row['status'] === 'สำเร็จ'): ?>
                                                <span class="badge bg-secondary">ปิดงานแล้ว</span>
                                            <?php elseif ($row['status'] === 'ยกเลิก'): ?>
                                                <span class="badge bg-danger">ยกเลิกงานแล้ว</span>
                                            <?php else: ?>
                                                <a href="edit_repair.php?id=<?= $row['id'] ?>" class="btn btn-outline-primary btn-sm btn-action">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </a>
                                            <?php endif; ?>
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