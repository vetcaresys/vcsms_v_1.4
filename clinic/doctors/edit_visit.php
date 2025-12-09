<?php
session_start();
include '../../config.php';

// Check if doctor is logged in
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $visit_id = $_POST['visit_id'] ?? null;
    $day = $_POST['day_of_week'] ?? null;
    $start = $_POST['start_time'] ?? null;
    $end = $_POST['end_time'] ?? null;

    // Basic validation
    if (!$visit_id || !$day || !$start || !$end) {
        $_SESSION['visit_error'] = "All fields are required.";
        header("Location: visitation.php");
        exit;
    }

    try {
        // Update the visitation in database
        $stmt = $pdo->prepare("
            UPDATE doctor_visits 
            SET day_of_week = ?, start_time = ?, end_time = ? 
            WHERE visit_id = ? AND doctor_id = ?
        ");
        $stmt->execute([$day, $start, $end, $visit_id, $_SESSION['staff_id']]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['visit_success'] = "Visitation updated successfully.";
        } else {
            $_SESSION['visit_error'] = "No changes were made.";
        }
    } catch (Exception $e) {
        $_SESSION['visit_error'] = "Failed to update visitation: " . $e->getMessage();
    }

    header("Location: visitation.php");
    exit;
}

// If not POST, redirect back
header("Location: visitation.php");
exit;
?>
