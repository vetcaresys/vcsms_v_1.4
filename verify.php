<?php
require 'config.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';
$isStaff = isset($_GET['staff']); // kung staff link ni

if (empty($email) || empty($token)) {
    showMessage("Invalid Verification Link", "The verification link is missing or incomplete.", "danger");
    exit;
}

if (!$isStaff) {
    // -------- USERS --------
    $stmt = $pdo->prepare("SELECT user_id, verification_token, is_verified FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['is_verified']) {
            showMessage("Account Already Verified", "Your account is already verified. You can log in to your dashboard.", "info");
            exit;
        }

        if ($user['verification_token'] === $token) {
            $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE email = ?")
                ->execute([$email]);
            showMessage("Email Verified Successfully", "Your account has been verified. You may now log in.", "success");
        } else {
            showMessage("Invalid Token", "The verification token is invalid or has expired. Please request a new one.", "danger");
        }
        exit;
    }
} else {
    // -------- STAFF --------
    $stmt = $pdo->prepare("SELECT staff_id, verification_token, is_verified FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    $staff = $stmt->fetch();

    if ($staff) {
        if ($staff['is_verified']) {
            showMessage("Staff Account Already Verified", "Your staff account is already verified. You can log in now.", "info");
            exit;
        }

        if ($staff['verification_token'] === $token) {
            $pdo->prepare("UPDATE staff SET is_verified = 1, verification_token = NULL WHERE email = ?")
                ->execute([$email]);
            showMessage("Staff Email Verified", "Your staff account has been verified. You may now log in.", "success");
        } else {
            showMessage("Invalid Token", "The verification token is invalid or has expired. Please request a new one.", "danger");
        }
        exit;
    }
}

showMessage("Account Not Found", "No account was found with the provided email address.", "danger");

/**
 * 🔹 Helper function to show a nice Bootstrap-styled message
 */
function showMessage($title, $message, $type = "info") {
    echo "
    <!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title - VetCareSys</title>
        <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>
        <style>
            body {
                background: #f4f6f9;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                font-family: Arial, sans-serif;
            }
            .card {
                max-width: 500px;
                width: 100%;
                border-radius: 12px;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
        </style>
    </head>
    <body>
        <div class='card'>
            <div class='card-header bg-$type text-white text-center'>
                <h4>$title</h4>
            </div>
            <div class='card-body text-center'>
                <p class='mb-3'>$message</p>
                <a href='login.php' class='btn btn-primary'>Go to Login</a>
            </div>
        </div>
    </body>
    </html>";
}
