<?php
session_start();
include '../../config.php';

// 🔒 Ensure only staff can access
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
  header('Location: ../clinic/staff/login.php');
  exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

// 🧍 Fetch staff info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// 🐾 Pets Count (system-wide)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets");
$stmt->execute();
$pets = $stmt->fetchColumn();

// 👥 Pet Owners
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'pet_owner'");
$stmt->execute();
$owners = $stmt->fetchColumn();

// 📅 Appointments
$stmt = $pdo->prepare("
  SELECT 
    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed
  FROM appointments
");
$stmt->execute();
$appointments = $stmt->fetch(PDO::FETCH_ASSOC);

//  Medical Records
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_records");
$stmt->execute();
$records = $stmt->fetchColumn();

// Inventory (Low stock)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE quantity < 5");
$stmt->execute();
$lowStock = $stmt->fetchColumn();

// Unread Inquiries
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'");
$stmt->execute();
$inquiries = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Clinic Staff Dashboard - VetCareSys</title>
  <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Buttons for print/export -->
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="includes/css/index.css">
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
            <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2" width="35"
              height="35">
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

  <div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2 class="fw-bold text-dark">Staff Dashboard</h2>
      <span class="text-muted">Updated every 15s</span>
    </div>

    <div class="row g-4" id="dashboard-widgets">
      <!-- Realtime cards go here -->
      <!-- 🐾 Registered Pets -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-heart-pulse fs-1 text-primary"></i>
            <h6 class="mt-2 text-muted">Registered Pets</h6>
            <h3 class="fw-bold text-dark" id="petsCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 👥 Pet Owners -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-person-badge fs-1 text-success"></i>
            <h6 class="mt-2 text-muted">Pet Owners</h6>
            <h3 class="fw-bold text-dark" id="ownersCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ⏳ Pending Appointments -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-clock-history fs-1 text-warning"></i>
            <h6 class="mt-2 text-muted">Pending Appointments</h6>
            <h3 class="fw-bold text-dark" id="pendingCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ✅ Approved Appointments -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-check2-square fs-1 text-info"></i>
            <h6 class="mt-2 text-muted">Approved Appointments</h6>
            <h3 class="fw-bold text-dark" id="approvedCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 🩺 Completed Appointments -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-check-circle fs-1 text-success"></i>
            <h6 class="mt-2 text-muted">Completed Appointments</h6>
            <h3 class="fw-bold text-dark" id="completedCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 📄 Medical Records -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-file-earmark-medical fs-1 text-danger"></i>
            <h6 class="mt-2 text-muted">Medical Records</h6>
            <h3 class="fw-bold text-dark" id="recordsCount">0</h3>
          </div>
        </div>
      </div>

      <!--  Low Stock -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-exclamation-triangle fs-1 text-danger"></i>
            <h6 class="mt-2 text-muted">Low Stock Items</h6>
            <h3 class="fw-bold text-dark" id="lowStockCount">0</h3>
          </div>
        </div>
      </div>

      <!-- Unread Inquiries -->
      <div class="col-md-4 col-lg-3">
        <div class="card shadow-sm border-0 summary-card">
          <div class="card-body">
            <i class="bi bi-envelope fs-1 text-secondary"></i>
            <h6 class="mt-2 text-muted">Unread Inquiries</h6>
            <h3 class="fw-bold text-dark" id="inquiriesCount">0</h3>
          </div>
        </div>
      </div>
    </div>

    <div class="card shadow-sm mt-4">
      <div class="card-header d-flex justify-content-between align-items-center bg-light border-bottom">
        <h5 class="mb-0 text-primary fw-bold">
          <i class="bi bi-clock-history me-2"></i> Inventory Activity Log
        </h5>
      </div>

      <div class="card-body">
        <div class="table-responsive">
          <table id="inventoryLogTable" class="table table-striped table-bordered align-middle text-center">
            <thead class="table-dark text-uppercase">
              <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Action</th>
                <th>Qty Added</th>
                <th>Previous Qty</th>
                <th>New Qty</th>
                <th>Performed By</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $logs = $pdo->query("
            SELECT l.*, i.item_name, s.name AS staff_name 
            FROM inventory_activity_log l
            JOIN inventory i ON l.item_id = i.item_id
            JOIN staff s ON l.staff_id = s.staff_id
            ORDER BY l.date_action DESC
          ")->fetchAll();

              if ($logs):
                foreach ($logs as $log): ?>
                  <tr>
                    <td><?= date('M d, Y h:i A', strtotime($log['date_action'])) ?></td>
                    <td class="fw-semibold"><?= htmlspecialchars($log['item_name']) ?></td>
                    <td>
                      <span
                        class="badge 
                    <?= $log['action_type'] == 'add' ? 'bg-success' : ($log['action_type'] == 'remove' ? 'bg-danger' : 'bg-info') ?>">
                        <?= ucfirst($log['action_type']) ?>
                      </span>
                    </td>
                    <td><?= $log['quantity_added'] ?></td>
                    <td><?= $log['previous_quantity'] ?></td>
                    <td><?= $log['new_quantity'] ?></td>
                    <td class="text-muted"><?= htmlspecialchars($log['staff_name']) ?></td>
                  </tr>
                <?php endforeach;
              else: ?>
                <tr>
                  <td colspan="7" class="text-center text-muted py-3">
                    No activity logs available.
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

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
            <h4 class="fw-bold text-primary mb-3"><?= htmlspecialchars($staff['name']) ?></h4>

            <!-- 🧾 Info Table -->
            <div class="table-responsive">
              <table class="table table-bordered align-middle">
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
                <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>"
                  required>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>"
                  required>
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
                  <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="currentPassword">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <!-- New Password -->
              <div class="mb-3 position-relative">
                <label class="form-label">New Password</label>
                <div class="input-group">
                  <input type="password" name="new_password" id="newPassword" class="form-control" minlength="6"
                    placeholder="Enter new password">
                  <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="newPassword">
                    <i class="bi bi-eye"></i>
                  </button>
                </div>
              </div>

              <!-- Confirm Password -->
              <div class="mb-3 position-relative">
                <label class="form-label">Confirm New Password</label>
                <div class="input-group">
                  <input type="password" name="confirm_password" id="confirmPassword" class="form-control" minlength="6"
                    placeholder="Re-enter new password">
                  <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="confirmPassword">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <!-- jQuery + DataTables JS -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>


  <script>
    function toggleEdit(isEditing) {
      const viewProfile = document.getElementById("viewProfile");
      const editProfile = document.getElementById("editProfile");

      if (isEditing) {
        viewProfile.style.display = "none";
        editProfile.style.display = "block";
      } else {
        editProfile.style.display = "none";
        viewProfile.style.display = "block";
      }
    }
  </script>

  <script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('profile_updated')) {
      Swal.fire({
        icon: 'success',
        title: 'Profile Updated!',
        text: 'Your profile information has been successfully saved.',
        confirmButtonColor: '#0d6efd',
        timer: 2000,
        showConfirmButton: false
      });

      // Clean URL (remove ?profile_updated=1 after showing alert)
      window.history.replaceState({}, document.title, window.location.pathname);
    }
  </script>

  <script>
    async function loadDashboard() {
      try {
        const response = await fetch('dashboard.php');
        const data = await response.json();

        // Update all counters dynamically
        document.getElementById('petsCount').textContent = data.pets || 0;
        document.getElementById('ownersCount').textContent = data.owners || 0;
        document.getElementById('pendingCount').textContent = data.appointments.pending || 0;
        document.getElementById('approvedCount').textContent = data.appointments.approved || 0;
        document.getElementById('completedCount').textContent = data.appointments.completed || 0;
        document.getElementById('recordsCount').textContent = data.records || 0;
        document.getElementById('lowStockCount').textContent = data.lowStock || 0;
        document.getElementById('inquiriesCount').textContent = data.inquiries || 0;

        // Add smooth pulse animation
        document.querySelectorAll('.summary-card').forEach(card => {
          card.classList.add('pulse');
          setTimeout(() => card.classList.remove('pulse'), 600);
        });

      } catch (error) {
        console.error("Dashboard load error:", error);
      }
    }

    // 🔁 Auto-refresh every 10 seconds
    loadDashboard();
    setInterval(loadDashboard, 10000);
  </script>


  <style>
    /* 🫀 Small pulse effect when data updates */
    @keyframes pulse {
      0% {
        transform: scale(1);
        box-shadow: 0 0 0 rgba(13, 110, 253, 0.4);
      }

      50% {
        transform: scale(1.03);
        box-shadow: 0 0 15px rgba(13, 110, 253, 0.3);
      }

      100% {
        transform: scale(1);
        box-shadow: 0 0 0 rgba(13, 110, 253, 0);
      }
    }

    .pulse {
      animation: pulse 0.6s ease;
    }
  </style>

  <script>
    $(document).ready(function () {
      $('#inventoryLogTable').DataTable({
        dom: 'Bfrtip',
        pageLength: 10,
        lengthMenu: [5, 10, 25, 50, 100],
        order: [[0, 'desc']],
        language: {
          search: "Search:",
          lengthMenu: "Show _MENU_ entries",
          info: "Showing _START_ to _END_ of _TOTAL_ entries"
        }
      });
    });
  </script>
  <script>
    async function loadDashboard() {
      try {
        const response = await fetch('fetch_dashboard_data.php');
        const data = await response.json();

        document.getElementById('petsCount').textContent = data.pets || 0;
        document.getElementById('ownersCount').textContent = data.owners || 0;
        document.getElementById('pendingCount').textContent = data.appointments.pending || 0;
        document.getElementById('approvedCount').textContent = data.appointments.approved || 0;
        document.getElementById('completedCount').textContent = data.appointments.completed || 0;
        document.getElementById('recordsCount').textContent = data.records || 0;
        document.getElementById('lowStockCount').textContent = data.lowStock || 0;
        document.getElementById('inquiriesCount').textContent = data.inquiries || 0;

      } catch (error) {
        console.error("Dashboard load error:", error);
      }
    }

    // Auto-refresh every 10 seconds
    loadDashboard();
    setInterval(loadDashboard, 10000);

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
</body>

</html>