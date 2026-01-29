<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /factory_monitoring/login.php");
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
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/users.css">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
</head>

<body>
    <div class="dashboard-container">
        <?php include 'SidebarAdmin.php'; ?>

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
                        // รูปจริงบน server
                        $serverPath = __DIR__ . '/uploads/' . $row['profile_image'];

                        if (!file_exists($serverPath) || empty($row['profile_image'])) {

                            // default.png ต้องอยู่ใน admin/uploads !!
                            $profileImage = '/factory_monitoring/admin/uploads/default.png';
                        } else {

                            // รูปผู้ใช้ปกติ
                            $profileImage = '/factory_monitoring/admin/uploads/' . $row['profile_image'];
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
</body>

</html>