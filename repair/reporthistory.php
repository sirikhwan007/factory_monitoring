<?php
// ตรวจสอบและเริ่ม session ก่อนที่จะมี output ใดๆ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . "/../config.php"; // ขึ้นไป 1 ระดับโฟลเดอร์

// ดึงข้อมูลประวัติการแจ้งซ่อม
$result = $conn->query("SELECT * FROM repair_history ORDER BY report_time DESC");

// สำหรับ Sidebar (ถ้าจำเป็นต้องใช้ตัวแปรเหล่านี้ใน Sidebar)
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username     = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role         = $_SESSION['role'] ?? 'ไม่ทราบสิทธิ์';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการแจ้งซ่อม</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="/factory_monitoring/repair/css/reporthistory.css">
</head>

<body>

    <div class="d-flex w-100">

        <div class="sidebar-wrapper">
            <?php include __DIR__ . '/../admin/SidebarAdmin.php'; ?>
        </div>

        <div class="dashboard">
        <div class="flex-grow-1">

            <div class="btn-hamburger d-block d-lg-none p-3"><i class="fa-solid fa-bars"></i></div>

            <section class="main">
                <div class="repair-history-container">
                    <h2 class="page-title"><i class="fas fa-history"></i> ประวัติการแจ้งซ่อม</h2>

                    <div class="table-wrapper">
                        <table class="repair-table">
                            <thead>
                                <tr>
                                    <th class="text-center">#</th>
                                    <th><i class="fas fa-microchip"></i> ID เครื่องจักร</th>
                                    <th><i class="fas fa-user-tag"></i> ชื่อผู้แจ้ง</th>
                                    <th><i class="fas fa-briefcase"></i> ตำแหน่ง</th>
                                    <th><i class="fas fa-tag"></i> ประเภท</th>
                                    <th><i class="fas fa-file-alt"></i> รายละเอียด</th>
                                    <th class="text-center"><i class="far fa-calendar-alt"></i> วันที่แจ้ง</th>
                                    <th class="text-center"><i class="far fa-calendar-check"></i> วันที่ซ่อมเสร็จ</th>
                                    <th class="text-center"><i class="fas fa-info-circle"></i> สถานะ / การจัดการ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $no = 1;
                                if ($result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()):
                                        // Status Logic
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

                                        // DateTime Logic
                                        $report_datetime = new DateTime($row['report_time']);
                                        $report_date = $report_datetime->format('d/m/Y');
                                        $report_time = $report_datetime->format('H:i');

                                        $repair_date = '-';
                                        $repair_time = '';
                                        if (!empty($row['repair_time'])) {
                                            $repair_datetime = new DateTime($row['repair_time']);
                                            $repair_date = $repair_datetime->format('d/m/Y');
                                            $repair_time = $repair_datetime->format('H:i');
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
                                                <?php if ($repair_date !== '-'): ?>
                                                    <div class="datetime-display">
                                                        <span class="datetime-date"><?= $repair_date ?></span>
                                                        <span class="datetime-time"><?= $repair_time ?> น.</span>
                                                    </div>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="action-cell">
                                                    <span class="status-badge <?= $status_class ?>">
                                                        <?= $status_icon ?> <?= $row['status'] ?>
                                                    </span>
                                                    <form action="update_status.php" method="POST" style="display:inline;">
                                                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                        <?php if ($row['status'] === 'รอดำเนินการ'): ?>
                                                            <button type="submit" name="status" value="กำลังซ่อม" class="btn btn-primary btn-action">
                                                                <i class="fas fa-play"></i> รับงาน
                                                            </button>
                                                        <?php elseif ($row['status'] === 'กำลังซ่อม'): ?>
                                                            <button type="submit" name="status" value="สำเร็จ" class="btn btn-success btn-action">
                                                                <i class="fas fa-check-double"></i> เสร็จสิ้น
                                                            </button>
                                                        <?php endif; ?>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile;
                                } else { ?>
                                    <tr>
                                        <td colspan="9" class="text-center py-4">
                                            <i class="fas fa-box-open fa-2x text-muted mb-2"></i><br>
                                            ไม่มีประวัติการแจ้งซ่อมในขณะนี้
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

</body>

</html>