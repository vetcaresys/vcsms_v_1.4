<div class="container py-4">
    <?php
    if (!empty($errorMsg)) {
        echo $errorMsg;
    }
    if (!empty($msg)) {
        echo $msg;
    }
    ?>

    <?php if (!isset($errorMsg)): ?>


        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold text-dark"><i class="bi bi-person-plus-fill me-2"></i>Manage Staff</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Staff
            </button>
        </div>

        <!-- Staff List -->
        <div class="card shadow-lg border-0 rounded-3">
            <!-- Card Header -->
            <div class="card-header bg-gradient bg-secondary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i> Registered Staff Members</h5>
            </div>

            <!-- Card Body -->
            <div class="card-body p-0">
                <?php if (count($staffMembers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-4">Name</th>
                                    <th>Role</th>
                                    <th>Contact</th>
                                    <th>Email</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffMembers as $staff): ?>
                                    <tr>
                                        <!-- profile -->
                                        <td class="fw-semibold text-dark px-4">
                                            <img src="../uploads/profiles/<?= !empty($staff['profile_picture']) ? htmlspecialchars($staff['profile_picture']) : 'default.png' ?>"
                                                alt="Profile" width="32" height="32" class="rounded-circle me-2"
                                                style="object-fit: cover;">
                                            <?php echo htmlspecialchars($staff['name']); ?>
                                        </td>

                                        <!-- Role -->
                                        <td>
                                            <?php if ($staff['role'] === 'doctor'): ?>
                                                <span class="badge rounded-pill bg-info px-3 py-2">
                                                    <i class="bi bi-stethoscope me-1"></i> Doctor
                                                </span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                                    <i class="bi bi-people-fill me-1"></i> Staff
                                                </span>
                                            <?php endif; ?>
                                        </td>

                                        <!-- Contact -->
                                        <td class="text-muted">
                                            <i class="bi bi-telephone me-2 text-success"></i>
                                            <?php echo htmlspecialchars($staff['contact_number']); ?>
                                        </td>

                                        <!-- Email -->
                                        <td class="text-muted">
                                            <i class="bi bi-envelope-at me-2 text-secondary"></i>
                                            <?php echo htmlspecialchars($staff['email']); ?>
                                        </td>

                                        <!-- Actions -->
                                        <td class="text-center">
                                            <div class="d-inline-flex gap-2">
                                                <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                    data-bs-target="#editStaffModal<?= $staff['staff_id'] ?>">
                                                    <i class="bi bi-pencil-square me-1"></i>
                                                </button>

                                                <!-- <a href="?delete=<?= $staff['staff_id']; ?>"
                                                    class="btn btn-sm btn-danger delete-btn" data-id="<?= $staff['staff_id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </a> -->
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="p-3 mb-0 text-center text-muted">No staff registered yet.</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

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
                                <input type="password" class="form-control" name="password"
                                    id="editStaffPassword<?= $staff['staff_id'] ?>"
                                    placeholder="Enter new password (leave blank to keep current)" minlength="8"
                                    pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
                                    title="Must be at least 8 characters, include uppercase, lowercase, number, and special character.">

                                <button type="button" class="btn btn-outline-secondary"
                                    id="toggleEditStaffPassword<?= $staff['staff_id'] ?>">
                                    Show
                                </button>
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

<?php foreach ($staffMembers as $staff): ?>
<script>
    const pwField<?= $staff['staff_id'] ?> = document.getElementById('editStaffPassword<?= $staff['staff_id'] ?>');
    const toggleBtn<?= $staff['staff_id'] ?> = document.getElementById('toggleEditStaffPassword<?= $staff['staff_id'] ?>');

    toggleBtn<?= $staff['staff_id'] ?>.addEventListener('click', function () {
        const isHidden = pwField<?= $staff['staff_id'] ?>.type === 'password';
        pwField<?= $staff['staff_id'] ?>.type = isHidden ? 'text' : 'password';
        this.textContent = isHidden ? 'Hide' : 'Show';
    });
</script>
<?php endforeach; ?>
