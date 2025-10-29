<?php
session_start();
require '../config.php';

// ✅ Only allow clinic_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = htmlspecialchars($_SESSION['name'] ?? '');

// Get user info and navbar
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$name = htmlspecialchars($_SESSION['name']);



// ✅ Get clinic info
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch();

$staffMembers = [];

if (!$clinic) {
    $_SESSION['error'] = "You must register your clinic first before adding staff.";
    header("Location: ../clinic/manage_clinic.php");
    exit;
}

$clinic_id = $clinic['clinic_id'];

// ✅ ADD STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staff_name = trim($_POST['name']);
    $staff_role = $_POST['role'];
    $contact = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    $errors = [];

    if (strlen($staff_name) < 3)
        $errors[] = "Name must be at least 3 characters.";
    if (!in_array($staff_role, ['staff', 'doctor']))
        $errors[] = "Invalid role.";
    if (!preg_match('/^09\d{9}$/', $contact))
        $errors[] = "Invalid contact number format.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    if (strlen($password_raw) < 6 || !preg_match('/[A-Za-z]/', $password_raw) || !preg_match('/[0-9]/', $password_raw))
        $errors[] = "Password must be at least 6 characters long and include letters & numbers.";

    // Handle profile picture
    $fileName = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetDir . $fileName);
    }

    if (empty($errors)) {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // Check if email exists
        $check = $pdo->prepare("SELECT 1 FROM staff WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['error'] = "Email already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO staff (clinic_id, name, role, contact_number, email, password, profile_picture)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$clinic_id, $staff_name, $staff_role, $contact, $email, $password, $fileName]);
            $_SESSION['success'] = "Staff added successfully!";
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }

    header("Location: manage_staff.php");
    exit;
}

// ✅ UPDATE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $id = $_POST['staff_id'];
    $staff_name = trim($_POST['name']);
    $staff_role = $_POST['role'];
    $contact = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "UPDATE staff SET name = ?, role = ?, contact_number = ?, email = ?";
    $params = [$staff_name, $staff_role, $contact, $email];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    // Optional profile picture
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetDir . $fileName);
        $sql .= ", profile_picture = ?";
        $params[] = $fileName;
    }

    $sql .= " WHERE staff_id = ? AND clinic_id = ?";
    $params[] = $id;
    $params[] = $clinic_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['success'] = "Staff updated successfully!";
    header("Location: manage_staff.php");
    exit;
}

// ✅ DELETE STAFF
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ? AND clinic_id = ?");
    $stmt->execute([$id, $clinic_id]);

    $_SESSION['success'] = "Staff deleted successfully!";
    header("Location: manage_staff.php");
    exit;
}

// ✅ FETCH STAFF LIST
$staffList = $pdo->prepare("SELECT * FROM staff WHERE clinic_id = ?");
$staffList->execute([$clinic_id]);
$staffMembers = $staffList->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Staff - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
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


        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: transform 0.2s ease;
        }

        .card:hover {
            transform: translateY(-3px);
        }
    </style>
</head>

