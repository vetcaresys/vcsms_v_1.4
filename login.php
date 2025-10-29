<?php
include 'config.php';
session_start();

// Get and clear flash message
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = trim($_POST['email']);
  $password = $_POST['password'];

  // Check USERS table
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch();

  if ($user && password_verify($password, $user['password'])) {
    if ($user['role'] !== 'admin' && !$user['is_verified']) {
      $_SESSION['resend_email'] = $email;
      $_SESSION['error'] = "Please verify your email before logging in. 
                <a href='resend_verification.php' class='btn btn-sm btn-link'>Resend Verification Email</a>";
      header('Location: login.php');
      exit; // redirect clears the POST
    } elseif ($user['role'] === 'clinic_owner') {
      $stmtClinic = $pdo->prepare("SELECT status FROM clinics WHERE user_id = ?");
      $stmtClinic->execute([$user['user_id']]);
      $clinic = $stmtClinic->fetch();

      if ($clinic) {
        if ($clinic['status'] === 'pending') {
          $_SESSION['error'] = "Your clinic is pending approval from admin. Please do wait the approved notification at your gmail account!";
          header('Location: login.php');
          exit;
        } elseif ($clinic['status'] === 'rejected') {
          $_SESSION['error'] = "Your clinic registration has been rejected.";
          header('Location: login.php');
          exit;
        } else {
          $_SESSION['user_id'] = $user['user_id'];
          $_SESSION['role'] = $user['role'];
          $_SESSION['name'] = $user['name'];

          $_SESSION['success'] = "Login successfully! Welcome, " . $user['name'] . "!";

          header('Location: clinic/index.php');
          exit;
        }
      } else {
        $_SESSION['error'] = "No clinic record found for this account.";
        header('Location: login.php');
        exit;
      }
    } else {
      $_SESSION['user_id'] = $user['user_id'];
      $_SESSION['role'] = $user['role'];
      $_SESSION['name'] = $user['name'];

      $_SESSION['success'] = "Login successfully! Welcome, " . $user['name'] . ".";

      switch ($user['role']) {
        case 'admin':
          header('Location: admin/index.php');
          break;
        case 'pet_owner':
          header('Location: petowner/index.php');
          break;
        default:
          $_SESSION['error'] = "Unknown role.";
          header('Location: login.php');
          break;
      }
      exit;
    }
  } else {
    // Check STAFF table
    $stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
    $stmt->execute([$email]);
    $staff = $stmt->fetch();

    if ($staff && password_verify($password, $staff['password'])) {
      if (!$staff['is_verified']) {
        $_SESSION['resend_email'] = $email;
        $_SESSION['error'] = "Please verify your email before logging in. 
                    <a href='resend_verification.php' class='btn btn-sm btn-link'>Resend Verification Email</a>";
        header('Location: login.php');
        exit;
      } elseif ($staff['status'] === 'pending') {
        $_SESSION['error'] = "Your staff account is pending approval from clinic admin. Please do wait the approved notification at your gmail account!";
        header('Location: login.php');
        exit;
      } else {
        $_SESSION['staff_id'] = $staff['staff_id'];
        $_SESSION['clinic_id'] = $staff['clinic_id'];
        $_SESSION['name'] = $staff['name'];
        $_SESSION['role'] = $staff['role'];

        if ($staff['role'] === 'doctor') {
          header('Location: clinic/doctors/index.php');
          exit;
        } else {
          header('Location: clinic/staff/index.php');
          exit;
        }
      }
    } else {
      $_SESSION['error'] = "Invalid email or password.";
      header('Location: login.php');
      exit;
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - VetCareSys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/login.css">
</head>

<body class="d-flex flex-column min-vh-100">
  <div class="bg-overlay"></div>

  <?php if (isset($_SESSION['success'])): ?>
    <script>
      Swal.fire({
        title: 'Success!',
        text: '<?= addslashes($_SESSION['success']) ?>',
        icon: 'success',
        confirmButtonColor: '#3085d6'
      });
    </script>
    <?php unset($_SESSION['success']);
  endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    <script>
      Swal.fire({
        title: 'Oops!',
        html: '<?= addslashes($_SESSION['error']) ?>',
        icon: 'error',
        confirmButtonColor: '#d33'
      });
    </script>
    <?php unset($_SESSION['error']);
  endif; ?>


  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
      <div class="ms-auto">
        <a href="register.php" class="btn btn-light">Register</a>
      </div>
    </div>
  </nav>

  <div class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
    <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
      <div class="logo-container">
        <img src="assets/img/VetCareSystemLogo.png" alt="VetCareSys Logo">
      </div>
      <h2 class="text-center mb-4 text-primary"> Login</h2>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2"><?= $error ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="mb-3">
          <label for="email" class="form-label">Email address</label>
          <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password"
              required>
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
              <i id="toggleIcon" class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <div class="mt-3 text-center"><a href="forgot_password.php">Forgot Password?</a></div>
      <div class="mt-4 text-center"><a href="register.php">Don't have an account?</a></div>
      <div class="text-center mt-2"><a href="index.php"><i class="bi bi-arrow-left"></i> Back to Homepage</a></div>
    </div>
  </div>

  <footer class="bg-light text-center text-lg-start border-top mt-5">
    <div class="container py-3">
      <p class="mb-1 text-muted">&copy; 2025 VetCareSys. All rights reserved.</p>
    </div>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    function togglePassword() {
      const pass = document.getElementById("password");
      const icon = document.getElementById("toggleIcon");
      if (pass.type === "password") {
        pass.type = "text";
        icon.classList.replace("bi-eye", "bi-eye-slash");
      } else {
        pass.type = "password";
        icon.classList.replace("bi-eye-slash", "bi-eye");
      }
    }
  </script>
</body>

</html>