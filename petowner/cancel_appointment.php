<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // ✅ Only cancel if status is 'pending'
    $stmt = $pdo->prepare("
        UPDATE appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        SET a.status = 'cancelled'
        WHERE a.appointment_id = ? 
          AND p.owner_id = ? 
          AND a.status = 'pending'
    ");
    $stmt->execute([$appointment_id, $user_id]);

    if ($stmt->rowCount()) {
        $_SESSION['msg'] = "Appointment has been cancelled.";
    } else {
        $_SESSION['msg'] = "Unable to cancel appointment. Only pending appointments can be cancelled.";
    }
}

header("Location: book_appointment.php");
exit;
?>
