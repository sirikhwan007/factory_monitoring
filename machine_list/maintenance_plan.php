<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: /login.php");
    exit();
}

$user_role = $_SESSION['role'];

$machine_id = $_GET['machine_id'] ?? '';
if ($machine_id === '') {
    die("ไม่พบ Machine ID");
}

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];
$sidebar_file = $sidebar_paths[$user_role] ?? null;

$sidebar_css_paths = [
    'Admin'      => '/admin/assets/css/index.css',
    'Manager'    => '/Manager/assets/css/Sidebar.css',
    'Operator'   => '/Operator/assets/css/SidebarOperator.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'delete') {
        $plan_id = (int)$_POST['plan_id'];
        $stmt = $conn->prepare("DELETE FROM maintenance_plan WHERE id = ?");
        $stmt->bind_param("i", $plan_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: maintenance_plan.php?machine_id=" . urlencode($machine_id));
        exit();
    }

    if ($action === 'add' || $action === 'edit') {
        $task_name      = trim($_POST['task_name']);
        $interval_month = (int)$_POST['interval_month'];
        $technician_id  = ($_POST['technician_id'] !== '') ? $_POST['technician_id'] : null;

        if ($action === 'add') {
            $last_maintenance = date('Y-m-d');
            $next_maintenance = date('Y-m-d', strtotime("+{$interval_month} months"));

            if ($technician_id === null) {
                $stmt = $conn->prepare("
                    INSERT INTO maintenance_plan (machine_id, task_name, interval_month, technician_id, last_maintenance, next_maintenance, status)
                    VALUES (?, ?, ?, NULL, ?, ?, 'ปกติ')
                ");
                $stmt->bind_param("ssiss", $machine_id, $task_name, $interval_month, $last_maintenance, $next_maintenance);
            } else {
                $stmt = $conn->prepare("
                    INSERT INTO maintenance_plan (machine_id, task_name, interval_month, technician_id, last_maintenance, next_maintenance, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'ปกติ')
                ");
                $stmt->bind_param("ssisss", $machine_id, $task_name, $interval_month, $technician_id, $last_maintenance, $next_maintenance);
            }
            $stmt->execute();
            $stmt->close();

        } elseif ($action === 'edit') {
            $plan_id = (int)$_POST['plan_id'];

            // ดึง last_maintenance เดิมมาเพื่อคำนวณ next_maintenance ใหม่
            $stmt_get = $conn->prepare("SELECT last_maintenance FROM maintenance_plan WHERE id = ?");
            $stmt_get->bind_param("i", $plan_id);
            $stmt_get->execute();
            $res = $stmt_get->get_result()->fetch_assoc();
            $last_m = $res['last_maintenance'];
            $stmt_get->close();

            $next_m = date('Y-m-d', strtotime($last_m . " +{$interval_month} months"));

            if ($technician_id === null) {
                $stmt = $conn->prepare("
                    UPDATE maintenance_plan 
                    SET task_name = ?, interval_month = ?, technician_id = NULL, next_maintenance = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("sisi", $task_name, $interval_month, $next_m, $plan_id);
            } else {
                $stmt = $conn->prepare("
                    UPDATE maintenance_plan 
                    SET task_name = ?, interval_month = ?, technician_id = ?, next_maintenance = ? 
                    WHERE id = ?
                ");
                $stmt->bind_param("sissi", $task_name, $interval_month, $technician_id, $next_m, $plan_id);
            }
            $stmt->execute();
            $stmt->close();
        }

        header("Location: maintenance_plan.php?machine_id=" . urlencode($machine_id));
        exit();
    }
}

// ตรวจสอบว่ามี Request ต้องการแก้ไขแผนหรือไม่ (ดึงข้อมูลมาแสดงในฟอร์ม)
$edit_id = $_GET['edit_id'] ?? null;
$edit_data = null;
if ($edit_id) {
    $edit_stmt = $conn->prepare("SELECT * FROM maintenance_plan WHERE id = ?");
    $edit_stmt->bind_param("i", $edit_id);
    $edit_stmt->execute();
    $edit_data = $edit_stmt->get_result()->fetch_assoc();
    $edit_stmt->close();
}

// ดึงรายการแผนทั้งหมด
$plans_stmt = $conn->prepare("
    SELECT
        mp.id,
        mp.task_name,
        mp.interval_month,
        mp.last_maintenance,
        mp.next_maintenance,
        mp.status,
        u.username AS technician_name
    FROM maintenance_plan mp
    LEFT JOIN users u 
        ON mp.technician_id = u.user_id
    WHERE mp.machine_id = ?
    ORDER BY mp.created_at DESC
");
$plans_stmt->bind_param("s", $machine_id);
$plans_stmt->execute();
$plans = $plans_stmt->get_result();

$techs = $conn->query("
    SELECT user_id, username
    FROM users
    WHERE role = 'Technician'
    ORDER BY username
");
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แผนซ่อมตามรอบ | <?= htmlspecialchars($machine_id) ?></title>
    <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: sans-serif;
        }

        .layout-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
        }

        .card {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
        }

        label {
            font-weight: 600;
            margin-top: 12px;
            display: block;
        }

        input,
        select {
            width: 100%;
            padding: 8px;
            margin-top: 6px;
            box-sizing: border-box;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        /* ปุ่มบันทึก */
        .btn-primary {
            margin-top: 15px;
            padding: 10px 18px;
            background: #ff8c00;
            border: none;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary:hover { background: #e07b00; }

        .btn-cancel {
            display: inline-block;
            margin-top: 15px;
            margin-left: 10px;
            padding: 10px 18px;
            background: #6c757d;
            color: #fff;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
        }
        .btn-cancel:hover { background: #5a6268; }

        .plan-item {
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .plan-item:last-child {
            border-bottom: none;
        }

        .plan-info {
            flex: 1;
        }

        .plan-actions {
            display: flex;
            gap: 10px;
        }

        /* ปุ่มแก้ไขใน List */
        .btn-edit {
            background: #0d6efd;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-edit:hover { background: #0b5ed7; }

        /* ปุ่มลบใน List */
        .btn-delete {
            background: #dc3545;
            color: #fff;
            padding: 6px 12px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 13px;
        }
        .btn-delete:hover { background: #c82333; }

        .status {
            font-weight: bold;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 15px;
                border-radius: 0;
                padding-top: 60px;
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
                width: 40px;
                height: 40px;
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

    <div class="layout-wrapper">
        <div class="sidebar-wrapper">
            <?php include $sidebar_file; ?>
        </div>

        <div class="main-content">
            <h2>🛠 แผนซ่อมตามรอบ : <?= htmlspecialchars($machine_id) ?></h2>
            
            <div class="card">
                <h3><?= $edit_data ? 'แก้ไขแผนซ่อมบำรุง' : 'เพิ่มแผนซ่อมบำรุง' ?></h3>
                <form method="post">
                    <input type="hidden" name="action" value="<?= $edit_data ? 'edit' : 'add' ?>">
                    <?php if ($edit_data): ?>
                        <input type="hidden" name="plan_id" value="<?= $edit_data['id'] ?>">
                    <?php endif; ?>

                    <label>รายการซ่อม</label>
                    <input type="text" name="task_name" required placeholder="เช่น เปลี่ยนลูกปืน" value="<?= htmlspecialchars($edit_data['task_name'] ?? '') ?>">

                    <label>รอบการซ่อม</label>
                    <select name="interval_month" required>
                        <?php 
                        $intervals = [1 => 'ทุก 1 เดือน', 3 => 'ทุก 3 เดือน', 6 => 'ทุก 6 เดือน', 12 => 'ทุก 12 เดือน'];
                        $current_interval = $edit_data['interval_month'] ?? 1;
                        foreach ($intervals as $val => $text): 
                            $selected = ($val == $current_interval) ? 'selected' : '';
                        ?>
                            <option value="<?= $val ?>" <?= $selected ?>><?= $text ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label>ช่างผู้รับผิดชอบ</label>
                    <select name="technician_id">
                        <option value="">-- ยังไม่ระบุ --</option>
                        <?php 
                        $current_tech = $edit_data['technician_id'] ?? null;
                        while ($t = $techs->fetch_assoc()): 
                            $selected = ($t['user_id'] == $current_tech) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($t['user_id']) ?>" <?= $selected ?>>
                                <?= htmlspecialchars($t['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit" class="btn-primary"><?= $edit_data ? 'บันทึกการแก้ไข' : 'บันทึกแผนใหม่' ?></button>
                    <?php if ($edit_data): ?>
                        <a href="maintenance_plan.php?machine_id=<?= urlencode($machine_id) ?>" class="btn-cancel">ยกเลิก</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="card">
                <h3>รายการแผนที่ตั้งไว้</h3>

                <?php if ($plans->num_rows === 0): ?>
                    <p>ยังไม่มีแผนซ่อม</p>
                <?php else: ?>
                    <?php while ($p = $plans->fetch_assoc()): ?>
                        <div class="plan-item">
                            <div class="plan-info">
                                <strong><?= htmlspecialchars($p['task_name']) ?></strong><br>
                                รอบ: ทุก <?= $p['interval_month'] ?> เดือน<br>
                                รอบถัดไป: <?= $p['next_maintenance'] ?><br>
                                ช่าง: <?= htmlspecialchars($p['technician_name'] ?? 'ไม่ระบุ') ?>
                            </div>
                            
                            <div class="plan-actions">
                                <a href="?machine_id=<?= urlencode($machine_id) ?>&edit_id=<?= $p['id'] ?>" class="btn-edit">
                                    <i class="fa-solid fa-pen-to-square"></i> แก้ไข
                                </a>

                                <form method="post" style="margin: 0;" onsubmit="confirmDelete(event, this);">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="plan_id" value="<?= $p['id'] ?>">
                                    <button type="submit" class="btn-delete">
                                        <i class="fa-solid fa-trash"></i> ลบ
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(event, formElement) {
            // หยุดการส่งฟอร์มทันที
            event.preventDefault(); 
            
            Swal.fire({
                title: 'ยืนยันการลบหรือไม่?',
                text: "ข้อมูลที่ลบแล้วจะไม่สามารถกู้คืนได้!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    formElement.submit(); 
                }
            });
        }
    </script>
</body>

</html>