<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
    echo json_encode([]);
    exit;
}

$clinic_id = $_GET['clinic_id'];

// Validate approved clinic
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE clinic_id = ? AND status = 'approved'");
$stmt->execute([$clinic_id]);
if (!$stmt->fetch()) {
    echo json_encode([]);
    exit;
}

// Fetch services
$stmt = $pdo->prepare("SELECT service_id, service_name, duration FROM clinic_services WHERE clinic_id = ?");
$stmt->execute([$clinic_id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($services);
