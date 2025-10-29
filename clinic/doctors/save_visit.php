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

$stmt = $pdo->prepare("INSERT INTO doctor_visits (doctor_id, clinic_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$doctor_id, $clinic_id, $day, $start, $end]);

header('Location: visitation.php');
exit;
?>
