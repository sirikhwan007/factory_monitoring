<?php
session_start();
require_once __DIR__ . '/../config.php';

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

/* ================= SIDEBAR ================= */
$sidebar_paths = [
    'Admin'      => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'    => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Technician' => __DIR__ . '/../Technician/SidebarTechnician.php',
];
$sidebar_file = $sidebar_paths[$user_role] ?? null;

/* ================= FINISH TASK ================= */

/* ================= FINISH TASK ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['finish_id'])) {

    $id = (int)$_POST['finish_id'];

    $q = $conn->prepare("
        SELECT
            id,
            machine_id,
            task_name,
            technician_id,
            interval_month,
            next_maintenance
        FROM maintenance_plan
        WHERE id = ? AND technician_id = ?
    ");
    $q->bind_param("ii", $id, $user_id);
    $q->execute();
    $res = $q->get_result();

    if ($res->num_rows === 1) {
        $row = $res->fetch_assoc();

        $interval   = (int)$row['interval_month'];
        $planned    = $row['next_maintenance'];   // วันที่ควรทำ
        $completed = date('Y-m-d H:i:s');
        // วันที่ทำจริง

        // ถึงรอบหรือเกินรอบเท่านั้น
        if ($planned <= $completed) {

            /* ===== คำนวณวันล่าช้า ===== */
            $delay = 0;
            if ($completed > $planned) {
                $delay = (new DateTime($planned))
                    ->diff(new DateTime($completed))
                    ->days;
            }

            $remark = $delay > 0 ? 'งานล่าช้า' : 'ตรงรอบ';

            /* 1 INSERT LOG */
            $log = $conn->prepare("
                INSERT INTO maintenance_logs
                (plan_id, machine_id, technician_id, task_name,
                 planned_date, completed_at, delay_days, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $log->bind_param(
                "isisssis",
                $row['id'],
                $row['machine_id'],
                $row['technician_id'],
                $row['task_name'],
                $planned,
                $completed,
                $delay,
                $remark
            );

            $log->execute();
            $log->close();

            /* UPDATE PLAN */
            $next = date('Y-m-d', strtotime("$planned +{$interval} months"));

            $u = $conn->prepare("
                UPDATE maintenance_plan
                SET last_maintenance = ?, next_maintenance = ?
                WHERE id = ?
            ");
            $u->bind_param("ssi", $completed, $next, $id);

            $u->execute();
            $u->close();
        }
    }

    $q->close();
    header("Location: maintenance_tasks.php");
    exit();
}


/* ================= LOAD TASKS ================= */
$sql = "
SELECT
    mp.id,
    mp.machine_id,
    mp.task_name,
    mp.next_maintenance,
    CASE
        WHEN mp.next_maintenance < CURDATE() THEN 'overdue'
        WHEN mp.next_maintenance <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            THEN 'warning'
        ELSE 'normal'
    END AS status
FROM maintenance_plan mp
WHERE mp.technician_id = ?
ORDER BY mp.next_maintenance ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>งานซ่อมของฉัน</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">

    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: 'Sarabun', sans-serif;
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
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .08);
        }

        .task {
            padding: 16px 0;
            border-bottom: 1px solid #eee;
        }

        .task:last-child {
            border-bottom: none;
        }

        .badge {
            display: inline-block;
            margin-top: 6px;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge.normal {
            background: #e6f4ea;
            color: #1e7e34;
        }

        .badge.warning {
            background: #fff4e5;
            color: #b45309;
        }

        .badge.overdue {
            background: #fdecea;
            color: #b91c1c;
        }

        button {
            margin-top: 8px;
            padding: 6px 16px;
            border: none;
            border-radius: 6px;
            background: #22c55e;
            color: #fff;
            cursor: pointer;
        }

        button[disabled] {
            background: #9ca3af;
            cursor: not-allowed;
        }
    </style>
</head>

<body>
    <div class="layout-wrapper">

        <?php if ($sidebar_file && file_exists($sidebar_file)) include $sidebar_file; ?>

        <div class="main-content">
            <h2>🛠 งานซ่อมของฉัน</h2>

            <div class="card">
                <?php if ($tasks->num_rows === 0): ?>
                    <p> ยังไม่มีงาน</p>
                <?php else: ?>
                    <?php while ($t = $tasks->fetch_assoc()): ?>
                        <div class="task">
                            <strong><?= htmlspecialchars($t['task_name']) ?></strong><br>
                            เครื่อง: <?= htmlspecialchars($t['machine_id']) ?><br>
                            รอบถัดไป: <?= $t['next_maintenance'] ?><br>

                            <span class="badge <?= $t['status'] ?>">
                                <?= $t['status'] === 'overdue'
                                    ? 'เกินรอบ'
                                    : ($t['status'] === 'warning' ? 'ใกล้ถึงรอบ' : 'ยังไม่ถึงรอบ') ?>
                            </span>

                            <form method="post">
                                <input type="hidden" name="finish_id" value="<?= $t['id'] ?>">
                                <button type="submit" <?= $t['status'] === 'normal' ? 'disabled' : '' ?>>
                                    ✔ งานเสร็จแล้ว
                                </button>
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>