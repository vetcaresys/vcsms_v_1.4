<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

// 🧾 Get POST data
$day        = $_POST['day_of_week'] ?? '';
$start_raw = $_POST['start_time'] ?? '';
$end_raw   = $_POST['end_time'] ?? '';

// 🛑 Basic validation
if (!$day || !$start_raw || !$end_raw) {
    $_SESSION['visit_error'] = "All fields are required.";
    header("Location: visitation.php");
    exit;
}

// ⏱ Convert AM/PM → MySQL TIME (HH:MM:SS)
$start = date("H:i:s", strtotime($start_raw));
$end   = date("H:i:s", strtotime($end_raw));

// 🛑 Validate time logic
if ($end <= $start) {
    $_SESSION['visit_error'] = "End time must be later than start time.";
    header("Location: visitation.php");
    exit;
}

// 🔎 PREVENT OVERLAPPING VISITS (IMPORTANT)
$overlapCheck = $pdo->prepare("
    SELECT 1
    FROM doctor_visits
    WHERE doctor_id = ?
      AND clinic_id = ?
      AND day_of_week = ?
      AND (? < end_time AND ? > start_time)
    LIMIT 1
");
$overlapCheck->execute([
    $doctor_id,
    $clinic_id,
    $day,
    $start,
    $end
]);

if ($overlapCheck->fetchColumn()) {
    $_SESSION['visit_error'] = "This visitation overlaps with an existing schedule.";
    header("Location: visitation.php");
    exit;
}

// ✔ INSERT VISITATION
$stmt = $pdo->prepare("
    INSERT INTO doctor_visits 
        (doctor_id, clinic_id, day_of_week, start_time, end_time)
    VALUES (?, ?, ?, ?, ?)
");

$stmt->execute([
    $doctor_id,
    $clinic_id,
    $day,
    $start,
    $end
]);

// ✅ Success
$_SESSION['visit_success'] = "Visitation added successfully!";
header("Location: visitation.php");
exit;
