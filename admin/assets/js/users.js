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
if(data.success){ alert('เพิ่มผู้ใช้เรียบร้อย'); location.reload(); }
else alert('เกิดข้อผิดพลาด: ' + data.error);
})
.catch(err => alert('เกิดข้อผิดพลาด: ' + err));
});

/* Edit User */
document.getElementById('editUserForm').addEventListener('submit', function(e) {
e.preventDefault();
const formData = new FormData(this);
fetch('actions/update_user.php', { method: 'POST', body: formData })
.then(res => res.json())
.then(data => {
console.log('Update User response:', data); // debug
if(data.success){ alert('แก้ไขเรียบร้อย'); location.reload(); }
else alert('เกิดข้อผิดพลาด: ' + data.error);
});
});

/* Delete User */
function deleteUser(user_id) {
console.log('Delete User ID:', user_id); // debug
if(confirm('ยืนยันการลบผู้ใช้นี้?')){
const formData = new FormData();
formData.append('user_id', user_id);
fetch('actions/delete_user.php', { method: 'POST', body: formData })
.then(res => res.json())
.then(data => {
console.log('Delete response:', data); // debug
if(data.success){ alert('ลบเรียบร้อย'); location.reload(); }
else alert('เกิดข้อผิดพลาด: ' + data.error);
})
.catch(err => alert('เกิดข้อผิดพลาด: ' + err));
}
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
