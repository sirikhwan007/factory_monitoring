<?php
session_start();
include __DIR__ . "/../config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /login.php");
  exit();
}

$user_role = $_SESSION['role'] ?? 'Operator';

$machine_id = $_GET['id'] ?? null;
if (!$machine_id) {
  die("ไม่พบเครื่องจักรที่เลือก");
}

$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();
if (!$machine) {
  die("ไม่พบข้อมูลเครื่องจักร");
}

$doc = null;
$q = $conn->prepare("SELECT file_path FROM machine_documents WHERE machine_id = ?");
$q->bind_param("s", $machine_id);
$q->execute();
$res = $q->get_result();
$doc = $res->fetch_assoc();
$q->close();

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
  'Operator'   => '/factory_monitoring/Operator/assets/css/SidebarOperator.css',
  'Technician' => '/factory_monitoring/Technician/assets/css/sidebar_technician.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Motor Monitoring Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/factory_monitoring/dashboard/dashboard.css">
  <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    @media (min-width: 993px) {
      .action-buttons-container {
        position: absolute;
        top: 0;
        right: 0;
        padding: 10px;
        z-index: 1060 !important;
      }
    }

    @media (max-width: 992px) {
      .dashboard {
        margin-left: 0;
        padding: 15px;
        border-radius: 0;
        width: 100%;
        padding-top: 60px;
      }

      .action-buttons-container {
        position: static;
        margin-top: 15px;
        display: block;
        width: 100%;
      }

      .action-buttons-container .dropdown .btn {
        width: 100%;
        padding: 8px;
      }

      .dropdown-menu {
        width: 100%;
        position: static !important;
        transform: none !important;
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

      .img-thumbnail {
        max-width: 80% !important;
        height: auto;
      }

      .d-flex.justify-content-center.gap-5 {
        flex-direction: column;
        align-items: center !important;
        gap: 1rem !important;
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
      <div id="dashboard-content">
        <div class="dashboard-header">
          Motor Dashboard
        </div>
        <div class="container my-4">
          <div class="card mb-3 shadow-sm p-3">
            <div class="row g-3 align-items-center">
              <div class="col-md-4 text-center" onclick="location.href='/machine_list/machine_detail.php?id=<?php echo $machine['machine_id']; ?>'" style="cursor: pointer;">
                <?php
                $imgSrc = !empty($machine['photo_url'])
                  ? $machine['photo_url']
                  : "/assets/default-machine.png";
                ?>
                <img src="<?php echo $imgSrc; ?>"
                  alt="รูปเครื่องจักร"
                  class="img-fluid rounded shadow-sm"
                  style="max-height: 200px; object-fit: cover;">
              </div>

              <div class="col-md-8 position-relative">
                <h4 class="mb-2"><?php echo $machine['name']; ?></h4>
                <p class="mb-1"><strong>รหัสเครื่องจักร:</strong> <?php echo $machine['machine_id']; ?></p>
                <p class="mb-1"><strong>สถานที่ติดตั้ง:</strong> <?php echo $machine['location']; ?></p>
                <p class="mb-1"><strong>รุ่น:</strong> <?php echo $machine['model']; ?></p>
                <p class="mb-1">
                  <strong>สถานะเครื่องจักร:</strong>
                  <span id="machine-status" class="badge bg-secondary">กำลังโหลด...</span>
                </p>
                <p class="mb-1">
                  <strong>สถานะการเชื่อมต่อ:</strong>
                  <span id="influx-status" class="badge bg-secondary">ตรวจสอบการเชื่อมต่อ...</span>
                </p>

                <div class="action-buttons-container ">
                  <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" style="font-size: 12px; padding: 4px 10px;">
                      <i class="fa-solid fa-gear"></i> จัดการเครื่องจักร
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuButton" style="min-width: 200px;">

                      <?php if ($user_role !== 'Operator' && $user_role !== 'Technician'): ?>
                        <li>
                          <a class="dropdown-item text-warning" href="/factory_monitoring/editmachine/machine_edit.php?id=<?= $machine['machine_id'] ?>">
                            <i class="fa-solid fa-pen-to-square"></i> แก้ไขข้อมูลเครื่องจักร
                          </a>
                        </li>
                        <li>
                          <a class="dropdown-item text-danger" href="/factory_monitoring/deletemachine/machine_delete.php?id=<?= $machine['machine_id'] ?>">
                            <i class="fa-solid fa-trash"></i> ลบเครื่องจักร
                          </a>
                        </li>
                        <li>
                          <hr class="dropdown-divider">
                        </li>
                      <?php endif; ?>

                      <?php if ($user_role !== 'Technician'): ?>
                        <li>
                          <a class="dropdown-item" href="/factory_monitoring/repair/report.php?machine_id=<?= $machine['machine_id'] ?>" style="color: #ff8c00;">
                            <i class="fa-solid fa-clipboard"></i> แจ้งซ่อม
                          </a>
                        </li>
                      <?php endif; ?>

                      <?php if ($user_role !== 'Operator' && $user_role !== 'Technician'): ?>
                        <li>
                          <a class="dropdown-item" href="/factory_monitoring/machine_list/maintenance_plan.php?machine_id=<?= urlencode($machine['machine_id']) ?>" style="color: #00CC99;">
                            <i class="fa-solid fa-calendar-check"></i> แผนซ่อมตามรอบ
                          </a>
                        </li>
                      <?php endif; ?>

                      <?php if ($doc): ?>
                        <li>
                          <a class="dropdown-item text-success" href="<?= $doc['file_path'] ?>" target="_blank">
                            <i class="fa-solid fa-file"></i> Datasheet
                          </a>
                        </li>
                      <?php endif; ?>

                      <li>
                        <hr class="dropdown-divider">
                      </li>
                      <li>
                        <button onclick="show24hHistory()" class="dropdown-item text-info">
                          <i class="fa-solid fa-clock-rotate-left"></i> ประวัติ 24 ชม.
                        </button>
                      </li>
                    </ul>
                  </div>
                </div>

              </div>

            </div>
          </div>
        </div>

        <!-- Card: Temperature -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="tempGauge"></canvas>
                <div class="value" id="temp">--</div>
              </div>
            </div>

            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Temperature (°C)</h5>
                <canvas id="tempChart"></canvas>
              </div>
            </div>

          </div>
        </div>

        <!-- Card: Vibration -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="vibGauge"></canvas>
                <div class="value" id="vib">--</div>
              </div>
            </div>
            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Vibration (%)</h5>
                <canvas id="vibChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Card: Voltage -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="voltGauge"></canvas>
                <div class="value" id="volt">--</div>
              </div>
            </div>
            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Voltage (V)</h5>
                <canvas id="voltChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Card: Current -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="currGauge"></canvas>
                <div class="value" id="curr">--</div>
              </div>
            </div>
            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Current (A)</h5>
                <canvas id="currChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Card: Power -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="powGauge"></canvas>
                <div class="value" id="pow">--</div>
              </div>
            </div>
            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Power (W)</h5>
                <canvas id="powChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Card: Energy -->
        <div class="card mb-3 shadow-sm">
          <div class="row g-0 align-items-center">
            <div class="col-auto p-3">
              <div class="gauge-container">
                <canvas id="energyGauge"></canvas>
                <div class="value" id="energy">--</div>
              </div>
            </div>
            <div class="col">
              <div class="card-body">
                <h5 class="card-title">Energy (Wh)</h5>
                <canvas id="energyChart"></canvas>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- JavaScript ภายนอก -->
  <script src="/factory_monitoring/dashboard/dashboard.js?v=<?php echo time(); ?>" defer></script>
  <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

  <script>
    // เพิ่มบรรทัดนี้เพื่อส่งค่า MAC Address ให้ JavaScript
    const MACHINE_MAC = "<?php echo $machine['mac_address']; ?>";
  </script>
</body>

</html>