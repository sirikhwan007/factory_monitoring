<?php
include "../config/db.php";

$id = $_GET['id'] ?? null;
$data = null;

if ($id) {
    $sql = "SELECT * FROM repair_history WHERE id = $id";
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
}
?>

<form method="post" action="repair_save.php">
    <input type="hidden" name="id" value="<?= $data['id'] ?? '' ?>">

    เครื่องจักร:
    <input type="text" name="machine_id" value="<?= $data['machine_id'] ?? '' ?>" required><br>

    ผู้แจ้ง:
    <input type="text" name="reporter" value="<?= $data['reporter'] ?? '' ?>"><br>

    ตำแหน่ง:
    <input type="text" name="position" value="<?= $data['position'] ?? '' ?>"><br>

    ประเภท:
    <select name="type">
        <option value="Preventive" <?= ($data['type'] ?? '') == 'Preventive' ? 'selected' : '' ?>>Preventive</option>
        <option value="Corrective" <?= ($data['type'] ?? '') == 'Corrective' ? 'selected' : '' ?>>Corrective</option>
    </select><br>

    รายละเอียด:
    <textarea name="detail"><?= $data['detail'] ?? '' ?></textarea><br>

    สถานะ:
    <select name="status">
        <option value="รอดำเนินการ">รอดำเนินการ</option>
        <option value="กำลังซ่อม">กำลังซ่อม</option>
        <option value="ซ่อมเสร็จ">ซ่อมเสร็จ</option>
    </select><br>

    <button type="submit">บันทึก</button>
</form>
