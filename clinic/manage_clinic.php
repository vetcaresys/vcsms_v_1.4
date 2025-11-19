<?php
include '../config.php';
session_start();
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

// Check if this user owns a MAIN clinic
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ? AND parent_clinic_id IS NULL");
$stmt->execute([$user_id]);
$mainClinic = $stmt->fetch();

// Check if this user is a BRANCH clinic
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ? AND parent_clinic_id IS NOT NULL");
$stmt->execute([$user_id]);
$branchClinic = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clinic & Branches</title>
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/manage_clinic.css">
</head>

<body>

    <?php if (!empty($_SESSION['msg'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    title: 'Success!',
                    text: <?= json_encode($_SESSION['msg']) ?>,
                    icon: 'success',
                    confirmButtonColor: '#3085d6'
                });
            });
        </script>
        <?php unset($_SESSION['msg']); ?>
    <?php endif; ?>

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
    <br>
    <div class="container mt-4">
        <div class="p-4 border rounded shadow-sm bg-white">
            <?php if ($mainClinic): ?>
                <!-- MAIN CLINIC VIEW -->
                <h2><?= htmlspecialchars($mainClinic['clinic_name']) ?> (Main Clinic)</h2>
                <p><strong>Address:</strong> <?= htmlspecialchars($mainClinic['address']) ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($mainClinic['contact_info']) ?></p>

                <!-- Add Branch Button -->
                <a href="register_branch.php?parent=<?= $mainClinic['clinic_id'] ?>" class="btn btn-primary mb-3">
                    + Register New Branch
                </a>

                <!-- Branch List -->
                <h3>Branches</h3>
                <ul class="list-group">
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM clinics WHERE parent_clinic_id = ?");
                    $stmt->execute([$mainClinic['clinic_id']]);
                    $branches = $stmt->fetchAll();

                    if ($branches):
                        foreach ($branches as $branch): ?>
                            <li class="list-group-item">
                                <strong><?= htmlspecialchars($branch['clinic_name']) ?></strong><br>
                                <?= htmlspecialchars($branch['address']) ?><br>
                                <small><?= htmlspecialchars($branch['contact_info']) ?></small>
                            </li>
                        <?php endforeach;
                    else: ?>
                        <li class="list-group-item text-muted">No branches yet.</li>
                    <?php endif; ?>
                </ul>


            <?php elseif ($branchClinic): ?>
                <!-- BRANCH CLINIC VIEW -->
                <div class="alert alert-info">
                    <strong>Note:</strong> This account belongs to a <b>branch clinic</b>.
                    Only the <b>main clinic</b> can register and manage branches.
                </div>
                <h4><?= htmlspecialchars($branchClinic['clinic_name']) ?> (Branch Clinic)</h4>
                <p><strong>Address:</strong> <?= htmlspecialchars($branchClinic['address']) ?></p>
                <p><strong>Contact:</strong> <?= htmlspecialchars($branchClinic['contact_info']) ?></p>

            <?php else: ?>
                <!-- USER WITHOUT A CLINIC -->
                <div class="alert alert-warning">
                    You haven’t registered your main clinic yet.
                    <a href="clinic_details.php" class="btn btn-sm btn-success">Register Main Clinic</a>
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
                                    pattern="^0\d{10}$" title="Enter a valid 11-digit number (e.g. 09123456789)"
                                    oninput="
                                // remove any non-digit characters
                                this.value = this.value.replace(/[^0-9]/g, '');
                                // limit to 11 digits only
                                if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                                <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                            </div>
                            <div class="mb-3"><label>Address</label><input type="text" name="address"
                                    class="form-control" value="<?= htmlspecialchars($user['address']) ?>"></div>
                            <div class="mb-3"><label>Profile Picture</label><input type="file" name="profile_picture"
                                    class="form-control"></div>

                            <h6 class="text-primary">Change Password (optional)</h6>
                            <!-- Current Password -->
                            <div class="mb-3 position-relative">
                                <label>Current Password</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" class="form-control"
                                        id="currentPassword" placeholder="Enter current password">
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
                                    <input type="password" name="confirm_password" class="form-control"
                                        id="confirmPassword" minlength="6" placeholder="Re-enter new password">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button"
                                        data-target="confirmPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <!-- ✅ Include SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <script>
            Swal.fire({
                title: '🎉 Branch Added!',
                text: <?= json_encode($_SESSION['msg']) ?>,
                icon: 'success',
                background: '#f8f9fb',
                color: '#2e2e2e',
                confirmButtonColor: '#0d6efd',
                confirmButtonText: 'Nice!',
                customClass: {
                    popup: 'rounded-4 shadow-lg'
                }
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