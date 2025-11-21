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

    $stmt = $pdo->prepare("
        INSERT INTO notifications 
            (user_id, role, message, subject, link, schedule_date, sms, number, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
      $user_id,                            // user_id
      'admin',                             // role
      'Registration request for ' . $email,  // message
      'Register',                          // subject
      null,                                // link
      null,                                // schedule_date
      null,                                // sms
      null,                                // number
      'unread',                            // status
      date('Y-m-d H:i:s')                  // created_at
    ]);

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
        <a href='https://mysql1001.site4now.net/verify.php?email=$email&token=$verification_token' class='button'>Verify Email</a>
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

  <link rel="icon" type="image/jpg" href="assets/img/favicon-removebg-preview.png">

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <style>
    /* 🌟 Global Layout */
    body {
      background: linear-gradient(135deg, #0d6efd, #4b87f8);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      font-family: 'Inter', sans-serif;
      margin: 0;
      padding: 20px;
    }

    /* 🧩 Register Container */
    .register-card {
      background: #fff;
      border-radius: 20px;
      padding: 45px 40px;
      width: 100%;
      max-width: 500px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
      animation: fadeSlideIn 0.8s ease forwards;
    }

    /* 🧾 Scrollbar aesthetic */
    .register-card::-webkit-scrollbar {
      width: 6px;
    }

    .register-card::-webkit-scrollbar-thumb {
      background: #bbb;
      border-radius: 5px;
    }

    .register-card::-webkit-scrollbar-thumb:hover {
      background: #888;
    }

    /* 🧍 Form Inputs */
    .form-control,
    .form-select {
      border-radius: 10px;
      border: 1px solid #ccc;
      padding: 10px 14px;
      margin-bottom: 12px;
      transition: border-color 0.3s;
      font-size: 15px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: #0d6efd;
      box-shadow: 0 0 4px rgba(13, 110, 253, 0.25);
    }

    /* 🖱️ Button Styling */
    .btn-custom {
      background-color: #636868;
      color: white;
      border-radius: 10px;
      transition: 0.3s;
      padding: 10px;
      font-weight: 500;
      width: 100%;
      letter-spacing: 0.3px;
    }

    .btn-custom:hover {
      background-color: #8d9292;
      color: black;
      transform: translateY(-2px);
    }

    /* 💬 Input Group (Show Password Section) */
    .input-group {
      display: flex;
      align-items: stretch;
      margin-bottom: 12px;
    }

    .input-group .form-control {
      height: 45px;
      font-size: 15px;
      border-radius: 10px 0 0 10px;
      box-shadow: none;
    }

    .input-group .btn {
      border-radius: 0 10px 10px 0;
      background-color: #f8f9fa;
      border: 1px solid #ced4da;
      border-left: none;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 0 14px;
      height: 45px;
      transition: background-color 0.2s ease;
    }

    .input-group .btn:hover {
      background-color: #e9ecef;
    }

    .input-group .bi {
      font-size: 1.1rem;
      position: relative;
      top: -1px;
    }

    /* ✨ Header (Logo + Title) */
    .register-card h2 {
      text-align: center;
      margin-bottom: 25px;
      color: #333;
    }

    .register-card h2 img {
      width: 80px;
      height: auto;
      display: block;
      margin: 0 auto 10px;
    }

    /* ✨ Animations */
    @keyframes fadeSlideIn {
      from {
        transform: translateY(30px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    /* 📱 Responsive Fixes */
    @media (max-width: 480px) {
      .register-card {
        padding: 30px 20px;
        max-width: 90%;
      }

      .form-control,
      .form-select {
        font-size: 14px;
      }

      .btn-custom {
        font-size: 14px;
      }

      .input-group .form-control {
        height: 42px;
      }

      .input-group .btn {
        height: 42px;
      }
    }
  </style>

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



  <div class="register-card">
    <h2 class="text-center mb-4">
      <img src="assets/img/favicon-removebg-preview.png" alt="VetCareSys Logo"
        style="width: 140px; height: auto; display: block; margin: 0 auto 10px;">
      <i class="bi bi-person-plus-fill"></i> Register to VetCareSys
    </h2>


    <!-- Registration Form -->
    <form method="POST" action="" enctype="multipart/form-data">
      <!-- Role -->
      <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
          <option value="">Select Role</option>
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