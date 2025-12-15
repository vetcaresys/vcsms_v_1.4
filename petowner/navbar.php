<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">

        <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
        <button class="navbar-toggler" type="button" id="mobileMenuBtn">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="topNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                <li class="nav-item"><a href="manage_pets.php" class="nav-link">Manage Pets</a></li>
                <li class="nav-item"><a href="book_appointment.php" class="nav-link">Book Appointment</a></li>
                <li class="nav-item"><a href="inquiry_form.php" class="nav-link">Inquires</a></li>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">

                <li class="nav-item me-3">
                    <a class="nav-link" href="history.php">
                        <i class="bi bi-list-check" style="font-size: 1.35rem;"></i>
                    </a>
                </li>

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
            <div class="dropdown">
                <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                    id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                    <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                        width="35" height="35">
                    <strong><?= $name ?></strong>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow text-center text-lg-start"
                    aria-labelledby="dropdownUser">
                    <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                            <i class="bi bi-person"></i> Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li>
                        <!-- Desktop -->
                        <form method="POST" action="logout.php" id="logoutForm" class="m-0">
                            <button class="dropdown-item text-danger" type="submit" id="logoutBtnDesktop">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</nav>

<!-- MOBILE SLIDE MENU -->
<div id="mobileMenu" class="mobile-menu">

    <!-- PROFILE SECTION -->
    <div class="mobile-profile d-flex align-items-center mb-3">
        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2" width="45"
            height="45">
        <div class="text-white">
            <strong><?= $name ?></strong><br>
            <span style="font-size: 0.8rem; opacity: 0.8;">Pet Owner</span>
        </div>
    </div>
    <hr class="text-white">
    <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="manage_pets.php">Manage Pets</a></li>
        <li><a href="book_appointment.php">Book Appointment</a></li>
        <hr>
        <li><a href="history.php"><i class="bi bi-list-check"></i> Activity Logs</a></li>

        <li>
            <a href="#" id="mobileNotifBtn">
                <i class="bi bi-bell-fill"></i> Notifications
                <span id="mobile_notif_count" class="badge bg-danger ms-2"
                    style="font-size: 0.65rem; padding: 3px 6px;"></span>
            </a>
        </li>

        <li><a href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                <i class="bi bi-person"></i> Profile</a></li>

        <!-- Mobile -->
        <li>
            <a href="logout.php" id="logoutBtnMobile">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </li>
    </ul>
</div>

<!-- Mobile Notification Panel -->
<div id="mobileNotifPanel"
    style="display:none; background:white; padding:15px; border-radius:10px; margin-top:10px; max-height:300px; overflow-y:auto;">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Notifications</h6>

        <button id="mobile_mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.8rem;"
            disabled>
            Mark all as read
        </button>
    </div>

    <ul id="mobile_notif_list" class="list-unstyled">
        <li class="text-center text-muted">Loading...</li>
    </ul>
</div>

<!-- DARK BACKDROP -->
<div id="mobileBackdrop" class="mobile-backdrop"></div>


<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">

            <!-- View Profile Section -->
            <div id="viewProfile">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">My Profile</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body text-center">
                    <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile Picture"
                        class="rounded-circle shadow-sm mb-3" width="110" height="110"
                        style="object-fit: cover; border: 3px solid #0d6efd;">
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
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($user['name']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($user['contact_number']) ?>" inputmode="numeric"
                                maxlength="11" pattern="^09\d{9}$" placeholder="e.g., 09123456789"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                            <div class="invalid-feedback">
                                Contact must be 11 digits and start with 09.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address"
                                class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
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
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        loadAdminNotifications();

        // Load when bell icon is clicked
        document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

        // 💡 NEW: Listener for Mark All button
        document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
    });

    function loadAdminNotifications() {
        fetch(`../petowner_fetch_notifications.php?user_id=` + `<?= ($user_id) ?>`)
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
                fetch(`../petowner_mark_all_as_read.php?user_id=` + `<?= ($user_id) ?>`, { method: 'POST' })
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
                fetch(`../mark_as_read.php?id=${id}`);
                // Simple visual update after click
                notifItem.classList.remove('bg-light');
                notifItem.querySelector('.badge')?.remove();
                loadAdminNotifications(); // Reload count
            }
        }
    });
