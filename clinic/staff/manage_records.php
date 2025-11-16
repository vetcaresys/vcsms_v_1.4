<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];
$role = $_SESSION['role'];
$name = htmlspecialchars($_SESSION['name']);

// 🖼️ Profile info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// 🐾 Fetch existing pet records
$stmt = $pdo->prepare("
    SELECT pr.record_id, pr.date_recorded, p.pet_name, u.name AS owner_name, rt.template_name, p.birth_date
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    JOIN record_templates rt ON pr.template_id = rt.template_id
    ORDER BY pr.date_recorded DESC
");
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🧩 Fetch available templates for modal dropdown
$templates = $pdo->query("SELECT template_id, template_name FROM record_templates")->fetchAll(PDO::FETCH_ASSOC);

// 🐕 Fetch all pets
$pets = $pdo->query("SELECT pet_id, pet_name FROM pets ORDER BY pet_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Pet Records - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="includes/css/manage_records.css">


</head>

<body class="bg-light">

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                title: 'Record Saved!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Remove ?success from URL after showing alert
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname);
                }
            });
        </script>
    <?php endif; ?>


    <!-- 🌟 Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">VetCareSys</a>
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
                            <form method="POST" action="../logout.php" class="m-0">
                                <button class="dropdown-item text-danger" type="submit"><i
                                        class="bi bi-box-arrow-right"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-primary"><i class="bi bi-clipboard2-pulse"></i> Manage Pet Records</h2>
                <p class="text-muted">Review and manage pet medical records from your clinic.</p>

                <button class="btn btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                    <i class="bi bi-plus-circle"></i> Add Record
                </button>


                <!-- Record Table -->
                <div class="card-body">

                    <table id="recordsTable" class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Pet Owner</th>
                                <th>Pet Name</th>
                                <th>Age</th>
                                <th>Record Type</th>
                                <th>Date Recorded</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r):
                                $birth = new DateTime($r['birth_date']);
                                $age = $birth->diff(new DateTime())->y . " yrs";
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($r['pet_name']) ?></td>
                                    <td><?= $age ?></td>
                                    <td><?= htmlspecialchars($r['template_name']) ?></td>
                                    <td><?= date("M d, Y h:i A", strtotime($r['date_recorded'])) ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm viewRecordBtn"
                                            data-id="<?= $r['record_id'] ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <button class="btn btn-warning btn-sm editRecordBtn"
                                            data-id="<?= $r['record_id'] ?>">
                                            <i class="bi bi-pencil"></i> Edit
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="save_pet_record.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Add New Pet Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">Select Pet</label>
                            <select name="pet_id" class="form-select" required>
                                <option value="">-- Select Pet --</option>
                                <?php foreach ($pets as $p): ?>
                                    <option value="<?= $p['pet_id'] ?>"><?= htmlspecialchars($p['pet_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Record Template</label>
                            <select name="template_id" id="templateSelect" class="form-select" required>
                                <option value="">-- Choose Record Type --</option>
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['template_id'] ?>">
                                        <?= htmlspecialchars($t['template_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="dynamicFields"></div>

                        <hr>
                        <h5 class="mt-4">🧴 Medicines / Supplies Used</h5>
                        <p class="text-muted">Select items used during this treatment and quantity.</p>

                        <div id="medicineContainer">
                            <div class="row mb-2 medicine-row">
                                <div class="col-md-6">
                                    <label class="form-label">Item</label>
                                    <select name="item_id[]" class="form-select">
                                        <option value="">-- Select Item --</option>
                                        <?php
                                        $items = $pdo->prepare("SELECT item_id, item_name, quantity FROM inventory WHERE clinic_id = ?");
                                        $items->execute([$clinic_id]);
                                        foreach ($items as $i) {
                                            echo '<option value="' . $i['item_id'] . '">' .
                                                htmlspecialchars($i['item_name']) . ' (Available: ' . $i['quantity'] . ')' .
                                                '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Quantity Used</label>
                                    <input type="number" name="quantity_used[]" class="form-control" min="1" value="1">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger removeRow w-100"><i
                                            class="bi bi-trash"></i></button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMedicineRow">
                            <i class="bi bi-plus-circle"></i> Add Another Item
                        </button>

                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Record</button>
                    </div>
                </form>
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
                                    <input type="password" name="current_password" id="currentPassword"
                                        class="form-control" placeholder="Enter current password">
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
                                    <input type="password" name="confirm_password" id="confirmPassword"
                                        class="form-control" minlength="6" placeholder="Re-enter new password">
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

    <!-- View Record Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> View Pet Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="recordDetails">
                    <div class="text-center text-muted">Loading record details...</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editRecordForm" method="POST" action="update_pet_record.php">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Pet Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editRecordContent">
                        <div class="text-center text-muted">Loading record data...</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50, 100],
                "order": [[4, "desc"]], // sort by date
                "language": {
                    "search": "Search Records:",
                    "lengthMenu": "Show _MENU_ entries"
                }
            });
        });
    </script>

    <script>
        document.querySelectorAll('.editRecordBtn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const modalBody = document.getElementById('editRecordContent');
                modalBody.innerHTML = "<div class='text-center text-muted'>Loading...</div>";
                const modal = new bootstrap.Modal(document.getElementById('editRecordModal'));
                modal.show();

                try {
                    const response = await fetch('edit_pet_record.php?id=' + id);
                    const html = await response.text();
                    modalBody.innerHTML = html;
                } catch (err) {
                    modalBody.innerHTML = "<div class='text-danger'>Failed to load record data.</div>";
                }
            });
        });
    </script>


    <script>
        document.querySelectorAll('.viewRecordBtn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const modalBody = document.getElementById('recordDetails');
                modalBody.innerHTML = "<div class='text-center text-muted'>Loading...</div>";

                const modal = new bootstrap.Modal(document.getElementById('viewRecordModal'));
                modal.show();

                try {
                    const response = await fetch('view_pet_record.php?id=' + id);
                    const html = await response.text();
                    modalBody.innerHTML = html;
                } catch (error) {
                    modalBody.innerHTML = "<div class='text-danger'>Failed to load record details.</div>";
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('templateSelect').addEventListener('change', async function () {
            const templateId = this.value;
            const container = document.getElementById('dynamicFields');
            container.innerHTML = '';

            if (!templateId) return;

            const res = await fetch('get_template.php?id=' + templateId);
            const data = await res.json();

            data.fields.forEach(field => {
                const wrapper = document.createElement('div');
                wrapper.classList.add('mb-3');
                wrapper.innerHTML = `
      <label class="form-label">${field.label}</label>
      ${field.type === 'textarea'
                        ? `<textarea name="${field.label.toLowerCase().replace(/ /g, '_')}" class="form-control"></textarea>`
                        : `<input type="${field.type}" name="${field.label.toLowerCase().replace(/ /g, '_')}" class="form-control">`}
    `;
                container.appendChild(wrapper);
            });
        });
    </script>

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                title: 'Record Saved!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

    <script>
        const tableRows = Array.from(document.querySelectorAll('#recordsTable tbody tr'));
        let currentEntries = 10; // default

        function updateTableDisplay() {
            const filter = document.getElementById('recordSearch').value.toLowerCase();
            const visibleRows = tableRows.filter(row => row.innerText.toLowerCase().includes(filter));

            visibleRows.forEach((row, i) => {
                row.style.display = i < currentEntries ? '' : 'none';
            });
        }

        document.getElementById('entriesSelect').addEventListener('change', function () {
            currentEntries = parseInt(this.value);
            updateTableDisplay();
        });

        document.getElementById('recordSearch').addEventListener('keyup', function () {
            updateTableDisplay();
        });

        document.getElementById('resetSearch').addEventListener('click', function () {
            document.getElementById('recordSearch').value = '';
            updateTableDisplay();
        });

        // Initialize table display
        updateTableDisplay();
    </script>

    <script>
        document.getElementById('recordSearch').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#recordsTable tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('resetSearch').addEventListener('click', function () {
            document.getElementById('recordSearch').value = '';
            const rows = document.querySelectorAll('#recordsTable tbody tr');
            rows.forEach(row => row.style.display = '');
        });


        let searchTimeout;
        document.getElementById('recordSearch').addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#recordsTable tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }, 300);
        });
    </script>

    <script>
        function toggleEdit(showEdit) {
            const viewProfile = document.getElementById('viewProfile');
            const editProfile = document.getElementById('editProfile');

            if (showEdit) {
                viewProfile.style.display = 'none';
                editProfile.style.display = 'block';
            } else {
                viewProfile.style.display = 'block';
                editProfile.style.display = 'none';
            }
        }
    </script>

    <script>
        document.getElementById('addMedicineRow').addEventListener('click', function () {
            const container = document.getElementById('medicineContainer');
            const newRow = container.querySelector('.medicine-row').cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(el => el.value = '');
            container.appendChild(newRow);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('removeRow')) {
                e.target.closest('.medicine-row').remove();
            }
        });
    </script>
   <script>
         document.addEventListener("DOMContentLoaded", function() {
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
    document.addEventListener("click", function(e) {
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