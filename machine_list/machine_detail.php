<?php
session_start();
include __DIR__ . "/../config.php"; // ตรวจสอบ path config ให้ถูกต้อง


$user_role = $_SESSION['role'] ?? 'Operator';


// ตรวจสอบข้อมูล Session สำหรับ Sidebar
$profileImage = $_SESSION['profile_image'] ?? 'default_profile.png';
$username     = $_SESSION['username'] ?? 'ผู้ใช้งาน';
$role         = $_SESSION['role'] ?? 'ไม่ทราบสิทธิ์';

// ตรวจสอบว่ามีการส่ง ID มาหรือไม่
if (!isset($_GET['id'])) {
    die("Error: ไม่พบ ID เครื่องจักร");
}

$machine_id = $_GET['id'];

// ดึงข้อมูลเครื่องจักรจากตาราง machines
$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

if (!$machine) {
    die("Error: ไม่พบข้อมูลเครื่องจักร ID: " . htmlspecialchars($machine_id));
}

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

// ตรวจสอบรูปภาพ
$machine_img = !empty($machine['photo_url']) ? $machine['photo_url'] : 'https://placehold.co/600x400?text=No+Image';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดเครื่องจักร : <?= htmlspecialchars($machine['machine_id']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

    <style>
        /* --- General & Layout Styles --- */
        body {
            background-color: #f8fafd;
            font-family: 'Kanit', sans-serif;
            margin: 0;
            overflow-x: hidden;
        }

        /* จัด Layout เป็น Flexbox ซ้าย-ขวา */
        .main {
            display: flex;
            width: 100%;
            min-height: 100vh;
        }

        /* Sidebar Wrapper Settings */
        .sidebar-wrapper {
            width: 250px;
            min-width: 250px;
            flex-shrink: 0;
            background: #fff;
            border-right: 1px solid #eee;
            transition: all 0.3s;
            z-index: 1000;
        }

        /* Content Area Settings */
        .content-container {
            flex-grow: 1;
            padding: 30px;
            width: calc(100% - 250px);
            overflow-y: auto;
        }

        /* --- Machine Detail Specific Styles --- */
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

        .spec-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .spec-value {
            font-size: 1.4rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .spec-label {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .img-cover {
            width: 100%;
            height: 350px;
            object-fit: cover;
        }

        .btn-back {
            margin-bottom: 20px;
        }

        /* --- Responsive Styles --- */
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
                padding: 20px;
            }

            .btn-hamburger {
                display: block;
            }

            .img-cover {
                height: 250px;
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
            <?php include $sidebar_file; ?>
        </div>
        <div class="content-container">

            <div class="container-fluid p-0">
                <a href="javascript:history.back()" class="btn btn-secondary btn-back"><i class="fas fa-arrow-left"></i> ย้อนกลับ</a>

                <div class="card card-machine">
                    <div class="machine-header">
                        <h2 class="m-0"><i class="fas fa-cogs"></i> <?= htmlspecialchars($machine['name']) ?></h2>
                        <small>Model: <?= htmlspecialchars($machine['model']) ?></small>
                    </div>
                    <div class="card-body p-0">
                        <div class="row g-0">
                            <div class="col-md-5">
                                <?php
                                $imgSrc = !empty($machine['photo_url'])
                                    ? "/factory_monitoring/" . $machine['photo_url']
                                    : "/factory_monitoring/assets/default-machine.png";
                                ?>
                                <img src="<?php echo $imgSrc; ?>"
                                    alt="รูปเครื่องจักร"
                                    class="img-fluid rounded shadow-sm"
                                    style="max-height: 75%; object-fit: cover;">
                            </div>

                            <div class="col-md-7 p-4">
                                <h4 class="text-primary mb-3">ข้อมูลทั่วไป</h4>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="30%">Machine ID:</th>
                                        <td><?= htmlspecialchars($machine['machine_id']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>MAC Address:</th>
                                        <td><code><?= htmlspecialchars($machine['mac_address']) ?></code></td>
                                    </tr>
                                    <tr>
                                        <th>Location:</th>
                                        <td> <?= htmlspecialchars($machine['location']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <span id="detail-status-display" style="font-weight: bold; font-size: 1rem;">
                                                กำลังโหลดสถานะ...
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Installed Date:</th>
                                        <td><?= date('d/m/Y', strtotime($machine['installed_at'])) ?></td>
                                    </tr>
                                </table>

                                <hr>

                                <h5 class="text-muted mb-3"><i class="fas fa-tachometer-alt"></i> ข้อมูลทางเทคนิค</h5>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <div class="spec-box">
                                            <div class="spec-value"><?= htmlspecialchars($machine['hp']) ?></div>
                                            <div class="spec-label">HP (แรงม้า)</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="spec-box">
                                            <div class="spec-value"><?= htmlspecialchars($machine['rpm']) ?></div>
                                            <div class="spec-label">RPM (รอบ/นาที)</div>
                                        </div>
                                    </div>
                                    <div class="col-4">
                                        <div class="spec-box">
                                            <div class="spec-value"><?= htmlspecialchars($machine['amp']) ?></div>
                                            <div class="spec-label">AMP (แอมป์)</div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- ส่วนรายงานการแจ้งซ่อม -->
                <div class="mt-5">
                    <h3 class="text-primary mb-4"><i class="fas fa-history"></i> ประวัติการแจ้งซ่อมของเครื่องนี้</h3>

                    <?php
                    // ดึงข้อมูลประวัติการแจ้งซ่อมของเครื่องนี้
                    $repair_stmt = $conn->prepare("SELECT * FROM repair_history WHERE machine_id = ? ORDER BY report_time DESC");
                    $repair_stmt->bind_param("s", $machine_id);
                    $repair_stmt->execute();
                    $repair_result = $repair_stmt->get_result();
                    ?>

                    <?php if ($repair_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th><i class="fas fa-user"></i> ผู้แจ้ง</th>
                                        <th><i class="fas fa-tag"></i> ประเภท</th>
                                        <th><i class="fas fa-file-alt"></i> รายละเอียด</th>
                                        <th><i class="fas fa-calendar"></i> วันที่แจ้ง</th>
                                        <th><i class="fas fa-info-circle"></i> สถานะ</th>
                                        <th class="text-center"><i class="fas fa-eye"></i> ดูรายละเอียด</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($repair_row = $repair_result->fetch_assoc()):
                                        $status_badge = '';
                                        switch ($repair_row['status']) {
                                            case 'สำเร็จ':
                                                $status_badge = '<span class="badge bg-success"><i class="fas fa-check-circle"></i> สำเร็จ</span>';
                                                break;
                                            case 'รอดำเนินการ':
                                                $status_badge = '<span class="badge bg-warning"><i class="fas fa-hourglass-half"></i> รอดำเนินการ</span>';
                                                break;
                                            case 'กำลังซ่อม':
                                                $status_badge = '<span class="badge bg-info"><i class="fas fa-tools"></i> กำลังซ่อม</span>';
                                                break;
                                            case 'ซ่อมไม่สำเร็จ':
                                                $status_badge = '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> ซ่อมไม่สำเร็จ</span>';
                                                break;
                                            default:
                                                $status_badge = '<span class="badge bg-secondary">' . htmlspecialchars($repair_row['status']) . '</span>';
                                        }

                                        $report_date = date('d/m/Y H:i', strtotime($repair_row['report_time']));
                                    ?>
                                        <tr>
                                            <td><?= htmlspecialchars($repair_row['reporter']) ?></td>
                                            <td><span class="badge bg-light text-dark"><?= htmlspecialchars($repair_row['type']) ?></span></td>
                                            <td>
                                                <span title="<?= htmlspecialchars($repair_row['detail']) ?>">
                                                    <?= htmlspecialchars(strlen($repair_row['detail']) > 50 ? substr($repair_row['detail'], 0, 50) . '...' : $repair_row['detail']) ?>
                                                </span>
                                            </td>
                                            <td><?= $report_date ?></td>
                                            <td><?= $status_badge ?></td>
                                            <td class="text-center">
                                                <a href="/factory_monitoring/repair/edit_repair.php?id=<?= htmlspecialchars($repair_row['id']) ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i> แก้ไข
                                                </a>
                                                <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#repairDetailModal"
                                                    onclick="viewRepairDetail(<?= htmlspecialchars(json_encode($repair_row)) ?>)">
                                                    <i class="fas fa-eye"></i> ดู
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info" role="alert">
                            <i class="fas fa-info-circle"></i> ไม่มีประวัติการแจ้งซ่อมสำหรับเครื่องนี้
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal สำหรับดูรายละเอียดการแจ้งซ่อม -->
    <div class="modal fade" id="repairDetailModal" tabindex="-1" aria-labelledby="repairDetailLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="repairDetailLabel"><i class="fas fa-tools"></i> รายละเอียดการแจ้งซ่อม</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">ผู้แจ้ง:</h6>
                            <p id="modalReporter"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">ตำแหน่ง:</h6>
                            <p id="modalPosition"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">ประเภท:</h6>
                            <p id="modalType"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">วันที่แจ้ง:</h6>
                            <p id="modalReportTime"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-12">
                            <h6 class="text-muted">รายละเอียด:</h6>
                            <p id="modalDetail"></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6 class="text-muted">สถานะ:</h6>
                            <p id="modalStatus"></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted">วันที่อัปเดต:</h6>
                            <p id="modalUpdatedTime"></p>
                        </div>
                    </div>

                    <div class="row" id="commentSection" style="display:none;">
                        <div class="col-md-12">
                            <h6 class="text-muted">หมายเหตุ:</h6>
                            <p id="modalComment"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ปิด</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/factory_monitoring/admin/assets/js/SidebarAdmin.js"></script>
    <script src="/factory_monitoring/Manager/assets/js/SidebarManager.js"></script>
    <script src="/factory_monitoring/machine_list/js/machine_detail.js"></script>
    <script src="/factory_monitoring/dashboard/dashboard.js"></script>

</body>

</html>