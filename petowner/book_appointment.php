<?php
session_start();
require '../config.php';

// Enable detailed error reporting for PDO
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 🔒 Only pet owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$owner_name = htmlspecialchars($_SESSION['name'] ?? '');

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
    try {
        $clinic_id = $_POST['clinic_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $service_id = $_POST['service_id'] ?? null;
        $appointment_date = $_POST['appointment_date'] ?? null;
        $residence = trim($_POST['residence'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $doctor_id = !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : null;

        // Validate required fields
        if (!$clinic_id || !$pet_id || !$service_id || !$appointment_date || !$phone) {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'Please fill in all required fields.';
            header('Location: book_appointment.php');
            exit;
        }

        // 🔎 Check if a pending/approved appointment already exists (same date, service, and pet)
        $check = $pdo->prepare("
            SELECT * FROM appointments 
                WHERE owner_id = ? 
                AND clinic_id = ? 
                AND appointment_date = ? 
                AND service_id = ? 
                AND pet_id = ? 
                AND status IN ('pending', 'approved')
            ");
        $check->execute([$user_id, $clinic_id, $appointment_date, $service_id, $pet_id]);

        if ($check->rowCount() > 0) {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'You already have a booking for this service on this date.';
            header("Location: book_appointment.php?clinic_id=$clinic_id");
            exit;
        }

        // Get selected start time
        $start_time = $_POST['appointment_start']; // ex: "09:00"

        // Auto compute 1-hour end time
        $end_time = date("H:i", strtotime($start_time . " +1 hour"));

        // Insert appointment with start and end time
        $insert = $pdo->prepare("
    INSERT INTO appointments 
    (clinic_id, pet_id, owner_id, service_id, doctor_id, residence, phone, message, updated_by,
     appointment_date, appointment_start, appointment_end, status)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pending')
");

        $inserted = $insert->execute([
            $clinic_id,
            $pet_id,
            $user_id,
            $service_id,
            $doctor_id,
            $residence,
            $phone,
            $message,
            $appointment_date,
            $start_time,
            $end_time
        ]);
        // Fetch appointment date for notification
        $appointment_petname_stmt = $pdo->prepare("SELECT pet_name FROM pets WHERE pet_id = ?");
        $appointment_petname_stmt->execute([$pet_id]);
        $appointment_petname = $appointment_petname_stmt->fetchColumn();


        $message_notif = "New appointment booked by $owner_name for $appointment_petname, on date of $appointment_date.";
        if ($inserted) {
            // 🔔 Create notification for employee/admin
            $notif = $pdo->prepare("
                INSERT INTO notifications 
                (user_id, role,clinic_id, message, subject, link, schedule_date, sms, number, status, created_at)
                VALUES (?, ?, ?, ?,?, ?, ?, ?, ?, ?, ?)
            ");
            $notif->execute([
                $user_id,
                'employee',
                $clinic_id,
                $message_notif ?: 'New appointment request.',
                'Book Appointment',
                null,
                $appointment_date,
                null,
                $phone,
                'unread',
                date('Y-m-d H:i:s')
            ]);

            $_SESSION['booking_msg'] = 'success';
        } else {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'Failed to insert appointment.';
        }
    } catch (PDOException $e) {
        $_SESSION['booking_msg'] = 'error';
        $_SESSION['booking_error_text'] = 'Database error: ' . $e->getMessage();
    }

    header('Location: book_appointment.php');
    exit;
}

// 👤 Fetch user info
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'profile_default.jpg';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();
$name = htmlspecialchars($user['name'] ?? '');

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

// 🗓️ Pending Appointments
$pendingStmt = $pdo->prepare("
    SELECT a.appointment_id, a.status, a.appointment_date, a.appointment_start, a.appointment_end,
           p.pet_name, c.clinic_name, s.service_name
    FROM appointments a
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services s ON a.service_id = s.service_id
    WHERE a.owner_id = ? AND a.status = 'pending'
    ORDER BY a.appointment_date DESC
");
$pendingStmt->execute([$user_id]);
$pendingAppointments = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Approved Appointments
$approvedStmt = $pdo->prepare("
    SELECT a.appointment_id, a.status, a.appointment_date, a.appointment_start, a.appointment_end,
           p.pet_name, c.clinic_name, s.service_name
    FROM appointments a
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services s ON a.service_id = s.service_id
    WHERE a.owner_id = ? AND a.status = 'approved'
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
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
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
    <link rel="stylesheet" href="css/book_appointment.css">

</head>

<body class="bg-light">

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

    <?php include 'booking_alert.php' ?>
    <?php include 'navbar.php' ?>
    <?php include 'appointment_booking_header.php' ?>
    <?php include 'pending_appointments.php' ?>
    <?php include 'approved_appointments.php' ?>
    <?php include 'booking_modal.php' ?>
    <?php include 'footer.php'; ?>

    <script src="js/cancel_appointment.js"></script>
    <script src="js/doctor_schedule_loader.js"></script>
    <script src="js/profile_toggle.js"></script>
    <script src="js/cancel_appointment.js"></script>
    <script src="js/clinic_selector.js"></script>
    <script src="js/validation.js"></script>
    <script src="js/clinic_services.js"></script>
    <script src="js/appointment_refresh.js"></script>
    <script src="js/clinic_schedule.js"></script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>