<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../clinic/staff/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $name    = $_POST['name'];
    $email   = $_POST['email'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];

    // Check if email belongs to another user
    $check = $pdo->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $check->execute([$email, $user_id]);

    if ($check->rowCount() > 0) {
        $_SESSION['message'] = "❌ Email already in use by another user.";
    } else {
        $stmt = $pdo->prepare("UPDATE users 
                               SET name = ?, email = ?, contact_number = ?, address = ?
                               WHERE user_id = ?");
        if ($stmt->execute([$name, $email, $contact, $address, $user_id])) {
            $_SESSION['message'] = "✅ Pet owner updated successfully!";
        } else {
            $_SESSION['message'] = "❌ Failed to update pet owner.";
        }
    }
}

header("Location: manage_petowner.php");
exit;
