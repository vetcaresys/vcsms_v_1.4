<?php
session_start();
require '../config.php';

// Redirect if not logged in or not a pet owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture path
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();
$name = htmlspecialchars($user['name']);

// Helper: Clinic logo
function getLogoPath($logo)
{
    return !empty($logo) ? "../uploads/logos/" . basename($logo) : "assets/default-clinic.jpg";
}

$contact = $user['contact_number'] ?? '';
if (!empty($contact)) {
    $contact = preg_replace('/\s+/', '', $contact); // remove spaces
    if (preg_match('/^09\d{9}$/', $contact)) {
        $contact = '+63' . substr($contact, 1);
    } elseif (preg_match('/^639\d{9}$/', $contact)) {
        $contact = '+' . $contact;
    }
} else {
    $contact = 'N/A';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    $errors = [];

    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!empty($contact) && !preg_match('/^09\d{9}$/', $contact)) {
        $errors[] = "Contact number must be 11 digits starting with 09.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // File validation (if uploaded)
    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Profile picture must be JPG or PNG.";
        }
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Profile picture must not exceed 2MB.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: pet_owner_dashboard.php"); // adjust filename
        exit;
    }

    // ✅ proceed with DB update if no errors

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pet Owner Dashboard - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/navbar.css">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* 🌟 Global Reset */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
            color: #2e2e2e;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
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

        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        /* 🧾 Cards */
        .card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #0d6efd;
        }

        /* 🧍 Modal */
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            background: linear-gradient(90deg, #0d6efd, #007bff);
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* ⚡ SweetAlert */
        .swal2-popup {
            font-family: 'Inter', sans-serif !important;
            border-radius: 15px !important;
        }

        /* 🧠 Responsive Footer */
        footer {
            background-color: #212529;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            margin-top: auto;
        }

        /* ✨ Dashboard Stats Grid */
        #dashboardStats .card {
            border-radius: 15px;
            text-align: center;
        }

        #dashboardStats .card h5 {
            color: #495057;
            font-family: 'Poppins', sans-serif;
        }

        #dashboardStats .display-6 {
            font-weight: 700;
        }
    </style>
</head>

<body>

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

    <?php if (isset($_SESSION['success'])): ?>
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

            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_pets.php" class="nav-link">Manage Pets</a></li>
                    <li class="nav-item"><a href="book_appointment.php" class="nav-link">Book Appointment</a></li>
                </ul>
                <div class="dropdown ms-auto">
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



    <!-- Main content -->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-primary">Welcome, <?= $name ?>!</h2>
                <p class="card-text">Manage your pets, view records, and book appointments.</p>
            </div>
        </div>
    </div>

    <main class="container mt-4">
        <div class="row g-4" id="dashboardStats">
            <!-- Stats will be loaded here via AJAX -->
        </div>
    </main>





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
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
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

    <!-- Footer -->
    <footer class="mt-auto bg-dark text-white py-3">
        <div class="container text-center small">
            All Rights Reserved. &copy; 2025 VetCareSys
        </div>
    </footer>



    <script>
        document.querySelector('input[name="contact_number"]').addEventListener('input', function (e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>

    <script>
        function toggleEdit(showEdit) {
            if (showEdit) {
                document.getElementById('viewProfile').style.display = 'none';
                document.getElementById('editProfile').style.display = 'block';
            } else {
                document.getElementById('viewProfile').style.display = 'block';
                document.getElementById('editProfile').style.display = 'none';
            }
        }
    </script>

    <script>
        (() => {
            'use strict';
            const form = document.getElementById('editProfileForm');
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function loadDashboardStats() {
            fetch("fetch_dashboard_stats.php")
                .then(res => res.json())
                .then(data => {
                    document.getElementById("dashboardStats").innerHTML = `
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">My Pets</h5>
                            <p class="display-6">${data.pets}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Upcoming</h5>
                            <p class="display-6 text-primary">${data.upcoming}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Completed</h5>
                            <p class="display-6 text-success">${data.completed}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Records</h5>
                            <p class="display-6 text-secondary">${data.records}</p>
                        </div>
                    </div>
                </div>
            `;
                });
        }

        // Load once
        loadDashboardStats();
        // Refresh every 10s
        setInterval(loadDashboardStats, 10000);
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