<?php
include '../../config.php'; // imong database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = $_POST['full_name'];
    $contact_no = $_POST['contact_no'];
    $address = $_POST['address'];
    $email = $_POST['email'];

    $stmt = $pdo->prepare("INSERT INTO pet_owners (full_name, contact_no, address, email) 
                           VALUES (?, ?, ?, ?)");
    $stmt->execute([$full_name, $contact_no, $address, $email]);

    header("Location: manage_petowner.php?success=1");
    exit();
}
?>
