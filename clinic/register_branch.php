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
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/register_branch.css">
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
                <input type="text" name="clinic_name" class="form-control" required
                    placeholder="ex. Paws & Claws Veterinary Clinic" pattern="^[A-Za-z\s\-\&]{3,50}$"
                    title="Branch name should be 3–50 characters and letters only.">
            </div>

            <div class="mb-3">
                <label class="form-label">Branch Address</label>
                <input type="text" name="address" class="form-control" required minlength="5" maxlength="100"
                    placeholder="ex. Brgy. Makawa, Aloran, Misamis Occidental">
            </div>

            <div class="mb-3">
                <label class="form-label">Contact Number</label>
                <input type="text" name="contact_info" class="form-control" placeholder="ex. 09123456789"
                    pattern="[0-9]{11}" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                    required>
            </div>

            <hr>
            <h5>Branch Login Credentials</h5>

            <div class="mb-3">
                <label class="form-label">Branch Email (Login)</label>
                <input type="email" name="email" class="form-control" required placeholder="ex. branch@email.com">
            </div>

            <div class="mb-3 position-relative">
                <label class="form-label">Branch Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="branchPassword" class="form-control" required
                        minlength="8" placeholder="At least 8 chars: Aresfast@123"
                        pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$"
                        title="Password must be at least 8 characters, include uppercase, lowercase, number, and special character.">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                        Show
                    </button>
                </div>
            </div>

            <hr>
            <h5>Uploads</h5>

            <div class="mb-3">
                <label class="form-label">Branch Logo
                    <small class="text-muted">(JPG, JPEG, or PNG only, max 2MB)</small>
                </label>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>

            <div class="mb-3">
                <label class="form-label">Business Permit (Image/PDF)
                    <small class="text-muted">(Accepted: JPG, JPEG, PNG, or PDF)</small>
                </label>
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

                        <h6 class="text-primary">Change Password (optional)</h6>
                        <!-- Current Password -->
                        <div class="mb-3 position-relative">
                            <label>Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" id="currentPassword"
                                    placeholder="Enter current password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="currentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Type your current password to confirm changes.</small>
                                    <a href="../forgot_password.php" class="small">Forgot password?</a>
                                </div>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3 position-relative">
                            <label>New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" id="newPassword"
                                    minlength="6" placeholder="Enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="newPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3 position-relative">
                            <label>Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                                    minlength="6" placeholder="Re-enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('branchPassword');
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? 'Hide' : 'Show';
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>