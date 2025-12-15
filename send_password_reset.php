<?php
require __DIR__ . "/config.php";
session_start();

$email = trim($_POST["email"] ?? '');

if (!$email) {
    $error = "Please enter your email.";
    goto show_alert;
}

// Search BOTH TABLES
$stmt = $pdo->prepare("
    SELECT 'users' AS type, user_id AS id, email 
    FROM users WHERE email = ?
    UNION
    SELECT 'staff' AS type, staff_id AS id, email 
    FROM staff WHERE email = ?
");
$stmt->execute([$email, $email]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

// No account found
if (!$account) {
    $error = "No account found with this email.";
    goto show_alert;
}

// Generate secure token
$token = bin2hex(random_bytes(16));
$token_hash = hash("sha256", $token);
$expiry = date("Y-m-d H:i:s", time() + 60 * 30); // 30 minutes

// Update correct table
if ($account['type'] === "users") {
    $sql = "UPDATE users SET reset_token_hash=?, reset_token_expires_at=? WHERE email=?";
} else {
    $sql = "UPDATE staff SET reset_token_hash=?, reset_token_expires_at=? WHERE email=?";
}

$stmt = $pdo->prepare($sql);
$stmt->execute([$token_hash, $expiry, $email]);

// Build email
$mail = require __DIR__ . "/mail.php";
$mail->addAddress($email);
$mail->Subject = "VetCareSys Password Reset Request";

$resetLink = "https://vetcaresys-001-site1.ntempurl.com/reset_password.php?token=$token";

$mail->Body = <<<HTML
<p>We received a request to reset your password.</p>
<p>Click below to reset your password:</p>
<p><a href="$resetLink">Reset Password</a></p>
<p>This link is valid for 30 minutes.</p>
HTML;

try {
    $mail->send();
    $success = "A password reset link has been sent to your email.";
} catch (Exception $e) {
    $error = "Failed to send email. Please try again later.";
}

show_alert:
?>
<!DOCTYPE html>
<html>

<head>
    <title>Password Reset</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/jpg" href="assets/img/favicon-removebg-preview.png">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            /* default body font */
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        .bold-text {
            font-family: 'Poppins', sans-serif;
            /* for headings or emphasis */
            font-weight: 600;
            /* or 500/700 depending on style */
        }
    </style>
</head>

<body>
    <script>
        <?php if (!empty($success)): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '<?= addslashes($success) ?>',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'forgot_password.php';
            });
        <?php elseif (!empty($error)): ?>
            Swal.fire({
                icon: 'error',
                title: 'Oops!',
                text: '<?= addslashes($error) ?>',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = 'forgot_password.php';
            });
        <?php endif; ?>
    </script>
</body>

</html>