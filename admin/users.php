<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

$page = 'dashboard';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/admin/assets/css/users.css">
    <link rel="stylesheet" href="/admin/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        @media (max-width: 992px) {
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
                /* ใช้ Flexbox */
                flex-direction: row !important;
                /* บังคับเรียงแนวนอน (ซ้ายไปขวา) */
                align-items: center !important;
                /* จัดให้อยู่กึ่งกลางแนวตั้ง */
                justify-content: flex-start !important;
                /* ชิดซ้าย */
                text-align: left !important;
                /* ข้อความชิดซ้าย */
                padding: 10px 20px !important;
                /* เพิ่มระยะห่างรอบๆ ให้กดง่ายขึ้น */
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

    <div class="dashboard-container">
        <div class="sidebar-wrapper">
            <?php include 'SidebarAdmin.php'; ?>
        </div>

        <div class="dashboard">
            <div class="main-content">
                <h2>จัดการผู้ใช้งาน</h2>

                <!-- Role Filter -->
                <div class="role-filter">
                    <button onclick="filterRole('all')" class="btn">All</button>
                    <button onclick="filterRole('Admin')" class="btn">Admin</button>
                    <button onclick="filterRole('Manager')" class="btn">Manager</button>
                    <button onclick="filterRole('Operator')" class="btn">Operator</button>
                    <button onclick="filterRole('Technician')" class="btn">Technician</button>
                </div>
                <button class="btn btn-success mb-3" onclick="openAddModal()">เพิ่มสมาชิก</button>

                <!-- Search -->
                <input type="text" id="searchInput" class="form-control mb-3" placeholder="ค้นหา username/email/phone...">

                <!-- Users Table -->
                <table class="user-table table table-striped">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Profile</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        require "../config.php";
                        $sql = "SELECT * FROM users ORDER BY user_id ASC";
                        $result = $conn->query($sql);

                        while ($row = $result->fetch_assoc()) {
                            
                            $serverPath = __DIR__ . '/uploads/' . $row['profile_image'];

                            if (!file_exists($serverPath) || empty($row['profile_image'])) {

                                
                                $profileImage = '/admin/uploads/default.png';
                            } else {

                                
                                $profileImage = '/admin/uploads/' . $row['profile_image'];
                            }

                            echo '<tr class="user-row" data-role="' . $row['role'] . '">

                        <td>' . $row['user_id'] . '</td>
                        <td>
                            <img src="' . $profileImage . '" 
                                style="width:45px; height:45px; border-radius:50%; object-fit:cover;">
                        </td>
                        <td>' . $row['username'] . '</td>
                        <td>' . $row['email'] . '</td>
                        <td>' . $row['phone'] . '</td>
                        <td>' . $row['role'] . '</td>
                        <td>' . $row['created_at'] . '</td>
                        <td>
                            <div class="action-btns">
                                <button class="btn btn-sm btn-primary" onclick=\'openEditModal(' . json_encode($row) . ')\'>
                                    <i class="fa fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteUser(\'' . $row['user_id'] . '\')">
                                    <i class="fa fa-trash"></i> Delete
                                </button>
                            </div>
                        </td>
                        </tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'users_modals.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="assets/js/SidebarAdmin.js"></script>
    <script src="assets/js/users.js"></script>
    <script>
        $(document).ready(function() {
            // ตรวจสอบค่า 'role' จาก URL (Query String)
            const urlParams = new URLSearchParams(window.location.search);
            const roleFilter = urlParams.get('role');

            if (roleFilter) {
                // เรียกใช้ฟังก์ชันกรองข้อมูลที่มีอยู่แล้วใน assets/js/users.js
                if (roleFilter === 'all') {
                    filterRole('all');
                } else {
                    filterRole(roleFilter);
                }

                // (ทางเลือก) ปรับสถานะปุ่ม Filter ให้แสดงผลว่าเลือก Role นั้นอยู่
                $(".role-filter .btn").each(function() {
                    if ($(this).text().trim() === roleFilter || (roleFilter === 'all' && $(this).text().trim() === 'All')) {
                        $(this).addClass("btn-primary text-white").siblings().removeClass("btn-primary text-white");
                    }
                });
            }
        });
    </script>
</body>

</html>