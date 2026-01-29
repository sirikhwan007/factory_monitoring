<?php
include "../config.php";

if (!isset($_GET['id'])) {
  die("ไม่พบเครื่องจักรที่เลือก");
}

$machine_id = $_GET['id'] ?? null;

// ดึงข้อมูลเครื่องจักรจาก DB
$stmt = $conn->prepare("SELECT * FROM machines WHERE machine_id = ?");
$stmt->bind_param("i", $machine_id);
$stmt->execute();
$result = $stmt->get_result();
$machine = $result->fetch_assoc();

if (!$machine) {
  die("ไม่พบข้อมูลเครื่องจักร");
}
// ดึงไฟล์ Datasheet จากตาราง machine_documents
$stmtDoc = $conn->prepare("SELECT file_path FROM machine_documents WHERE machine_id = ?");
$stmtDoc->bind_param("i", $machine_id);
$stmtDoc->execute();
$resDoc = $stmtDoc->get_result();
$doc = $resDoc->fetch_assoc();
$stmtDoc->close();

$current_datasheet = $doc['file_path'] ?? "";

?>

<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>แก้ไขข้อมูลเครื่องจักร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
  <link rel="stylesheet" href="/factory_monitoring/editmachine/edit.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>

  <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

  <section class="main">

    <?php include __DIR__ . '/../admin/SidebarAdmin.php'; ?>

    <div class="dashboard">
      <div class="container my-5">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-warning text-white text-center">
            <h2 class="mb-0">แก้ไขข้อมูลเครื่องจักร</h2>
          </div>
          <div class="card-body p-4">
            <form action="machine_update.php" method="POST" enctype="multipart/form-data" class="row g-3">

              <input type="hidden" name="machine_id_old" value="<?= $machine['machine_id'] ?>">
              <input type="hidden" name="old_photo" value="<?= $machine['photo_url'] ?>">


              <div class="col-md-6">
                <label class="form-label">Machine ID:</label>
                <input type="text" name="machine_id" class="form-control" value="<?= $machine['machine_id'] ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">MAC Address:</label>
                <input type="text" name="mac_address" class="form-control" value="<?= $machine['mac_address'] ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Name:</label>
                <input type="text" name="name" class="form-control" value="<?= $machine['name'] ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Model:</label>
                <input type="text" name="model" class="form-control" value="<?= $machine['model'] ?>">
              </div>

              <div class="col-6">
                <label class="form-label">Installed At:</label>
                <input type="date" name="installed_at" class="form-control" value="<?= $machine['installed_at'] ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Location:</label>
                <input type="text" name="location" class="form-control" value="<?= $machine['location'] ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label">Amp:</label>
                <input type="number" step="0.01" name="amp" class="form-control" value="<?= $machine['amp'] ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label">HP:</label>
                <input type="number" step="0.01" name="hp" class="form-control" value="<?= $machine['hp'] ?>">
              </div>

              <div class="col-md-4">
                <label class="form-label">RPM:</label>
                <input type="number" step="0.01" name="rpm" class="form-control" value="<?= $machine['rpm'] ?>">
              </div>

              <div class="d-flex justify-content-center align-items-start gap-5">
                <!-- รูปปัจจุบัน -->
                <div class="text-center">
                  <p class="fw-bold mb-2">รูปปัจจุบัน</p>
                  <?php if (!empty($machine['photo_url'])): ?>
                    <img src="/factory_monitoring/<?= $machine['photo_url'] ?>" style="max-width:200px;" class="img-thumbnail">
                  <?php else: ?>
                    <p class="text-muted">ไม่มีรูปภาพ</p>
                  <?php endif; ?>
                </div>

                <!-- รูปใหม่ -->
                <div class="text-center">
                  <p class="fw-bold mb-2">รูปล่าสุด</p>
                  <img id="image-preview" src="#" alt="Preview"
                    style="max-width:200px; display:none;" class="img-thumbnail mb-2">
                </div>
              </div>

              <!-- ปุ่มอัปโหลดรูป -->
              <div class="col-12 text-center mt-3">
                <label class="form-label d-block mb-2">เปลี่ยนรูปภาพ:</label>

                <div class="d-flex justify-content-center align-items-center gap-3">
                  <input type="file" id="photo" name="photo" accept="image/*" class="d-none">
                  <label for="photo" class="custom-upload-button">
                    UPLOAD <i class="fas fa-upload"></i>
                  </label>
                </div>
              </div>

              <!-- Datasheet -->
              <input type="hidden" name="old_datasheet" value="<?= $current_datasheet ?>">

              <div class="col-12 mt-4">
                <label class="form-label fw-bold">Datasheet (PDF/DOCX/XLSX):</label>

                <div class="upload-container text-center">
    <!-- ไฟล์ปัจจุบัน -->
    <?php if (!empty($current_datasheet)): ?>
      <p>
        ไฟล์ปัจจุบัน:
        <a href="/factory_monitoring/<?= $current_datasheet ?>" target="_blank">
          <?= basename($current_datasheet) ?>
        </a>
      </p>
    <?php else: ?>
      <p class="text-muted">ไม่มีไฟล์ Datasheet</p>
    <?php endif; ?>

    <!-- ไฟล์ล่าสุด (ใหม่) -->
    <p id="datasheet-name" class="file-name mt-2 mb-2">
        <?php if(!empty($new_datasheet_name ?? '')): ?>
            ไฟล์ล่าสุด: <?= htmlspecialchars($new_datasheet_name) ?>
        <?php endif; ?>
    </p>

    <!-- ปุ่มอัปโหลด -->
    <input type="file" id="datasheet" name="datasheet"
           accept=".pdf,.doc,.docx,.xls,.xlsx" class="d-none">

    <label for="datasheet" class="custom-upload-button mt-2">
        UPLOAD DATASHEET <i class="fas fa-file-upload"></i>
    </label>
</div>

                <div class="col-12 mt-4 d-flex justify-content-center">
                  <button type="submit" class="btn btn-warning btn-lg">
                    <i class="fas fa-save me-2"></i> บันทึกการแก้ไข
                  </button>
                </div>

            </form>

          </div>
        </div>
      </div>
    </div>

    <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>
    <script>
      document.getElementById('photo').addEventListener('change', function() {
        const preview = document.getElementById('image-preview');
        if (this.files && this.files[0]) {
          const reader = new FileReader();
          reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
          }
          reader.readAsDataURL(this.files[0]);
        } else {
          preview.src = '#';
          preview.style.display = 'none';
        }
      });

      // แสดงชื่อไฟล์ที่เลือก
      document.getElementById("datasheet").addEventListener("change", function() {
        const fileName = this.files.length > 0 ? this.files[0].name : "";
        document.getElementById("datasheet-name").textContent = fileName;
      });


      const fileInput = document.getElementById('datasheet');
const fileNameDisplay = document.getElementById('datasheet-name');

fileInput.addEventListener('change', function() {
    if (this.files && this.files.length > 0) {
        fileNameDisplay.textContent = 'ไฟล์ล่าสุด: ' + this.files[0].name;
    } else {
        fileNameDisplay.textContent = '';
    }
});

    </script>
</body>

</html>