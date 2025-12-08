<?php
include '../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$name = htmlspecialchars($_SESSION['name']);

// ✅ One consistent definition for profile picture
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

// Get clinic
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$existingClinic = $stmt->fetch();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = $_POST['clinic_name'];
    $address = $_POST['address'];
    if (stripos($address, 'Misamis Occidental') === false) {
        $msg = "Address must include 'Misamis Occidental'.";
    }
    $contact_info = $_POST['contact_info'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $logo_path = $existingClinic['logo'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $upload_dir = "../uploads/logos/";
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES["logo"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                $logo_path = $file_name;
            } else {
                $msg = "Failed to upload logo.";
            }
        } else {
            $msg = "Invalid image type.";
        }
    }

    if (empty($msg)) {
        if ($existingClinic) {
            $sql = "UPDATE clinics SET clinic_name=?, address=?, contact_info=?, latitude=?, longitude=?";
            $params = [$clinic_name, $address, $contact_info, $latitude, $longitude];
            if ($logo_path) {
                $sql .= ", logo=?";
                $params[] = $logo_path;
            }
            $sql .= " WHERE user_id=?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Clinic updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clinics (user_id, clinic_name, address, contact_info, latitude, longitude, logo) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$user_id, $clinic_name, $address, $contact_info, $latitude, $longitude, $logo_path]);
            $msg = "Clinic registered successfully!";
        }

        // Refresh clinic info
        $stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existingClinic = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Clinic - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_clinic_details.css">
    <link rel="stylesheet" href="assets/css/footer.css">
</head>

<body class="bg-light">

    <?php if (!empty($msg)): ?>
        <script>
            Swal.fire({
                icon: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'error'" : "'success'" ?>,
                title: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'Error!'" : "'Success!'" ?>,
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
    <?php endif; ?>

    <?php include 'assets/body/alert_popup.php' ?>
    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/clinic_form.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>
 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>