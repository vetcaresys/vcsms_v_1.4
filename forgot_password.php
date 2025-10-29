<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password - VetCareSys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg, #5a5d5e, #0e0f0f);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      color: #333;
    }
    .forgot-card {
      background: #fff;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0,0,0,0.2);
      max-width: 450px;
      width: 100%;
    }
    .btn-custom {
      background-color: #636868;
      color: white;
      border-radius: 10px;
      transition: 0.3s;
    }
    .btn-custom:hover {
      background-color: #898b8c;
      color: black;
    }
  </style>
</head>
<body>

  <div class="forgot-card">
    <h2 class="text-center mb-3 text-primary"><i class="bi bi-key-fill"></i> Forgot Password</h2>
    <p class="text-center text-muted mb-4">
      Enter your registered email address below, and we will send you a link to reset your password.
    </p>

    <form method="post" action="send_password_reset.php">
      <div class="mb-3">
        <label for="email" class="form-label">Email Address</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
      </div>

      <button type="submit" class="btn btn-custom w-100">Send Password Reset Link</button>
    </form>

    <div class="mt-4 text-center">
      <a href="login.php" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Login</a>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