</script>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function setupLogout(btnId, isForm = false, formId = '') {
        document.getElementById(btnId).addEventListener('click', function (e) {
            e.preventDefault();

            // Close mobile menu if open
            const mobileMenu = document.getElementById("mobileMenu");
            const mobileBackdrop = document.getElementById("mobileBackdrop");
            if (mobileMenu.classList.contains("active")) {
                mobileMenu.classList.remove("active");
                mobileBackdrop.classList.remove("active");
            }

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
                    if (isForm) {
                        document.getElementById(formId).submit(); // desktop form
                    } else {
                        window.location.href = this.getAttribute('href'); // mobile
                    }
                }
            });
        });
    }

    // Desktop logout
    setupLogout('logoutBtnDesktop', true, 'logoutForm');

    // Mobile logout
    setupLogout('logoutBtnMobile');
</script>

<script src="//cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.getElementById('logoutBtn').addEventListener('click', function (e) {
        e.preventDefault(); // prevent immediate navigation

        const logoutUrl = this.getAttribute('href'); // get the actual logout URL

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
                window.location.href = logoutUrl; // navigate to logout
            }
        });
    });
</script>

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

<style>
    /* ========== 📱 MOBILE NAVBAR ONLY (max 768px) ========== */
    @media (max-width: 768px) {

        /* Brand text and spacing */
        .navbar .navbar-brand {
            font-size: 1rem;
            padding-left: 5px;
        }

        /* Collapse menu full width */
        .navbar-collapse {
            background: #0d6efd;
            padding: 10px;
            border-radius: 6px;
            margin-top: 8px;
        }

        /* Nav links spacing */
        .navbar-nav .nav-link {
            padding: 10px 5px;
            font-size: 0.95rem;
            border-radius: 6px;
            color: white !important;
        }

        /* Hover feel on mobile */
        .navbar-nav .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        /* Icons slightly bigger */
        .navbar-nav .nav-link i {
            font-size: 1.4rem !important;
        }

        /* Notification badge smaller */
        #notif_count {
            font-size: 0.55rem !important;
            padding: 2px 5px !important;
            top: 0 !important;
            right: 4px !important;
        }

        /* Notification dropdown stretches full width */
        .dropdown-menu {
            max-width: 100% !important;
            left: 0 !important;
            right: 0 !important;
            border-radius: 10px;
            padding: 8px 12px;
            margin-top: 5px !important;
        }

        /* Fix the dropdown container so text fits */
        #notif_list_container {
            max-height: 350px;
            overflow-y: auto;
        }

        /* Profile name and image smaller */
        #dropdownUser img {
            width: 30px !important;
            height: 30px !important;
        }

        #dropdownUser strong {
            font-size: 0.9rem;
        }

        /* Fix toggler spacing */
        .navbar-toggler {
            border: none;
            padding: 4px 6px;
        }
    }
</style>

<style>
    /* === SLIDE-IN MOBILE MENU === */
    .mobile-menu {
        position: fixed;
        top: 0;
        left: -260px;
        width: 260px;
        height: 100%;
        background: #0d6efd;
        padding: 20px;
        box-shadow: 3px 0 10px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transition: left 0.3s ease;
    }

    .mobile-menu ul {
        list-style: none;
        padding: 0;
        margin-top: 40px;
    }

    .mobile-menu ul li {
        margin-bottom: 20px;
    }

    .mobile-menu ul a {
        color: white;
        font-size: 1.1rem;
        text-decoration: none;
        display: block;
    }

    .mobile-backdrop {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
        display: none;
        z-index: 999;
    }

    .mobile-menu.active {
        left: 0;
    }

    .mobile-backdrop.active {
        display: block;
    }

    .mobile-backdrop {
        z-index: 800 !important;
    }

    /* Make modal scrollable in mobile */
    .modal-fullscreen-sm-down .modal-body {
        max-height: calc(100vh - 120px);
        overflow-y: auto;
    }

    /* Remove unwanted body padding from Bootstrap when modal opens */
    body.modal-open {
        padding-right: 0 !important;
    }

    .navbar .nav-link i {
        z-index: 10001 !important;
    }

    /* Keep navbar, icons, and dropdown ABOVE slide menu */
    /* .navbar {
        position: relative !important;
        z-index: 1200 !important;
    } */

    .navbar .nav-link,
    .navbar .nav-link i,
    #notifDropdown,
    .dropdown-menu {
        position: relative;
        z-index: 1300 !important;
    }

    .mobile-profile img {
        border: 2px solid #fff;
    }

    .mobile-profile {
        padding-left: 10px;
    }

    @media (max-width: 768px) {

        @media (max-width: 768px) {
            .navbar .dropdown-menu {
                width: 100% !important;
                max-width: 100% !important;
                left: 0 !important;
                right: 0 !important;
                border-radius: 10px;
                padding: 8px 12px;
                margin-top: 5px !important;
            }
        }


        /* Mobile only profile dropdown */
        #dropdownUser+.dropdown-menu {
            width: 100% !important;
        }
    }
