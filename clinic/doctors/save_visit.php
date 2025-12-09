<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

$day = $_POST['day_of_week'];
$start = $_POST['start_time'];
$end = $_POST['end_time'];

// 🔎 CHECK DUPLICATE VISITATION
$check = $pdo->prepare("
    SELECT * FROM doctor_visits
    WHERE doctor_id = ? AND clinic_id = ? AND day_of_week = ? 
    AND start_time = ? AND end_time = ?
");

$check->execute([$doctor_id, $clinic_id, $day, $start, $end]);

if ($check->rowCount() > 0) {
    $_SESSION['visit_error'] = "This visitation schedule already exists!";
    header("Location: visitation.php");
    exit;
}

// ✔️ INSERT IF NO DUPLICATE
$stmt = $pdo->prepare("
    INSERT INTO doctor_visits (doctor_id, clinic_id, day_of_week, start_time, end_time)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([$doctor_id, $clinic_id, $day, $start, $end]);

$_SESSION['visit_success'] = "Visitation added successfully!";
header('Location: visitation.php');
exit;
?>
