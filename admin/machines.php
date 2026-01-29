<?php
session_start();

// เช็กล็อกอิน
if (!isset($_SESSION['user_id'])) {
  header("Location: /factory_monitoring/login.php");
  exit();
}

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

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>รายการเครื่องจักร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/machine_list.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />

</head>

<body>

  <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

  <section class="main">

    <?php include __DIR__ . '/SidebarAdmin.php'; ?>

    <div class="dashboard">
      <h2 class="dashboard-title">รายการเครื่องจักร</h2>

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
  <script>
    document.getElementById('searchInput').addEventListener('input', function() {
      const keyword = this.value.toLowerCase();
      const cards = document.querySelectorAll('.machine-card');

      cards.forEach(card => {
        const name = card.querySelector('.machine-name').textContent.toLowerCase();
        const id = card.querySelector('.machine-id').textContent.toLowerCase();
        const status = card.querySelector('.machine-status').textContent.toLowerCase();
        const location = card.querySelector('.machine-location').textContent.toLowerCase();

        if (
          name.includes(keyword) ||
          id.includes(keyword) ||
          status.includes(keyword) ||
          location.includes(keyword)
        ) {
          card.style.display = "block"; // แสดงเมื่อเจอผลลัพธ์
        } else {
          card.style.display = "none"; // ซ่อนเมื่อไม่ตรง
        }
      });
    });
    //สถานะเครื่องจักร
    async function updateMachineStatus(machineId) {
      try {
        const res = await fetch(`http://192.168.1.75:5000/api/last-power/${machineId}`);
        const data = await res.json();

        const power = data.power ?? 0;
        const statusElement = document.getElementById(`status-${machineId}`);

        let statusText = "";
        let color = "";

        if (power > 0) { // 
          statusText = `กำลังทำงาน (${power} W)`;
          color = "#28a745"; // เขียว
        } else {
          statusText = `หยุดทำงาน (${power} W)`;
          color = "#dc3545"; // แดง
        }

        statusElement.textContent = `สถานะ: ${statusText}`;
        statusElement.style.color = color;

      } catch (error) {
        console.error("Error fetching power:", error);
        const statusElement = document.getElementById(`status-${machineId}`);
        if (statusElement) {
          statusElement.textContent = "สถานะเครื่องจักร: ไม่พบข้อมูล";
          statusElement.style.color = "#6c757d";
        }
      }
    }

    document.addEventListener("DOMContentLoaded", () => {
      const machineCards = document.querySelectorAll(".machine-card");

      machineCards.forEach(card => {
        const idText = card.querySelector(".machine-id").textContent;
        const machineId = idText.replace("ID:", "").trim();
        updateMachineStatus(machineId);

        // อัปเดตทุก 5 วินาที (optional)
        setInterval(() => updateMachineStatus(machineId), 5000);
      });
    });
  </script>
</body>

</html>