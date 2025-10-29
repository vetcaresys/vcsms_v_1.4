<?php
session_start();
require '../config.php';

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

// ✅ Get clinic info
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

$clinic_id = $clinic['clinic_id'] ?? null;
$parent_id = $_GET['parent'] ?? null;
$name = htmlspecialchars($_SESSION['name']);

// Make sure the parent clinic exists and belongs to this logged-in user
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_id = ? AND user_id = ? AND parent_clinic_id IS NULL");
$stmt->execute([$parent_id, $user_id]);
$mainClinic = $stmt->fetch();

if (!$mainClinic) {
    die("Invalid main clinic.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = trim($_POST['clinic_name']);
    $address = trim($_POST['address']);
    $contact_info = trim($_POST['contact_info']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check digits only
    if (!preg_match('/^[0-9]{11}$/', $contact_info)) {
        $msg = "<div class='alert alert-danger'>Invalid contact number. Please enter exactly 11 digits.</div>";
    }

    // File uploads
    $base_dir = dirname(__DIR__) . "/uploads/"; // this points to /uploads in root
    if (!is_dir($base_dir)) {
        mkdir($base_dir, 0777, true);
    }

    // Logo
    if (!empty($_FILES['logo']['name'])) {
        $logo_dir = $base_dir . "logos/";
        if (!is_dir($logo_dir)) {
            mkdir($logo_dir, 0777, true);
        }

        $file_name = time() . "_logo_" . basename($_FILES["logo"]["name"]);
        $target_file = $logo_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            die("Logo file too large. Max 2MB allowed.");
        }

        if (in_array($file_type, ['jpg', 'jpeg', 'png'])) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                // ✅ Save filename only
                $logo_path = $file_name;
            }
        }
    }


    // Business Permit
    $permit_path = "";
    if (!empty($_FILES['business_permit']['name'])) {
        $permit_dir = $base_dir . "permits/";
        if (!is_dir($permit_dir)) {
            mkdir($permit_dir, 0777, true);
        }

        $file_name = time() . "_permit_" . basename($_FILES["business_permit"]["name"]);
        $target_file = $permit_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'pdf'])) {
            if (move_uploaded_file($_FILES["business_permit"]["tmp_name"], $target_file)) {
                // Save relative path for DB
                $permit_path = "uploads/permits/" . $file_name;
            }
        }
    }


    // 1. Check if email already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $msg = "<div class='alert alert-danger'>This email is already taken. Please choose another.</div>";
    } else {
        try {
            $pdo->beginTransaction();

            // 2. Create a new user for the branch
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'clinic_owner')");
            $stmt->execute([$clinic_name, $email, $password]);
            $branch_user_id = $pdo->lastInsertId();

            // 3. Create branch clinic linked to parent clinic
            $stmt = $pdo->prepare("INSERT INTO clinics 
                (user_id, clinic_name, address, contact_info, parent_clinic_id, logo, business_permit) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$branch_user_id, $clinic_name, $address, $contact_info, $parent_id, $logo_path, $permit_path]);

            $pdo->commit();

            $_SESSION['msg'] = "Branch registered successfully!";
            header("Location: manage_clinic.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
        }
    }

    $_SESSION['msg'] = "Branch registered successfully!";
header("Location: manage_clinic.php");
exit;

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Branch</title>
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

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
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
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">View
                                Profile</a></li>
                        <li><a class="dropdown-item" href="manage_clinic_details.php">Update Clinic Info</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="logout.php" class="m-0">
                                <button class="dropdown-item text-danger" type="submit">
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
    <br>
    <div class="container border rounded p-4 shadow-sm bg-white">
        <h2>Register Branch for <?= htmlspecialchars($mainClinic['clinic_name']) ?></h2>
        <?php if (!empty($msg))
            echo $msg; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">Branch Name</label>
                <input type="text" name="clinic_name" class="form-control" required pattern="^[A-Za-z\s\-\&]{3,50}$"
                    title="Branch name should be 3–50 characters and letters only.">
            </div>

            <div class="mb-3">
                <label class="form-label">Branch Address</label>
                <input type="text" name="address" class="form-control" required minlength="5" maxlength="100">
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Info</label>
                <input type="text" name="contact_info" class="form-control" pattern="[0-9]{11}" maxlength="11"
                    title="Please enter exactly 11 digits" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    required>
            </div>

            <hr>
            <h5>Branch Login Credentials</h5>
            <div class="mb-3">
                <label class="form-label">Branch Email (Login)</label>
                <input type="email" name="email" class="form-control" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Branch Password</label>
                <input type="password" name="password" class="form-control" required minlength="8"
                    pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
                    title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character.">
            </div>

            <hr>
            <h5>Uploads</h5>
            <div class="mb-3">
                <label class="form-label">Branch Logo</label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label">Business Permit (Image/PDF)</label>
                <input type="file" name="business_permit" class="form-control" accept="image/*,.pdf">
            </div>

            <button type="submit" class="btn btn-success">Register Branch</button>
            <a href="manage_clinic.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
    <br><br>
    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Profile Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">

                    <!-- Profile Picture -->
                    <?php
                    $profilePic = !empty($user['profile_picture'])
                        ? "../uploads/profiles/" . htmlspecialchars($user['profile_picture'])
                        : "../uploads/profiles/default.png";
                    ?>
                    <img src="<?= $profilePic ?>" alt="Profile" width="120" height="120" class="rounded-circle mb-3">

                    <h6 class="mb-3">User Information</h6>
                    <p><strong>Name:</strong> <?= htmlspecialchars($user['name']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                    <p><strong>Contact:</strong> <?= htmlspecialchars($user['contact_number'] ?? '') ?></p>
                    <p><strong>Address:</strong> <?= htmlspecialchars($user['address'] ?? '') ?></p>

                    <button class="btn btn-sm btn-outline-primary" data-bs-target="#editUserModal"
                        data-bs-toggle="modal" data-bs-dismiss="modal">
                        Edit User Info
                    </button>

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
                        <div class="mb-3"><label>Contact Number</label><input type="text" name="contact_number"
                                class="form-control" value="<?= htmlspecialchars($user['contact_number']) ?>"></div>
                        <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"
                                value="<?= htmlspecialchars($user['address']) ?>"></div>
                        <div class="mb-3"><label>Profile Picture</label><input type="file" name="profile_picture"
                                class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>