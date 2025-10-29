<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VetCareSys</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootswatch Theme (Lux) -->
  <!-- <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.2/dist/lux/bootstrap.min.css" rel="stylesheet"> -->
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="assets/css/index.css">
</head>

<body class="d-flex flex-column min-vh-100 bg-light">

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container">
      <a class="navbar-brand fw-bold" href="#">VetCareSys</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div id="navbarContent" class="collapse navbar-collapse">
        <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a href="login.php" class="btn btn-outline-light me-2">Login</a>
          </li>
          <li class="nav-item">
            <a href="register.php" class="btn btn-light">Register</a>
          </li>
        </ul>
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

  <footer class="bg-light text-center text-lg-start border-top mt-5">
    <div class="container py-3">
      <p class="mb-1 text-muted">&copy; 2025 VetCareSys. All rights reserved.</p>
      <!-- <p class="mb-2">
                <a href="index.php" class="text-decoration-none me-3">Home</a>
                <a href="about.php" class="text-decoration-none me-3">About</a>
                <a href="contact.php" class="text-decoration-none">Contact</a>
            </p>
            <div>
                <a href="#" class="text-muted me-3"><i class="bi bi-facebook"></i></a>
                <a href="#" class="text-muted me-3"><i class="bi bi-twitter"></i></a>
                <a href="#" class="text-muted"><i class="bi bi-instagram"></i></a>
            </div> -->
    </div>
  </footer>


  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>