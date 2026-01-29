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
            <input type="text" name="user_id" required placeholder="ใส่เลข ID ที่ต้องการ">
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