<body class="bg-light d-flex flex-column min-vh-100">


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


    <div class="container py-4">
        <?php
        if (!empty($errorMsg)) {
            echo $errorMsg;
        }
        if (!empty($msg)) {
            echo $msg;
        }
        ?>

        <?php if (!isset($errorMsg)): ?>


            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="fw-bold text-dark"><i class="bi bi-person-plus-fill me-2"></i>Manage Staff</h4>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStaffModal">
                    <i class="bi bi-plus-circle me-1"></i> Add New Staff
                </button>
            </div>

            <!-- Staff List -->
            <div class="card shadow-lg border-0 rounded-3">
                <!-- Card Header -->
                <div
                    class="card-header bg-gradient bg-secondary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i> Registered Staff Members</h5>
                </div>

                <!-- Card Body -->
                <div class="card-body p-0">
                    <?php if (count($staffMembers) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Name</th>
                                        <th>Role</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staffMembers as $staff): ?>
                                        <tr>
                                            <!-- profile -->
                                            <td class="fw-semibold text-dark px-4">
                                                <img src="../uploads/profiles/<?= !empty($staff['profile_picture']) ? htmlspecialchars($staff['profile_picture']) : 'default.png' ?>"
                                                    alt="Profile" width="32" height="32" class="rounded-circle me-2"
                                                    style="object-fit: cover;">

                                                <!-- Staff Name -->
                                                <!-- <td class="fw-semibold text-dark px-4"> -->
                                                <!-- <i class="bi bi-person-circle me-2 text-primary fs-5"></i> -->
                                                <?php echo htmlspecialchars($staff['name']); ?>
                                            </td>

                                            <!-- Role -->
                                            <td>
                                                <?php if ($staff['role'] === 'doctor'): ?>
                                                    <span class="badge rounded-pill bg-info px-3 py-2">
                                                        <i class="bi bi-stethoscope me-1"></i> Doctor
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-warning text-dark px-3 py-2">
                                                        <i class="bi bi-people-fill me-1"></i> Staff
                                                    </span>
                                                <?php endif; ?>
                                            </td>

                                            <!-- Contact -->
                                            <td class="text-muted">
                                                <i class="bi bi-telephone me-2 text-success"></i>
                                                <?php echo htmlspecialchars($staff['contact_number']); ?>
                                            </td>

                                            <!-- Email -->
                                            <td class="text-muted">
                                                <i class="bi bi-envelope-at me-2 text-secondary"></i>
                                                <?php echo htmlspecialchars($staff['email']); ?>
                                            </td>

                                            <!-- Actions -->
                                            <td class="text-center">
                                                <div class="d-inline-flex gap-2">
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                                        data-bs-target="#editStaffModal<?= $staff['staff_id'] ?>">
                                                        <i class="bi bi-pencil-square me-1"></i> Edit
                                                    </button>

                                                    <a href="?delete=<?= $staff['staff_id']; ?>"
                                                        class="btn btn-sm btn-danger delete-btn"
                                                        data-id="<?= $staff['staff_id']; ?>">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="p-3 mb-0 text-center text-muted">No staff registered yet.</p>
                    <?php endif; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Success!',
                text: '<?= addslashes($_SESSION['success']); ?>',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php elseif (isset($_SESSION['error'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Error!',
                text: '<?= addslashes($_SESSION['error']); ?>',
                icon: 'error',
                confirmButtonColor: '#d33',
                timer: 2500,
                showConfirmButton: true
            });
        </script>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- ✅ SweetAlert2 Alerts -->
    <?php if (isset($_SESSION['success']) || isset($_SESSION['error'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: '<?= isset($_SESSION['success']) ? "Success!" : "Error!"; ?>',
                text: '<?= addslashes($_SESSION['success'] ?? $_SESSION['error']); ?>',
                icon: '<?= isset($_SESSION['success']) ? "success" : "error"; ?>',
                confirmButtonColor: '<?= isset($_SESSION['success']) ? "#3085d6" : "#d33"; ?>',
                timer: 2500,
                showConfirmButton: true
            });
        </script>
        <?php unset($_SESSION['success'], $_SESSION['error']); ?>
    <?php endif; ?>

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
                                pattern="^0\d{10}$" title="Enter a valid 11-digit number (e.g. 09123456789)" oninput="
                                // remove any non-digit characters
                                this.value = this.value.replace(/[^0-9]/g, '');
                                // limit to 11 digits only
                                if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                            <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                        </div>
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

    <!-- Edit staff -->
    <?php foreach ($staffMembers as $staff): ?>
        <div class="modal fade" id="editStaffModal<?= $staff['staff_id'] ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data" onsubmit="return validateStaffForm(this)">
                        <!-- ✅ tell PHP this is Update Staff form -->
                        <input type="hidden" name="update_staff" value="1">
                        <input type="hidden" name="staff_id" value="<?= $staff['staff_id'] ?>">

                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Edit Staff</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <!-- Name -->
                            <div class="mb-3">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($staff['name']) ?>" pattern="[A-Za-z\s]{2,50}"
                                    title="Name should be 2-50 letters only" required>
                            </div>

                            <!-- Role -->
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="staff" <?= $staff['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                                    <option value="doctor" <?= $staff['role'] === 'doctor' ? 'selected' : '' ?>>Doctor</option>
                                </select>
                            </div>

                            <!-- Contact Number -->
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= htmlspecialchars($staff['contact_number']) ?>" pattern="09\d{9}"
                                    maxlength="11" required title="Must be a valid PH number (e.g., 09123456789)">
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($staff['email']) ?>" required>
                            </div>

                            <!-- Password -->
                            <div class="mb-3">
                                <label>New Password (leave blank to keep current)</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="Enter new password (optional)" minlength="6" maxlength="20"
                                    pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,20}$"
                                    title="Password must be 6-20 characters, include letters & numbers">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="submit" name="update_staff" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Add Staff Modal -->
    <div class="modal fade" id="addStaffModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="add_staff" value="1">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Add New Staff</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="Enter full name"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select" required>
                                    <option value="staff">Staff</option>
                                    <option value="doctor">Doctor</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control" placeholder="09XXXXXXXXX"
                                    maxlength="11" inputmode="numeric" pattern="^09\d{9}$"
                                    title="Must be 11 digits starting with 09" required
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" placeholder="example@email.com"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control"
                                    placeholder="At least 6 chars, letters & numbers" minlength="6"
                                    pattern="^(?=.*[A-Za-z])(?=.*\d).+$" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i> Save Staff
                        </button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>





    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function validateStaffForm(form) {
            const contact = form.contact_number.value.trim();
            if (!/^09\d{9}$/.test(contact)) {
                alert("Contact number must start with 09 and be 11 digits.");
                return false;
            }

            const pass = form.password.value.trim();
            if (pass.length > 0 && !/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,20}$/.test(pass)) {
                alert("Password must be 6–20 characters, with at least 1 letter & 1 number.");
                return false;
            }

            return true; // ✅ Pass all checks
        }

        didClose: () => {
            location.reload();
        }
    </script>

    <script>
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const href = btn.getAttribute('href');
                Swal.fire({
                    title: "Are you sure?",
                    text: "This staff will be permanently deleted.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#6c757d",
                    confirmButtonText: "Yes, delete it!"
                }).then(result => {
                    if (result.isConfirmed) {
                        window.location.href = href;
                    }
                });
            });
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