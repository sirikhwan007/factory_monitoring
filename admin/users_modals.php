<style>
    .modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.6);
    backdrop-filter: blur(4px);
}

.modal-content {
    background: #fff;
    width: 500px;
    max-width: 95%;
    margin: 80px auto;
    padding: 24px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    animation: pop 0.2s ease;
}

@keyframes pop {
    from { transform: scale(0.95); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
}

.modal-title {
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 600;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full {
    grid-column: span 2;
}

label {
    font-size: 13px;
    margin-bottom: 5px;
    color: #555;
}

input, select {
    padding: 10px;
    border-radius: 10px;
    border: 1px solid #ddd;
    outline: none;
    transition: 0.2s;
}

input:focus, select:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.15);
}

.btn-save {
    margin-top: 16px;
    width: 100%;
    padding: 12px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s;
}

.btn-save:hover {
    background: #1d4ed8;
}
</style>
<!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>แก้ไขผู้ใช้</h3>
        <form id="editUserForm" enctype="multipart/form-data">
            <label>Profile Image</label>
            <input type="file" name="profile_image" accept="image/*">
            <input type="hidden" name="user_id" id="edit_user_id">
            <label>Username</label>
            <input type="text" name="username" id="edit_username" required>
            <label>Email</label>
            <input type="email" name="email" id="edit_email" required>
            <label>Phone</label>
            <input type="text" name="phone" id="edit_phone" required>
            <label>Role</label>
            <select name="role" id="edit_role">
                <option value="Admin">Admin</option>
                <option value="Manager">Manager</option>
                <option value="Operator">Operator</option>
                <option value="Technician">Technician</option>
            </select>
            <button type="submit">บันทึก</button>
        </form>
    </div>
</div>

<!-- Add Modal -->
<div id="addModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddModal()">&times;</span>
        <h3>เพิ่มสมาชิกใหม่</h3>
        <form id="addUserForm" enctype="multipart/form-data">
            <label>Profile Image</label>
            <input type="file" name="profile_image" accept="image/*">
            <label>User ID</label>
            <input type="text"
                name="user_id"
                id="add_user_id"
                required
                placeholder="กรอก ID"
                oninput="checkBinary(this)">
            <small id="id_warning" style="color: red; display: none;">ไม่อนุญาตให้ใช้เพียง 0 และ 1</small>
            <label>Username</label>
            <input type="text" name="username" required placeholder="Username">
            <label>Password</label>
            <input type="password" name="password" required placeholder="Password">
            <label>Email</label>
            <input type="email" name="email" required placeholder="Email">
            <label>Phone</label>
            <input type="text" name="phone" placeholder="Phone">
            <label>Role</label>
            <select name="role">
                <option value="Admin">Admin</option>
                <option value="Manager">Manager</option>
                <option value="Operator">Operator</option>
                <option value="Technician">Technician</option>
            </select>
            <button type="submit">บันทึก</button>
        </form>
    </div>
</div>