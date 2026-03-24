<?php
session_start();
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$user_id   = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

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
        $planned    = $row['next_maintenance'];
        $completed = date('Y-m-d H:i:s');

        if ($planned <= $completed) {

            $delay = 0;
            if ($completed > $planned) {
                $delay = (new DateTime($planned))
                    ->diff(new DateTime($completed))
                    ->days;
            }

            $remark = $delay > 0 ? 'งานล่าช้า' : 'ตรงรอบ';

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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>งานซ่อมของฉัน</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="/Technician/assets/css/sidebar_technician.css">
    <style>
        body {
            margin: 0;
            background: #f4f6f9;
            font-family: 'Sarabun', sans-serif;
            overflow-x: hidden;
        }

        .sidebar-wrapper {
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            height: 100vh;
            background: #fff;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 2000;
            transition: all 0.3s ease;
        }

        .main-content {
            margin-left: 250px;
            padding: 30px;
            transition: all 0.3s ease;
            min-height: 100vh;
        }

        .card {
            background: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .05);
        }

        .task {
            padding: 20px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 10px 0;
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

        button[type="submit"] {
            width: 100%;
            max-width: 180px;
            padding: 10px;
            border: none;
            border-radius: 8px;
            background: #22c55e;
            color: #fff;
            cursor: pointer;
            font-weight: bold;
            transition: 0.2s;
        }

        button[type="submit"]:hover {
            background: #16a34a;
        }

        button[disabled] {
            background: #cbd5e1;
            cursor: not-allowed;
        }

        .btn-hamburger {
            display: none;
        }

        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding: 50px;
            }

            h2 {
                font-size: 1.4rem;
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

    <div class="layout-wrapper">
        <div class="main-content">
            <div class="header-section">
                <h2 style="margin:0;">🛠 แผนงานของฉัน</h2>
            </div>

            <div class="card">
                <?php if ($tasks->num_rows === 0): ?>
                    <p style="text-align:center; color:#666;">ยังไม่มีแผนงานในขณะนี้</p>
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