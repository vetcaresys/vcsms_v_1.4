<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

$clinicStmt = $pdo->prepare("SELECT clinic_name FROM clinics WHERE clinic_id = ?");
$clinicStmt->execute([$clinic_id]);
$clinic = $clinicStmt->fetch(PDO::FETCH_ASSOC);

// Store in session so pwede gamiton anywhere
$_SESSION['clinic_name'] = $clinic['clinic_name'] ?? 'N/A';

// Get doctor info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($doctor['name']);
$profilePic = !empty($doctor['profile_picture']) ? $doctor['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// Fetch clinic info
$stmt = $pdo->prepare("SELECT clinic_name, address, contact_info, logo FROM clinics WHERE clinic_id = ?");
$stmt->execute([$clinic_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch doctor’s visitation schedule
$visits = $pdo->prepare("SELECT * FROM doctor_visits WHERE doctor_id = ? AND clinic_id = ?");
$visits->execute([$doctor_id, $clinic_id]);
$visits = $visits->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Clinic & Visitations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/visitation.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <!-- 🌟 Navbar -->
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
                        <a href="visitation.php" class="nav-link text-white">Visitations</a>
                    </li>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                            <span id="notif_count"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.65rem; padding: 3px 6px;">
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-2"
                            style="width: 320px; max-height: 400px;" id="notif_list_container">
                            
                            <li class="d-flex justify-content-between align-items-center mb-2 px-2">
                                <h6 class="mb-0">Notifications</h6>
                                <button id="mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.8rem;" disabled>
                                    Mark all as read
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>

                            <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                                <li class="text-center text-muted">Loading...</li>
                            </div>
                        </ul>
                    </li>
                </ul>
                <!-- Profile -->
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i
                                    class="bi bi-person"></i> My Profile</a></li>
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
    <div class="container py-5">
        <h2 class="fw-bold">Clinic Information</h2>
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex align-items-center">
                <img src="../../uploads/logos/<?= htmlspecialchars($clinic['logo']) ?>" width="80" class="me-3 rounded">
                <div>
                    <h4><?= htmlspecialchars($clinic['clinic_name']) ?></h4>
                    <p class="mb-1"><strong>Address:</strong> <?= htmlspecialchars($clinic['address']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($clinic['contact_info']) ?></p>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>My Visitations</h4>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVisitModal">+ Add
                Visitation</button>
        </div>

        <table class="table table-bordered bg-white">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visits)): ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">No visitations added yet.</td>
                    </tr>
                <?php else:
                    foreach ($visits as $v): ?>
                        <tr>
                            <td><?= htmlspecialchars($v['day_of_week']) ?></td>
                            <td><?= htmlspecialchars($v['start_time']) ?></td>
                            <td><?= htmlspecialchars($v['end_time']) ?></td>
                            <td>
                                <form action="delete_visit.php" method="POST" class="d-inline">
                                    <input type="hidden" name="visit_id" value="<?= $v['visit_id'] ?>">
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Visitation Modal -->
    <div class="modal fade" id="addVisitModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="save_visit.php" method="POST" class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Visitation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Day of Week</label>
                        <select name="day_of_week" class="form-select" required>
                            <option value="">--Select Day--</option>
                            <?php
                            $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                            foreach ($days as $day)
                                echo "<option value='$day'>$day</option>";
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" name="start_time" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">End Time</label>
                        <input type="time" name="end_time" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Save</button>
                </div>
            </form>
        </div>
    </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleEdit(editMode) {
            if (!editMode) {
                document.querySelector("#editProfile form").reset();
            }
            document.getElementById("viewProfile").style.display = editMode ? "none" : "block";
            document.getElementById("editProfile").style.display = editMode ? "block" : "none";
        }
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

            // 💡 NEW: Listener for Mark All button
            document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
        });

        function loadAdminNotifications() {
            fetch("../../doc_fetch_notifications.php")
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
                    fetch("../../doc_mark_all_as_read.php", { method: 'POST' })
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
</body>

</html>