<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="update_user.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit User Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control"
                            value="<?= htmlspecialchars($user['name']) ?>" required></div>
                    <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"
                            value="<?= htmlspecialchars($user['email']) ?>" required></div>
                    <div class="mb-3">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" class="form-control"
                            value="<?= htmlspecialchars($user['contact_number']) ?>" maxlength="11" pattern="^0\d{10}$"
                            title="Enter a valid 11-digit number (e.g. 09123456789)" oninput="
                                // remove any non-digit characters
                                this.value = this.value.replace(/[^0-9]/g, '');
                                // limit to 11 digits only
                                if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                        <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                    </div>
                    <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"
                            value="<?= htmlspecialchars($user['address']) ?>"></div>
                    <div class="mb-3"><label>Profile Picture</label><input type="file" name="profile_picture"
                            class="form-control"></div>

                    <h6 class="text-primary">Change Password (optional)</h6>
                    <!-- Current Password -->
                    <div class="mb-3 position-relative">
                        <label>Current Password</label>
                        <div class="input-group">
                            <input type="password" name="current_password" class="form-control" id="currentPassword"
                                placeholder="Enter current password">
                            <button class="btn btn-outline-secondary toggle-pass" type="button"
                                data-target="currentPassword">
                                <i class="bi bi-eye"></i>
                            </button>

                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <small class="text-muted">Type your current password to confirm changes.</small>
                                <a href="../forgot_password.php" class="small">Forgot password?</a>
                            </div>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="mb-3 position-relative">
                        <label>New Password</label>
                        <div class="input-group">
                            <input type="password" name="new_password" class="form-control" id="newPassword"
                                minlength="6" placeholder="Enter new password">
                            <button class="btn btn-outline-secondary toggle-pass" type="button"
                                data-target="newPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm New Password -->
                    <div class="mb-3 position-relative">
                        <label>Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                                minlength="6" placeholder="Re-enter new password">
                            <button class="btn btn-outline-secondary toggle-pass" type="button"
                                data-target="confirmPassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
            </form>
        </div>
    </div>
</div>