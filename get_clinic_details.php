<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing clinic ID']);
    exit;
}

$id = intval($_GET['id']);

// Fetch clinic info
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE clinic_id = ?");
$stmt->execute([$id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    echo json_encode(['error' => 'Clinic not found']);
    exit;
}

// Fetch schedules
$stmt = $pdo->prepare("SELECT day_of_week, open_time, close_time FROM clinic_schedules WHERE clinic_id = ?");
$stmt->execute([$id]);
$clinic['schedules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch services
$stmt = $pdo->prepare("SELECT service_name, duration, price FROM clinic_services WHERE clinic_id = ?");
$stmt->execute([$id]);
$clinic['services'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($clinic);
?>
