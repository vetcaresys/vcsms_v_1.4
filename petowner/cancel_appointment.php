<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id'])) {
    $appointment_id = intval($_GET['id']);
    $user_id = $_SESSION['user_id'];

    // ✅ Only cancel if status is 'pending'
    $stmt = $pdo->prepare("
        UPDATE appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        SET a.status = 'cancelled'
        WHERE a.appointment_id = ? 
          AND p.owner_id = ? 
          AND a.status = 'pending'
    ");
    $stmt->execute([$appointment_id, $user_id]);
    // Fetch appointment date for notification
    $appointment_name_stmt = $pdo->prepare("SELECT name FROM users WHERE user_id = ?");
    $appointment_name_stmt->execute([$user_id]);
    $appointment_name = $appointment_name_stmt->fetchColumn();

    $message = "Appointment from $appointment_name has been cancelled.";
    if ($stmt->rowCount()) {
        $_SESSION['msg'] = "Appointment has been cancelled.";
         // 🔔 Create notification for employee/admin
            $notif = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, role, message, subject, link, schedule_date, sms, number, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $notif->execute([
                $user_id,
                'employee',
                $message ?: 'New appointment cancelled.',
                'Cancelled Book Appointment',
                null,
                $appointment_date,
                null,
                null,
                'unread',
                date('Y-m-d H:i:s')
            ]);
    } else {
        $_SESSION['msg'] = "Unable to cancel appointment. Only pending appointments can be cancelled.";
    }
}

header("Location: book_appointment.php");
exit;
?>
