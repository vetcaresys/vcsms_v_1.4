<?php
session_start();
require '../../config.php';
require '../../mail.php'; // PHPMailer setup
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Set timezone
date_default_timezone_set('Asia/Manila');

// ✅ Staff authentication
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../index.php');
    exit;
}

$clinic_id = $_SESSION['clinic_id'];
$staff_name = htmlspecialchars($_SESSION['name'] ?? '');

// --- Function to send appointment email ---
function sendAppointmentEmail($pdo, $appointment_id, $clinic_id, $status = null)
{
    // Fetch appointment + clinic details
    $stmt = $pdo->prepare("
        SELECT 
            u.email, u.name AS owner_name, p.pet_name, s.service_name, 
            a.appointment_date, a.appointment_start, a.appointment_end,
            c.clinic_name, c.address
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN users u ON p.owner_id = u.user_id
        JOIN clinic_services s ON a.service_id = s.service_id
        JOIN clinics c ON a.clinic_id = c.clinic_id
        WHERE a.appointment_id = ? AND a.clinic_id = ?
    ");
    $stmt->execute([$appointment_id, $clinic_id]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment)
        return;

    $ownerEmail = $appointment['email'];
    $ownerName = htmlspecialchars($appointment['owner_name']);
    $petName = htmlspecialchars($appointment['pet_name']);
    $service = htmlspecialchars($appointment['service_name']);
    $appointmentDate = date("F j, Y", strtotime($appointment['appointment_date']));
    $appointmentStart = date("g:i A", strtotime($appointment['appointment_start']));
    $appointmentEnd = date("g:i A", strtotime($appointment['appointment_end']));
    $clinicName = htmlspecialchars($appointment['clinic_name']);
    $clinicAddress = htmlspecialchars($appointment['address']);

    $mapLink = "https://www.google.com/maps/dir/?api=1&origin=My+Location&destination=" . urlencode($clinicAddress);
    $statusText = $status ? "has been <strong>" . htmlspecialchars(ucfirst($status)) . "</strong>" : "details have been updated";

    // PHPMailer setup
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "vetcaresys@gmail.com";   // Gmail
        $mail->Password = "ddghlcrdfyroulbj";    // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('vetcaresys@gmail.com', 'VetCareSys');
        $mail->addAddress($ownerEmail, $ownerName);

        $mail->isHTML(true);
        $mail->Subject = "Appointment Update for {$petName}";

        $mail->Body = "
            <p>Dear <strong>{$ownerName}</strong>,</p>
            <p>This is to inform you that the appointment for your pet <strong>{$petName}</strong> regarding <strong>{$service}</strong> {$statusText}.</p>

            <p><strong>Appointment Details:</strong><br>
            📅 Date: {$appointmentDate}<br>
            🕘 Time: {$appointmentStart} - {$appointmentEnd}<br>
            🏥 Clinic: {$clinicName}<br>
            📍 Address: {$clinicAddress}</p>

            <p><a href='{$mapLink}' target='_blank' style='display:inline-block;padding:8px 12px;background-color:#0d6efd;color:#fff;text-decoration:none;border-radius:5px;'>View on Google Maps</a></p>

            <p>Please ensure to arrive on time. Should you have any questions or need to reschedule, contact us at the clinic directly.</p>

            <p>Thank you for trusting <strong>VetCareSys</strong>.</p>
            <hr>
            <small style='color:gray;'>This is an automated message. Please do not reply to this email.</small>
        ";

        $mail->AltBody = "Dear {$ownerName},\n\nThe appointment for {$petName} ({$service}) {$statusText}.\n
Date: {$appointmentDate}\n
Time: {$appointmentStart} - {$appointmentEnd}\n
Clinic: {$clinicName}\n
Address: {$clinicAddress}\n
Google Maps: {$mapLink}\n\n
Please arrive on time. Contact the clinic for questions.\n
Thank you for trusting VetCareSys.";

        $mail->send();
    } catch (Exception $e) {
        error_log("Mailer Error: {$mail->ErrorInfo}");
    }
}

