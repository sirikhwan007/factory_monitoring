<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../config.php';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>พนักงานหน้างาน | Factory Monitoring</title>
    <link rel="stylesheet" href="/factory_monitoring/Manager/assets/css/Sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            background: #f4f6f9;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .main-content h2 {
            margin-bottom: 20px;
            font-weight: 600;
            color: #333;
        }

        .role-filter {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .role-filter .btn {
            padding: 7px 16px;
            border-radius: 20px;
            border: none;
            background: #eaeaea;
            color: #333;
            cursor: pointer;
            font-size: 14px;
            transition: 0.2s;
        }

        .role-filter .btn:hover {
            background: #6f1e51;
            color: #fff;
        }

        .role-filter .btn.active {
            background: #6f1e51;
            color: #fff;
        }

        /* Search */
        .search-box {
            width: 100%;
            max-width: 350px;
            padding: 8px 14px;
            border-radius: 20px;
            border: 1px solid #ccc;
            margin-bottom: 20px;
            transition: 0.2s;
        }

        .search-box:focus {
            outline: none;
            border-color: #6f1e51;
            box-shadow: 0 0 0 2px rgba(111, 30, 81, 0.15);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 14px 12px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 14px;
        }

        th {
            background: #f5f6fa;
            font-weight: 600;
            color: #444;
        }

        tbody tr {
            transition: 0.2s;
        }

        tbody tr:hover {
            background: #fafafa;
        }

        /* ===== Profile Image ===== */
        .profile-img {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #ddd;
        }

        /* ===== Role Badge (optional) ===== */
        .role-badge {
            padding: 4px 12px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 500;
            color: #fff;
            display: inline-block;
        }

        .role-operator {
            background: #2980b9;
        }

        .role-technician {
            background: #27ae60;
        }


        @media (max-width: 992px) {
            .main-content {
                margin-left: 0;
                padding-top: 80px;
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
                /* เลื่อนออกมาแสดง */
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
        <?php include 'partials/SidebarManager.php'; ?>
    </div>

    <div class="main-content">
        <h2>รายชื่อพนักงานหน้างาน</h2>
        <div class="role-filter">
            <button class="btn active" onclick="filterRole('all')">All</button>
            <button class="btn" onclick="filterRole('operator')">Operator</button>
            <button class="btn" onclick="filterRole('technician')">Technician</button>
        </div>

        <!-- Search -->
        <input type="text"
            id="searchInput"
            class="search-box"
            placeholder="ค้นหา username / email / phone...">


        <?php
        $sql = "SELECT username, email, phone, role, profile_image, created_at
        FROM users
        WHERE role IN ('operator','technician')";

        $result = $conn->query($sql);
        ?>

        <table>
            <thead>
                <tr>
                    <th>รูป</th>
                    <th>ชื่อ</th>
                    <th>Email</th>
                    <th>ตำแหน่ง</th>
                    <th>เบอร์โทร</th>
                    <th>วันที่สร้าง</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()):
                    $image = !empty($row['profile_image']) ? $row['profile_image'] : 'default.png';
                ?>
                    <tr>
                        <td>
                            <img src="/factory_monitoring/admin/uploads/<?= htmlspecialchars($image) ?>"
                                class="profile-img"
                                onerror="this.src='/admin/uploads/default.png'">
                        </td>
                        <td><?= htmlspecialchars($row['username']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td><?= ucfirst(htmlspecialchars($row['role'])) ?></td>
                        <td><?= htmlspecialchars($row['phone']) ?></td>
                        <td><?= $row['created_at'] ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
        let currentRole = 'all';

        function filterRole(role) {
            currentRole = role;

            document.querySelectorAll('.role-filter .btn')
                .forEach(btn => btn.classList.remove('active'));

            event.target.classList.add('active');

            applyFilters();
        }

        document.getElementById('searchInput')
            .addEventListener('keyup', applyFilters);

        function applyFilters() {
            const keyword = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                const roleCell = row.children[3].innerText.toLowerCase(); // column role

                const matchRole = (currentRole === 'all' || roleCell === currentRole);
                const matchSearch = text.includes(keyword);

                row.style.display = (matchRole && matchSearch) ? '' : 'none';
            });
        }
    </script>


</body>

</html>