<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath)) ? $picPath : "profile_default.jpg";
$name = htmlspecialchars($_SESSION['name']);

// Get clinic info
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

$clinic_id = $clinic['clinic_id'] ?? null;
$parent_id = $_GET['parent'] ?? null;

// Ensure parent clinic exists and belongs to user
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_id = ? AND user_id = ? AND parent_clinic_id IS NULL");
$stmt->execute([$parent_id, $user_id]);
$mainClinic = $stmt->fetch();

if (!$mainClinic) {
    die("Invalid main clinic.");
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = trim($_POST['clinic_name']);
    $address = trim($_POST['address']);
    $contact_info = trim($_POST['contact_info']);
    $email = trim($_POST['email']);
    $password_plain = $_POST['password'];
    $password = password_hash($password_plain, PASSWORD_DEFAULT);

    $msg = '';

    // Validate contact number
    if (!preg_match('/^[0-9]{11}$/', $contact_info)) {
        $msg = "<div class='alert alert-danger'>Invalid contact number. Please enter exactly 11 digits.</div>";
    }

    // Validate password strength
    if ($msg === '' && !preg_match('/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/', $password_plain)) {
        $msg = "<div class='alert alert-danger'>Password does not meet the required strength.</div>";
    }

    // Check duplicate email
    if ($msg === '') {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $msg = "<div class='alert alert-danger'>This email is already taken. Please choose another.</div>";
        }
    }

    // Check duplicate branch name under the same parent
    if ($msg === '') {
        $stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_name = ? AND parent_clinic_id = ?");
        $stmt->execute([$clinic_name, $parent_id]);
        if ($stmt->fetch()) {
            $msg = "<div class='alert alert-danger'>A branch with this name already exists under this clinic.</div>";
        }
    }

    // Handle uploads (logo and permit)
    $base_dir = dirname(__DIR__) . "/uploads/";
    if (!is_dir($base_dir)) mkdir($base_dir, 0777, true);

    $logo_path = '';
    if ($msg === '' && !empty($_FILES['logo']['name'])) {
        $logo_dir = $base_dir . "logos/";
        if (!is_dir($logo_dir)) mkdir($logo_dir, 0777, true);

        $file_name = time() . "_logo_" . basename($_FILES["logo"]["name"]);
        $target_file = $logo_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if ($_FILES['logo']['size'] > 2 * 1024 * 1024) {
            $msg = "<div class='alert alert-danger'>Logo file too large. Max 2MB allowed.</div>";
        } elseif (!in_array($file_type, ['jpg', 'jpeg', 'png'])) {
            $msg = "<div class='alert alert-danger'>Invalid logo file type. Only JPG, JPEG, PNG allowed.</div>";
        } elseif (!move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $msg = "<div class='alert alert-danger'>Failed to upload logo.</div>";
        } else {
            $logo_path = "uploads/logos/" . $file_name;
        }
    }

    $permit_path = '';
    if ($msg === '' && !empty($_FILES['business_permit']['name'])) {
        $permit_dir = $base_dir . "permits/";
        if (!is_dir($permit_dir)) mkdir($permit_dir, 0777, true);

        $file_name = time() . "_permit_" . basename($_FILES["business_permit"]["name"]);
        $target_file = $permit_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (!in_array($file_type, ['jpg', 'jpeg', 'png', 'pdf'])) {
            $msg = "<div class='alert alert-danger'>Invalid business permit file type.</div>";
        } elseif (!move_uploaded_file($_FILES["business_permit"]["tmp_name"], $target_file)) {
            $msg = "<div class='alert alert-danger'>Failed to upload business permit.</div>";
        } else {
            $permit_path = "uploads/permits/" . $file_name;
        }
    }

    // Insert branch only if no errors
    if ($msg === '') {
        try {
            $pdo->beginTransaction();

            // Create branch user
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'clinic_owner')");
            $stmt->execute([$clinic_name, $email, $password]);
            $branch_user_id = $pdo->lastInsertId();

            // Create branch clinic
            $stmt = $pdo->prepare("INSERT INTO clinics (user_id, clinic_name, address, contact_info, parent_clinic_id, logo, business_permit) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$branch_user_id, $clinic_name, $address, $contact_info, $parent_id, $logo_path, $permit_path]);

            $pdo->commit();

            $_SESSION['msg'] = "Branch registered successfully!";
            header("Location: manage_clinic.php");
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) { // Duplicate entry (email)
                $msg = "<div class='alert alert-danger'>This email is already registered. Please use another.</div>";
            } else {
                $msg = "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
        }
    }
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
    <link rel="stylesheet" href="assets/css/register_branch.css">
</head>

<body>

    <?php include 'assets/body/navbar.php' ?>

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

    <?php include 'assets/body/footer_all.php' ?>
    <script>
        document.getElementById('togglePassword').addEventListener('click', function () {
            const passwordField = document.getElementById('branchPassword');
            const isPassword = passwordField.type === 'password';
            passwordField.type = isPassword ? 'text' : 'password';
            this.textContent = isPassword ? 'Hide' : 'Show';
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>