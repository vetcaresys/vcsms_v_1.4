<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    http_response_code(403);
    exit;
}

$user_id = $_SESSION['user_id'];

// Total pets
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE owner_id = ?");
$stmt->execute([$user_id]);
$totalPets = $stmt->fetchColumn();

// Upcoming appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM appointments a 
    JOIN pets p ON a.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND a.status IN ('pending','approved')
");
$stmt->execute([$user_id]);
$upcoming = $stmt->fetchColumn();

// Completed appointments
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM appointments a 
    JOIN pets p ON a.pet_id = p.pet_id 
    WHERE p.owner_id = ? AND a.status = 'completed'
");
$stmt->execute([$user_id]);
$completed = $stmt->fetchColumn();

// Pet records
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id 
    WHERE p.owner_id = ?
");
$stmt->execute([$user_id]);
$records = $stmt->fetchColumn();

echo json_encode([
    'pets' => $totalPets,
    'upcoming' => $upcoming,
    'completed' => $completed,
    'records' => $records
]);
