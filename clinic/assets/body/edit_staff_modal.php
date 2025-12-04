<!-- Edit staff -->
<?php foreach ($staffMembers as $staff): ?>
    <div class="modal fade" id="editStaffModal<?= $staff['staff_id'] ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data" onsubmit="return validateStaffForm(this)">
                    <!-- ✅ tell PHP this is Update Staff form -->
                    <input type="hidden" name="update_staff" value="1">
                    <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">

                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Staff</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <!-- Name -->
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($staff['name']) ?>" pattern="[A-Za-z\s]{2,50}"
                                title="Name should be 2-50 letters only" required>
                        </div>

                        <!-- Role -->
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select name="role" class="form-select" required>
                                <option value="staff" <?= $staff['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                <option value="doctor" <?= $staff['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                            </select>
                        </div>

                        <!-- Contact Number -->
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($staff['contact_number']) ?>" pattern="09\d{9}" maxlength="11"
                                required title="Must be a valid PH number (e.g., 09123456789)">
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($staff['email']) ?>" required>
                        </div>

                        <!-- Password -->
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="editStaffPassword"
                                    placeholder="Enter new password (leave blank to keep current)" minlength="8"
                                    pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
                                    title="Must be at least 8 characters, include uppercase, lowercase, number, and special character.">
                                <button type="button" class="btn btn-outline-secondary" id="toggleEditStaffPassword">
                                    Show</button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" name="update_staff" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>