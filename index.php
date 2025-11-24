<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VetCareSys</title>
  <link rel="icon" type="image/jpg" href="assets/img/favicon-removebg-preview.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/index.css">
  <style>
    .vcs-footer {
      background: #ffffff;
      border-top: 1px solid #e5e5e5;
      box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
    }

    .footer-link {
      margin: 0 10px;
      text-decoration: none;
      color: #6c757d;
      font-weight: 500;
      transition: 0.2s;
    }

    .footer-link:hover {
      color: #0d6efd;
    }

    .footer-social {
      color: #6c757d;
      margin-left: 12px;
      font-size: 1.3rem;
      transition: 0.2s;
    }

    .footer-social:hover {
      color: #0d6efd;
      transform: translateY(-2px);
    }

    .vcs-footer h4 {
      font-size: 1.5rem;
      color: #0d6efd;
    }

    .vcs-footer p {
      font-size: 0.9rem;
    }

    #contact {
      background: #ffffff;
      /* solid white */
      position: relative;
      z-index: 2;
    }

    #contact form {
      background: #fff !important;
      border-radius: 12px;
    }
  </style>
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">VetCareSys</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <!-- Collapsible Content -->
      <div class="collapse navbar-collapse justify-content-end" id="navbarContent">
        <div class="d-flex flex-column flex-lg-row gap-2 mt-3 mt-lg-0">
          <a href="login.php" class="btn btn-outline-light">Login</a>
          <a href="register.php" class="btn btn-light">Register</a>
        </div>
      </div>
    </div>
  </nav>

  <!-- 🌿 Hero Section -->
  <header class="py-5">
    <div class="container">
      <div class="row align-items-center text-center text-lg-start">
        <!-- Text Column -->
        <div class="col-lg-6 mb-4 mb-lg-0">
          <h1 class="display-5 fw-bold text-primary">
            The Better Way to Manage Your Pet's Health
          </h1>
          <p class="lead text-muted mb-4">
            Get more value as a pet owner while your pets enjoy top-notch services from trusted veterinary clinics.
          </p>
          <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-start">
            <a href="browse_clinic.php" class="btn btn-primary btn-lg me-sm-3 mb-3 mb-sm-0">
              <i class="bi bi-search"></i> Browse Clinics
            </a>
            <a href="login.php" class="btn btn-outline-primary btn-lg">
              <i class="bi bi-building"></i> Book an Appointment?
            </a>
          </div>
        </div>
        <!-- Image Column -->
        <div class="col-lg-6 text-center">
          <img src="R.png" class="img-fluid rounded shadow-sm" alt="Pet Care">
        </div>
      </div>
    </div>
  </header>

  <section id="contact" class="py-5">
    <div class="container">
      <h2 class="text-center mb-4">Have a Question?</h2>
      <p class="text-center text-muted mb-4">Send us your inquiry and we'll get back to you soon!</p>
      <form action="submit_inquiry.php" method="POST" class="mx-auto p-4 shadow rounded">
        <div class="mb-3">
          <label for="name" class="form-label">Your Name</label>
          <input type="text" name="name" id="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="email" class="form-label">Email</label>
          <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="subject" class="form-label">Subject</label>
          <input type="text" name="subject" id="subject" class="form-control" required>
        </div>
        <div class="mb-3">
          <label for="message" class="form-label">Message</label>
          <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send Inquiry</button>
      </form>
    </div>
  </section>

  <section id="about" class="py-5" style="background-color: #f8f9fb; position: relative; z-index: 5;">
    <div class="container">
      <h2 class="text-center mb-4 fw-bold text-primary">About VetCareSys</h2>
      <p class="text-center mb-5" style="color: #495057; font-weight: 500; opacity: 1;">
        VetCareSys is a web-based veterinary management system that helps clinics organize pet records,
        schedules, and staff with ease. Built for clinic owners in Misamis Occidental, it brings clarity,
        structure, and reliability into everyday clinic operations.
      </p>

      <div class="row g-4">
        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
            <i class="bi bi-journal-medical fs-1 text-primary mb-3"></i>
            <h5 class="fw-bold">Record Management</h5>
            <p style="color: #495057; opacity: 1;">Manage pet health records efficiently and securely.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
            <i class="bi bi-people-fill fs-1 text-primary mb-3"></i>
            <h5 class="fw-bold">Staff & Doctors</h5>
            <p style="color: #495057; opacity: 1;">Organize roles, staff accounts, and doctor schedules.</p>
          </div>
        </div>

        <div class="col-md-4">
          <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
            <i class="bi bi-geo-alt-fill fs-1 text-primary mb-3"></i>
            <h5 class="fw-bold">Clinic Mapping</h5>
            <p style="color: #495057; opacity: 1;">Built-in GPS map locator for easier clinic discovery.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <footer class="vcs-footer mt-5">
    <div class="container py-4">

      <div class="row align-items-center gy-4">

        <!-- Logo + Tagline -->
        <div class="col-md-4 text-center text-md-start">
          <h4 class="fw-bold mb-1">VetCareSys</h4>
          <p class="text-muted small mb-0">Your trusted companion in clinic management.</p>
        </div>

        <!-- Quick Links -->
        <div class="col-md-4 text-center">
          <a href="index.php" class="footer-link">Home</a>
          <a href="#about" class="footer-link">About</a>
          <a href="#contact" class="footer-link">Contact</a>
        </div>

        <!-- Social Icons -->
        <div class="col-md-4 text-center text-md-end">
          <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-twitter"></i></a>
          <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
        </div>

      </div>

      <div class="text-center pt-3">
        <p class="text-muted small mb-0">&copy; 2025 VetCareSys. All rights reserved.</p>
      </div>

    </div>
  </footer>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Check URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('sent') === '1') {
      Swal.fire({
        title: "Message Sent!",
        text: "Your inquiry has been successfully submitted.",
        icon: "success",
        confirmButtonColor: "#0d6efd"
      });
    }
  </script>

</body>

</html>