</style>

<script>
    document.getElementById("mobileMenuBtn").addEventListener("click", function () {
        document.getElementById("mobileMenu").classList.add("active");
        document.getElementById("mobileBackdrop").classList.add("active");
    });

    document.getElementById("mobileBackdrop").addEventListener("click", function () {
        document.getElementById("mobileMenu").classList.remove("active");
        this.classList.remove("active");
    });
</script>

<script>
    // Auto-close mobile menu when profile modal opens
    const profileModal = document.getElementById('profileModal');
    profileModal.addEventListener('show.bs.modal', () => {
        const mobileMenu = document.getElementById("mobileMenu");
        const mobileBackdrop = document.getElementById("mobileBackdrop");

        mobileMenu.classList.remove("active");
        mobileBackdrop.classList.remove("active");
    });

</script>

<script>
    // Toggle Profile Sections (minimize)
    document.getElementById('toggleProfileSection').addEventListener('click', function () {
        const viewProfile = document.getElementById('viewProfile');
        const editProfile = document.getElementById('editProfile');

        // Toggle whichever is currently visible
        if (viewProfile.style.display !== 'none') {
            viewProfile.style.display = 'none';
            this.querySelector('i').classList.replace('bi-dash', 'bi-plus'); // change icon
        } else if (editProfile.style.display !== 'none') {
            editProfile.style.display = 'none';
            this.querySelector('i').classList.replace('bi-dash', 'bi-plus');
        } else {
            // If both hidden, show viewProfile
            viewProfile.style.display = 'block';
            this.querySelector('i').classList.replace('bi-plus', 'bi-dash');
        }
    });

</script>

<script>
    document.getElementById("mobileNotifBtn").addEventListener("click", function () {
        const panel = document.getElementById("mobileNotifPanel");

        // Toggle visibility
        panel.style.display = (panel.style.display === "none") ? "block" : "none";

        loadMobileNotifications();
    });

    function loadMobileNotifications() {
        fetch(`../petowner_fetch_notifications.php?user_id=` + `<?= ($user_id) ?>`)
            .then(res => res.json())
            .then(data => {
                const list = document.getElementById("mobile_notif_list");
                const count = document.getElementById("mobile_notif_count");
                const markAllBtn = document.getElementById("mobile_mark_all_btn");

                list.innerHTML = "";
                let unreadCount = 0;

                if (!data || data.length === 0) {
                    list.innerHTML = `<li class="text-center text-muted py-3">No notifications</li>`;
                    count.textContent = "";
                    markAllBtn.disabled = true;
                    return;
                }

                data.forEach(n => {
                    if (n.status === "unread") unreadCount++;

                    list.innerHTML += `
                    <li class="p-2 mb-1 ${n.status === 'unread' ? 'bg-light' : ''}">
                        <div>
                            <small class="text-secondary">
                                <i class="bi bi-calendar"></i> ${n.display_date}
                            </small>
                            <small class="float-end text-muted">
                                <i class="bi bi-clock"></i> ${n.display_time}
                            </small>
                        </div>

                        <div class="fw-bold" style="font-size:0.85rem;">
                            ${n.subject}
                            ${n.status === "unread" ? `<span class="badge bg-danger ms-1">New</span>` : ""}
                        </div>

                        <small class="text-muted">${n.message}</small>
                    </li>
                `;
                });

                count.textContent = unreadCount > 0 ? unreadCount : "";
                markAllBtn.disabled = unreadCount === 0;
            });
    }

    // Mark all as read (mobile)
    document.getElementById("mobile_mark_all_btn").addEventListener("click", function () {
        fetch(`../petowner_mark_all_as_read.php?user_id=` + `<?= ($user_id) ?>`, { method: 'POST' })
            .then(res => {
                if (res.ok) {
                    loadMobileNotifications();
                    loadAdminNotifications(); // sync top nav
                }
            });
    });
</script>