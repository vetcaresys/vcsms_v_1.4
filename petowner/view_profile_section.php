<!-- View Profile Section -->
<div id="viewProfile">
    <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">My Profile</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>

    <div class="modal-body text-center">
        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile Picture" class="rounded-circle shadow-sm mb-3"
            width="110" height="110" style="object-fit: cover; border: 3px solid #0d6efd;">
        <h4 class="fw-bold mb-3"><?= htmlspecialchars($user['name']) ?></h4>

        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <tbody>
                    <tr>
                        <th style="width: 35%;">Email</th>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                    <tr>
                        <th>Contact</th>
                        <td><?= htmlspecialchars($contact) ?></td>
                    </tr>
                    <tr>
                        <th>Address</th>
                        <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-footer">
        <button class="btn btn-primary" onclick="toggleEdit(true)">
            <i class="bi bi-pencil-square"></i> Edit Profile
        </button>
    </div>
</div>