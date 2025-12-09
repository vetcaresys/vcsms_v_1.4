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
   HANDLE CLINIC LOGO
---------------------------------------- */
$logoFile = !empty($clinic['logo']) ? $clinic['logo'] : 'default.png'; // fallback if no logo

if (isset($_FILES['logo']) && !empty($_FILES['logo']['name'])) {
    $uploadDir = "../../uploads/logos/";
    if (!is_dir($uploadDir))
        mkdir($uploadDir, 0777, true);

    $fileName = time() . "_" . basename($_FILES["logo"]["name"]);
    $targetFile = $uploadDir . $fileName;
    $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

    if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFile)) {
            // Update database with new logo
            $updateStmt = $pdo->prepare("UPDATE clinics SET logo = ? WHERE clinic_id = ?");
            $updateStmt->execute([$fileName, $clinic_id]);

            // Update local variable to reflect new logo immediately
            $logoFile = $fileName;
        } else {
            $msg = "Failed to upload logo.";
        }
    } else {
        $msg = "Invalid image type. Only JPG, JPEG, PNG, GIF allowed.";
    }
}

// Full path to display in HTML
$logoPath = "../../uploads/logos/" . $logoFile . "?t=" . time();

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
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css">
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
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

    <?php if (isset($_SESSION['visit_error'])): ?>
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?= $_SESSION['visit_error']; ?>'
            });
        </script>
        <?php unset($_SESSION['visit_error']); endif; ?>

    <?php if (isset($_SESSION['visit_success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '<?= $_SESSION['visit_success']; ?>'
            });
        </script>
        <?php unset($_SESSION['visit_success']); endif; ?>

    <?php include 'includes/navbar.php' ?>

    <?php include 'includes/view_visitation.php' ?>

    <?php include 'includes/add_visitation_modal.php' ?>

    <br><br>

    <?php include 'includes/footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>