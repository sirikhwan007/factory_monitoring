<?php
session_start();
include __DIR__ . "/../config.php";

// เช็กล็อกอิน
if (!isset($_SESSION['user_id'])) {
  header("Location: /login.php");
  exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'Operator';

$sql_user = "SELECT username, role FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$username = $_SESSION['username'] ?? 'ผู้ใช้งาน';

// ดึงข้อมูลเครื่องจักร
$machines = [];
$sql = "SELECT machine_id, name, status, location, photo_url, mac_address FROM machines"; // เพิ่ม mac_address
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $row['photo_url'] = !empty($row['photo_url']) ? htmlspecialchars($row['photo_url']) : 'default.png';
    $machines[] = $row;
  }
}

$unassigned_machines = [];
$unassigned_sql = "SELECT DISTINCT machine_id FROM maintenance_plan WHERE technician_id IS NULL OR technician_id = ''";
$unassigned_res = $conn->query($unassigned_sql);
if ($unassigned_res && $unassigned_res->num_rows > 0) {
    while ($row = $unassigned_res->fetch_assoc()) {
        $unassigned_machines[] = $row['machine_id'];
    }
}

$sidebar_paths = [
  'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
  'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
  'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
  'Technician' => __DIR__ . '/../Technician/SidebarTechnician.php',
];

$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

$sidebar_css_paths = [
  'Admin'      => '/factory_monitoring/admin/assets/css/index.css',
  'Manager'    => '/factory_monitoring/Manager/assets/css/Sidebar.css',
  'Operator'   => '/Operator/assets/css/SidebarOperator.css',
  'Technician' => '/Technician/assets/css/sidebar_technician.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];

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
  <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
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
      transition: all 0.3s ease;
    }

    .sidebar {
      width: 250px;
      min-width: 250px;
      position: fixed;
      top: 0;
      left: 0;
      height: 100vh;
      background: #ffffff;
      box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
      z-index: 1000;
      transition: all .3s ease;
      overflow-y: auto;
    }

    .status-filter {
      display: flex !important;
      flex-direction: row !important;
      gap: 12px;
      flex-wrap: nowrap;
      overflow-x: auto;
      margin-bottom: 20px;
      padding: 10px 0;
    }

    .status-filter::-webkit-scrollbar {
      display: none;
    }

    .btn-filter {
      white-space: nowrap;
      padding: 8px 22px;
      border-radius: 30px;
      border: 1px solid #dee2e6;
      background-color: #fff;
      color: #6c757d;
      font-size: 14px;
      cursor: pointer;
      transition: all 0.3s ease;
    }

    .btn-filter.btn-all.active {
      background-color: #0d6efd;
      color: white;
      border-color: #0d6efd;
    }

    .btn-filter.btn-running.active {
      background-color: #28a745;
      color: white;
      border-color: #28a745;
    }

    .btn-filter.btn-warning.active {
      background-color: #ffc107;
      color: #212529;
      border-color: #ffc107;
    }

    .btn-filter.btn-danger-custom.active {
      background-color: #fd7e14;
      color: white;
      border-color: #fd7e14;
    }

    .btn-filter.btn-stopped.active {
      background-color: #dc3545;
      color: white;
      border-color: #dc3545;
    }

    .btn-filter:hover:not(.active) {
      background-color: #f8f9fa;
      border-color: #adb5bd;
    }

    @keyframes alert-vibrate {
        0%, 100% { transform: rotate(0deg); }
        10%, 30%, 50%, 70%, 90% { transform: translate(-1px, -1px) rotate(-3deg); }
        20%, 40%, 60%, 80% { transform: translate(1px, 1px) rotate(3deg); }
    }

    .unassigned-alert-badge {
        position: absolute;
        top: -12px;
        right: -12px;
        z-index: 10;

        background-color: #dc3545; 
        border: 2px solid #ffffff; 
        box-shadow: 0 4px 8px rgba(0,0,0,0.3); 
        color: white;
        border-radius: 50%;

        width: 25px;
        height: 25px;

        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 12px;
    }

    /* 3. สไตล์ Animation สั่นวนลูป (ทำงานตลอด) */
    .vibrating-alert {
        animation: alert-vibrate 0.7s infinite;
        animation-delay: 1.5s;
    }

    
    .unassigned-alert-badge:hover {
        animation-play-state: paused;
        cursor: pointer;
    }

    @media (max-width: 992px) {
      .dashboard {
        margin-left: 0;
        padding: 15px;
        border-radius: 0;
        padding-top: 60px;
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
  <div class="sidebar-wrapper">
    <?php include $sidebar_file; ?>
  </div>
  <section class="main">
    <div class="dashboard">
      <h2 class="dashboard-title">รายการเครื่องจักร</h2>

      <div class="status-filter">
        <button onclick="filterStatus('all', this)" class="btn-filter btn-all active">เครื่องจักรทั้งหมด</button>
        <button onclick="filterStatus('กำลังทำงาน', this)" class="btn-filter btn-running">กำลังทำงาน</button>
        <button onclick="filterStatus('ผิดปกติ', this)" class="btn-filter btn-warning">ผิดปกติ</button>
        <button onclick="filterStatus('อันตราย', this)" class="btn-filter btn-danger-custom">อันตราย</button>
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
            <div class="machine-card position-relative"
              data-mac-address="<?php echo htmlspecialchars($m['mac_address']); ?>"
              onclick="location.href='../dashboard/Dashboard.php?id=<?php echo $m['machine_id']; ?>'">

              <?php if (in_array($m['machine_id'], $unassigned_machines)): ?>
                <span class="unassigned-alert-badge vibrating-alert" 
                      title="มีแผนซ่อมบำรุงที่ยังไม่มีผู้รับผิดชอบ">
                    </span>
              <?php endif; ?>

              <img src="<?php echo $m['photo_url']; ?>" alt="รูปเครื่องจักร">
              <div class="machine-name"><?php echo htmlspecialchars($m['name']); ?></div>
              <div class="machine-id">ID: <?php echo htmlspecialchars($m['machine_id']); ?></div>

              <div class="machine-status" id="status-<?php echo $m['machine_id']; ?>">
                กำลังตรวจสอบ...
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
  <script src="/machine_list/js/machine.js"></script>
  <script src="/dashboard/dashboard.js"></script>
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