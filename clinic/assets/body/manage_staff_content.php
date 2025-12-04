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
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                    data-bs-target="#editStaffModal<?= $staff['staff_id'] ?>">
                                                    <i class="bi bi-pencil-square me-1"></i> Edit
                                                </button>

                                                <a href="?delete=<?= $staff['staff_id']; ?>"
                                                    class="btn btn-sm btn-danger delete-btn" data-id="<?= $staff['staff_id']; ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </a>
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