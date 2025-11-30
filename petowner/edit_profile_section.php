<!-- Edit Profile Section -->
<div id="editProfile" style="display:none;">
    <form id="editProfileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Edit Profile</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                    required>
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_number" class="form-control"
                    value="<?= htmlspecialchars($user['contact_number']) ?>" inputmode="numeric" maxlength="11"
                    pattern="^09\d{9}$" placeholder="e.g., 09123456789"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                <div class="invalid-feedback">
                    Contact must be 11 digits and start with 09.
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Picture</label>
                <input type="file" name="profile_picture" class="form-control">
            </div>

            <!-- 🔵 CHANGE PASSWORD SECTION -->
            <hr>
            <h6 class="text-primary">Change Password (optional)</h6>

            <!-- Current Password -->
            <div class="mb-3 position-relative">
                <label class="form-label">Current Password</label>
                <div class="input-group">
                    <input type="password" name="current_password" id="currentPassword" class="form-control"
                        placeholder="Enter current password">
                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="currentPassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- New Password -->
            <div class="mb-3 position-relative">
                <label class="form-label">New Password</label>
                <div class="input-group">
                    <input type="password" name="new_password" id="newPassword" class="form-control" minlength="6"
                        placeholder="Enter new password">
                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="newPassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-3 position-relative">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                    <input type="password" name="confirm_password" id="confirmPassword" class="form-control"
                        minlength="6" placeholder="Re-enter new password">
                    <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="confirmPassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button>
            <button type="submit" class="btn btn-success">Save Changes</button>
        </div>
    </form>
</div>