// --- Handle GET status update (Approve/Cancel) ---
if (isset($_GET['update'], $_GET['status'])) {
    $appointment_id = $_GET['update'];
    $new_status = $_GET['status'];

    $stmt = $pdo->prepare("
        SELECT owner_id, appointment_date, appointment_start, appointment_end, phone 
        FROM appointments 
        WHERE appointment_id = ? AND clinic_id = ?
    ");
    $stmt->execute([$appointment_id, $clinic_id]);
    $appointment_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$appointment_data) {
        header('Location: some_error_page.php?error=notfound');
        exit;
    }

    $petowner_id = $appointment_data['owner_id'];
    $appointment_date = $appointment_data['appointment_date'];
    $appointment_start = $appointment_data['appointment_start'];
    $appointment_end = $appointment_data['appointment_end'];
    $phone = $appointment_data['phone'];

    $formatted_date = date("F j, Y", strtotime($appointment_date));
    $formatted_start = date("g:i A", strtotime($appointment_start));
    $formatted_end = date("g:i A", strtotime($appointment_end));

    $updateStmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND clinic_id = ?");
    $updateStmt->execute([$new_status, $appointment_id, $clinic_id]);

    sendAppointmentEmail($pdo, $appointment_id, $clinic_id, $new_status);

    $notification_message = "Your appointment has been " . htmlspecialchars($new_status) . " for {$formatted_date} from {$formatted_start} to {$formatted_end}.";
    $notification_subject = ucfirst($new_status) . " Appointment Confirmation";

    $notifStmt = $pdo->prepare("
        INSERT INTO notifications 
            (user_id, role,clinic_id, message, subject, schedule_date, sms, number, status, created_at)
        VALUES (?, ?, ?,?, ?, ?, ?, ?, ?, ?)
    ");
    $notifStmt->execute([
        $petowner_id,
        'pet_owner',
        $clinic_id,
        $notification_message,
        $notification_subject,
        substr($appointment_date, 0, 10),
        '1',
        $phone,
        'unread',
        date('Y-m-d H:i:s')
    ]);

    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'index.php'));
    exit;
}

// --- Handle POST edit from modal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_start = $_POST['appointment_start'];
    $appointment_end = $_POST['appointment_end'];
    $doctor_id = $_POST['doctor_id'];

    $stmt = $pdo->prepare("
        UPDATE appointments 
        SET appointment_date = ?, appointment_start = ?, appointment_end = ?, doctor_id = ? 
        WHERE appointment_id = ? AND clinic_id = ?
    ");
    $stmt->execute([$appointment_date, $appointment_start, $appointment_end, $doctor_id, $appointment_id, $clinic_id]);

    sendAppointmentEmail($pdo, $appointment_id, $clinic_id);
}

