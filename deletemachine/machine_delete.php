<?php
include "../config.php";
session_start();

// ตรวจสอบ Role ของผู้ใช้งาน (ดึงจาก session ที่คุณตั้งไว้ตอน Login)
$user_role = $_SESSION['role'] ?? 'Operator';

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

$sidebar_paths = [
  'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
  'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
  'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];

// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

if (!$machine) {
  die("ไม่พบข้อมูลเครื่องจักร");
}
?>

<!DOCTYPE html>

<html lang="th">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ลบเครื่องจักร</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
  <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
  <link rel="stylesheet" href="/factory_monitoring/Operator/assets/css/SidebarOperator.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .dashboard {
      margin-left: 250px;
    }
  </style>
</head>

<body>

  <div class="btn-hamburger"><i class="fa-solid fa-bars"></i></div>

  <section class="main">
    <?php include $sidebar_file; ?>
    <div class="dashboard">
      <div class="container my-5">
        <div class="card shadow-lg border-0">
          <div class="card-header bg-danger text-white text-center">
            <h2 class="mb-0">ยืนยันการลบเครื่องจักร</h2>
          </div>
          <div class="card-body p-4">
            <form action="/factory_monitoring/deletemachine/machine_save_delete.php" method="POST" onsubmit="return confirmDelete();">


              <input type="hidden" name="machine_id" value="<?= $machine['machine_id'] ?>">

              <!-- รูปเครื่องจักร -->
              <div class="text-center mb-4">
                <?php if (!empty($machine['photo_url'])): ?>
                  <img src="/factory_monitoring/<?= $machine['photo_url'] ?>" style="max-width:200px;" class="img-thumbnail">
                <?php else: ?>
                  <p class="text-muted">ไม่มีรูปภาพ</p>
                <?php endif; ?>
              </div>

              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Machine ID:</label>
                  <input type="text" class="form-control" value="<?= $machine['machine_id'] ?>" readonly>
                </div>

                <div class="col-md-6">
                  <label class="form-label">MAC Address:</label>
                  <input type="text" class="form-control" value="<?= $machine['mac_address'] ?>" readonly>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Name:</label>
                  <input type="text" class="form-control" value="<?= $machine['name'] ?>" readonly>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Model:</label>
                  <input type="text" class="form-control" value="<?= $machine['model'] ?>" readonly>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Installed At:</label>
                  <input type="date" class="form-control" value="<?= $machine['installed_at'] ?>" readonly>
                </div>

                <div class="col-md-6">
                  <label class="form-label">Location:</label>
                  <input type="text" class="form-control" value="<?= $machine['location'] ?>" readonly>
                </div>

                <div class="col-md-4">
                  <label class="form-label">Amp:</label>
                  <input type="number" step="0.01" class="form-control" value="<?= $machine['amp'] ?>" readonly>
                </div>

                <div class="col-md-4">
                  <label class="form-label">HP:</label>
                  <input type="number" step="0.01" class="form-control" value="<?= $machine['hp'] ?>" readonly>
                </div>

                <div class="col-md-4">
                  <label class="form-label">RPM:</label>
                  <input type="number" step="0.01" class="form-control" value="<?= $machine['rpm'] ?>" readonly>
                </div>

                <!-- ช่องกรอกรายละเอียด -->
                <div class="col-12 mt-3">
                  <label class="form-label">รายละเอียด:</label>
                  <textarea name="delete_reason" class="form-control" rows="4" placeholder="กรอกเหตุผลการลบเครื่องจักร..." required></textarea>
                </div>
              </div>

              <div class="col-12 mt-4 d-flex justify-content-center gap-3">
                <a href="/factory_monitoring/dashboard/Dashboard.php?id=<?= $machine['machine_id'] ?>" class="btn btn-secondary btn-lg">
                  <i class="fas fa-arrow-left me-2"></i> ยกเลิก
                </a>
                <button type="button" id="delete-btn" class="btn btn-danger btn-lg">
                  <i class="fas fa-trash me-2"></i> ลบเครื่องจักร
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>
    </div>
  </section>

  <script src="/factory_monitoring/admin/SidebarAdmin.js"></script>
  <script>
document.getElementById('delete-btn').addEventListener('click', function() {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: "ข้อมูลเครื่องจักรนี้จะหายไปจากระบบ และไม่สามารถกู้คืนได้!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33', // สีแดงสำหรับปุ่มลบ
        cancelButtonColor: '#6c757d', // สีเทาสำหรับยกเลิก
        confirmButtonText: '<i class="fas fa-trash"></i> ยืนยันการลบ',
        cancelButtonText: 'ยกเลิก',
        reverseButtons: true // สลับตำแหน่งปุ่มให้ "ยกเลิก" อยู่ซ้าย "ลบ" อยู่ขวา
    }).then((result) => {
        if (result.isConfirmed) {
            // ถ้ากดยืนยัน ให้ทำการ Submit Form
            document.getElementById('delete-form').submit();
        }
    });
});
</script>

</body>

</html>