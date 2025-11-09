<?php
session_start();
require '../config.php';

// Check if session exists and role is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Fetch pending clinics from the clinics table
$sql = "
    SELECT 
        c.clinic_id, 
        c.logo, 
        c.business_permit, 
        u.name AS owner_name, 
        u.email, 
        u.contact_number AS owner_contact, 
        u.address AS owner_address
    FROM clinics c
    JOIN users u ON c.user_id = u.user_id
    WHERE c.status = 'pending'
";

$stmt = $pdo->query($sql);
$pending_clinics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - VetCareSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
            color: #2e2e2e;
            line-height: 1.6;
        }

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

        .nav-link:hover {
            color: #ffc107 !important;
        }

        .table tbody tr:hover {
            background-color: #f2f7ff;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                title: 'Success!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']);
    endif; ?>

    <!-- ✅ Updated Navbar with Notification Bell -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">VetCareSys Admin</a>

            <ul class="navbar-nav ms-auto align-items-center">

                <!-- 🔔 Notification Bell -->
                <li class="nav-item dropdown me-3">
                    <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                        <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                        <span id="notif_count"
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                            style="font-size: 0.65rem; padding: 3px 6px;">
                        </span>
                    </a>

                    <!-- Notification Dropdown -->
                    <ul class="dropdown-menu dropdown-menu-end p-2"
                        style="width: 320px; max-height: 400px; overflow-y: auto;" id="notif_list">
                        <li class="text-center text-muted">No notifications</li>
                    </ul>
                </li>

                <!-- Logout -->
                <li class="nav-item">
                    <form method="POST" action="logout.php" id="logoutForm" class="d-inline">
                        <button type="submit" class="btn btn-light btn-sm" id="logoutBtn">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container my-4 flex-grow-1">
        <h2 class="mb-3">Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p class="text-muted">Manage clinics and approve or reject applications here.</p>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">Pending Clinic Approvals</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-hover align-middle mb-0">
                        <thead class="table-primary">
                            <tr>
                                <th>Clinic Logo</th>
                                <th>Owner</th>
                                <th>Email</th>
                                <th>Contact Number</th>
                                <th>Owner Address</th>
                                <th>Business Permit</th>
                                <th style="width: 160px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pending_clinics) > 0): ?>
                                <?php foreach ($pending_clinics as $clinic): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($clinic['logo'])): ?>
                                                <img src="../<?= htmlspecialchars($clinic['logo']) ?>"
                                                    alt="Clinic Logo"
                                                    style="width:60px; height:60px; object-fit:cover; border-radius:8px; border:1px solid #ccc;">
                                            <?php else: ?>
                                                <span class="text-muted">No Logo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($clinic['owner_name']) ?></td>
                                        <td><?= htmlspecialchars($clinic['email']) ?></td>
                                        <td><?= htmlspecialchars($clinic['owner_contact']) ?></td>
                                        <td><?= htmlspecialchars($clinic['owner_address']) ?></td>
                                        <td>
                                            <?php if (!empty($clinic['business_permit'])): ?>
                                                <a href="../<?= htmlspecialchars($clinic['business_permit']) ?>"
                                                    target="_blank" class="btn btn-info btn-sm">
                                                    <i class="bi bi-file-earmark-text"></i> View Permit
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="admin_action.php?id=<?= $clinic['clinic_id'] ?>&action=approve"
                                                class="btn btn-success btn-sm">
                                                <i class="bi bi-check-circle"></i> Approve
                                            </a>
                                            <a href="admin_action.php?id=<?= $clinic['clinic_id'] ?>&action=reject"
                                                class="btn btn-danger btn-sm">
                                                <i class="bi bi-x-circle"></i> Reject
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No pending clinic approvals.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container text-center small">
            All Rights Reserved. &copy; 2025 VetCareSys
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
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
                    document.getElementById('logoutForm').submit();
                }
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);
        });

        function loadAdminNotifications() {
            fetch("../fetch_notifications.php")
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById("notif_list");
                    const count = document.getElementById("notif_count");

                    list.innerHTML = "";
                    let unreadCount = 0;

                    if (!data || data.length === 0) {
                        list.innerHTML = `<li class="text-center text-muted">No notifications</li>`;
                        count.textContent = "";
                        return;
                    }

                    data.forEach(n => {
                        if (n.status === "unread") unreadCount++;

                        list.innerHTML += `
                            <li>
                                <a href="${n.link ?? '#'}" class="dropdown-item d-flex justify-content-between align-items-start notif-item"
                                data-id="${n.notif_id}">
                                    <div>
                                        <strong>${n.subject}</strong><br>
                                        <small class="text-muted">${n.message}</small>
                                    </div>
                                    ${n.status === "unread" ? `<span class="badge bg-danger">New</span>` : ""}
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        `;
                    });

                    count.textContent = unreadCount > 0 ? unreadCount : "";
                });
        }

        // Mark as read when opening a notification
        document.addEventListener("click", function(e) {
            if (e.target.closest(".notif-item")) {
                const id = e.target.closest(".notif-item").dataset.id;
                fetch(`mark_as_read.php?id=${id}`);
            }
        });
    </script>

</body>
</html>
