<?php
header('Content-Type: application/json');

use PHPMailer\PHPMailer\Exception;

// Include PHPMailer setup
require '../mail.php'; // Make sure $mail is defined here

try {
    // Only allow POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get POST data and sanitize
    $to      = filter_var($_POST['recipient_email'] ?? '', FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!$to || !$subject || !$message) {
        throw new Exception('All fields are required.');
    }

    // Set email
    $mail->setFrom('loelynates@gmail.com', 'VetCareSys Admin');
    $mail->addAddress($to);
    $mail->Subject = $subject;
    $mail->Body    = nl2br(htmlspecialchars($message)); // preserve line breaks
    $mail->AltBody = htmlspecialchars($message);      // plain text fallback

    // Send email
    $mail->send();

    echo json_encode([
        'status' => 'success',
        'msg'    => 'Email successfully sent to ' . $to
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'msg'    => 'Mailer Error: ' . $e->getMessage()
    ]);
}
