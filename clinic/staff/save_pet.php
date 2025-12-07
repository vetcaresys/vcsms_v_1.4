<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

// Handle Upload
$photoName = null;

if (!empty($_FILES['photo']['name'])) {
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photoName = time() . "_" . rand(1000, 9999) . "." . $ext;
    $uploadPath = "../../uploads/pets/" . $photoName;
    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadPath);
}

$stmt = $pdo->prepare("
    INSERT INTO pets (owner_id, pet_name, species, breed, birth_date, description, photo)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $_POST['owner_id'],
    $_POST['pet_name'],
    $_POST['species'],
    $_POST['breed'],
    $_POST['birth_date'],
    $_POST['description'],
    $photoName
]);

// Redirect back
header("Location: manage_pet_details.php?added=1");
exit;
?>