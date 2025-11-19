<?php
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$name, $email, $subject, $message])) {
        // Optional: send an email to admin here
        $_SESSION['success'] = "Your inquiry has been submitted successfully!";
        header("Location: index.php#contact");
        exit;
    } else {
        $_SESSION['error'] = "Failed to submit inquiry. Please try again.";
        header("Location: index.php#contact");
        exit;
    }
}
?>
