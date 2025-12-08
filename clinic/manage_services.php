<?php
session_start();
require '../config.php';

// Only allow clinic owners
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


// Get this owner's clinic ID
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch();

if (!$clinic) {
    $errorMsg = "<div class='alert alert-danger'>You must register your clinic first.</div>";
} else {
    $clinic_id = $clinic['clinic_id'];

    // Add new service
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
        $service_name = ($_POST['service_name'] === 'Other') ? $_POST['custom_service'] : $_POST['service_name'];
        $duration = $_POST['duration'];

        $stmt = $pdo->prepare("INSERT INTO clinic_services (clinic_id, service_name, duration)
                       VALUES (?, ?, ?)");
        $stmt->execute([$clinic_id, $service_name, $duration]);
    }
    // Update service
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
        $service_id = $_POST['service_id'];
        $service_name = ($_POST['service_name'] === 'Other') ? $_POST['custom_service'] : $_POST['service_name'];
        $duration = $_POST['duration'];

        $stmt = $pdo->prepare("UPDATE clinic_services 
                               SET service_name = ?, duration = ? 
                               WHERE service_id = ? AND clinic_id = ?");
        $stmt->execute([$service_name, $duration, $service_id, $clinic_id]);
    }
    // Delete service
    elseif (isset($_GET['delete'])) {
        $service_id = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM clinic_services WHERE service_id = ? AND clinic_id = ?");
        $stmt->execute([$service_id, $clinic_id]);
    }

    // Fetch all services
    $services = $pdo->prepare("SELECT * FROM clinic_services WHERE clinic_id = ?");
    $services->execute([$clinic_id]);
    $serviceList = $services->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_services.css">
</head>

<body class="bg-light">

    <?php
    $alertScript = ""; // prepare variable to hold JS alert scripts
    
    if (!$clinic) {
        $alertScript = "
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'warning',
                title: 'No Clinic Found',
                text: 'You must register your clinic first before managing services.',
                confirmButtonColor: '#0d6efd'
            });
        });
        </script>
    ";
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Added!',
                    text: 'New service has been added successfully.',
                    confirmButtonColor: '#198754'
                });
            });
            </script>
        ";
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Updated!',
                    text: 'Service details were updated successfully.',
                    confirmButtonColor: '#ffc107'
                });
            });
            </script>
        ";
        } elseif (isset($_GET['delete'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Deleted!',
                    text: 'The service has been removed.',
                    confirmButtonColor: '#dc3545'
                });
            });
            </script>
        ";
        }
    }
    ?>
    <?= $alertScript ?? '' ?>

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_services_content.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/edit_service_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/services_alert.js"></script>
</body>
</html>