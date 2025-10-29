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

// Look up user by token
$stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token_hash = ?");
$stmt->execute([$token_hash]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    $_SESSION['error'] = "Invalid or expired password reset token.";
    header("Location: login.php");
    exit;
}

// Check token expiry
if (strtotime($user["reset_token_expires_at"]) <= time()) {
    $_SESSION['error'] = "Password reset token has expired.";
    header("Location: login.php");
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100 justify-content-center align-items-center">

<div class="card p-4 shadow" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-3 text-primary">Reset Password</h3>

    <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form method="POST" action="process_reset_password.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <div class="mb-3">
            <label for="password" class="form-label">New Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
        </div>

        <div class="mb-3">
            <label for="password_confirmation" class="form-label">Confirm New Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
    </form>
</div>

</body>
</html>
