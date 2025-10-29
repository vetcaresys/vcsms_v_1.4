<?php
session_start();
include '../../config.php';

// Ensure staff is logged in
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$clinic_id = $_SESSION['clinic_id'];

// 🐾 Pets
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets");
$stmt->execute();
$pets = $stmt->fetchColumn();

// 👥 Pet Owners
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'pet_owner'");
$stmt->execute();
$owners = $stmt->fetchColumn();

// 📅 Appointments
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed
    FROM appointments
");
$stmt->execute();
$appointments = $stmt->fetch(PDO::FETCH_ASSOC);

// 🧾 Medical Records
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_records");
$stmt->execute();
$records = $stmt->fetchColumn();

// 📦 Inventory (low stock)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE quantity < 5");
$stmt->execute();
$lowStock = $stmt->fetchColumn();

// 💌 Inquiries (unread)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'");
$stmt->execute();
$inquiries = $stmt->fetchColumn();

// Send JSON response
header('Content-Type: application/json');
echo json_encode([
    'pets' => (int)$pets,
    'owners' => (int)$owners,
    'pending' => (int)$appointments['pending'],
    'approved' => (int)$appointments['approved'],
    'completed' => (int)$appointments['completed'],
    'records' => (int)$records,
    'lowStock' => (int)$lowStock,
    'inquiries' => (int)$inquiries
]);
?>
