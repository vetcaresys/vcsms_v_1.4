<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
  $role = $_POST['role'] ?? 'pet_owner';
  $contact = trim($_POST['contact_number'] ?? '');
  $address = trim($_POST['address'] ?? '');
  $verification_token = bin2hex(random_bytes(16));

  // Default Status
  $status = ($role === 'clinic_owner') ? 'pending' : 'active';

  // 🔍 Check kung duplicate ang email
  $checkUser = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $checkUser->execute([$email]);
  $existingUser = $checkUser->fetch();

  if ($existingUser) {
    $_SESSION['duplicate'] = "The email you’re trying to register is already in the system. Please use a different email or just log in instead.";
    header("Location: register.php");
    exit;
  }

  // Insert User
  $stmt = $pdo->prepare("
        INSERT INTO users 
            (name, email, password, role, contact_number, address, 
             profile_picture, reset_token_hash, reset_token_expires_at, 
             verification_token, is_verified)
        VALUES (?, ?, ?, ?, ?, ?, NULL, NULL, NULL, ?, 0)
    ");
  $stmt->execute([$name, $email, $password, $role, $contact, $address, $verification_token]);
  $user_id = $pdo->lastInsertId();

  // If role = clinic_owner → insert clinic details
  if ($role === 'clinic_owner') {
    $clinicName = trim($_POST['clinic_name'] ?? '');
    $clinicAddr = trim($_POST['clinic_address'] ?? '');
    $clinicContact = trim($_POST['clinic_contact'] ?? '');

    // Upload Clinic Logo
    $logoPath = null;
    if (!empty($_FILES['clinic_logo']['name'])) {
      $logoDir = "uploads/logos/";
      if (!is_dir($logoDir))
        mkdir($logoDir, 0777, true);

      $logoPath = $logoDir . uniqid() . "_" . basename($_FILES['clinic_logo']['name']);
      move_uploaded_file($_FILES['clinic_logo']['tmp_name'], $logoPath);
    }

    // Upload Business Permit
    $permitPath = null;
    if (!empty($_FILES['business_permit']['name'])) {
      $permitDir = "uploads/permits/";
      if (!is_dir($permitDir))
        mkdir($permitDir, 0777, true);

      $permitPath = $permitDir . uniqid() . "_" . basename($_FILES['business_permit']['name']);
      move_uploaded_file($_FILES['business_permit']['tmp_name'], $permitPath);
    }

    // Insert Clinic into DB
    $stmt = $pdo->prepare("
            INSERT INTO clinics 
                (parent_clinic_id, user_id, clinic_name, address, contact_info, 
                 latitude, longitude, logo, business_permit, status)
            VALUES (NULL, ?, ?, ?, ?, NULL, NULL, ?, ?, 'pending')
        ");
    $stmt->execute([$user_id, $clinicName, $clinicAddr, $clinicContact, $logoPath, $permitPath]);
  }

  // ✅ Send Verification Email
  $mail = require 'mail.php';
  $mail->setFrom("loelynates@gmail.com", "VetCareSys");
  $mail->addAddress($email, $name);
  $mail->Subject = "Verify Your VetCareSys Account";
  $mail->isHTML(true);

  // 🧩 Email Body
  $mail->Body = "
<html>
<head>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f4f6f8;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 600px;
      margin: 30px auto;
      background: #ffffff;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    }
    .header {
      background: #0d6efd;
      text-align: center;
      padding: 25px;
    }
    .header img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
    }
    .header h2 {
      color: #fff;
      margin: 10px 0 0 0;
      font-size: 22px;
      font-weight: 600;
    }
    .content {
      padding: 25px;
      color: #333;
      font-size: 15px;
      line-height: 1.7;
    }
    .button {
      display: inline-block;
      background: #0d6efd;
      color: #ffffff !important;
      padding: 12px 24px;
      text-decoration: none;
      border-radius: 5px;
      font-weight: 600;
      letter-spacing: 0.5px;
      transition: background 0.3s;
    }
    .button:hover {
      background: #0b5ed7;
    }
    .footer {
      background: #f1f3f5;
      text-align: center;
      padding: 15px;
      font-size: 12px;
      color: #777;
    }
  </style>
</head>
<body>
  <div class='container'>
    <div class='header'>
      <h2>VetCareSys</h2>
    </div>
    <div class='content'>
      <p>Dear <b>$name</b>,</p>
      <p>Welcome to <b>VetCareSys</b> — your partner in smart veterinary management.</p>
      <p>Please confirm your email address to activate your account:</p>
      <p style='text-align:center;'>
        <a href='http://localhost/vcsms_v_1.4/verify.php?email=$email&token=$verification_token' class='button'>Verify Email</a>
      </p>
      <p>If you didn’t register for VetCareSys, please disregard this email.</p>
      <p>Kind regards,<br><b>VetCareSys Team</b></p>
    </div>
    <div class='footer'>
      <p>© 2025 VetCareSys. All rights reserved.</p>
    </div>
  </div>
</body>
</html>
";
  // ✅ Send email
  if ($mail->send()) {
    $_SESSION['msg'] = "Registered successfully! Please check your email to verify your account.";
    header("Location: register.php");
    exit;
  } else {
    echo "Error sending verification email: " . $mail->ErrorInfo;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - VetCareSys</title>

  <link rel="icon" type="image/jpg" href="../img/favicon.jpg">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/register.css">
</head>

<body>

  <?php if (isset($_SESSION['duplicate'])): ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
      Swal.fire({
        icon: 'error',
        title: 'Duplicate Email',
        text: '<?= addslashes($_SESSION['duplicate']) ?>',
        confirmButtonColor: '#d33'
      });
    </script>
    <?php unset($_SESSION['duplicate']); endif; ?>

  <div class="register-wrapper">
    <div class="register-image"></div>

    <div class="register-card">
      <h2 class="text-center mb-4">
        <i class="bi bi-person-plus-fill"></i> Register to VetCareSys
      </h2>

      <!-- Registration Form -->
      <form method="POST" action="" enctype="multipart/form-data">
        <!-- Role -->
        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role" class="form-select" required>
            <option value="">-- Select Role --</option>
            <option value="pet_owner">Pet Owner</option>
            <option value="clinic_owner">Clinic Owner</option>
          </select>
        </div>

        <!-- Common Fields -->
        <div class="mb-3">
          <input type="text" name="name" class="form-control" placeholder="Full Name" required>
        </div>

        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" class="form-control" name="email" id="email" placeholder="Enter your email" required>
        </div>

        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <input type="password" class="form-control" id="password" name="password" required
              placeholder="Enter your password">
            <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
              <i id="toggleIcon" class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <div class="mb-3">
          <label for="contact_number" class="form-label">Contact Number</label>
          <input type="tel" class="form-control" name="contact_number" id="contact_number" maxlength="11"
            placeholder="09XXXXXXXXX" required oninput="this.value = this.value.replace(/[^0-9]/g, '')">
        </div>

        <div class="mb-3">
          <label for="address" class="form-label">Address</label>
          <input type="text" class="form-control" name="address" id="address" required placeholder="Complete address">
        </div>

        <!-- Clinic-Owner Only -->
        <div class="mb-3 clinic-only" style="display:none;">
          <label class="form-label">Upload Clinic Logo</label>
          <input type="file" name="clinic_logo" class="form-control" accept="image/*">
        </div>

        <div class="mb-3 clinic-only" style="display:none;">
          <label class="form-label">Upload Business Permit</label>
          <input type="file" name="business_permit" class="form-control" accept="image/*,.pdf">
        </div>

        <!-- Submit -->
        <button type="submit" class="btn btn-custom w-100 mb-2">Register</button>

        <div class="text-center">
          <a href="login.php" class="text-decoration-none">Already have an Account?</a> |
          <a href="index.php" class="text-decoration-none">Back to Homepage</a>
        </div>
      </form>
    </div>
  </div>

  <!-- Success Modal -->
  <?php if (!empty($_SESSION['msg'])): ?>
    <div class="modal fade" id="successModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Success</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <?= htmlspecialchars($_SESSION['msg']); ?>
          </div>
          <div class="modal-footer">
            <a href="login.php" class="btn btn-success">Go to Login</a>
          </div>
        </div>
      </div>
    </div>

    <script>
      document.addEventListener("DOMContentLoaded", function () {
        var successModal = new bootstrap.Modal(document.getElementById('successModal'));
        successModal.show();
      });
    </script>
    <?php unset($_SESSION['msg']);
  endif; ?>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Toggle Password Visibility
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

    // Show clinic-only fields when role = clinic_owner
    document.querySelector('[name="role"]').addEventListener('change', function () {
      document.querySelectorAll('.clinic-only').forEach(div => {
        div.style.display = this.value === 'clinic_owner' ? 'block' : 'none';
      });
    });
  </script>
</body>

</html>