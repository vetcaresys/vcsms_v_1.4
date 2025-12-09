<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

/* --------------------------------------
   FETCH CLINIC (FULL DATA INCLUDING LOGO)
---------------------------------------- */
$clinicStmt = $pdo->prepare("
    SELECT clinic_name, address, contact_info, logo 
    FROM clinics 
    WHERE clinic_id = ?
");
$clinicStmt->execute([$clinic_id]);
$clinic = $clinicStmt->fetch(PDO::FETCH_ASSOC);

// Store name in session
$_SESSION['clinic_name'] = $clinic['clinic_name'] ?? 'N/A';

/* --------------------------------------
   FETCH DOCTOR MAIN STAFF INFO
---------------------------------------- */
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($doctor['name']);
$profilePic = !empty($doctor['profile_picture']) ? $doctor['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

/* --------------------------------------
   FETCH DOCTOR ADDITIONAL DETAILS
---------------------------------------- */
$docInfoStmt = $pdo->prepare("SELECT * FROM doctors WHERE staff_id = ?");
$docInfoStmt->execute([$doctor_id]);
$doctorInfo = $docInfoStmt->fetch(PDO::FETCH_ASSOC);

/* --------------------------------------
   FETCH DOCTOR VISITATION SCHEDULE
---------------------------------------- */
$visits = $pdo->prepare("SELECT * FROM doctor_visits WHERE doctor_id = ? AND clinic_id = ?");
$visits->execute([$doctor_id, $clinic_id]);
$visits = $visits->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>My Clinic & Visitations</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/visitation.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="bg-light">

    <?php if (isset($_SESSION['visit_error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Duplicate Visitation',
                text: '<?= $_SESSION['visit_error']; ?>'
            });
        </script>
        <?php unset($_SESSION['visit_error']); endif; ?>

    <?php if (isset($_SESSION['visit_success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?= $_SESSION['visit_success']; ?>'
            });
        </script>
        <?php unset($_SESSION['visit_success']); endif; ?>


    <?php include 'includes/navbar.php' ?>

    <?php include 'includes/view_visitation.php' ?>

    <?php include 'includes/add_visitation_modal.php' ?>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>