<?php
session_start();
require '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$doctor_id = $_SESSION['staff_id'];

try {
    // 🩺 Pending appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'pending'");
    $stmt->execute([$doctor_id]);
    $pending = $stmt->fetchColumn();

    // 📅 Today's appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND DATE(appointment_date) = CURDATE()");
    $stmt->execute([$doctor_id]);
    $today = $stmt->fetchColumn();

    // ✅ Completed appointments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND status = 'completed'");
    $stmt->execute([$doctor_id]);
    $completed = $stmt->fetchColumn();

    // 🐾 Pets handled
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT pet_id) FROM appointments WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $pets = $stmt->fetchColumn();

    // 🧾 Records created (optional)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_records WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $records = $stmt->fetchColumn();

    echo json_encode([
        'pending' => $pending,
        'today' => $today,
        'completed' => $completed,
        'pets' => $pets,
        'records' => $records
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
