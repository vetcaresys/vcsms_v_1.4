<?php
require __DIR__ . "/config.php";
session_start();

$token = $_POST['token'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirmation = $_POST['password_confirmation'] ?? '';

if (!$token) {
    $_SESSION['error'] = "Invalid request.";
    header("Location: login.php");
    exit;
}

if ($password !== $password_confirmation) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: reset_password.php?token=" . urlencode($token));
    exit;
}

$token_hash = hash("sha256", $token);

// Look up token in both tables
$stmt = $pdo->prepare("
    SELECT 'users' AS type, user_id AS id 
    FROM users WHERE reset_token_hash = ?
    UNION
    SELECT 'staff' AS type, staff_id AS id 
    FROM staff WHERE reset_token_hash = ?
");
$stmt->execute([$token_hash, $token_hash]);
$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    $_SESSION['error'] = "Invalid or expired token.";
    header("Location: login.php");
    exit;
}

// Update password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

if ($account['type'] === 'users') {
    $update = $pdo->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?");
} else {
    $update = $pdo->prepare("UPDATE staff SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE staff_id = ?");
}

$update->execute([$hashed_password, $account['id']]);

// SweetAlert success
?>
<!DOCTYPE html>
<html>

<head>
    <title>Password Reset</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        Swal.fire({
            icon: 'success',
            title: 'Password Reset Successful!',
            text: 'You can now log in with your new password.',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = 'login.php';
        });
    </script>
</body>

</html>