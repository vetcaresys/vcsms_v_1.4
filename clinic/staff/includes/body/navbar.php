<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a href="index.php" class="nav-link text-white">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a href="manage_petowner.php" class="nav-link text-white">Manage Client</a>
                </li>
                <li class="nav-item">
                    <a href="manage_pet_details.php" class="nav-link text-white">Pet Details</a>
                </li>
                <li class="nav-item">
                    <a href="manage_customer_appointment.php" class="nav-link text-white">Appointments</a>
                </li>
                <li class="nav-item">
                    <a href="manage_records.php" class="nav-link text-white">Medical Records</a>
                </li>
                <li class="nav-item">
                    <a href="manage_inventory.php" class="nav-link text-white">Inventory</a>
                </li>
            </ul>
            <?php if (!empty($_SESSION['clinic_name']) && !empty($_SESSION['clinic_logo'])): ?>
                <span class="px-3 py-2 rounded-3 text-white fw-semibold d-flex align-items-center" style="
              background: url('<?= htmlspecialchars($_SESSION['clinic_logo']) ?>') no-repeat center center;
              background-size: contain;
              font-size: 0.9rem;
              gap: 0.5rem;
              min-width: 120px;
              height: 40px;
          ">
                    <span><?= htmlspecialchars($_SESSION['clinic_name']) ?></span>
                </span>
            <?php elseif (!empty($_SESSION['clinic_name'])): ?>
                <span class="px-3 py-2 rounded-3 text-white fw-semibold"
                    style="background: rgba(255,255,255,0.2); backdrop-filter: blur(6px); font-size: 0.9rem;">
                    <?= htmlspecialchars($_SESSION['clinic_name']) ?>
                </span>
            <?php endif; ?>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                        <span id="notif_count"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem; padding: 3px 6px;">
                        </span>
                    </a>

                    <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 320px; max-height: 400px;"
                        id="notif_list_container">

                        <li class="d-flex justify-content-between align-items-center mb-2 px-2">
                            <h6 class="mb-0">Notifications</h6>
                            <button id="mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none"
                                style="font-size: 0.8rem;" disabled>
                                Mark all as read
                            </button>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                            <li class="text-center text-muted">Loading...</li>
                        </div>
                    </ul>
                </li>
            </ul>
            <!-- Profile Dropdown -->
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                        width="35" height="35">
                    <strong><?= $name ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                    <li>
                        <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person"></i> My Profile
                        </a>
                    </li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <form method="POST" action="../logout.php" id="logoutForm" class="m-0">
                            <button class="dropdown-item text-danger" type="submit" id="logoutBtn">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">

            <!-- VIEW PROFILE -->
            <div id="viewProfile">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">My Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile Picture"
                        class="rounded-circle border border-3 border-primary mb-3" width="130" height="130"
                        style="object-fit: cover;">
                    <h4 class="fw-bold text-primary mb-3 justify-content-start"><?= htmlspecialchars($staff['name']) ?>
                    </h4>

                    <!-- 🧾 Info Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle text-start">
                            <tbody>
                                <tr>
                                    <th style="width:30%">Full Name</th>
                                    <td><?= htmlspecialchars($staff['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($staff['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= htmlspecialchars($staff['contact_number'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <th>Role</th>
                                    <td><?= htmlspecialchars($staff['role']) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-outline-primary" onclick="toggleEdit(true)">
                        <i class="bi bi-pencil-square me-1"></i> Edit Profile
                    </button>
                </div>
            </div>

            <!-- EDIT PROFILE -->
            <div id="editProfile" style="display:none;">
                <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Profile</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($staff['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($staff['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($staff['contact_number']) ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Profile Picture</label>
                            <input type="file" name="profile_picture" class="form-control">
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

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button>
                        <button type="submit" class="btn btn-success"><i class='bi bi-save'></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>

    // Profile Edit Toggle
    function toggleEdit(isEdit) {
        document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
        document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
    }
</script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        loadAdminNotifications();

        // Load when bell icon is clicked
        document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

        // 💡 NEW: Listener for Mark All button
        document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
    });

    function loadAdminNotifications() {
        fetch("../../emp_fetch_notifications.php")
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById("notif_list");
                const count = document.getElementById("notif_count");
                const markAllBtn = document.getElementById("mark_all_btn"); // Get the button

                list.innerHTML = "";
                let unreadCount = 0;

                if (!data || data.length === 0) {
                    list.innerHTML = `<li class="text-center text-muted py-3">No notifications</li>`;
                    count.textContent = "";
                    markAllBtn.disabled = true; // Disable button if no notifs
                    return;
                }

                // Locate the loadAdminNotifications function and replace the following loop:
                data.forEach(n => {
                    if (n.status === "unread") unreadCount++;

                    list.innerHTML += `
                        <li>
                            <a href="${n.link ?? '#'}" class="dropdown-item d-flex justify-content-between align-items-start notif-item ${n.status === "unread" ? 'bg-light' : ''}"
                            data-id="${n.notif_id}">
                                <div class="w-100"> 
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-secondary fw-semibold" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar"></i> ${n.display_date}
                                        </small>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock"></i> ${n.display_time}
                                        </small>
                                    </div>

                                    <span style="
                                        font-size: 0.85rem; 
                                        max-width: 100%; 
                                        display: block; 
                                        overflow: hidden; 
                                        text-overflow: ellipsis; 
                                        white-space: nowrap;
                                        /* Conditional Style: Use font-weight: bold (700) if unread, normal (400) if read */
                                        font-weight: ${n.status === "unread" ? '700' : '400'};
                                    ">
                                    ${n.status === "unread" ? `<span class="badge bg-danger ms-2">New</span>` : ""}

                                        ${n.subject}

                                    </span>
                                    
                                    <small class="text-muted" style="font-size: 0.78rem;">${n.message}</small>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>
                    `;
                });
                count.textContent = unreadCount > 0 ? unreadCount : "";
                markAllBtn.disabled = (unreadCount === 0); // Enable button only if there are unread notifications
            });
    }

    // 💡 NEW: Function to mark all notifications as read
    function markAllAsRead() {
        Swal.fire({
            title: 'Mark all as read?',
            text: "All current unread notifications will be marked as read.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Mark All'
        }).then((result) => {
            if (result.isConfirmed) {
                // Fetch the new PHP endpoint to update the database
                fetch("../../emp_mark_all_as_read.php", { method: 'POST' })
                    .then(response => {
                        if (response.ok) {
                            Swal.fire('Success!', 'All notifications marked as read.', 'success');
                            // Reload the notifications immediately after success
                            loadAdminNotifications();
                        } else {
                            Swal.fire('Error!', 'Could not mark all as read.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Fetch error:', error);
                        Swal.fire('Error!', 'Network or server issue.', 'error');
                    });
            }
        });
    }

    // Mark as read when opening a notification (Your original function, updated for clarity)
    document.addEventListener("click", function (e) {
        if (e.target.closest(".notif-item")) {
            const notifItem = e.target.closest(".notif-item");
            const id = notifItem.dataset.id;

            // Only send the request if it's currently marked as unread
            if (notifItem.classList.contains('bg-light')) {
                fetch(`../../mark_as_read.php?id=${id}`);
                // Simple visual update after click
                notifItem.classList.remove('bg-light');
                notifItem.querySelector('.badge')?.remove();
                loadAdminNotifications(); // Reload count
            }
        }
    });
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

<script>
    document.getElementById('logoutBtn').addEventListener('click', function (e) {
        e.preventDefault(); // Prevent form from submitting instantly

        Swal.fire({
            title: 'Are you sure you want to logout?',
            text: "You’ll be logged out of your current session.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'No, stay here'
        }).then((result) => {
            if (result.isConfirmed) {
                // Submit the form only if confirmed
                document.getElementById('logoutForm').submit();
            }
        });
    });
</script>