// --- Fetch all appointments ---
$stmt = $pdo->prepare("
    SELECT a.*, p.pet_name, p.owner_id, s.service_name, 
           u.name AS owner_name, u.email AS owner_email, u.contact_number AS owner_contact
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinic_services s ON a.service_id = s.service_id
    JOIN users u ON p.owner_id = u.user_id
    WHERE a.clinic_id = ?
    ORDER BY 
        CASE a.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'completed' THEN 3
            WHEN 'cancelled' THEN 4
            ELSE 5
        END ASC
");
$stmt->execute([$clinic_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['doctor_id'])) {
    $doctor_id = $_POST['doctor_id'];

    $stmt = $pdo->prepare("
        SELECT day_of_week, start_time, end_time
        FROM doctor_visits
        WHERE doctor_id = ? AND clinic_id = ?
        ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
    ");
    $stmt->execute([$doctor_id, $clinic_id]);

    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}


// --- Staff profile info ---
$staff_id = $_SESSION['staff_id'];
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Customer Appointments - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="includes/css/manage_customer_appointment.css">
    <style>
        /* 🌟 Global Styles */
body {
    font-family: 'Inter', sans-serif;
    background-color: #f8f9fb;
    color: #2e2e2e;
    line-height: 1.6;
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

/* Links */
.nav-link {
    font-weight: 500;
    transition: color 0.3s ease;
}

.nav-link:hover {
    color: #ffc107 !important;
}

/* 🧾 Summary Cards */
.summary-card {
    border: none;
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
}

.summary-card h5 {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
}

.summary-card h2 {
    font-weight: 700;
    font-size: 2rem;
}

/* 💼 Tables */
.table {
    border-radius: 10px;
    overflow: hidden;
    font-size: 0.95rem;
}

.table thead {
    background-color: #0d6efd;
    color: white;
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
}

.table tbody tr:hover {
    background-color: #f2f7ff;
}

/* 🪄 Buttons */
.btn {
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

/* 🧩 Modals */
.modal-content {
    border-radius: 15px;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
}

.modal-header {
    border-radius: 15px 15px 0 0;
    background: linear-gradient(90deg, #0d6efd, #007bff);
    color: white;
}

.modal-title {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
}

/* 🧍 Form */
.form-label {
    font-weight: 600;
    color: #333;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #ccc;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
}

/* ⚡ Sweet alert pop */
.swal2-popup {
    font-family: 'Inter', sans-serif !important;
    border-radius: 15px !important;
}

/* 🌈 Badges */
.badge {
    font-size: 0.85rem;
    padding: 6px 10px;
    border-radius: 8px;
}

/* 🐾 Page Titles */
h4.text-primary {
    font-family: 'Poppins', sans-serif;
    font-weight: 600;
    color: #0d6efd !important;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* 📦 Footer vibe */
.container-footer {
    text-align: center;
    margin-top: 50px;
    font-size: 0.9rem;
    color: #777;
}

/* 🧭 Datatables */
div.dataTables_wrapper .dataTables_filter input {
    border-radius: 8px;
    border: 1px solid #ddd;
}

div.dataTables_wrapper .dataTables_length select {
    border-radius: 6px;
}

/* 🧁 Animations */
.card,
.modal-content {
    transition: all 0.25s ease-in-out;
}

/* 🩵 Table container border and shadow */
.table-responsive {
    border: 2px solid #cfe2ff;
    /* light blue border */
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
    background-color: #ffffff;
    padding: 1rem;
}

/* 🩵 Table border styling */
.table {
    border: 1px solid #dee2e6;
    border-collapse: separate !important;
    border-radius: 10px;
    overflow: hidden;
}

/* 🩵 Header style */
.table thead.table-primary th {
    background-color: #0d6efd !important;
    color: white;
    font-weight: 600;
    text-transform: uppercase;
}

/* 🩵 Row hover effect */
.table-hover tbody tr:hover {
    background-color: #f8f9fa !important;
    transition: background-color 0.2s ease-in-out;
}

/* 🩵 Cell padding and borders */
.table td,
.table th {
    vertical-align: middle;
    border-color: #dee2e6;
    padding: 0.75rem;
}

/* 🩵 Make the badge look tighter */
.badge {
    font-size: 0.85rem;
    padding: 0.45em 0.6em;
}

/* 🩵 Add smooth animation to buttons */
.btn {
    transition: all 0.2s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
}

/* 🌟 Global Styles */
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fb;
      color: #2e2e2e;
      line-height: 1.6;
      background: linear-gradient(135deg, #f0f4ff, #ffffff);
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

    /* Links */
    .nav-link {
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .nav-link:hover {
      color: #ffc107 !important;
    }

    /* 🧾 Summary Cards */
    .summary-card {
      border: none;
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-card h5 {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .summary-card h2 {
      font-weight: 700;
      font-size: 2rem;
    }

    /* 💼 Tables */
    .table {
      border-radius: 10px;
      overflow: hidden;
      font-size: 0.95rem;
    }

    .table thead {
      background-color: #0d6efd;
      color: white;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .table tbody tr:hover {
      background-color: #f2f7ff;
    }

    /* 🪄 Buttons */
    .btn {
      border-radius: 8px;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* 🧩 Modals */
    .modal-content {
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      border-radius: 15px 15px 0 0;
      background: linear-gradient(90deg, #0d6efd, #007bff);
      color: white;
    }

    .modal-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    /* 🧍 Form */
    .form-label {
      font-weight: 600;
      color: #333;
    }

    .form-control {
      border-radius: 8px;
      border: 1px solid #ccc;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* ⚡ Sweet alert pop */
    .swal2-popup {
      font-family: 'Inter', sans-serif !important;
      border-radius: 15px !important;
    }

    /* 🌈 Badges */
    .badge {
      font-size: 0.85rem;
      padding: 6px 10px;
      border-radius: 8px;
    }

    /* 🐾 Page Titles */
    h4.text-primary {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      color: #0d6efd !important;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* 📦 Footer vibe */
    .container-footer {
      text-align: center;
      margin-top: 50px;
      font-size: 0.9rem;
      color: #777;
    }

    /* 🧭 Datatables */
    div.dataTables_wrapper .dataTables_filter input {
      border-radius: 8px;
      border: 1px solid #ddd;
    }

    div.dataTables_wrapper .dataTables_length select {
      border-radius: 6px;
    }

    /* 🧁 Animations */
    .card,
    .modal-content {
      transition: all 0.25s ease-in-out;
    }

    @media (max-width: 576px) {
      .navbar-brand img {
        width: 28px;
        height: 28px;
      }

      .dropdown-toggle strong {
        display: none;
        /* hide name on very small screens */
      }
    }
    </style>
</head>

<body class="bg-light">

    <?php include 'includes/body/navbar.php' ?>

    <!--  Main Content -->
    <div class="container py-5">


        <?php if (count($appointments) > 0): ?>
            <div class="table-responsive">
                <!-- Table with DataTables -->
                <table id="appointmentsTable" class="table table-bordered table-hover align-middle">
                    <h2 class="text-primary">Welcome, Staff <?php echo $staff_name; ?>!</h2>
                    <h4 class="mb-4">Manage Booking Appointments</h4>

                    <?php if (isset($update_message)): ?>
                        <div class="alert alert-success"><?php echo $update_message; ?></div>
                    <?php endif; ?>

                    <thead class="table-primary">
                        <tr>
                            <th>Pet</th>
                            <th>Owner</th>
                            <th>Service</th>
                            <th>Appointment Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr>
                                <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                <td><?= htmlspecialchars($appt['owner_name']); ?></td>
                                <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                <td><?= date("M d, Y - h:i A", strtotime($appt['appointment_date'])); ?></td>
                                <td>
                                    <span class="badge 
                            <?php
                            switch ($appt['status']) {
                                case 'pending':
                                    echo 'bg-warning text-dark';
                                    break;
                                case 'approved':
                                    echo 'bg-primary';
                                    break;
                                case 'completed':
                                    echo 'bg-success';
                                    break;
                                case 'cancelled':
                                    echo 'bg-danger';
                                    break;
                                default:
                                    echo 'bg-secondary';
                            }
                            ?>">
                                        <?= ucfirst($appt['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($appt['status'] === 'pending'): ?>
                                        <a href="#" class="btn btn-sm btn-info text-white view-appointment"
                                            data-id="<?= $appt['appointment_id']; ?> data-bs-toggle=" tooltip"
                                            data-bs-placement="top" title="View Record">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="?update=<?= $appt['appointment_id']; ?>&status=approved"
                                            class="btn btn-sm btn-success me-1 action-appointment" data-status="approved"
                                            data-id="<?= $appt['appointment_id']; ?> data-bs-toggle=" tooltip"
                                            data-bs-placement="top" title="Approve Record">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </a>
                                    <?php elseif ($appt['status'] === 'approved'): ?>
                                        <a href="?update=<?= $appt['appointment_id']; ?>&status=completed"
                                            class="btn btn-sm btn-primary me-1 action-appointment" data-status="completed"
                                            data-id="<?= $appt['appointment_id']; ?> data-bs-toggle=" tooltip"
                                            data-bs-placement="top" title="Complete Record">
                                            <i class="bi bi-check2-square"></i>
                                        </a>
                                    <?php else: ?>
                                        <em class="text-muted">No further actions</em>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info">No appointments found yet.</div>
        <?php endif; ?>
    </div>

    <!-- 👁️ View & Edit Appointment Modal -->
    <div class="modal fade" id="viewAppointmentModal" tabindex="-1" aria-labelledby="viewAppointmentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewAppointmentModalLabel">Appointment Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- VIEW MODE -->
                    <div id="viewMode">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Pet Name:</strong> <span id="v_pet_name"></span></p>
                                <p><strong>Owner:</strong> <span id="v_owner_name"></span></p>
                                <p><strong>Owner Email:</strong> <span id="v_owner_email"></span></p>
                                <p><strong>Owner Contact:</strong> <span id="v_owner_contact"></span></p>
                                <p><strong>Residence:</strong> <span id="v_residence"></span></p>
                                <p><strong>Phone:</strong> <span id="v_phone"></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Service:</strong> <span id="v_service_name"></span></p>
                                <p><strong>Doctor:</strong> <span id="v_doctor_name"></span></p>
                                <p><strong>Clinic:</strong> <span id="v_clinic_name"></span></p>
                                <p><strong>Clinic Address:</strong> <span id="v_clinic_address"></span></p>
                                <p><strong>Date:</strong> <span id="v_appointment_date"></span></p>
                                <p><strong>Time:</strong> <span id="v_appointment_time"></span></p>
                            </div>
                        </div>

                        <hr>

                        <p><strong>Message/Notes:</strong></p>
                        <p id="v_message" class="border rounded p-2 bg-light"></p>

                        <hr>
                        <p><strong>Status:</strong> <span class="badge" id="v_status_badge"></span></p>
                        <p><strong>Last Updated By:</strong> <span id="v_updated_by"></span></p>
                        <p><strong>Last Updated:</strong> <span id="v_updated_at"></span></p>
                    </div>

                    <!-- EDIT MODE -->
                    <div id="editMode" style="display:none;">
                        <form id="viewEditAppointmentForm" method="POST">
                            <input type="hidden" name="appointment_id" id="edit_appointment_id">

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Appointment Date</label>
                                        <input type="date" name="appointment_date" id="edit_appointment_date"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" name="appointment_start" id="edit_appointment_start"
                                            class="form-control">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" name="appointment_end" id="edit_appointment_end"
                                            class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Assign Doctor</label>
                                        <select name="doctor_id" id="edit_doctor_id" class="form-select">
                                            <option value=""> Select Doctor </option>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT staff_id, name FROM staff WHERE clinic_id = ? AND role = 'doctor'");
                                            $stmt->execute([$clinic_id]);
                                            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $doc):
                                                ?>
                                                <option value="<?= $doc['staff_id'] ?>">
                                                    <?= htmlspecialchars($doc['name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label>Doctor Schedule</label>
                                        <div id="editDoctorSchedule"
                                            style="border: 1px solid #ccc; padding: 10px; border-radius: 5px;">
                                            <em>Select a doctor to view their clinic visit schedules.</em>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Message/Notes</label>
                                <textarea name="message" id="edit_message" class="form-control" rows="3"></textarea>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="toggleViewBtn">Edit</button>
                    <button type="submit" form="viewEditAppointmentForm" class="btn btn-success" id="saveChangesBtn"
                        style="display:none;">Save Changes</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // When doctor is changed in EDIT MODAL
$(document).on("change", "#edit_doctor_id", function () {
    let doctorId = $(this).val();
    let clinicId = <?= $clinic_id ?>;  // STAFF CLINIC IS ALWAYS FIXED

    if (!doctorId) {
        $("#editDoctorSchedule").html("<em>No doctor selected.</em>");
        return;
    }

    $.ajax({
        url: "fetch_doctor_schedule.php",
        type: "POST",
        data: {
            doctor_id: doctorId,
            clinic_id: clinicId
        },
        success: function (response) {
            $("#editDoctorSchedule").html(response);
        }
    });
});
    </script>

    <script>
        $(document).ready(function () {
            $('.view-appointment').click(function (e) {
                e.preventDefault();
                const id = $(this).data('id');
                console.log("View clicked:", id);

                $.get('fetch_appointments.php', { id }, function (data) {
                    let a = {};
                    try { a = JSON.parse(data); } catch { a = {}; }

                    if (!a.appointment_id) {
                        alert('Could not load appointment details.');
                        return;
                    }

                    // Fill view mode
                    $('#v_pet_name').text(a.pet_name);
                    $('#v_owner_name').text(a.owner_name);
                    $('#v_owner_email').text(a.owner_email || 'N/A');
                    $('#v_owner_contact').text(a.owner_contact || 'N/A');
                    $('#v_residence').text(a.residence || 'N/A');
                    $('#v_phone').text(a.phone || 'N/A');
                    $('#v_service_name').text(a.service_name);
                    $('#v_doctor_name').text(a.doctor_name || 'Not assigned');
                    $('#v_clinic_name').text(a.clinic_name);
                    $('#v_clinic_address').text(a.clinic_address);
                    $('#v_message').text(a.message || 'No additional notes');

                    const date = new Date(a.appointment_date);
                    const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                    $('#v_appointment_date').text(formattedDate);

                    const start = a.appointment_start ? a.appointment_start.slice(0, 5) : 'N/A';
                    const end = a.appointment_end ? a.appointment_end.slice(0, 5) : 'N/A';
                    $('#v_appointment_time').text(`${start} - ${end}`);

                    const badge = $('#v_status_badge');
                    badge.text(a.status.charAt(0).toUpperCase() + a.status.slice(1));
                    badge.attr('class', 'badge ' + ({
                        pending: 'bg-warning text-dark',
                        approved: 'bg-primary',
                        completed: 'bg-success',
                        cancelled: 'bg-danger'
                    }[a.status] || 'bg-secondary'));

                    $('#v_updated_by').text(a.updated_by_name || 'System');
                    $('#v_updated_at').text(a.updated_at ? new Date(a.updated_at).toLocaleString() : 'N/A');

                    // Fill edit mode
                    $('#edit_appointment_id').val(a.appointment_id);
                    // Format date for <input type="date">
                    $('#edit_appointment_date').val(a.appointment_date ? a.appointment_date.slice(0, 10) : '');
                    $('#edit_appointment_start').val(a.appointment_start);
                    $('#edit_appointment_end').val(a.appointment_end);
                    $('#edit_doctor_id').val(a.doctor_id || '');
                    $('#edit_message').val(a.message || '');

                    // Show modal
                    $('#viewMode').show();
                    $('#editMode').hide();
                    $('#toggleViewBtn').text('Edit').removeClass('btn-success').addClass('btn-outline-secondary');
                    $('#saveChangesBtn').hide();
                    $('#viewAppointmentModal').modal('show');
                });
            });

            // Toggle between view/edit mode
            $('#toggleViewBtn').click(function () {
                const isView = $('#viewMode').is(':visible');
                $('#viewMode').toggle(!isView);
                $('#editMode').toggle(isView);
                $('#saveChangesBtn').toggle(isView);
                $(this)
                    .text(isView ? 'Cancel Edit' : 'Edit')
                    .toggleClass('btn-outline-secondary btn-success');
            });
        });
    </script>

    <script>
        $('#viewEditAppointmentForm').on('submit', function (e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $('#saveChangesBtn').prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Saving...');

            $.ajax({
                url: 'update_appointment.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('✅ Appointment updated successfully!');
                        $('#viewAppointmentModal').modal('hide');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert('⚠️ Update failed: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function () {
                    alert('❌ Error connecting to server.');
                },
                complete: function () {
                    $('#saveChangesBtn').prop('disabled', false).text('Save Changes');
                }
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = document.getElementById('editAppointmentModal');
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-id');
                const date = button.getAttribute('data-date');
                const start = button.getAttribute('data-start');
                const end = button.getAttribute('data-end');
                const doctor = button.getAttribute('data-doctor');

                document.getElementById('edit_appointment_id').value = id;
                document.getElementById('edit_appointment_date').value = date;
                document.getElementById('edit_appointment_start').value = start;
                document.getElementById('edit_appointment_end').value = end;
                document.getElementById('edit_doctor_id').value = doctor;
            });
        });
    </script>

    <script>
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#appointmentsTable').DataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50, 100],
                "order": [], // ❌ remove auto-sorting
                "language": {
                    "search": "Search appointments:",
                    "lengthMenu": "Show _MENU_ entries per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ appointments",
                    "infoEmpty": "No appointments available",
                    "zeroRecords": "No matching appointments found"
                }
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('.action-appointment').click(function (e) {
                e.preventDefault();
                const btn = $(this);
                const status = btn.data('status');
                const id = btn.data('id');

                Swal.fire({
                    title: `Are you sure you want to ${status}?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, proceed!',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirect to your PHP update URL
                        window.location.href = `?update=${id}&status=${status}`;
                    }
                });
            });

            // Optional: Show success message if URL has ?success=approved/completed
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            if (success) {
                Swal.fire({
                    title: 'Success!',
                    text: `Appointment ${success} successfully!`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                // Remove the query string after showing
                window.history.replaceState({}, document.title, window.location.pathname);
            }
        });
    </script>
</body>

</html>