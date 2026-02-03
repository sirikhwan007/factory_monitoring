<div id="editModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span> 

        <h3>แก้ไขข้อมูลส่วนตัว</h3> 

        <form action="profile_update.php" method="post" enctype="multipart/form-data" role="form">
            
            <div class="form-group">
                <label for="username-input">ชื่อผู้ใช้</label>
                <input type="text" id="username-input" name="username" 
                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email-input">อีเมล์</label>
                <input type="email" id="email-input" name="email" 
                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="phone-input">เบอร์โทร</label>
                <input type="text" id="phone-input" name="phone" 
                       value="<?php echo htmlspecialchars($user['phone']); ?>" required>
            </div>

            <!--  เพิ่มรหัสผ่านใหม่ -->
            <div class="form-group">
                <label for="password-input">รหัสผ่านใหม่ (ไม่บังคับ)</label>
                <input type="password" id="password-input" name="password" placeholder="New Password">
            </div>

            <div class="form-group">
                <label for="confirm-password-input">ยืนยันรหัสผ่าน</label>
                <input type="password" id="confirm-password-input" name="confirm_password" placeholder="Confirm Password">
            </div>
            <!--  จบส่วนรหัสผ่าน -->

            <div class="form-group">
                <label for="profile-image-input">รูปโปรไฟล์</label>
                <input type="file" id="profile-image-input" name="profile_image" accept="image/*">
            </div>

            <button type="submit" class="btn-primary">บันทึกการเปลี่ยนแปลง</button>
        </form>
    </div>
</div>
