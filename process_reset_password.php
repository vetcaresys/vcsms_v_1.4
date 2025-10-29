<?php
require __DIR__ . "/config.php"; 
session_start();

$token = $_POST["token"] ?? null;
$password = $_POST["password"] ?? null;
$password_confirmation = $_POST["password_confirmation"] ?? null;

// --- Basic validation ---
if (!$token || !$password || !$password_confirmation) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}

if ($password !== $password_confirmation) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}

$token_hash = hash("sha256", $token);

// --- Find the user ---
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token_hash = ?");
$stmt->execute([$token_hash]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "Invalid or used token.";
    header("Location: login.php");
    exit;
}

// --- Check token expiry ---
if (strtotime($user["reset_token_expires_at"]) <= time()) {
    $_SESSION['error'] = "Token has expired.";
    header("Location: login.php");
    exit;
}

// --- Hash new password and update DB ---
$new_password_hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users 
        SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL 
        WHERE user_id = ?");
$stmt->execute([$new_password_hash, $user["user_id"]]);

// --- Redirect with success message ---
$_SESSION['msg'] = "Password has been reset successfully. You can now log in.";
header("Location: login.php");
exit;
