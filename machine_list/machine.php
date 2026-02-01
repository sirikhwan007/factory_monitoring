<?php
session_start();

// เช็กล็อกอิน
if (!isset($_SESSION['user_id'])) {
  header("Location: /factory_monitoring/login.php");
  exit();
}

$user_role = $_SESSION['role'] ?? 'Operator';

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "factory_monitoring");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลเครื่องจักร
$machines = [];
$sql = "SELECT machine_id, name, status, location, photo_url FROM machines";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $row['photo_url'] = !empty($row['photo_url']) ? htmlspecialchars($row['photo_url']) : 'default.png';
    $machines[] = $row;
  }
}

$sidebar_paths = [
  'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
  'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
  'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];


$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>รายการเครื่องจักร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/factory_monitoring/machine_list/css/machine_list.css">
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
  <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
  <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    .dashboard {
      flex: 1;
      overflow: auto;
      background: #f4f9fd;
      border-radius: 20px;
      padding: 30px;
      margin: 0px;
      margin-left: 250px;
    }

    /* บังคับกลุ่มปุ่มให้เรียงแนวนอน */
    .status-filter {
      display: flex !important;
      flex-direction: row !important;
      gap: 12px;
      /* ระยะห่างระหว่างปุ่ม */
      flex-wrap: nowrap;
      /* ห้ามขึ้นบรรทัดใหม่ */
      overflow-x: auto;
      /* ให้ปัดข้างได้ถ้าปุ่มยาวเกินจอ */
      margin-bottom: 20px;
      padding: 10px 0;
    }

    /* ซ่อนแถบเลื่อน (Scrollbar) เพื่อความสวยงาม */
    .status-filter::-webkit-scrollbar {
      display: none;
    }

    /* สไตล์ปุ่มตัวกรอง (เลียนแบบหน้า Users) */
    .btn-filter {
      white-space: nowrap;
      /* ห้ามข้อความในปุ่มตัดบรรทัด */
      padding: 8px 22px;
      border-radius: 30px;
      border: 1px solid #dee2e6;
      background-color: #fff;
      color: #6c757d;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    /* เครื่องจักรทั้งหมด - สีน้ำเงิน */
.btn-filter.btn-all.active {
    background-color: #0d6efd ;
    color: white ;
    border-color: #0d6efd ;
}

/* กำลังทำงาน - สีเขียว */
.btn-filter.btn-running.active {
    background-color: #28a745 ;
    color: white ;
    border-color: #28a745 ;
}

/* ผิดปกติ - สีเหลือง */
.btn-filter.btn-warning.active {
    background-color: #ffc107 ;
    color: #212529 ; /* ตัวหนังสือสีเข้มเพื่อให้มองเห็นชัดบนพื้นเหลือง */
    border-color: #ffc107 ;
}


.btn-filter.btn-stopped.active {
    background-color: #dc3545 ;
    color: white ;
    border-color: #dc3545 ;
}

/* Hover Effect (เลือกใช้สีเทาอ่อนสำหรับทุกปุ่มตอนยังไม่คลิก) */
.btn-filter:hover:not(.active) {
    background-color: #f8f9fa;
    border-color: #adb5bd;
}
  </style>
</head>

<body>

  <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

  <section class="main">

    <?php include $sidebar_file; ?>

    <div class="dashboard">
      <h2 class="dashboard-title">รายการเครื่องจักร</h2>


      <div class="status-filter">
        <button onclick="filterStatus('all', this)" class="btn-filter btn-all active">เครื่องจักรทั้งหมด</button>
        <button onclick="filterStatus('กำลังทำงานปกติ', this)" class="btn-filter btn-running">กำลังทำงาน</button>
        <button onclick="filterStatus('ผิดปกติ', this)" class="btn-filter btn-warning">ผิดปกติ</button>
        <button onclick="filterStatus('หยุดทำงาน', this)" class="btn-filter btn-stopped">หยุดทำงาน</button>
      </div>

      <div class="machine-header">
        <input type="text" id="searchInput" placeholder="ค้นหาเครื่องจักร..." class="search-input">
        <a href="../addmachine/machine.php" class="btn-add-machine">
          <i class="fa-solid fa-plus"></i> เพิ่มเครื่องจักร
        </a>
      </div>
      <div class="machine-cards-wrapper">
        <?php if (count($machines) > 0): ?>
          <?php foreach ($machines as $m): ?>
            <div class="machine-card" onclick="location.href='/factory_monitoring/dashboard/Dashboard.php?id=<?php echo $m['machine_id']; ?>'">
              <img src="/factory_monitoring/<?php echo $m['photo_url']; ?>" alt="รูปเครื่องจักร">
              <div class="machine-name"><?php echo htmlspecialchars($m['name']); ?></div>
              <div class="machine-id">ID: <?php echo htmlspecialchars($m['machine_id']); ?></div>
              <div class="machine-status" id="status-<?php echo $m['machine_id']; ?>">
                กำลังโหลดสถานะ...
              </div>
              <div class="machine-location">ที่ตั้ง: <?php echo htmlspecialchars($m['location']); ?></div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>ไม่มีข้อมูลเครื่องจักร</p>
        <?php endif; ?>
      </div>
    </div>

  </section>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="assets/js/SidebarAdmin.js"></script>
  <script src="assets/js/SidebarManager.js"></script>
  <script src="/factory_monitoring/machine_list/js/machine.js"></script>
  <script>
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
        <
        script >
        Swal.fire({
          title: 'ลบข้อมูลสำเร็จ!',
          text: 'เครื่องจักรและไฟล์ที่เกี่ยวข้องถูกลบออกจากระบบแล้ว',
          icon: 'success',
          confirmButtonColor: '#28a745'
        });
    <?php endif; ?>
  </script>
</body>

</html>