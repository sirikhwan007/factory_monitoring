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
    <title>พนักงานหน้างาน | Factory Monitoring</title>
    <link rel="stylesheet" href="/manager/assets/css/Sidebar.css">

    <style>
/* ===== Layout ===== */
body {
    background: #f4f6f9;
}

.main-content {
    margin-left: 260px;
    padding: 30px;
}

/* ===== Page Title ===== */
.main-content h2 {
    margin-bottom: 20px;
    font-weight: 600;
    color: #333;
}

/* ===== Filter + Search ===== */
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

/* ===== Table ===== */
table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(0,0,0,0.05);
}

th, td {
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

/* ===== Responsive ===== */
@media (max-width: 992px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

    </style>
</head>

<body>

<?php include __DIR__ . '/partials/SidebarManager.php'; ?>

<div class="main-content">
    <h2>รายชื่อพนักงานหน้างาน</h2>
    <!-- Role Filter -->
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
                    <img src="/admin/uploads/<?= htmlspecialchars($image) ?>"
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

// กดปุ่ม filter
function filterRole(role) {
    currentRole = role;

    // active button
    document.querySelectorAll('.role-filter .btn')
        .forEach(btn => btn.classList.remove('active'));

    event.target.classList.add('active');

    applyFilters();
}

// พิมพ์ค้นหา
document.getElementById('searchInput')
    .addEventListener('keyup', applyFilters);

// รวม filter + search
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
