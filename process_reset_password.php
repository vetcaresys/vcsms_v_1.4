<?php
require __DIR__ . "/config.php";
session_start();

$token = $_GET['token'] ?? null;

if (!$token) {
    $_SESSION['error'] = "Invalid password reset link.";
    header("Location: login.php");
    exit;
}

$token_hash = hash("sha256", $token);

// Look up token in both tables
$stmt = $pdo->prepare("
    SELECT 'users' AS type, user_id AS id, reset_token_expires_at 
    FROM users WHERE reset_token_hash = ?
    UNION
    SELECT 'staff' AS type, staff_id AS id, reset_token_expires_at 
    FROM staff WHERE reset_token_hash = ?
");
$stmt->execute([$token_hash, $token_hash]);

$account = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$account) {
    $_SESSION['error'] = "Invalid or expired reset link.";
    header("Location: login.php");
    exit;
}

if (strtotime($account["reset_token_expires_at"]) <= time()) {
    $_SESSION['error'] = "Password reset link has expired.";
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create New Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
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
<body class="d-flex min-vh-100 justify-content-center align-items-center bg-light">

<div class="card p-4 shadow" style="max-width:400px; width:100%;">
    <h3 class="text-center text-primary mb-3">Create New Password</h3>

    <form method="POST" action="update_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label class="form-label">New Password</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
    </form>
</div>

</body>
</html>
