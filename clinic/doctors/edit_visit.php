<?php
session_start();
include '../../config.php';

// 🔐 Check doctor login
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: visitation.php");
    exit;
}

// 🧾 Get POST data
$visit_id  = $_POST['visit_id'] ?? '';
$day       = $_POST['day_of_week'] ?? '';
$start_raw = $_POST['start_time'] ?? '';
$end_raw   = $_POST['end_time'] ?? '';

// 🛑 Validation
if (!$visit_id || !$day || !$start_raw || !$end_raw) {
    $_SESSION['visit_error'] = "All fields are required.";
    header("Location: visitation.php");
    exit;
}

// ⏱ Convert AM/PM → MySQL TIME
$start = date("H:i:s", strtotime($start_raw));
$end   = date("H:i:s", strtotime($end_raw));

// 🛑 Validate time order
if ($end <= $start) {
    $_SESSION['visit_error'] = "End time must be later than start time.";
    header("Location: visitation.php");
    exit;
}

// 🔎 CHECK OVERLAP (EXCLUDE CURRENT VISIT)
$overlap = $pdo->prepare("
    SELECT 1
    FROM doctor_visits
    WHERE doctor_id = ?
      AND clinic_id = ?
      AND day_of_week = ?
      AND visit_id != ?
      AND (? < end_time AND ? > start_time)
    LIMIT 1
");

$overlap->execute([
    $doctor_id,
    $clinic_id,
    $day,
    $visit_id,
    $start,
    $end
]);

if ($overlap->fetchColumn()) {
    $_SESSION['visit_error'] = "This schedule overlaps with another visitation.";
    header("Location: visitation.php");
    exit;
}

// ✏️ UPDATE VISITATION
$stmt = $pdo->prepare("
    UPDATE doctor_visits
    SET day_of_week = ?, start_time = ?, end_time = ?
    WHERE visit_id = ? AND doctor_id = ? AND clinic_id = ?
");

$stmt->execute([
    $day,
    $start,
    $end,
    $visit_id,
    $doctor_id,
    $clinic_id
]);

if ($stmt->rowCount() > 0) {
    $_SESSION['visit_success'] = "Visitation updated successfully!";
} else {
    $_SESSION['visit_error'] = "No changes were made.";
}

header("Location: visitation.php");
exit;
?>