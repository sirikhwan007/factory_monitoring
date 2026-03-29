/* Open/Close Modals */
function openAddModal() { document.getElementById('addModal').style.display = 'block'; }
function closeAddModal() { document.getElementById('addModal').style.display = 'none'; }
function openEditModal(user) {
    console.log('Open Edit Modal:', user); // debug
    document.getElementById('edit_user_id').value = user.user_id;
    document.getElementById('edit_username').value = user.username;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_phone').value = user.phone;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('editModal').style.display = 'block';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

/* Add User */
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('actions/add_user.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        console.log('Add User response:', data); // debug
        if(data.success){ 
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'เพิ่มผู้ใช้เรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.error
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: err
        });
    });
});

/* Edit User */
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    fetch('actions/update_user.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
        console.log('Update User response:', data); // debug
        if(data.success){ 
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: 'แก้ไขข้อมูลเรียบร้อยแล้ว',
                showConfirmButton: false,
                timer: 1500
            }).then(() => {
                location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เกิดข้อผิดพลาด',
                text: data.error
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: err
        });
    });
});

/* Delete User */
function deleteUser(user_id) {
    console.log('Delete User ID:', user_id); // debug
    
    Swal.fire({
        title: 'คุณแน่ใจหรือไม่?',
        text: "คุณต้องการลบผู้ใช้งานนี้ใช่หรือไม่! ข้อมูลที่ลบจะไม่สามารถกู้คืนได้",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('user_id', user_id);
            fetch('actions/delete_user.php', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                console.log('Delete response:', data);
                if(data.success){ 
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ!',
                        text: 'ผู้ใช้งานถูกลบออกจากระบบแล้ว',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.error
                    });
                }
            })
            .catch(err => {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: err
                });
            });
        }
    });
}

/* Filter by Role */
function filterRole(role) {
console.log('Filter role:', role); // debug
document.querySelectorAll('.user-row').forEach(row => {
row.style.display = (role==='all' || row.dataset.role===role)?'':'none';
});
}

/* Search */
document.getElementById('searchInput').addEventListener('keyup', function(){
const filter = this.value.toLowerCase();
document.querySelectorAll('.user-row').forEach(row => {
const text = row.textContent.toLowerCase();
row.style.display = text.includes(filter)?'':'none';
});
});

function checkBinary(input) {
    const val = input.value;
    const warning = document.getElementById('id_warning');
    
    // ตรวจสอบว่ามีแต่เลข 0 และ 1 หรือไม่
    const isBinary = /^[01]+$/.test(val) && val.length > 1;
    
    if (isBinary) {
        warning.style.display = 'block';
        input.style.borderColor = 'red';
    } else {
        warning.style.display = 'none';
        input.style.borderColor = '';
    }
}