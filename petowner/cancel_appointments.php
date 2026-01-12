<?php
session_start();
require_once __DIR__ . '/../config.php';

// 🔒 Only pet owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

// ✅ POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['appointment_id'])) {
    header('Location: book_appointment.php');
    exit;
}

$appointment_id = (int) $_POST['appointment_id'];
$user_id = $_SESSION['user_id'];

try {

    // 🔍 Validate ownership (PET OWNER) + status
    $stmt = $pdo->prepare("
        SELECT 
            a.status,
            a.appointment_date,
            a.appointment_start,
            u.name AS owner_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN users u ON p.owner_id = u.user_id
        WHERE a.appointment_id = ?
          AND p.owner_id = ?
          AND a.status IN ('pending', 'approved')
    ");
    $stmt->execute([$appointment_id, $user_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    // ❌ Not found or not allowed
    if (!$appointment) {
        $_SESSION['booking_msg'] = 'error';
        $_SESSION['booking_error_text'] =
            'Unable to cancel this appointment.';
        header('Location: book_appointment.php');
        exit;
    }

    // // ⛔ Prevent cancelling past approved appointments
    // if (
    //     $appointment['status'] === 'approved' &&
    //     strtotime($appointment['appointment_date']) <= strtotime(date('Y-m-d'))
    // ) {
    //     $_SESSION['booking_msg'] = 'error';
    //     $_SESSION['booking_error_text'] =
    //         'This appointment can no longer be cancelled.';
    //     header('Location: book_appointment.php');
    //     exit;
    // }    

    // ❌ Cancel appointment
    $cancel = $pdo->prepare("
        UPDATE appointments
        SET status = 'cancelled'
        WHERE appointment_id = ?
    ");
    $cancel->execute([$appointment_id]);

    // 🔔 Optional: notify clinic / employee
    $message = ucfirst($appointment['status']) .
        " appointment from {$appointment['owner_name']} has been cancelled.";

    $notif = $pdo->prepare("
        INSERT INTO notifications
        (user_id, role, message, subject, status, created_at)
        VALUES (?, 'employee', ?, 'Appointment Cancelled', 'unread', NOW())
    ");
    $notif->execute([$user_id, $message]);

    $_SESSION['booking_msg'] = 'cancelled';

} catch (PDOException $e) {

    $_SESSION['booking_msg'] = 'error';
    $_SESSION['booking_error_text'] = 'Database error occurred.';
}

header('Location: book_appointment.php');
exit;
?>