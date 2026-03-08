<?php
include "../config.php";
session_start();

$user_role = $_SESSION['role'] ?? 'Operator';
$sidebar_paths = [
    'Admin'    => __DIR__ . '/../admin/SidebarAdmin.php',
    'Manager'  => __DIR__ . '/../Manager/partials/SidebarManager.php',
    'Operator' => __DIR__ . '/../Operator/SidebarOperator.php',
];
// เลือกไฟล์
$sidebar_file = $sidebar_paths[$user_role] ?? $sidebar_paths['Operator'];

$sidebar_css_paths = [
    'Admin'      => '/factory_monitoring/admin/assets/css/index.css',
    'Manager'    => '/factory_monitoring/Manager/assets/css/Sidebar.css',
    'Operator'   => '/factory_monitoring/Operator/assets/css/SidebarOperator.css',
];
$current_sidebar_css = $sidebar_css_paths[$user_role] ?? $sidebar_css_paths['Operator'];
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการเครื่องจักร</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="/factory_monitoring/addmachine/machine.css">
    <link rel="stylesheet" href="<?php echo $current_sidebar_css; ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        @media (max-width: 992px) {
      .dashboard {
        margin-left: 0;
        padding: 15px;
        border-radius: 0;
        padding-top: 0px;
      }

      .main {
        flex-direction: column;
      }

      .sidebar-wrapper * {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
      }

      .sidebar-wrapper a,
      .sidebar-wrapper .nav-link {
        display: flex !important;
        flex-direction: row !important;
        align-items: center !important;
        justify-content: flex-start !important;
        text-align: left !important;
        padding: 10px 20px !important;
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

      .repair-history-container {
        width: 100%;
        padding: 60px 15px 15px;
      }

      .btn-hamburger {
        display: flex;
        position: fixed;
        top: 15px;
        left: 15px;
        width: 35px;
        height: 35px;
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
    <div class="btn-hamburger" onclick="document.querySelector('.sidebar-wrapper').classList.toggle('active')">
        <i class="fa-solid fa-bars"></i>
    </div>

    <section class="main">
        <div class="sidebar-wrapper">
            <?php include $sidebar_file; ?>
        </div>
        <div class="dashboard">
            <div class="container my-5">
                <div class="card shadow-lg border-0">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 class="mb-0">เพิ่มข้อมูลเครื่องจักรใหม่</h2>
                    </div>
                    <div class="card-body p-4">
                        <form action="/factory_monitoring/addmachine/machine_save.php" method="POST" enctype="multipart/form-data" class="row g-3">
                            <div class="col-md-6">
                                <label for="machine_id" class="form-label">Machine ID <span class="text-danger">*</span>:</label>
                                <input type="text" id="machine_id" name="machine_id" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="mac_address" class="form-label">MAC Address <span class="text-danger">*</span>:</label>
                                <input type="text" id="mac_address" name="mac_address" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label for="name" class="form-label">Name:</label>
                                <input type="text" id="name" name="name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="model" class="form-label">Model:</label>
                                <input type="text" id="model" name="model" class="form-control">
                            </div>

                            <div class="col-6">
                                <label for="installed_at" class="form-label">Installed At:</label>
                                <input type="date" id="installed_at" name="installed_at" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label for="location" class="form-label">Location:</label>
                                <input type="text" id="location" name="location" class="form-control">
                            </div>

                            <div class="col-md-4">
                                <label for="amp" class="form-label">Amp:</label>
                                <input type="number" step="0.01" id="amp" name="amp" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="hp" class="form-label">HP:</label>
                                <input type="number" step="0.01" id="hp" name="hp" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label for="rpm" class="form-label">RPM:</label>
                                <input type="number" step="0.01" id="rpm" name="rpm" class="form-control">
                            </div>
                            <!--รูปเครื่องจักร-->
                            <div class="col-12 border-top pt-3 mt-3">
                                <label class="form-label d-block">Photo:</label>

                                <div class="image-upload-area text-center"> <!-- เพิ่ม text-center -->
                                    <div class="live-preview-container mb-2">
                                        <img id="image-preview" src="#" alt="Image Preview" style="display: none;" class="uploaded-photo-preview">
                                    </div>
                                    <span id="file-name" class="file-name text-muted">ยังไม่ได้เลือกไฟล์</span>
                                </div>

                                <div class="text-center mt-3">
                                    <input type="file" id="photo" name="photo" accept="image/*" class="file-input">
                                    <label for="photo" class="custom-upload-button">
                                        UPLOAD <i class="fas fa-upload"></i>
                                    </label>
                                </div>
                                <small class="text-muted d-block text-center mt-2">*รองรับไฟล์รูปภาพเท่านั้น (สูงสุด 5MB)</small>
                            </div>

                            <!--datasheet-->
                            <div class="col-12 border-top pt-3 mt-3">
                                <label class="form-label">Datasheet (PDF หรือเอกสารอื่นๆ):</label>

                                <div class="live-preview-container mb-2">
                                    <span id="datasheet-name" class="file-name text-muted">ยังไม่ได้เลือกไฟล์</span>
                                </div>

                                <div class="text-center">
                                    <input type="file" id="datasheet" name="datasheet"
                                        accept=".pdf,.doc,.docx,.xls,.xlsx,.txt"
                                        class="file-input">
                                    <label for="datasheet" class="custom-upload-button">
                                        UPLOAD DATASHEET <i class="fas fa-file-upload"></i>
                                    </label>
                                </div>

                                <small class="text-muted d-block text-center mt-2">
                                    *รองรับไฟล์ PDF หรือเอกสารเท่านั้น (สูงสุด 10MB)
                                </small>
                            </div>


                            <div class="col-12 text-center mt-3 d-flex justify-content-center">
                                <button type="submit" class="btn btn-success btn-lg" style="min-width: 250px;">
                                    <i class="fas fa-save me-2"></i> บันทึกข้อมูลเครื่องจักร
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
                const fileNameSpan = document.getElementById('file-name');
                const imagePreview = document.getElementById('image-preview');

                if (this.files.length > 0) {
                    const file = this.files[0];
                    fileNameSpan.textContent = file.name;
                    fileNameSpan.classList.remove('text-muted');
                    fileNameSpan.classList.add('text-success');

                    const reader = new FileReader();
                    reader.onload = function(e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
                    fileNameSpan.classList.remove('text-success');
                    fileNameSpan.classList.add('text-muted');
                    imagePreview.style.display = 'none';
                    imagePreview.src = '#';
                }
            });

            document.getElementById('datasheet').addEventListener('change', function() {
                const fileNameSpan = document.getElementById('datasheet-name');

                if (this.files.length > 0) {
                    const file = this.files[0];
                    fileNameSpan.textContent = file.name;
                    fileNameSpan.classList.remove('text-muted');
                    fileNameSpan.classList.add('text-primary');
                } else {
                    fileNameSpan.textContent = 'ยังไม่ได้เลือกไฟล์';
                    fileNameSpan.classList.remove('text-primary');
                    fileNameSpan.classList.add('text-muted');
                }
            });
            // ตรวจสอบ URL parameters เพื่อแสดงป๊อปอัพ
            const urlParams = new URLSearchParams(window.location.search);

            // ถ้า URL มีคำว่า ?status=success
            if (urlParams.get('status') === 'success') {
                Swal.fire({
                    title: 'บันทึกสำเร็จ!',
                    text: 'เพิ่มข้อมูลเครื่องจักรใหม่เรียบร้อยแล้ว',
                    icon: 'success',
                    confirmButtonColor: '#198754'
                }).then(() => {
                    // ลบ parameter ออกจาก URL เพื่อไม่ให้ป๊อปอัพเด้งซ้ำตอนกด Refresh
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }

            // ถ้า URL มีคำว่า ?status=error
            if (urlParams.get('status') === 'error') {
                Swal.fire({
                    title: 'เกิดข้อผิดพลาด!',
                    text: urlParams.get('message') || 'ไม่สามารถบันทึกข้อมูลได้',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
            }

            // 2. กรณีข้อมูลซ้ำ (Error)
            const errorType = urlParams.get('error');
            if (errorType) {
                let msg = 'เกิดข้อผิดพลาดในการบันทึกข้อมูล';
                if (errorType === 'duplicate_id') msg = ' Machine ID นี้มีในระบบแล้ว!';
                if (errorType === 'duplicate_mac') msg = ' MAC Address นี้มีในระบบแล้ว!';
                if (errorType === 'invalid_file') msg = ' ไฟล์ที่อัปโหลดไม่ถูกต้อง!';

                Swal.fire({
                    title: 'ข้อมูลซ้ำ!',
                    text: msg,
                    icon: 'error',
                    confirmButtonColor: '#d33'
                }).then(() => {
                    // ลบ error ออกจาก URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                });
            }

            $(document).ready(function() {
                // เมื่อคลิกที่ลิงก์ใน sidebar
                $('.sidebar-wrapper a').click(function() {
                    if (!$(this).hasClass('dropdown-toggle')) {
                        $('.sidebar-wrapper').removeClass('active');
                        $('.sidebar-overlay').removeClass('active'); // เพิ่มบรรทัดนี้
                    }
                });

                // ปรับแต่งปุ่ม Hamburger ให้เปิด Overlay ด้วย
                $('.btn-hamburger').click(function() {

                    document.querySelector('.sidebar-overlay').classList.toggle('active');
                });
            });
        </script>
</body>

</html>