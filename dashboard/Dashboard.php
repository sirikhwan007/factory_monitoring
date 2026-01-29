<?php
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: /factory_monitoring/login.php");
  exit();
}

// ตรวจสอบ Role ของผู้ใช้งาน (ดึงจาก session ที่คุณตั้งไว้ตอน Login)
$user_role = $_SESSION['role'] ?? 'Operator';


// รับค่า machine_id ที่ส่งมา
$machine_id = $_GET['id'] ?? null;

if (!$machine_id) {
  die("ไม่พบเครื่องจักรที่เลือก");
}

// เชื่อมต่อฐานข้อมูล
$conn = new mysqli("localhost", "root", "", "factory_monitoring");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ดึงข้อมูลเครื่องจักร
$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("s", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

if (!$machine) {
  die("ไม่พบข้อมูลเครื่องจักร");
}

// ดึงไฟล์ Datasheet
$doc = null;
$q = $conn->prepare("SELECT file_path FROM machine_documents WHERE machine_id = ?");
$q->bind_param("s", $machine_id);
$q->execute();
$res = $q->get_result();
$doc = $res->fetch_assoc();
$q->close();


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
  <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
  <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns/dist/chartjs-adapter-date-fns.bundle.min.js" defer></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
  <link rel="stylesheet" href="/factory_monitoring/dashboard/dashboard.css">
  <link rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
    crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>
  <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

  <section class="main">

    <?php include __DIR__ . '/../admin/SidebarAdmin.php'; ?>

    <div class="dashboard">
      <div id="dashboard-content">
        <div class="dashboard-header">
          Motor Dashboard
        </div>

        <!--เอาไว้แสดงข้อมูลเครื่องจักร-->
        <div class="container my-4">
          <div class="card mb-3 shadow-sm p-3">

            <div class="row g-3 align-items-center">

              <!-- รูปเครื่องจักร -->
              <div class="col-md-4 text-center">
                <?php
                // ตรวจสอบว่ามีข้อมูลชื่อไฟล์รูปภาพหรือไม่
                $imgSrc = !empty($machine['photo_url'])
                  ? "/factory_monitoring/" . $machine['photo_url']
                  : "/factory_monitoring/assets/default-machine.png";
                ?>
                <img src="<?php echo $imgSrc; ?>"
                  alt="รูปเครื่องจักร"
                  class="img-fluid rounded shadow-sm"
                  style="max-height: 200px; object-fit: cover;">
              </div>

              <!-- รายละเอียดเครื่องจักร -->
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

                <div class="action-buttons-container position-absolute top-0 end-0 d-flex flex-column align-items-end gap-2">

                  <?php if ($user_role !== 'Operator'): ?>
                    <a href="/factory_monitoring/editmachine/machine_edit.php?id=<?= $machine['machine_id'] ?>"
                      class="btn btn-warning btn-sm"
                      style="padding: 4px 8px; font-size: 12px; width: fit-content;">
                      <i class="fa-solid fa-pen-to-square"></i> แก้ไขข้อมูลเครื่องจักร
                    </a>

                    <a href="/factory_monitoring/deletemachine/machine_delete.php?id=<?= $machine['machine_id'] ?>"
                      class="btn btn-danger btn-sm"
                      style="padding: 4px 8px; font-size: 12px; width: fit-content;">
                      <i class="fa-solid fa-trash"></i> ลบเครื่องจักร
                    </a>
                  <?php endif; ?>

                  <a href="/factory_monitoring/repair/report.php?machine_id=<?= $machine['machine_id'] ?>"
                    class="btn btn-warning btn-sm"
                    style="padding: 4px 8px; font-size: 12px; background-color:#ff8c00; border-color:#ff8c00; width: fit-content;">
                    <i class="fa-solid fa-clipboard"></i> แจ้งซ่อม
                  </a>

                  <?php if ($doc): ?>
                    <a href="/factory_monitoring/<?= $doc['file_path'] ?>"
                      class="btn btn-success btn-sm"
                      style="padding: 4px 8px; font-size: 12px; width: fit-content;"
                      target="_blank">
                      <i class="fa-solid fa-file"></i> Datasheet
                    </a>
                  <?php endif; ?>

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
                <h5 class="card-title">Vibration (g)</h5>
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

      </div>
    </div>
    </div>

    <!-- JavaScript ภายนอก -->
    <script src="/factory_monitoring/dashboard/dashboard.js?v=<?php echo time(); ?>" defer></script>
    <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>

    <script>
      // เพิ่มบรรทัดนี้เพื่อส่งค่า MAC Address ให้ JavaScript
      const MACHINE_MAC = "<?php echo $machine['mac_address']; ?>";
    </script>
</body>

</html>