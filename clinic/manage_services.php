<?php
session_start();
require '../config.php';

// Only allow clinic owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get user info and navbar
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$name = htmlspecialchars($_SESSION['name']);


// Get this owner's clinic ID
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch();

if (!$clinic) {
    $errorMsg = "<div class='alert alert-danger'>You must register your clinic first.</div>";
} else {
    $clinic_id = $clinic['clinic_id'];

    // Add new service
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
        $service_name = ($_POST['service_name'] === 'Other') ? $_POST['custom_service'] : $_POST['service_name'];
        $duration = $_POST['duration'];

        $stmt = $pdo->prepare("INSERT INTO clinic_services (clinic_id, service_name, duration)
                       VALUES (?, ?, ?)");
        $stmt->execute([$clinic_id, $service_name, $duration]);
    }
    // Update service
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
        $service_id = $_POST['service_id'];
        $service_name = ($_POST['service_name'] === 'Other') ? $_POST['custom_service'] : $_POST['service_name'];
        $duration = $_POST['duration'];

        $stmt = $pdo->prepare("UPDATE clinic_services 
                               SET service_name = ?, duration = ? 
                               WHERE service_id = ? AND clinic_id = ?");
        $stmt->execute([$service_name, $duration, $service_id, $clinic_id]);
    }
    // Delete service
    elseif (isset($_GET['delete'])) {
        $service_id = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM clinic_services WHERE service_id = ? AND clinic_id = ?");
        $stmt->execute([$service_id, $clinic_id]);
    }

    // Fetch all services
    $services = $pdo->prepare("SELECT * FROM clinic_services WHERE clinic_id = ?");
    $services->execute([$clinic_id]);
    $serviceList = $services->fetchAll();
}



?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/manage_services.css">
</head>

