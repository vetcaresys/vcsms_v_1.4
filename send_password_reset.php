<?php
require __DIR__ . "/config.php";

$email = trim($_POST["email"] ?? '');

if (!$email) {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error - VetCareSys</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light">
        <div class="card p-4 shadow" style="max-width: 500px; width: 100%;">
            <h3 class="text-center text-danger mb-3">Invalid Input</h3>
            <div class="alert alert-danger">
                Please enter your email address to reset your password.
            </div>
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </body>
    </html>
    HTML;
    exit;
}

// Generate secure token
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes expiry

// Update user with reset token
$sql = "UPDATE users
        SET reset_token_hash = ?, reset_token_expires_at = ?
        WHERE email = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$token_hash, $expiry, $email]);

if ($stmt->rowCount()) {
    $mail = require __DIR__ . "/mail.php";
    $mail->setFrom("noreply@vetcaresys.com", "VetCareSys Support");
    $mail->addAddress($email);
    $mail->Subject = "VetCareSys Password Reset Request";

    $resetLink = "http://localhost/vcsms_v_1.4/reset_password.php?token=$token";
    $mail->Body = <<<HTML
        <p>Dear User,</p>
        <p>We received a request to reset the password associated with this email address. 
        If you made this request, please click the link below to reset your password:</p>
        <p><a href="$resetLink">Reset Password</a></p>
        <p>This link is valid for 30 minutes. If you did not request a password reset, 
        please ignore this email and your password will remain unchanged.</p>
        <p>Thank you,<br>VetCareSys Team</p>
    HTML;

    try {
        $mail->send();
        // Success message
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset Sent - VetCareSys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light">
            <div class="card p-4 shadow" style="max-width: 500px; width: 100%;">
                <h3 class="text-center text-primary mb-3">Password Reset Requested</h3>
                <div class="alert alert-success">
                    A password reset link has been sent to <strong>{$email}</strong>. 
                    Please check your inbox and follow the instructions to reset your password.
                </div>
                <div class="text-center mt-3">
                    <a href="login.php" class="btn btn-primary">Back to Login</a>
                </div>
            </div>
        </body>
        </html>
        HTML;

    } catch (Exception $e) {
        // Email sending failed
        echo <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Password Reset Failed - VetCareSys</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light">
            <div class="card p-4 shadow" style="max-width: 500px; width: 100%;">
                <h3 class="text-center text-danger mb-3">Unable to Send Email</h3>
                <div class="alert alert-danger">
                    We were unable to send the password reset email. Please try again later.<br>
                    Error details: {$mail->ErrorInfo}
                </div>
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="btn btn-secondary">Back</a>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

} else {
    // No account associated with email
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Email Not Found - VetCareSys</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="d-flex flex-column min-vh-100 justify-content-center align-items-center bg-light">
        <div class="card p-4 shadow" style="max-width: 500px; width: 100%;">
            <h3 class="text-center text-warning mb-3">Email Not Found</h3>
            <div class="alert alert-warning">
                There is no account associated with <strong>{$email}</strong>. Please check the email address and try again.
            </div>
            <div class="text-center mt-3">
                <a href="forgot_password.php" class="btn btn-secondary">Back</a>
            </div>
        </div>
    </body>
    </html>
    HTML;
}
