<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

require "../config.php";

$sql_pending = "SELECT COUNT(*) AS total_pending FROM users WHERE status = 'pending'";
$result_pending = $conn->query($sql_pending);
$row_pending = $result_pending ? $result_pending->fetch_assoc() : ['total_pending' => 0];
$total_pending = $row_pending['total_pending'];

$status_filter = $_GET['status'] ?? '';
$role_filter = $_GET['role'] ?? 'all';

$sql = "SELECT * FROM users WHERE 1=1";

if ($status_filter === 'pending') {
    $sql .= " AND status = 'pending'";
}

if (!empty($role_filter) && $role_filter !== 'all') {
    $role = $conn->real_escape_string($role_filter);
    $sql .= " AND role = '$role'";
}

$sql .= " ORDER BY user_id ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Users Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/users.css">
    <link rel="stylesheet" href="/factory_monitoring/admin/assets/css/index.css">

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body {
            background: #f4f6f9;
        }

        .sidebar-wrapper {
            background: #0f172a;
        }

        .dashboard {
            margin-left: 260px;
            padding: 24px;
        }

        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 15px;
        }

        .filter-bar a {
            padding: 8px 14px;
            border-radius: 999px;
            background: #e5e7eb;
            color: #111;
            text-decoration: none;
            font-size: 14px;
        }

        .filter-bar a.active {
            background: #2563eb;
            color: #fff;
        }

        .table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .table thead {
            background: #111827;
            color: white;
        }

        .action-btns {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .action-btns .btn {
            font-size: 12px;
            padding: 5px 8px;
            border-radius: 8px;
        }

        .btn-success {
            background: #16a34a;
            border: none;
        }

        .btn-warning {
            background: #f59e0b;
            border: none;
            color: white;
        }

        .btn-primary {
            background: #2563eb;
            border: none;
        }

        .btn-danger {
            background: #dc2626;
            border: none;
        }

        @media (max-width: 768px) {
            .dashboard {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>

    <div class="btn-hamburger" onclick="toggleSidebar()">
        <i class="fa-solid fa-bars"></i>
    </div>

    <div class="sidebar-overlay" onclick="closeSidebar()"></div>

    <div class="sidebar-wrapper">
        <?php include 'SidebarAdmin.php'; ?>
    </div>

    <div class="dashboard">

        <h2>จัดการผู้ใช้งาน</h2>

        <div class="filter-bar">
            <a href="?role=all" class="<?= $role_filter == 'all' ? 'active' : '' ?>">All</a>
            <a href="?role=Admin" class="<?= $role_filter == 'Admin' ? 'active' : '' ?>">Admin</a>
            <a href="?role=Manager" class="<?= $role_filter == 'Manager' ? 'active' : '' ?>">Manager</a>
            <a href="?role=Operator" class="<?= $role_filter == 'Operator' ? 'active' : '' ?>">Operator</a>
            <a href="?role=Technician" class="<?= $role_filter == 'Technician' ? 'active' : '' ?>">Technician</a>
        </div>

        <a href="?status=pending" class="btn btn-warning mb-3">
            รออนุมัติ <?= $total_pending ?> คน
        </a>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= $row['user_id'] ?></td>
                            <td><?= $row['username'] ?></td>
                            <td><?= $row['email'] ?></td>
                            <td><?= $row['phone'] ?></td>
                            <td><?= $row['role'] ?></td>
                            <td><?= $row['status'] ?></td>

                            <td>
                                <div class="action-btns">

                                    <button class="btn btn-success"
                                        onclick="approveUser('<?= $row['user_id'] ?>')">
                                        <i class="fa fa-check"></i>
                                    </button>

                                    <button class="btn btn-warning"
                                        onclick="rejectUser('<?= $row['user_id'] ?>')">
                                        <i class="fa fa-times"></i>
                                    </button>

                                    <button class="btn btn-primary"
                                        onclick="openEditModal('<?= $row['user_id'] ?>')">
                                        <i class="fa fa-pen"></i>
                                    </button>

                                    <button class="btn btn-danger"
                                        onclick="deleteUser('<?= $row['user_id'] ?>')">
                                        <i class="fa fa-trash"></i>
                                    </button>

                                </div>
                            </td>

                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
    <?php include 'users_modals.php'; ?>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar-wrapper').classList.toggle('active');
            document.querySelector('.sidebar-overlay').classList.toggle('active');
        }

        function closeSidebar() {
            document.querySelector('.sidebar-wrapper').classList.remove('active');
            document.querySelector('.sidebar-overlay').classList.remove('active');
        }
    </script>

    <script>
        window.approveUser = function(userId) {

            Swal.fire({
                title: 'Approve?',
                icon: 'question',
                showCancelButton: true
            }).then(result => {

                if (!result.isConfirmed) return;

                fetch("/factory_monitoring/admin/actions/approve_user.php", {
                        method: "POST",
                        body: new URLSearchParams({
                            user_id: userId
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) location.reload();
                        else alert(data.error || "error");
                    })
                    .catch(err => alert(err));
            });
        };


        window.rejectUser = function(userId) {

            Swal.fire({
                title: 'Reject?',
                icon: 'warning',
                showCancelButton: true
            }).then(result => {

                if (!result.isConfirmed) return;

                fetch("/factory_monitoring/admin/actions/reject_user.php", {
                        method: "POST",
                        body: new URLSearchParams({
                            user_id: userId
                        })
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) location.reload();
                        else alert(data.error || "error");
                    });
            });
        };

        window.openEditModal = function(userId) {
    // แก้ Path ให้ถูกต้องตามโครงสร้างโฟลเดอร์ของคุณ (ไม่ต้องมี /admin/ นำหน้า)
    fetch("actions/update_user.php?user_id=" + encodeURIComponent(userId))
        .then(async r => {
            const text = await r.text(); // อ่านสิ่งที่ตอบกลับมาเป็น Text ธรรมดาก่อน
            
            if (!r.ok) {
                throw new Error("Server Status " + r.status + ": " + text);
            }

            try {
                return JSON.parse(text); // ลองแปลงเป็น JSON ดู
            } catch (e) {
                // ถ้าแปลงไม่ได้ ให้โยน Text นั้นออกมาให้เราเห็นเต็มๆ
                throw new Error("เซิร์ฟเวอร์ไม่ได้ตอบกลับเป็น JSON:\n" + text);
            }
        })
        .then(data => {
            if (!data || data.error) {
                alert(data.error || "โหลดข้อมูลไม่สำเร็จ");
                return;
            }

            const modal = document.getElementById("editModal");
            if (!modal) {
                console.error("editModal not found");
                return;
            }

            // ใส่ข้อมูลลงในฟอร์ม
            document.getElementById("edit_user_id").value = data.user_id || '';
            document.getElementById("edit_username").value = data.username || '';
            document.getElementById("edit_email").value = data.email || '';
            document.getElementById("edit_phone").value = data.phone || '';
            document.getElementById("edit_role").value = data.role || '';

            modal.style.display = "block";
        })
        .catch(err => {
            console.error(err);
            // โชว์ Error แบบเจาะจงให้รู้ไปเลยว่าพังเพราะอะไร
            alert("Fetch Fail: " + err.message); 
        });
};

        window.closeEditModal = function() {
            document.getElementById("editModal").style.display = "none";
        };

        document.getElementById("editUserForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch("/factory_monitoring/admin/actions/update_user.php", {
                    method: "POST",
                    body: formData
                })
                .then(async r => {
                    const text = await r.text();
                    if (!r.ok) {
                        throw new Error("Server Error (500): " + text);
                    }
                    return JSON.parse(text); 
                })
                .then(data => {
                    if (data.success) {
                        Swal.fire('สำเร็จ', 'แก้ไขข้อมูลแล้ว', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('ผิดพลาด', data.error, 'error');
                    }
                })
                .catch(err => {
                    console.error("Debug Error:", err.message);
                    Swal.fire('ผิดพลาด', 'ระบบทำงานขัดข้อง กรุณาเช็ค Console', 'error');
                });
        });

        window.deleteUser = function(userId) {
            Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "คุณต้องการลบผู้ใช้งานนี้ใช่หรือไม่? ข้อมูลจะหายไปถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ยืนยัน',
                cancelButtonText: 'ยกเลิก'
            }).then((result) => {
                if (result.isConfirmed) {
                    
                    fetch("/factory_monitoring/admin/actions/delete_user.php", {
                            method: "POST",
                            headers: {
                                "Content-Type": "application/x-www-form-urlencoded",
                            },
                            body: new URLSearchParams({
                                user_id: userId
                            })
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('ลบสำเร็จ!', 'ข้อมูลผู้ใช้ถูกลบแล้ว', 'success')
                                    .then(() => location.reload());
                            } else {
                                Swal.fire('เกิดข้อผิดพลาด!', data.error || 'ไม่สามารถลบข้อมูลได้', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('ผิดพลาด!', 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', 'error');
                        });
                }
            });
        };
    </script>

</body>

</html>