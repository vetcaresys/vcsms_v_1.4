<?php
session_start(); // ← Required

require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
    
    if ($stmt->execute([$name, $email, $subject, $message])) {
        header("Location: index.php?sent=1"); // ← SweetAlert trigger
        exit;
    } else {
        header("Location: index.php?sent=0"); // ← Optional error
        exit;
    }
}
?>
