<?php
session_start();
require '../config.php';

// 🔒 Only pet owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$owner_name = htmlspecialchars($_SESSION['name']);

// 🐾 Fetch pets of the owner
$pets_stmt = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ?");
$pets_stmt->execute([$user_id]);
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// 🏥 Fetch approved clinics
$clinics = $pdo->query("
    SELECT clinic_id, clinic_name, address, logo
    FROM clinics
    WHERE status = 'approved'
")->fetchAll(PDO::FETCH_ASSOC);

// 📝 Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $clinic_id = $_POST['clinic_id'];
    $pet_id = $_POST['pet_id'];
    $service_id = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $residence = $_POST['residence'];
    $phone = $_POST['phone'];
    $message = $_POST['message'];

    $insert = $pdo->prepare("
    INSERT INTO appointments 
    (clinic_id, pet_id, service_id, doctor_id, residence, phone, message, updated_by, appointment_date, status)
    VALUES (?, ?, ?, NULL, ?, ?, ?, NULL, ?, 'pending')
    ");
    $insert->execute([
        $clinic_id,
        $pet_id,
        $service_id,
        $residence,
        $phone,
        $message,
        $appointment_date
    ]);

    $check = $pdo->prepare("
    SELECT * FROM appointments 
    WHERE owner_id = ? 
      AND clinic_id = ? 
      AND appointment_date = ? 
      AND status IN ('pending', 'approved')
    ");
    $check->execute([$user_id, $clinic_id, $appointment_date]);

    if ($check->rowCount() > 0) {
        $_SESSION['error'] = "You already have a booking on this date. Cancel it first before rebooking.";
        header("Location: book_appointment.php?clinic_id=$clinic_id");
        exit;
    }


    $_SESSION['booking_msg'] = $insert->rowCount() ? 'success' : 'error';
    header('Location: book_appointment.php');
    exit;
}

// 👤 Fetch user info
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();
$name = htmlspecialchars($user['name']);

// Format contact number
$contact = $user['contact_number'] ?? '';
if (!empty($contact)) {
    $contact = preg_replace('/\s+/', '', $contact);
    if (preg_match('/^09\d{9}$/', $contact)) {
        $contact = '+63' . substr($contact, 1);
    } elseif (preg_match('/^639\d{9}$/', $contact)) {
        $contact = '+' . $contact;
    }
} else {
    $contact = 'N/A';
}

// 🗓️ Pending/cancellable appointments
$pendingStmt = $pdo->prepare("
    SELECT a.appointment_id, a.status, a.appointment_date, a.appointment_start, a.appointment_end,
           p.pet_name, c.clinic_name, s.service_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinics c ON a.clinic_id = c.clinic_id
    JOIN clinic_services s ON a.service_id = s.service_id
    WHERE p.owner_id = ? AND a.status = 'pending'
    ORDER BY a.appointment_date DESC
");
$pendingStmt->execute([$user_id]);
$pendingAppointments = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Approved appointments
$approvedStmt = $pdo->prepare("
    SELECT a.appointment_id, a.status, a.appointment_date, a.appointment_start, a.appointment_end,
           p.pet_name, c.clinic_name, s.service_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinics c ON a.clinic_id = c.clinic_id
    JOIN clinic_services s ON a.service_id = s.service_id
    WHERE p.owner_id = ? AND a.status = 'approved'
    ORDER BY a.appointment_date DESC
");
$approvedStmt->execute([$user_id]);
$approvedAppointments = $approvedStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - VetCareSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* 🌟 Global Reset */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
            color: #2e2e2e;
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* 🧭 Navbar */
        .navbar {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 50%;
            background: #fff;
            padding: 3px;
            margin-right: 10px;
            transition: transform 0.2s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.08);
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        /* 🧾 Cards */
        .card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .card-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #0d6efd;
        }

        /* 🧍 Modal */
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            background: linear-gradient(90deg, #0d6efd, #007bff);
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* ⚡ SweetAlert */
        .swal2-popup {
            font-family: 'Inter', sans-serif !important;
            border-radius: 15px !important;
        }

        /* 🧠 Responsive Footer */
        footer {
            background-color: #212529;
            color: #fff;
            text-align: center;
            padding: 15px 0;
            font-size: 0.9rem;
            font-family: 'Inter', sans-serif;
            margin-top: auto;
        }

        /* ✨ Dashboard Stats Grid */
        #dashboardStats .card {
            border-radius: 15px;
            text-align: center;
        }

        #dashboardStats .card h5 {
            color: #495057;
            font-family: 'Poppins', sans-serif;
        }

        #dashboardStats .display-6 {
            font-weight: 700;
        }

        /* Keep navbar links always white */
        .navbar-dark .navbar-nav .nav-link {
            color: white !important;
        }

        .navbar-dark .navbar-nav .nav-link:hover,
        .navbar-dark .navbar-nav .nav-link:focus,
        .navbar-dark .navbar-nav .nav-link.active {
            color: white !important;
        }

        /* for clinic schedule */
        #clinicScheduleDisplay ul {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        #clinicScheduleDisplay li {
            background: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 6px;
            margin-bottom: 6px;
            padding: 8px 12px;
        }
    </style>
