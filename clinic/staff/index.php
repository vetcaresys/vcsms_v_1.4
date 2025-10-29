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
  <style>
    /* 🌟 Global Styles */
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fb;
      color: #2e2e2e;
      line-height: 1.6;
    }

    /* 🧭 Navbar */
    .navbar {
      background: linear-gradient(90deg, #0d6efd, #007bff);
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      letter-spacing: 0.3px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.25rem;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
    }

    .navbar-brand img {
      width: 38px;
      height: 38px;
      object-fit: cover;
      border-radius: 50%;
      background: #fff;
      padding: 3px;
      margin-right: 10px;
      transition: transform 0.2s ease;
    }

    .navbar-brand img:hover {
      transform: scale(1.08);
    }

    /* Links */
    .nav-link {
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .nav-link:hover {
      color: #ffc107 !important;
    }

    /* 🧾 Summary Cards */
    .summary-card {
      border: none;
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-card h5 {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .summary-card h2 {
      font-weight: 700;
      font-size: 2rem;
    }

    /* 💼 Tables */
    .table {
      border-radius: 10px;
      overflow: hidden;
      font-size: 0.95rem;
    }

    .table thead {
      background-color: #0d6efd;
      color: white;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .table tbody tr:hover {
      background-color: #f2f7ff;
    }

    /* 🪄 Buttons */
    .btn {
      border-radius: 8px;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* 🧩 Modals */
    .modal-content {
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      border-radius: 15px 15px 0 0;
      background: linear-gradient(90deg, #0d6efd, #007bff);
      color: white;
    }

    .modal-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    /* 🧍 Form */
    .form-label {
      font-weight: 600;
      color: #333;
    }

    .form-control {
      border-radius: 8px;
      border: 1px solid #ccc;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* ⚡ Sweet alert pop */
    .swal2-popup {
      font-family: 'Inter', sans-serif !important;
      border-radius: 15px !important;
    }

    /* 🌈 Badges */
    .badge {
      font-size: 0.85rem;
      padding: 6px 10px;
      border-radius: 8px;
    }

    /* 🐾 Page Titles */
    h4.text-primary {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      color: #0d6efd !important;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* 📦 Footer vibe */
    .container-footer {
      text-align: center;
      margin-top: 50px;
      font-size: 0.9rem;
      color: #777;
    }

    /* 🔍 Align Search label and input properly */
    .dataTables_wrapper .dataTables_filter {
      display: flex;
      justify-content: flex-end;
      /* keep it aligned right like default */
      align-items: center;
      margin-bottom: 12px !important;
      gap: 8px;
    }

    .dataTables_wrapper .dataTables_filter label {
      display: flex;
      align-items: center;
      gap: 6px;
      /* space between “Search:” and input */
      margin-bottom: 0;
      /* remove awkward spacing */
    }

    .dataTables_wrapper .dataTables_filter input {
      border-radius: 8px;
      border: 1px solid #ddd;
      padding: 6px 10px;
      outline: none;
      transition: all 0.2s ease;
      height: 36px;
    }

    .dataTables_wrapper .dataTables_filter input:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 4px rgba(13, 110, 253, 0.3);
    }
  </style>
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
        <!-- Profile Dropdown -->
        <div class="dropdown ms-auto">
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
      <div class="modal-content">

        <!-- View Profile -->
        <div id="viewProfile">
          <div class="modal-header">
            <h5 class="modal-title">My Profile</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3" width="100">
            <h4><?= $name ?></h4>
            <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($staff['contact_number']) ?></p>
            <p><strong>Role:</strong> <?= htmlspecialchars($staff['role']) ?></p>
          </div>
          <div class="modal-footer">
            <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
          </div>
        </div>

        <!-- Edit Profile -->
        <div id="editProfile" style="display:none;">
          <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title">Edit Profile</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            </div>
            <div class="modal-footer">
              <!-- <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button> -->
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
</body>

</html>