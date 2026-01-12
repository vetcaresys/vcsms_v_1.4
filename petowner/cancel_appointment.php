<?php
session_start();
require '../config.php';

// 🔒 Only pet owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

// ✅ POST only
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {

    $appointment_id = intval($_POST['appointment_id']);
    $user_id = $_SESSION['user_id'];

    // 🔍 Get appointment details (for validation + notification)
    $fetch = $pdo->prepare("
        SELECT a.appointment_date, u.name AS owner_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN users u ON u.user_id = p.owner_id
        WHERE a.appointment_id = ?
          AND p.owner_id = ?
          AND a.status = 'pending'
    ");
    $fetch->execute([$appointment_id, $user_id]);
    $appointment = $fetch->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {

        // ❌ Cancel pending appointment
        $cancel = $pdo->prepare("
            UPDATE appointments
            SET status = 'cancelled'
            WHERE appointment_id = ?
        ");
        $cancel->execute([$appointment_id]);

        // 🔔 Optional: notify clinic/employee
        $message = "Pending appointment from {$appointment['owner_name']} has been cancelled.";

        $notif = $pdo->prepare("
            INSERT INTO notifications
            (user_id, role, message, subject, status, created_at)
            VALUES (?, 'employee', ?, 'Cancelled Pending Appointment', 'unread', NOW())
        ");
        $notif->execute([
            $user_id,
            $message
        ]);

        $_SESSION['booking_msg'] = 'cancelled';

    } else {
        $_SESSION['booking_msg'] = 'error';
        $_SESSION['booking_error_text'] = 'Unable to cancel. Only pending appointments can be cancelled.';
    }
}

header('Location: book_appointment.php');
exit;
?>