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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
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
</body>

</html>