</head>

<body class="bg-light">

    <?php if (isset($_SESSION['booking_msg'])): ?>
        <script>
            Swal.fire({
                icon: '<?= $_SESSION['booking_msg'] === "success" ? "success" : "error" ?>',
                title: '<?= $_SESSION['booking_msg'] === "success" ? "Appointment booked!" : "Booking failed." ?>',
                text: '<?= $_SESSION['booking_msg'] === "success" ? "Please wait for approval." : "Please try again later." ?>'
            });
        </script>
        <?php unset($_SESSION['booking_msg']); endif; ?>


    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_pets.php" class="nav-link">Manage Pets</a></li>
                    <li class="nav-item"><a href="book_appointment.php" class="nav-link active">Book Appointment</a>
                    </li>
                </ul>
                <div class="dropdown ms-auto">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow text-center text-lg-start"
                        aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person"></i> Profile</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="logout.php" id="logoutForm" class="m-0">
                                <button class="dropdown-item text-danger" type="submit" id="logoutBtn">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <!-- Book Appointment Section -->
    <div class="container my-4">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-0 text-primary"><i class="bi bi-calendar2-heart"></i> Book Appointment</h4>
            <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#bookingModal">
                <i class="bi bi-calendar-plus"></i> Book Now
            </button>
        </div>
    </div>

    <!-- Pending Appointments -->
    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Pending Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="pendingTable" class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pet</th>
                                <th>Clinic</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($pendingAppointments) === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No pending appointments</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($pendingAppointments as $appt): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                        <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                        <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                        <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                        <td><span class="badge bg-warning"><?= ucfirst($appt['status']); ?></span></td>
                                        <td>
                                            <button class="btn btn-sm btn-danger cancel-btn"
                                                data-id="<?= $appt['appointment_id'] ?>">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Approved Appointments -->
    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-check-circle"></i> Approved Appointments</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="approvedTable" class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pet</th>
                                <th>Clinic</th>
                                <th>Service</th>
                                <th>Date & Time</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($approvedAppointments) === 0): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">No approved appointments</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($approvedAppointments as $appt):
                                    $dt = new DateTime($appt['appointment_date']); // full datetime from appointment_date                              
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                        <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                        <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                        <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                        <td><span class="badge bg-primary"><?= ucfirst($appt['status']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Optional: DataTables & Cancel Script -->
    <script>

        // Cancel button for pending appointments
        $(document).on('click', '.cancel-btn', function () {
            const id = $(this).data('id');
            Swal.fire({
                title: "Cancel Appointment?",
                text: "You cannot undo this action.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, cancel it"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "cancel_appointment.php?id=" + id;
                }
            });
        });

    </script>



    <!-- Booking Modal -->
    <div class="modal fade" id="bookingModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-calendar-check"></i> Book an Appointment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="row g-3 p-3">

                    <!-- Full Name -->
                    <div class="col-md-6">
                        <label class="form-label">Full Name *</label>
                        <input type="text" class="form-control" value="<?= htmlspecialchars($owner_name) ?>" readonly>
                    </div>

                    <!-- Email -->
                    <div class="col-md-6">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>"
                            readonly>
                    </div>

                    <!-- Clinic -->
                    <div class="col-md-6">
                        <label class="form-label">Select Branch *</label>
                        <select name="clinic_id" id="clinicSelect" class="form-select" required>
                            <option value="">Select a branch</option>
                            <?php foreach ($clinics as $clinic): ?>
                                <option value="<?= $clinic['clinic_id'] ?>">
                                    <?= htmlspecialchars($clinic['clinic_name']) ?> -
                                    <?= htmlspecialchars($clinic['address']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Service -->
                    <div class="col-md-6">
                        <label class="form-label">Reason of Appointment *</label>
                        <select name="service_id" id="serviceSelect" class="form-select" required disabled>
                            <option value="">Please select a clinic first</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Clinic Schedule</label>
                        <div id="clinicScheduleDisplay" class="border rounded p-3 bg-light text-muted">
                            Please select a clinic to view available schedules.
                        </div>
                    </div>

                    <!-- Residence -->
                    <div class="col-md-6">
                        <label class="form-label">Area of Residence</label>
                        <input type="text" name="residence" class="form-control" placeholder="eg. Makawa, Aloran">
                    </div>

                    <!-- Appointment Date -->
                    <div class="col-md-6">
                        <label class="form-label">Appointment Date *</label>
                        <input type="date" name="appointment_date" class="form-control" min="<?= date('Y-m-d') ?>"
                            required>
                    </div>

                    <!-- Phone -->
                    <div class="col-md-6">
                        <label class="form-label">Phone Number *</label>
                        <input type="text" name="phone" id="phone" class="form-control"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>" maxlength="11" pattern="^09\d{9}$"
                            required>
                    </div>

                    <!-- Pet -->
                    <div class="col-md-6">
                        <label class="form-label">Select Your Pet *</label>
                        <select name="pet_id" class="form-select" required>
                            <option value="">-- Select Pet --</option>
                            <?php foreach ($pets as $pet): ?>
                                <option value="<?= $pet['pet_id'] ?>"><?= htmlspecialchars($pet['pet_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Message -->
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea name="message" class="form-control" rows="3"></textarea>
                    </div>

                    <!-- Submit -->
                    <div class="modal-footer">
                        <button type="submit" name="submit_booking" class="btn btn-success">
                            <i class="bi bi-calendar-plus"></i> Confirm Booking
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">

                <div id="viewProfile">
                    <div class="modal-header">
                        <h5 class="modal-title">My Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3"
                            width="100">
                        <h4><?= htmlspecialchars($user['name']) ?></h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($contact) ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($user['address'] ?? 'N/A') ?></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
                    </div>
                </div>

                <div id="editProfile" style="display:none;">
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= htmlspecialchars($user['contact_number']) ?>" inputmode="numeric"
                                    maxlength="11" pattern="^09\d{9}$" placeholder="e.g., 09123456789" required
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                <div class="invalid-feedback">
                                    Contact must be 11 digits and start with 09.
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }

        $(document).on("click", ".cancel-btn", function () {
            const id = $(this).data("id");
            Swal.fire({
                title: "Cancel Appointment?",
                text: "You cannot undo this action.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, cancel it"
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "cancel_appointment.php?id=" + id;
                }
            });
        });
    </script>

    <script>
        function reloadWithClinic(clinicId) {
            if (clinicId) {
                window.location.href = "?clinic_id=" + clinicId;
            }
        }
    </script>

    <script>
        function validatePhone(input) {
            // Allow only digits while typing
            input.value = input.value.replace(/[^0-9]/g, '');

            // Regex validation (must start with 09 and be 11 digits)
            const regex = /^09\d{9}$/;
            if (!regex.test(input.value)) {
                input.setCustomValidity("Invalid phone number. Must start with 09 and be 11 digits long.");
            } else {
                input.setCustomValidity("");
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('clinicSelect').addEventListener('change', function () {
            const clinicId = this.value;
            const serviceSelect = document.getElementById('serviceSelect');

            if (!clinicId) {
                serviceSelect.innerHTML = '<option value="">Please select a clinic first</option>';
                serviceSelect.disabled = true;
                return;
            }

            // Fetch services from PHP
            fetch('get_services.php?clinic_id=' + clinicId)
                .then(res => res.json())
                .then(data => {
                    serviceSelect.innerHTML = '';
                    if (data.length > 0) {
                        serviceSelect.disabled = false;
                        serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
                        data.forEach(service => {
                            const opt = document.createElement('option');
                            opt.value = service.service_id;
                            opt.textContent = `${service.service_name}`;
                            serviceSelect.appendChild(opt);
                        });
                    } else {
                        serviceSelect.disabled = true;
                        serviceSelect.innerHTML = '<option value="">No services available</option>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    serviceSelect.disabled = true;
                    serviceSelect.innerHTML = '<option value="">Error loading services</option>';
                });
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById("searchInput");
            const statusFilter = document.getElementById("statusFilter");
            const rows = document.querySelectorAll("#appointmentsTable tbody tr");

            function filterTable() {
                const searchText = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value.toLowerCase();

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    const status = row.querySelector("td:last-child").textContent.toLowerCase();

                    const matchesSearch = text.includes(searchText);
                    const matchesStatus = !statusValue || status.includes(statusValue);

                    row.style.display = (matchesSearch && matchesStatus) ? "" : "none";
                });
            }

            searchInput.addEventListener("keyup", filterTable);
            statusFilter.addEventListener("change", filterTable);
        });
    </script>

    <?php if (isset($_SESSION['profile_msg'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: 'success',
                    title: 'Profile Updated',
                    text: 'Your profile has been successfully updated!',
                    confirmButtonColor: '#28a745'
                });
            });
        </script>
        <?php unset($_SESSION['profile_msg']);
    endif; ?>


    <script>
        function confirmLogout() {
            Swal.fire({
                title: "Logout?",
                text: "Are you sure you want to log out?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#dc3545",
                cancelButtonColor: "#6c757d",
                confirmButtonText: "Yes, logout"
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('logoutForm').submit();
                }
            });
        }
    </script>
    <script>
        $(document).ready(function () {
            $('#appointmentsTable').DataTable({
                "pageLength": 5, // default 5 per page
                "lengthMenu": [5, 10, 25, 50], // options
                "ordering": true,
                "info": true, // enables "Showing 1 to X of Y entries"
                "language": {
                    "search": "🔍 Search:"
                }
            });
        });
    </script>

    <script>
        let lastUpdated = null;

        function refreshAppointments() {
            $.get('get_appointments.php', function (data) {
                const table = $('#appointmentsTable').DataTable();

                const temp = $('<table>').html(data);
                const newest = $(temp).find('tr:first').data('updated');

                // Notify if something changed
                if (lastUpdated && newest !== lastUpdated) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Appointment Updated',
                        text: 'One or more of your appointments have been changed by the clinic.',
                        timer: 4000,
                        showConfirmButton: false
                    });
                }

                lastUpdated = newest;

                table.clear().draw();
                $('#appointmentsTable tbody').html(data);
                table.rows.add($('#appointmentsTable tbody tr')).draw(); // re-init
            });
        }

        // Poll every 15 seconds
        setInterval(refreshAppointments, 15000);

        // Load immediately
        refreshAppointments();
    </script>

    <script>
        $(document).ready(function () {
            $('#clinicSelect').on('change', function () {
                var clinicId = $(this).val();

                if (clinicId) {
                    $.ajax({
                        url: 'get_clinic_schedule.php',
                        method: 'GET',
                        data: { clinic_id: clinicId },
                        success: function (response) {
                            $('#clinicScheduleDisplay').html(response);
                        },
                        error: function () {
                            $('#clinicScheduleDisplay').html('<div class="text-danger">Failed to load schedule. Try again.</div>');
                        }
                    });
                } else {
                    $('#clinicScheduleDisplay').html('Please select a clinic to view available schedules.');
                }
            });
        });
    </script>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function (e) {
            e.preventDefault(); // Prevent form from submitting instantly

            Swal.fire({
                title: 'Are you sure you want to logout?',
                text: "You’ll be logged out of your current session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'No, stay here'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form only if confirmed
                    document.getElementById('logoutForm').submit();
                }
            });
        });
    </script>
</body>

</html>