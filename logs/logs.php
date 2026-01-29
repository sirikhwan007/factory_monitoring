<?php
session_start();
include "../config.php";

// ตรวจสอบล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

//ย้ายฟังก์ชัน decodeUnicode ออกมานอกลูป foreach
if (!function_exists('decodeUnicode')) {
    function decodeUnicode($str)
    {
        return preg_replace_callback('/\\\\u([0-9A-Fa-f]{4})/', function ($m) {
            return mb_convert_encoding(pack('H*', $m[1]), 'UTF-8', 'UTF-16BE');
        }, $str);
    }
}

// ดึงข้อมูล logs
$logs = [];
$sql = "SELECT l.log_id, l.user_id, u.username, l.role, l.action, l.description, l.created_at
        FROM logs l
        LEFT JOIN users u ON l.user_id = u.user_id
        ORDER BY l.created_at DESC";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ประวัติการเข้าใช้</title>
    <link rel="stylesheet" href="/factory_monitoring/logs/logs.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

    <section class="main">
        <?php include __DIR__ . '/../admin/SidebarAdmin.php'; ?>


        <div class="dashboard">
            <div class="logs-container">
                <h2><i class="fa-solid fa-list-check me-2"></i>ประวัติการเข้าใช้</h2>

                <input type="text" id="searchInput" class="form-control search-input" placeholder="ค้นหา...">

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-active">
                            <tr>
                                <th>ID</th>
                                <th>ผู้ใช้งาน</th>
                                <th>สิทธิ์</th>
                                <th>การกระทำ</th>
                                <th>รายละเอียด</th>
                                <th>วันที่เวลา</th>
                            </tr>
                        </thead>
                        <tbody id="logsTable">
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($log['log_id']) ?></td>
                                        <td><?= htmlspecialchars($log['username'] ?? 'ไม่ระบุ') ?></td>

                                        <td>
                                            <?php
                                            $roleColor = match ($log['role']) {
                                                'admin' => 'danger',
                                                'teacher' => 'primary',
                                                'student' => 'success',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $roleColor ?> badge-role">
                                                <?= htmlspecialchars($log['role']) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php
                                            $actionColor = match (strtoupper($log['action'])) {
                                                'DELETE' => 'danger',
                                                'UPDATE' => 'warning text-dark',
                                                'INSERT' => 'success',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $actionColor ?> badge-action">
                                                <?= htmlspecialchars($log['action']) ?>
                                            </span>
                                        </td>

                                        <td class="desc-cell">
                                            <?php
                                            $desc = $log['description'] ?? '';

                                            // ตัด prefix เช่น "ลบเครื่องจักร:" ออก
                                            $pos = strpos($desc, '{');
                                            if ($pos !== false) {
                                                $desc = substr($desc, $pos);
                                            }

                                            $desc = decodeUnicode($desc); // ใช้งานฟังก์ชันที่ย้ายออกมา

                                            // regex ดึง JSON
                                            preg_match('/\{.*\}/s', $desc, $match);
                                            $jsonText = $match[0] ?? '';
                                            $restText = trim(str_replace($jsonText, '', $desc));

                                            $data = json_decode($jsonText, true);
                                            if (!is_array($data)) {
                                                $data = [];
                                                if (preg_match_all('/"([^"]+)"\s*:\s*(?:"([^"]*)"|([0-9.+-Ee]+|true|false|null))/iu', $jsonText, $m)) {
                                                    for ($i = 0; $i < count($m[0]); $i++) {
                                                        $key = $m[1][$i];
                                                        $val = $m[2][$i] !== '' ? $m[2][$i] : $m[3][$i];
                                                        $lv = strtolower($val);
                                                        if ($lv === 'true') $val = true;
                                                        elseif ($lv === 'false') $val = false;
                                                        elseif ($lv === 'null') $val = null;
                                                        $data[$key] = $val;
                                                    }
                                                }
                                            }
                                            // ลบ key ไม่ต้องการ
                                            unset($data['status'], $data['photo_url']);
                                            // แสดงผลแบบ key:value
                                            $displayText = '';
                                            if (!empty($data)) {
                                                $result = [];
                                                foreach ($data as $k => $v) {
                                                    if (is_bool($v)) $v = $v ? 'true' : 'false';
                                                    elseif (is_null($v)) $v = 'null';
                                                    $result[] = "$k: $v";
                                                }
                                                $displayText = implode(', ', $result);
                                            }
                                            if ($restText !== "") {
                                                // เพิ่มรายละเอียดที่เหลือในส่วนท้าย
                                                if ($displayText !== '') {
                                                    $displayText .= ", ";
                                                }
                                                $displayText .= $restText;
                                            }
                                            // แสดงข้อความในตาราง และเก็บข้อมูลเต็มไว้ใน data-desc สำหรับ popup
                                            echo htmlspecialchars($displayText);
                                            ?>
                                            <span class="d-none" data-desc="<?= htmlspecialchars($displayText, ENT_QUOTES, 'UTF-8') ?>"></span>
                                        </td>
                                        <td><?= htmlspecialchars($log['created_at']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>


        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

        <script>
            // ฟังก์ชันค้นหา
            document.getElementById('searchInput').addEventListener('input', function() {
                const keyword = this.value.toLowerCase();
                const rows = document.querySelectorAll('#logsTable tr');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(keyword) ? '' : 'none';
                });
            });

            // คลิกที่รายละเอียดแล้วเด้ง popup
            document.querySelectorAll('#logsTable .desc-cell').forEach(cell => {
                cell.addEventListener('click', function() {
                    const hiddenSpan = this.querySelector('.d-none');
                    let data = hiddenSpan ? hiddenSpan.dataset.desc : this.textContent.trim();
                    if (!data) data = this.textContent.trim();

                    const action = this.dataset.action || '';

                    let html = '';

                    if (action === 'UPDATE' && data.includes('---- หลังแก้ไข ----')) {
                        // แยกสองคอลัมน์เฉพาะ UPDATE
                        let parts = data.split('---- หลังแก้ไข ----');
                        let before = parts[0].replace('---- ก่อนแก้ไข ----', '').trim();
                        let after = parts[1].trim();

                        before = before.replace(/\n/g, '<br>');
                        after = after.replace(/\n/g, '<br>');

                        html = `
            <div style="display:flex; gap:20px; width:100%;">
                <div style="flex:1; border-right:1px solid #ccc; padding-right:10px;">
                    <strong>ก่อนแก้ไข</strong><br>${before}
                </div>
                <div style="flex:1; padding-left:10px;">
                    <strong>หลังแก้ไข</strong><br>${after}
                </div>
            </div>
            `;
                    } else {
                        // INSERT / DELETE / อื่นๆ แสดงแบบข้อความปกติ
                        html = data.replace(/\n/g, '<br>');
                    }

                    Swal.fire({
                        title: 'รายละเอียด',
                        html: html,
                        icon: 'info',
                        width: 800,
                        scrollbarPadding: false,
                        confirmButtonText: 'ปิด'
                    });
                });
            });
        </script>

</body>

</html>