<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../clinic/staff/login.php');
    exit;
}

$name = htmlspecialchars($_SESSION['name']);
$clinic_id = $_SESSION['clinic_id'];

// start sa profile
$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

// Get staff info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();
//end sa profile


// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    try {
        // Check if email already exists
        $check = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $_SESSION['message'] = "❌ Email is already registered.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, contact_number, address) 
                                   VALUES (?, ?, ?, 'pet_owner', ?, ?)");
            if ($stmt->execute([$owner_name, $email, $password, $contact, $address])) {
                $_SESSION['message'] = "✅ Pet owner registered successfully!";
            } else {
                $_SESSION['message'] = "❌ Failed to register.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "❌ Database error: " . $e->getMessage();
    }

    // Redirect back so refresh won't resubmit form
    header("Location: manage_petowner.php");
    exit;
}

// ✅ Fetch all pet owners
// (Remove `created_at` if wala sa imong table, use name/email/id instead)
$ownersStmt = $pdo->prepare("SELECT * FROM users WHERE role = 'pet_owner' ORDER BY user_id DESC");
$ownersStmt->execute();
$owners = $ownersStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register Pet Owner - VetCareSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
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

<body class="bg-light">

    <?php if (!empty($_SESSION['message'])): ?>
        <script>
            Swal.fire({
                icon: "<?= strpos($_SESSION['message'], '✅') !== false ? 'success' : 'error' ?>",
                title: "<?= strpos($_SESSION['message'], '✅') !== false ? 'Success' : 'Error' ?>",
                text: "<?= $_SESSION['message'] ?>",
                confirmButtonColor: "#3085d6"
            });
        </script>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>




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
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
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

    <div class="container py-4 text-end">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerOwnerModal">
            Register Pet Owner
        </button>
    </div>

    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Registered Pet Owners</h5>
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table id="ownersTable" class="table table-striped align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Date Registered</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($owners)): ?>
                                <?php foreach ($owners as $index => $o): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($o['name']) ?></td>
                                        <td><?= htmlspecialchars($o['email']) ?></td>
                                        <td><?= htmlspecialchars($o['contact_number']) ?></td>
                                        <td><?= htmlspecialchars($o['address']) ?></td>
                                        <td><?= htmlspecialchars($o['created_at'] ?? '') ?></td>
                                        <td class="d-flex gap-2">
                                            <!-- Edit Button -->
                                            <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                data-bs-toggle="modal" data-bs-target="#editOwnerModal"
                                                data-id="<?= $o['user_id'] ?>" data-name="<?= htmlspecialchars($o['name']) ?>"
                                                data-email="<?= htmlspecialchars($o['email']) ?>"
                                                data-contact="<?= htmlspecialchars($o['contact_number']) ?>"
                                                data-address="<?= htmlspecialchars($o['address']) ?>">
                                                <i class="bi bi-pencil-square"></i> Edit
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">No pet owners registered yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editOwnerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editOwnerForm" method="POST" action="edit_petowner.php">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Edit Pet Owner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" maxlength="11"
                                pattern="\d{11}" title="Contact number must be 11 digits (e.g., 09xxxxxxxxx)" required
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <!-- Register Pet Owner Modal -->
    <div class="modal fade" id="registerOwnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Register Pet Owner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($message)): ?>
                        <div
                            class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form id="registerOwnerForm" method="POST" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" class="form-control" maxlength="11" pattern="\d{11}"
                                title="Contact number must be 11 digits (e.g., 09xxxxxxxxx)" required
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" class="form-control" rows="3" required></textarea>
                        </div>

                        <button type="submit" class="btn btn-success w-100">Register</button>
                    </form>
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
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3"
                            width="100">
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

    <!-- <form method="POST" action="delete_petowner.php" onsubmit="return confirm('Are you sure?');">
        <input type="hidden" name="user_id" value="<?= $o['user_id'] ?>">
        <button class="btn btn-sm btn-danger">🗑 Delete</button>
    </form> -->

    <script>
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }
    </script>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        var editModal = document.getElementById('editOwnerModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

            document.getElementById('edit_user_id').value = button.getAttribute('data-id');
            document.getElementById('edit_name').value = button.getAttribute('data-name');
            document.getElementById('edit_email').value = button.getAttribute('data-email');
            document.getElementById('edit_contact').value = button.getAttribute('data-contact');
            document.getElementById('edit_address').value = button.getAttribute('data-address');
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#ownersTable').DataTable();
        });
    </script>
    <script>
        document.getElementById("registerOwnerForm").addEventListener("submit", function (e) {
            e.preventDefault(); // stop form for validation check

            let name = this.querySelector("[name='name']").value.trim();
            let email = this.querySelector("[name='email']").value.trim();
            let password = this.querySelector("[name='password']").value.trim();
            let contact = this.querySelector("[name='contact']").value.trim();
            let address = this.querySelector("[name='address']").value.trim();

            if (!name || !email || !password || !contact || !address) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Fields",
                    text: "Please fill in all fields before submitting.",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            // All good → submit the form
            this.submit();
        });


        $('form[action="delete_petowner.php"]').on('submit', function (e) {
            e.preventDefault();
            let form = this;
            Swal.fire({
                title: "Are you sure?",
                text: "This pet owner will be permanently deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>

    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
    <script>
        document.getElementById("registerOwnerForm").addEventListener("submit", function (e) {
            e.preventDefault(); // stop form for validation check

            let name = this.querySelector("[name='name']").value.trim();
            let email = this.querySelector("[name='email']").value.trim();
            let password = this.querySelector("[name='password']").value.trim();
            let contact = this.querySelector("[name='contact']").value.trim();
            let address = this.querySelector("[name='address']").value.trim();

            if (!name || !email || !password || !contact || !address) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Fields",
                    text: "Please fill in all fields before submitting.",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            // Check valid email format
            let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Email",
                    text: "Please enter a valid email address.",
                });
                return;
            }

            // Check password length
            if (password.length < 6) {
                Swal.fire({
                    icon: "error",
                    title: "Weak Password",
                    text: "Password must be at least 6 characters long.",
                });
                return;
            }

            // Check contact is numeric and 11 digits (Philippines format)
            if (!/^\d{11}$/.test(contact)) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Contact",
                    text: "Contact number must be 11 digits (e.g., 09xxxxxxxxx).",
                });
                return;
            }

            // If all good → submit form
            this.submit();
        });
    </script>


</body>

</html>