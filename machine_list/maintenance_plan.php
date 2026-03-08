<?php
session_start();
require_once __DIR__ . '/../config.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Manager'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

$user_role = $_SESSION['role'];

/* ================= PARAM ================= */
$machine_id = $_GET['machine_id'] ?? '';
if ($machine_id === '') {
    die("ไม่พบ Machine ID");
}

/* ================= SIDEBAR ================= */
$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];
$sidebar_file = $sidebar_paths[$user_role] ?? null;

$sidebar_css_paths = [
    'Admin'      => '/factory_monitoring/admin/assets/css/index.css',
    'Manager'    => '/factory_monitoring/Manager/assets/css/Sidebar.css',
    'Operator'   => '/factory_monitoring/Operator/assets/css/SidebarOperator.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];

/* ================= ADD PLAN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_name'])) {

    $task_name      = trim($_POST['task_name']);
    $interval_month = (int)$_POST['interval_month'];

    $technician_id = ($_POST['technician_id'] !== '')
        ? $_POST['technician_id']
        : null;

    $last_maintenance = date('Y-m-d');
    $next_maintenance = date('Y-m-d', strtotime("+{$interval_month} months"));

    if ($technician_id === null) {

        $stmt = $conn->prepare("
        INSERT INTO maintenance_plan
        (machine_id, task_name, interval_month, technician_id, last_maintenance, next_maintenance, status)
        VALUES (?, ?, ?, NULL, ?, ?, 'ปกติ')
    ");

        $stmt->bind_param(
            "ssiss",
            $machine_id,
            $task_name,
            $interval_month,
            $last_maintenance,
            $next_maintenance
        );
    } else {

        $stmt = $conn->prepare("
        INSERT INTO maintenance_plan
        (machine_id, task_name, interval_month, technician_id, last_maintenance, next_maintenance, status)
        VALUES (?, ?, ?, ?, ?, ?, 'ปกติ')
    ");

        $stmt->bind_param(
            "ssisss",
            $machine_id,
            $task_name,
            $interval_month,
            $technician_id,
            $last_maintenance,
            $next_maintenance
        );
    }

    $stmt->execute();
    $stmt->close();

    header("Location: maintenance_plan.php?machine_id=" . urlencode($machine_id));
    exit();
}

/* ================= FETCH PLANS ================= */
$plans_stmt = $conn->prepare("
    SELECT
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

/* ================= FETCH TECHNICIANS ================= */
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
        }

        button {
            margin-top: 15px;
            padding: 10px 18px;
            background: #ff8c00;
            border: none;
            color: #fff;
            border-radius: 6px;
            cursor: pointer;
        }

        .plan-item {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }

        .plan-item:last-child {
            border-bottom: none;
        }

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

      

      .sidebar-wrapper * {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
      }

      .sidebar-wrapper a,
      .sidebar-wrapper .nav-link {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: flex-start !important;
        text-align: left !important;
        padding: 10px 20px !important;
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
    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active')">
        <i class="fa-solid fa-bars"></i>
    </div>

    <div class="layout-wrapper">

        <!-- SIDEBAR -->
        <div class="sidebar-wrapper">
            <?php include $sidebar_file; ?>
        </div>

        <!-- MAIN -->
        <div class="main-content">

            <h2>🛠 แผนซ่อมตามรอบ : <?= htmlspecialchars($machine_id) ?></h2>

            <!-- ADD PLAN -->
            <div class="card">
                <h3>เพิ่มแผนซ่อม</h3>
                <form method="post">

                    <label>รายการซ่อม</label>
                    <input type="text" name="task_name" required placeholder="เช่น เปลี่ยนลูกปืน">

                    <label>รอบการซ่อม</label>
                    <select name="interval_month" required>
                        <option value="1">ทุก 1 เดือน</option>
                        <option value="3">ทุก 3 เดือน</option>
                        <option value="6">ทุก 6 เดือน</option>
                        <option value="12">ทุก 12 เดือน</option>
                    </select>

                    <label>ช่างผู้รับผิดชอบ</label>
                    <select name="technician_id">
                        <option value="">-- ยังไม่ระบุ --</option>
                        <?php while ($t = $techs->fetch_assoc()): ?>
                            <option value="<?= $t['user_id'] ?>">
                                <?= htmlspecialchars($t['username']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <button type="submit">บันทึกแผน</button>
                </form>
            </div>

            <!-- PLAN LIST -->
            <div class="card">
                <h3>รายการแผนที่ตั้งไว้</h3>

                <?php if ($plans->num_rows === 0): ?>
                    <p>ยังไม่มีแผนซ่อม</p>
                <?php else: ?>
                    <?php while ($p = $plans->fetch_assoc()): ?>
                        <div class="plan-item">
                            <strong><?= htmlspecialchars($p['task_name']) ?></strong><br>
                            รอบ: ทุก <?= $p['interval_month'] ?> เดือน<br>
                            รอบถัดไป: <?= $p['next_maintenance'] ?><br>
                            ช่าง: <?= htmlspecialchars($p['technician_name'] ?? 'ไม่ระบุ') ?><br>
                            
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>

</body>

</html>