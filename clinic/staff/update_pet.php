<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

$pet_id = $_POST['pet_id'];

// Fetch existing pet to delete old photo later
$stmt = $pdo->prepare("SELECT photo FROM pets WHERE pet_id = ?");
$stmt->execute([$pet_id]);
$oldPhoto = $stmt->fetchColumn();

// Update fields
$pet_name   = $_POST['pet_name'];
$species    = (isset($_POST['species_other']) && $_POST['species'] === 'Others') 
                ? trim($_POST['species_other']) 
                : trim($_POST['species']);
$breed      = (isset($_POST['breed_other']) && $_POST['breed'] === 'Others') 
                ? trim($_POST['breed_other']) 
                : trim($_POST['breed']);
$birth_date = $_POST['birth_date'];
$description= $_POST['description'];

$photoName = $oldPhoto;

// Check if new photo uploaded
if (!empty($_FILES['photo']['name'])) {
    // delete old photo
    if ($oldPhoto && file_exists("../../uploads/pets/" . $oldPhoto)) {
        unlink("../../uploads/pets/" . $oldPhoto);
    }

    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = time() . "_" . rand(1000,9999) . "." . $ext;
    move_uploaded_file($_FILES['photo']['tmp_name'], "../../uploads/pets/" . $photoName);
}

$stmtUpdate = $pdo->prepare("
    UPDATE pets 
    SET pet_name=?, species=?, breed=?, birth_date=?, description=?, photo=? 
    WHERE pet_id=?
");

$success = $stmtUpdate->execute([
    $pet_name, $species, $breed, $birth_date, $description, $photoName, $pet_id
]);

// ✅ Set SweetAlert session message
if ($success) {
    $_SESSION['alert'] = ['type' => 'success', 'message' => 'Pet updated successfully!'];
} else {
    $_SESSION['alert'] = ['type' => 'error', 'message' => 'Error updating pet.'];
}

header("Location: manage_pet_details.php");
exit;
