<?php
include "../config.php";
session_start();

$user_role = $_SESSION['role'] ?? 'Operator';

if (!isset($_GET['id'])) {
    die("ไม่พบเครื่องจักรที่เลือก");
}

$machine_id = $_GET['id'] ?? null;

// ดึงข้อมูลเครื่องจักรจาก DB
$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id); 
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

if (!$machine) {
    die("ไม่พบข้อมูลเครื่องจักร");
}

$mac_address = $machine['mac_address'];


$stmtTh = $conn->prepare("SELECT * FROM thresholds WHERE mac_address = ?");
$stmtTh->bind_param("s", $mac_address);
$stmtTh->execute();
$resTh = $stmtTh->get_result();
$threshold = $resTh->fetch_assoc();


if (!$threshold) {
    $stmtDef = $conn->prepare("SELECT * FROM thresholds WHERE mac_address = 'default'");
    $stmtDef->execute();
    $resDef = $stmtDef->get_result();
    $threshold = $resDef->fetch_assoc() ?? [];
}

$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

$sidebar_css_paths = [
    'Admin'      => '/admin/assets/css/index.css',
    'Manager'    => '/Manager/assets/css/Sidebar.css',
    'Operator'   => '/Operator/assets/css/SidebarOperator.css',
    'Technician' => '/Technician/assets/css/sidebar_technician.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];

?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ตั้งค่า Thresholds</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
        }

        .dashboard {
            margin-left: 250px;
        }

        .form-container {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .form-title {
            background-color: #86b7fe;
            text-align: center;
            font-weight: bold;
            margin-bottom: 30px;
            text-transform: uppercase;
            font-size: 22px;
            color: #333; 
        }

        /* สไตล์ช่อง Input */
        .form-control:read-only {
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            color: #333;
        }

        .threshold-input {
            background-color: #ffffff; 
            border: 1px solid #ced4da; 
            color: #000000; 
        }

        .threshold-input:focus {
            background-color: #ffffff;
            color: #000000;
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }

        /* Grid Layout */
        .th-row {
            margin-bottom: 15px;
            align-items: center;
        }

        .btn-submit {
            background-color: #198754;
            border: none;
            color: #ffffff;
            font-weight: bold;
            padding: 10px 40px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: #157347;
            color: #ffffff;
        }

        @media (max-width: 992px) {
            .dashboard {
                margin-left: 0;
                padding: 15px;
            }

            .main {
                flex-direction: column;
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

    <section class="main">

        <div class="sidebar-wrapper">
            <?php include $sidebar_file; ?>
        </div>

        <div class="dashboard">
            <div class="container my-5">

                <div class="form-container">
                    <div class="form-title">SETTING Thresholds</div>

                    <form id="thresholdForm">
                        <div class="row mb-3 align-items-center">
                            <label class="col-sm-3 col-form-label fw-bold">Machine ID :</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" value="<?= htmlspecialchars($machine['machine_id']) ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-5 align-items-center">
                            <label class="col-sm-3 col-form-label fw-bold">MAC Address :</label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" name="mac_address" value="<?= htmlspecialchars($machine['mac_address']) ?>" readonly>
                            </div>
                        </div>

                        <div class="row mb-3 text-center fw-bold">
                            <div class="col-3"></div>
                            <div class="col-4">Warning</div>
                            <div class="col-4">Danger</div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Temp (°C)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_temp" class="form-control threshold-input text-center" value="<?= $threshold['warn_temp'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_temp" class="form-control threshold-input text-center" value="<?= $threshold['danger_temp'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Vibration (%)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_vib" class="form-control threshold-input text-center" value="<?= $threshold['warn_vib'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_vib" class="form-control threshold-input text-center" value="<?= $threshold['danger_vib'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Voltage (V)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_volt" class="form-control threshold-input text-center" value="<?= $threshold['warn_volt'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_volt" class="form-control threshold-input text-center" value="<?= $threshold['danger_volt'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Current (A)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_cur" class="form-control threshold-input text-center" value="<?= $threshold['warn_cur'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_cur" class="form-control threshold-input text-center" value="<?= $threshold['danger_cur'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Power (W)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_power" class="form-control threshold-input text-center" value="<?= $threshold['warn_power'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_power" class="form-control threshold-input text-center" value="<?= $threshold['danger_power'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="row th-row">
                            <label class="col-3 col-form-label fw-bold">Energy (Wh)</label>
                            <div class="col-4">
                                <input type="number" step="0.1" name="warn_energy" class="form-control threshold-input text-center" value="<?= $threshold['warn_energy'] ?? '' ?>" required>
                            </div>
                            <div class="col-4">
                                <input type="number" step="0.1" name="danger_energy" class="form-control threshold-input text-center" value="<?= $threshold['danger_energy'] ?? '' ?>" required>
                            </div>
                        </div>

                        <div class="text-center mt-5">
                            <button type="submit" class="btn-submit">ยืนยัน</button>
                        </div>

                    </form>
                </div>

            </div>
        </div>
    </section>

    <script>
        // จัดการตอนกดปุ่ม Submit 
        document.getElementById('thresholdForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // 1. ดึงข้อมูลจากฟอร์ม
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            // 2. แปลงข้อความตัวเลขให้เป็นตัวเลขทศนิยม (Float) ยกเว้น MAC Address
            for (let key in data) {
                if (key !== 'mac_address') {
                    data[key] = parseFloat(data[key]);
                }
            }

            
            const validationPairs = [{
                    warn: 'warn_temp',
                    danger: 'danger_temp',
                    label: 'อุณหภูมิ (Temp)'
                },
                {
                    warn: 'warn_vib',
                    danger: 'danger_vib',
                    label: 'ความสั่นสะเทือน (VIB)'
                },
                {
                    warn: 'warn_volt',
                    danger: 'danger_volt',
                    label: 'แรงดันไฟฟ้า (V)'
                },
                {
                    warn: 'warn_cur',
                    danger: 'danger_cur',
                    label: 'กระแสไฟฟ้า (A)'
                },
                {
                    warn: 'warn_power',
                    danger: 'danger_power',
                    label: 'กำลังไฟฟ้า (P)'
                },
                {
                    warn: 'warn_energy',
                    danger: 'danger_energy',
                    label: 'พลังงาน (E)'
                }
            ];

            for (let item of validationPairs) {
                if (data[item.warn] >= data[item.danger]) {
                    Swal.fire(
                        'ตั้งค่าไม่ถูกต้อง!',
                        `ค่า Warning ของ <b>${item.label}</b> ต้องน้อยกว่า Danger`,
                        'error'
                    );
                    return; 
                }
            }
            
            try {
                const response = await fetch('https://factory-monitoring.onrender.com/api/thresholds', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                if (response.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'บันทึกสำเร็จ',
                        text: 'อัปเดตเกณฑ์แจ้งเตือนเรียบร้อยแล้ว',
                        confirmButtonColor: '#3085d6'
                    }).then(() => {
                        
                        window.location.href = '../dashboard/Dashboard.php';
                    });
                } else {
                    const resData = await response.json();
                    Swal.fire('ผิดพลาด', resData.error || 'ไม่สามารถบันทึกข้อมูลได้', 'error');
                }
            } catch (error) {
                console.error(error);
                Swal.fire('เชื่อมต่อล้มเหลว', 'ไม่สามารถเชื่อมต่อไปยัง Node.js Server ได้', 'error');
            }
        });
    </script>
</body>

</html>