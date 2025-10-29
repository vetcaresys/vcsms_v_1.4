<?php
session_start();
require '../../config.php';

// ✅ Ensure doctor login
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];

// ✅ Fetch doctor info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $contact = $_POST['contact_number'];
    $address = $_POST['address'];
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : $doctor['password'];

    // ✅ Handle profile picture
    $profile_picture = $doctor['profile_picture'];
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        $targetFilePath = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
            $profile_picture = $fileName;
        }
    }

    // ✅ Update query
    $stmt = $pdo->prepare("UPDATE staff SET name=?, email=?, contact_number=?, address=?, profile_picture=?, password=? WHERE staff_id=?");
    $stmt->execute([$name, $email, $contact, $address, $profile_picture, $password, $doctor_id]);

    $_SESSION['success'] = "Profile updated successfully!";
    $_SESSION['name'] = $name; // update session name
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>

<body class="bg-light">

<div class="container my-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-pencil-square"></i> Edit Profile</h5>
        </div>
        <div class="card-body">
            <form method="POST" enctype="multipart/form-data" class="row g-3">
                
                <!-- Full Name -->
                <div class="col-md-6">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($doctor['name']) ?>" required>
                </div>

                <!-- Email -->
                <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($doctor['email']) ?>" required>
                </div>

                <!-- Contact -->
                <div class="col-md-6">
                    <label class="form-label">Contact Number</label>
                    <input type="text" name="contact_number" class="form-control" value="<?= htmlspecialchars($doctor['contact_number']) ?>" maxlength="11" pattern="^09\d{9}$" required>
                </div>

                <!-- Address -->
                <div class="col-md-6">
                    <label class="form-label">Address</label>
                    <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($doctor['address']) ?>">
                </div>

                <!-- Password -->
                <div class="col-md-6">
                    <label class="form-label">New Password (leave blank to keep current)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <!-- Profile Picture -->
                <div class="col-md-6">
                    <label class="form-label">Profile Picture</label>
                    <input type="file" name="profile_picture" class="form-control">
                    <?php if (!empty($doctor['profile_picture'])): ?>
                        <small class="text-muted">Current: <?= htmlspecialchars($doctor['profile_picture']) ?></small>
                    <?php endif; ?>
                </div>

                <div class="col-12 text-end">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-success">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
