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
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";
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
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_clinic.css">
</head>

<body>

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_clinic_modal.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <br>
    <?php include 'assets/body/footer_all.php' ?>

    <?php if (isset($_SESSION['msg'])): ?>
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
        <?php unset($_SESSION['msg']); endif; ?>


    <?php if (isset($_SESSION['error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Entry',
                text: <?= json_encode($_SESSION['error']) ?>,
                confirmButtonColor: '#d33',
                background: '#fff',
                customClass: {
                    popup: 'rounded-4 shadow'
                }
            });
        </script>
        <?php unset($_SESSION['error']); endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>