<?php
session_start();
require_once "../config.php";

/* AUTH */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: /factory_monitoring/login.php");
    exit();
}

/* LOAD SCHEDULE */
$sql = "
SELECT 
    ms.machine_id,
    m.name AS machine_name,
    ms.maintenance_type,
    ms.last_maintenance_date,
    ms.next_maintenance_date
FROM maintenance_schedule ms
JOIN machines m ON ms.machine_id = m.machine_id
ORDER BY ms.next_maintenance_date ASC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>Maintenance Schedule</title>
</head>
<body>

<h2>ตารางบำรุงรักษาตามรอบ (PM)</h2>

<table border="1" cellpadding="8">
<tr>
    <th>เครื่อง</th>
    <th>ประเภท</th>
    <th>ล่าสุด</th>
    <th>รอบถัดไป</th>
    <th>ดำเนินการ</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
    <td><?= htmlspecialchars($row['machine_name']) ?></td>
    <td><?= $row['maintenance_type'] ?></td>
    <td><?= $row['last_maintenance_date'] ?></td>
    <td><?= $row['next_maintenance_date'] ?></td>
    <td>
        <form action="do_maintenance.php" method="POST">
            <input type="hidden" name="machine_id" value="<?= $row['machine_id'] ?>">
            <input type="hidden" name="maintenance_type" value="<?= $row['maintenance_type'] ?>">
            <button type="submit">ทำ PM</button>
        </form>
    </td>
</tr>
<?php endwhile; ?>

</table>

</body>
</html>
