<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <!-- View Profile -->
            <div id="viewProfile">
                <div class="modal-header">
                    <h5 class="modal-title">My Profile</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">

                    <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3"
                        width="100">

                    <h4 class="fw-bold mb-3"><?= $name ?></h4>

                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-start">
                            <tbody>
                                <!-- Staff Info -->
                                <tr>
                                    <th width="35%">Email</th>
                                    <td><?= htmlspecialchars($doctor['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= htmlspecialchars($doctor['contact_number']) ?></td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td><?= htmlspecialchars($doctor['role']) ?></td>
                                </tr>
                                <tr>
                                    <th>Clinic</th>
                                    <td><?= htmlspecialchars($_SESSION['clinic_name'] ?? 'N/A') ?></td>
                                </tr>

                                <!-- Doctor Info -->
                                <tr>
                                    <th>Specialization</th>
                                    <td><?= htmlspecialchars($doctorInfo['specialization'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Education</th>
                                    <td><?= htmlspecialchars($doctorInfo['education'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Years of Experience</th>
                                    <td><?= htmlspecialchars($doctorInfo['experience'] ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>License Number</th>
                                    <td><?= htmlspecialchars($doctorInfo['license_no'] ?? 'N/A') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
                </div>

            </div>

            <script>
                function submitProfileForm() {
                    Swal.fire({
                        title: "Profile Updated!",
                        text: "Your profile has been successfully updated.",
                        icon: "success",
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        document.getElementById("editProfileForm").submit();
                    });
                }
            </script>

            <!-- Edit Profile -->
            <div id="editProfile" style="display:none;">
                <form id="editProfileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($doctor['name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($doctor['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($doctor['contact_number']) ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control">
                        </div>

                        <hr>
                        <h6 class="fw-bold mt-3">Doctor Details</h6>

                        <div class="mb-3">
                            <label class="form-label">Specialization</label>
                            <input type="text" name="specialization" class="form-control"
                                value="<?= htmlspecialchars($doctorInfo['specialization'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Educational Background</label>
                            <input type="text" name="education" class="form-control"
                                value="<?= htmlspecialchars($doctorInfo['education'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Years of Experience</label>
                            <input type="text" name="experience" class="form-control"
                                value="<?= htmlspecialchars($doctorInfo['experience'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">License Number</label>
                            <input type="text" name="license_no" class="form-control"
                                value="<?= htmlspecialchars($doctorInfo['license_no'] ?? '') ?>">
                        </div>

                        <hr>
                        <h6 class="text-primary">Change Password (optional)</h6>

                        <!-- Current Password -->
                        <div class="mb-3 position-relative">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" id="currentPassword" class="form-control"
                                    placeholder="Enter current password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="currentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3 position-relative">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" id="newPassword" class="form-control"
                                    minlength="6" placeholder="Enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="newPassword">
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
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer d-flex">
                        <button type="button" class="btn btn-success" onclick="submitProfileForm()">
                            Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleEdit(editMode) {
        if (!editMode) {
            document.querySelector("#editProfile form").reset();
        }
        document.getElementById("viewProfile").style.display = editMode ? "none" : "block";
        document.getElementById("editProfile").style.display = editMode ? "block" : "none";
    }
</script>

<!-- for the show password -->
<script>
    document.querySelectorAll('.toggle-pass').forEach(btn => {
        btn.addEventListener('click', () => {
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    });
</script>