<body class="bg-light">

    <?php
    $alertScript = ""; // prepare variable to hold JS alert scripts
    
    if (!$clinic) {
        $alertScript = "
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'warning',
                title: 'No Clinic Found',
                text: 'You must register your clinic first before managing services.',
                confirmButtonColor: '#0d6efd'
            });
        });
        </script>
    ";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Added!',
                    text: 'New service has been added successfully.',
                    confirmButtonColor: '#198754'
                });
            });
            </script>
        ";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Updated!',
                    text: 'Service details were updated successfully.',
                    confirmButtonColor: '#ffc107'
                });
            });
            </script>
        ";
        } elseif (isset($_GET['delete'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Deleted!',
                    text: 'The service has been removed.',
                    confirmButtonColor: '#dc3545'
                });
            });
            </script>
        ";
        }
    }
    ?>
    <?= $alertScript ?? '' ?>


    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_clinic.php" class="nav-link text-white">Manage Clinic</a></li>
                    <li class="nav-item"><a href="manage_staff.php" class="nav-link text-white">Manage Staff</a></li>
                    <li class="nav-item"><a href="manage_clinic_schedules.php" class="nav-link text-white">Manage
                            Schedules</a></li>
                    <li class="nav-item"><a href="manage_services.php" class="nav-link text-white">Manage Services</a>
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
                        <img src="../uploads/profiles/<?= htmlspecialchars($profilePic) ?>" alt="Profile" width="32"
                            height="32" class="rounded-circle me-2">
                        <strong><?= htmlspecialchars($name) ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">View
                                Profile</a></li>
                        <li><a class="dropdown-item" href="manage_clinic_details.php">Update Clinic Info</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="logout.php" id="logoutForm" class="m-0">
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
            <div class="row g-4">
                <!-- Add Service Form (Left Column) -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Service Name</label>
                                    <select name="service_name" id="service_name" class="form-select" required
                                        onchange="toggleCustomService()">
                                        <option value="" disabled selected>Select a service</option>
                                        <option value="General Check-up">General Check-up</option>
                                        <option value="Vaccination">Vaccination</option>
                                        <option value="Deworming">Deworming</option>
                                        <option value="Grooming">Grooming</option>
                                        <option value="Dental Cleaning">Dental Cleaning</option>
                                        <option value="Spaying / Neutering">Spaying / Neutering</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Emergency Treatment">Emergency Treatment</option>
                                        <option value="Ultrasound">Ultrasound</option>
                                        <option value="X-ray">X-ray</option>
                                        <option value="Laboratory Test">Laboratory Test</option>
                                        <option value="Other">Other (specify)</option>
                                    </select>

                                    <!-- Hidden custom input field -->
                                    <input type="text" name="custom_service" id="custom_service" class="form-control mt-2"
                                        placeholder="Enter custom service name" style="display:none;">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="duration" class="form-control" placeholder="e.g., 30 minutes"
                                        required>
                                </div>
                                <div class="col-12 d-flex align-items-end">
                                    <button type="submit" name="add_service" class="btn btn-success w-100">
                                        <i class="bi bi-check-lg"></i> Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Current Services Table (Right Column) -->
                <div class="col-lg-8">
                    <div class="card shadow h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Current Services</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($serviceList)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Service</th>
                                                <th>Duration</th>
                                                <th style="width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($serviceList as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['service_name']); ?></td>
                                                    <td><?= htmlspecialchars($row['duration']); ?></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <!-- Edit button -->
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                                data-bs-target="#editServiceModal<?= $row['service_id']; ?>">
                                                                <i class="bi bi-pencil-square"></i> Edit
                                                            </button>

                                                            <!-- Delete button -->
                                                            <button type="button" class="btn btn-sm btn-danger"
                                                                onclick="confirmDelete(<?= $row['service_id']; ?>)">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </div>
                                                    </td>

                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="p-3 mb-0">No services added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Profile Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <?php
                        $profilePic = !empty($user['profile_picture'])
                            ? "../uploads/profiles/" . htmlspecialchars($user['profile_picture'])
                            : "../assets/default-profile.png"; // fallback kung walay pic
                        ?>
                        <img src="<?= $profilePic ?>" alt="Profile Picture"
                            class="rounded-circle border border-3 border-primary mb-3" width="150" height="150"
                            style="object-fit: cover;">
                        <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                        <small class="text-muted">Clinic Staff</small>
                    </div>

                    <!-- Formal Info Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Full Name</th>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= htmlspecialchars($user['contact_number'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary px-4" data-bs-target="#editUserModal"
                            data-bs-toggle="modal" data-bs-dismiss="modal">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                                value="<?= htmlspecialchars($user['contact_number']) ?>" maxlength="11"
                                pattern="^0\d{10}$" title="Enter a valid 11-digit number (e.g. 09123456789)" oninput="
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

    <?php foreach ($serviceList as $row): ?>
        <!-- Edit Service Modal -->
        <div class="modal fade" id="editServiceModal<?= $row['service_id']; ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" action="">
                        <div class="modal-header bg-warning text-dark">
                            <h5 class="modal-title">Edit Service</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="service_id" value="<?= $row['service_id']; ?>">

                            <div class="mb-3">
                                <label class="form-label">Service Name</label>
                                <input type="text" name="service_name" class="form-control"
                                    value="<?= htmlspecialchars($row['service_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Duration</label>
                                <input type="text" name="duration" class="form-control"
                                    value="<?= htmlspecialchars($row['duration']); ?>" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_service" class="btn btn-warning">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
        function toggleCustomService() {
            const select = document.getElementById('service_name');
            const customInput = document.getElementById('custom_service');
            if (select.value === 'Other') {
                customInput.style.display = 'block';
                customInput.required = true;
            } else {
                customInput.style.display = 'none';
                customInput.required = false;
                customInput.value = '';
            }
        }
    </script>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "This service will be permanently deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, delete it"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?delete=" + id;
                }
            });
        }
    </script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
    document.addEventListener("DOMContentLoaded", function () {
      loadAdminNotifications();

      // Load when bell icon is clicked
      document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

      // 💡 NEW: Listener for Mark All button
      document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
    });

    function loadAdminNotifications() {
      fetch("../clinic_fetch_notifications.php")
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
          fetch("../clinic_mark_all_as_read.php", { method: 'POST' })
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
</body>

</html>