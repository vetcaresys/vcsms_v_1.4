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

        /* 🧭 Datatables */
        div.dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        div.dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
        }

        /* 🧁 Animations */
        .card,
        .modal-content {
            transition: all 0.25s ease-in-out;
        }
    </style>
</head>

<body class="d-flex flex-column min-vh-100">

    <?php if (isset($_SESSION['success'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Success!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']);
    endif; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">VetCareSys Admin</a>
            <div class="ms-auto">
                <form method="POST" action="logout.php" id="logoutForm" class="d-inline">
                    <button type="submit" class="btn btn-light btn-sm" id="logoutBtn">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </div>
    </nav>

    <?php if (isset($_SESSION['message'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Notification',
                text: "<?= addslashes($_SESSION['message']) ?>",
                icon: 'success',
                confirmButtonColor: '#3085d6',
                timer: 2500,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="container my-4 flex-grow-1">
        <h2 class="mb-3">Welcome, Admin <?php echo htmlspecialchars($_SESSION['name']); ?>!</h2>
        <p class="text-muted">Manage clinics and approve or reject applications here.</p>

        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold">
                Pending Clinic Approvals
            </div>
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
                                                <img src="<?= htmlspecialchars($clinic['logo']) ?>" 
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
                                                <a href="<?= htmlspecialchars($clinic['business_permit']) ?>" 
                                                    target="_blank" 
                                                    class="btn btn-info btn-sm">
                                                    <i class="bi bi-file-earmark-text"></i> View Permit
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">No file</span>
                                            <?php endif; ?>
                                        </td>
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

    <!-- Footer -->
    <footer class="bg-dark text-white py-3 mt-auto">
        <div class="container text-center small">
            All Rights Reserved. &copy; 2025 VetCareSys
        </div>
    </footer>

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
</body>

</html>