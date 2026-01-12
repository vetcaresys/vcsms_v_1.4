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
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";
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
$staffList = $pdo->prepare("
    SELECT * 
    FROM staff 
    WHERE clinic_id = ? 
    ORDER BY staff_id DESC
");
$staffList->execute([$clinic_id]);
$staffMembers = $staffList->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Staff - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_staff.css">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_staff_content.php' ?>
    <?php include 'assets/body/manage_staff_alert.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/add_staff_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script src="assets/js/staff_form_validation.js"></script>
    <script src="assets/js/delete_staff.js"></script>
    <script src="assets/js/staff_password_toggle.js"></script>
    <script src="assets/js/staff_table.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></script>
</body>
</html>