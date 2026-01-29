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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งซ่อมเครื่องจักร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/factory_monitoring/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        /* --- General & Typography --- */
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8fafd; /* สีพื้นหลังโทนอ่อนสบายตา */
            color: #333;
            line-height: 1.6;
        }

        .main {
            padding: 0%; /* ระยะห่างขอบ */
        }

        .dashboard-title {
            font-size: 2.2rem; /* ขนาดหัวข้อ */
            font-weight: 700;
            color: #2c3e50; /* สีหัวข้อเข้ม */
            margin-bottom: 35px; /* ระยะห่างด้านล่างหัวข้อ */
            display: flex;
            align-items: center;
            justify-content: flex-start; /* จัดชิดซ้าย */
            gap: 15px;
        }
        .dashboard-title i {
            color: #e74c3c; /* สีไอคอน (สีแดงสำหรับการแจ้งซ่อม) */
            font-size: 2.5rem;
        }

        /* --- Form Card (ส่วนสำคัญที่ทำให้เป็นกรอบสวยงามและอยู่ตรงกลาง) --- */
        .repair-form-card {
            background: #ffffff;
            border-radius: 15px; /* มุมโค้งมน */
            box-shadow: 0 10px 40px rgba(0,0,0,0.08); /* เงาที่นุ่มนวล */
            padding: 40px; /* ระยะห่างภายในการ์ด */
            max-width: 650px; /* ความกว้างสูงสุดของการ์ด */
            margin: auto; /* จัดการ์ดให้อยู่กึ่งกลาง */
        }

        /* --- Form Labels & Inputs --- */
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-label i {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .form-control, .form-select {
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 12px 15px;
            font-size: 1rem;
            color: #495057;
            transition: all 0.2s ease-in-out;
            box-shadow: none;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.25rem rgba(0, 123, 255, 0.25);
            outline: none;
        }
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* --- Submit Button --- */
        .btn-submit-repair {
            background-color: #28a745; /* สีเขียว */
            color: #fff;
            padding: 15px 25px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 10px;
            border: none;
            transition: all 0.2s ease-in-out;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        .btn-submit-repair:hover {
            background-color: #218838;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3);
        }
        .btn-submit-repair i {
            font-size: 1.2rem;
        }

        /* --- Responsive Adjustments --- */
        @media (max-width: 768px) {
            .main { padding: 1.5rem; }
            .dashboard-title { font-size: 1.8rem; margin-bottom: 25px; }
            .repair-form-card { padding: 30px; }
        }

        @media (max-width: 576px) {
            .main { padding: 1rem; }
            .dashboard-title { font-size: 1.5rem; margin-bottom: 20px; }
            .repair-form-card { padding: 20px; }
        }
    </style>
</head>

<body>

<div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

<section class="main">

    <?php include __DIR__ . '/SidebarAdmin.php'; ?>

    <div class="dashboard">

        <h2 class="dashboard-title"><i class="fas fa-tools"></i> แจ้งซ่อมเครื่องจักร</h2>

        <div class="repair-form-card"> 
            <form action="processrepair.php" method="POST">

                <div class="mb-3">
                    <label class="form-label" for="reporterName"><i class="fas fa-user"></i> ชื่อผู้แจ้งซ่อม</label>
                    <input type="text" class="form-control" id="reporterName" name="reporter" value="<?= htmlspecialchars($_SESSION['username'] ?? '') ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="position"><i class="fas fa-briefcase"></i> ตำแหน่ง</label>
                    <input type="text" class="form-control" id="position" name="position" value="<?= htmlspecialchars($_SESSION['role'] ?? '') ?>" required>
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
                    <label class="form-label" for="machineID"><i class="fas fa-microchip"></i> ID เครื่องจักร</label>
                    <select class="form-select" id="machineID" name="machine_id" required>
                        <option value="">-- เลือกเครื่องจักร --</option>
                        <?php foreach ($machines as $machine): ?>
                            <option value="<?= htmlspecialchars($machine['machine_id']) ?>"><?= htmlspecialchars($machine['machine_id']) ?> - <?= htmlspecialchars($machine['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="problemDetail"><i class="fas fa-comment-dots"></i> รายละเอียดปัญหา</label>
                    <textarea class="form-control" id="problemDetail" name="detail" rows="4" placeholder="กรุณาระบุรายละเอียดปัญหาที่พบ" required></textarea>
                </div>

                <button type="submit" class="btn btn-submit-repair w-100">
                    <i class="fas fa-paper-plane"></i> ส่งคำขอแจ้งซ่อม
                </button>

            </form>
        </div>
    </div>

</section>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="/factory_monitoring/admin/assets/js/SidebarAdmin.js"></script>

</body>
</html>