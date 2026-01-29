<?php
// ตรวจสอบและเริ่ม session ก่อนที่จะมี output ใดๆ
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// เช็กล็อกอิน (ถ้าจำเป็น)
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
    exit();
}

// เชื่อมต่อฐานข้อมูล
include __DIR__ . "/../config.php";

// ดึงรายการเครื่องจักรเพื่อเลือก ID
$machines = [];
$sql = "SELECT machine_id, name FROM machines ORDER BY name ASC";
$res = $conn->query($sql);
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $machines[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>แจ้งซ่อมเครื่องจักร</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" />

    <!-- Sidebar CSS -->
    <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">

    <style>
        :root {
            --sidebar-width: 260px;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafd;
        }

        /* ===== Layout Fix ===== */
        .main {
            display: flex;
        }

        .dashboard {
            margin-left: var(--sidebar-width);
            padding: 2rem;
            width: 100%;
            min-height: 100vh;
            box-sizing: border-box;
        }

        /* ===== Title ===== */
        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 30px;
        }

        .dashboard-title i {
            color: #e74c3c;
        }

        /* ===== Form Card ===== */
        .repair-form-card {
            background: #fff;
            max-width: 650px;
            margin: auto;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        }

        .form-label {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-label i {
            color: #6c757d;
        }

        .form-control,
        .form-select {
            border-radius: 10px;
            padding: 12px 15px;
        }

        textarea.form-control {
            min-height: 120px;
        }

        /* ===== Button ===== */
        .btn-submit-repair {
            background: #28a745;
            color: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            padding: 15px;
            border-radius: 10px;
            border: none;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-submit-repair:hover {
            background: #218838;
        }

        /* ===== Responsive ===== */
        @media (max-width: 992px) {
            .dashboard {
                margin-left: 0;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

    <section class="main">

        
        
        <!-- Sidebar -->
        <?php $activePage = 'report'; // เปลี่ยนตามหน้า
        include __DIR__ . '/SidebarOperator.php'; ?>

        <!-- Content -->
        <div class="dashboard">

            <h2 class="dashboard-title"><i class="fas fa-tools"></i> แจ้งซ่อมเครื่องจักร</h2>

            <div class="repair-form-card">
                <form action="processrepair.php" method="POST">

                    <div class="mb-3">
                        <label class="form-label" for="reporterName"><i class="fas fa-user"></i> ชื่อผู้แจ้งซ่อม</label>
                        <input type="text" class="form-control" id="reporterName" name="reporter"
                            value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="position"><i class="fas fa-briefcase"></i> ตำแหน่ง</label>
                        <input type="text" class="form-control" id="position" name="position"
                            value="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="repairType"><i class="fas fa-wrench"></i> ประเภทการซ่อม</label>
                        <select class="form-select" id="repairType" name="type" required>
                            <option value="">-- เลือกประเภท --</option>
                            <option value="Preventive">Preventive (การบำรุงรักษาเชิงป้องกัน)</option>
                            <option value="Predictive">Predictive (การบำรุงรักษาเชิงคาดการณ์)</option>
                            <option value="Breakdown">Breakdown (การซ่อมแซมเมื่อเกิดการชำรุด)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="machineID"><i class="fas fa-microchip"></i> ID
                            เครื่องจักร</label>
                        <select class="form-select" id="machineID" name="machine_id" required>
                            <option value="">-- เลือกเครื่องจักร --</option>
                            <?php foreach ($machines as $machine): ?>
                                <option value="<?= htmlspecialchars($machine['machine_id']) ?>">
                                    <?= htmlspecialchars($machine['machine_id']) ?> -
                                    <?= htmlspecialchars($machine['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label" for="problemDetail"><i class="fas fa-comment-dots"></i>
                            รายละเอียดปัญหา</label>
                        <textarea class="form-control" id="problemDetail" name="detail" rows="4"
                            placeholder="กรุณาระบุรายละเอียดปัญหาที่พบ" required></textarea>
                    </div>

                    <button type="submit" class="btn btn-submit-repair w-100">
                        <i class="fas fa-paper-plane"></i> ส่งคำขอแจ้งซ่อม
                    </button>

                </form>
            </div>
        </div>
    </section>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="SidebarAdmin.js"></script>

</body>

</html>