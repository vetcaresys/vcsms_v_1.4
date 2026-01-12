<?php
require 'config.php';
date_default_timezone_set('Asia/Manila');

$appointment_id = $_POST['appointment_id'];
$user_id = $_SESSION['user_id'];

// Get appointment
$stmt = $pdo->prepare("
    SELECT schedule_date, status
    FROM appointments
    WHERE appointment_id = ?
");
$stmt->execute([$appointment_id]);
$app = $stmt->fetch();

if (!$app) {
    echo json_encode(['allowed' => false, 'msg' => 'Appointment not found.']);
    exit;
}

// ❌ If already ongoing or done
if (in_array($app['status'], ['ongoing', 'completed'])) {
    echo json_encode(['allowed' => false, 'msg' => 'Appointment can no longer be cancelled.']);
    exit;
}

// Time difference
$now = new DateTime();
$apptTime = new DateTime($app['schedule_date']);
$diffMinutes = ($apptTime->getTimestamp() - $now->getTimestamp()) / 60;

// ❌ Less than 1 hour
if ($diffMinutes < 60) {
    echo json_encode([
        'allowed' => false,
        'msg' => 'Cancellation is allowed only up to 1 hour before the appointment.'
    ]);
    exit;
}

// ✅ Allowed
echo json_encode(['allowed' => true]);
?>