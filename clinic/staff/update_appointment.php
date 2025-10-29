<?php
require '../../config.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$appointment_id = $_POST['appointment_id'] ?? null;
$appointment_date = $_POST['appointment_date'] ?? null;
$appointment_start = $_POST['appointment_start'] ?? null;
$appointment_end = $_POST['appointment_end'] ?? null;
$doctor_id = $_POST['doctor_id'] ?? null;
$message = $_POST['message'] ?? '';

$clinic_id = $_SESSION['clinic_id'] ?? null;
$staff_id = $_SESSION['staff_id'] ?? null;

if (!$appointment_id || !$clinic_id) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET appointment_date = ?, appointment_start = ?, appointment_end = ?, doctor_id = ?, message = ?, 
            updated_by = ?, updated_at = NOW()
        WHERE appointment_id = ? AND clinic_id = ?
    ");
    $stmt->execute([
        $appointment_date, $appointment_start, $appointment_end, 
        $doctor_id, $message, $staff_id, $appointment_id, $clinic_id
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>