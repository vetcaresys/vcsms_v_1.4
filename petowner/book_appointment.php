<?php
session_start();
require '../config.php';

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 🔒 Only pet owners allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$owner_name = $_SESSION['name'] ?? 'Customer';

// 🐾 Fetch pets
$pets_stmt = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ?");
$pets_stmt->execute([$user_id]);
$pets = $pets_stmt->fetchAll(PDO::FETCH_ASSOC);

// 🏥 Fetch approved clinics
$clinics = $pdo->query("
    SELECT clinic_id, clinic_name, address, logo
    FROM clinics
    WHERE status = 'approved'
")->fetchAll(PDO::FETCH_ASSOC);

// 📝 Handle booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {

    try {
        $clinic_id = $_POST['clinic_id'] ?? null;
        $pet_id = $_POST['pet_id'] ?? null;
        $service_id = $_POST['service_id'] ?? null;
        $appointment_date = $_POST['appointment_date'] ?? null;
        $start_time = $_POST['appointment_start'] ?? null;
        $residence = trim($_POST['residence'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $doctor_id = !empty($_POST['doctor_id']) ? $_POST['doctor_id'] : null;

        // Required fields
        if (!$clinic_id || !$pet_id || !$service_id || !$appointment_date || !$start_time || !$phone) {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'Please fill in all required fields.';
            header('Location: book_appointment.php');
            exit;
        }

        // ✅ STANDARDIZE PHONE NUMBER (CRITICAL FIX)
        // ✅ STANDARDIZE: 09xxxxxxxxx ONLY
        $phone = preg_replace('/\s+/', '', $phone);

        if (!preg_match('/^09\d{9}$/', $phone)) {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'Invalid phone number. Use 09XXXXXXXXX.';
            header('Location: book_appointment.php');
            exit;
        }


        // Duplicate check
        $check = $pdo->prepare("
            SELECT appointment_id FROM appointments
            WHERE owner_id = ?
              AND clinic_id = ?
              AND appointment_date = ?
              AND service_id = ?
              AND pet_id = ?
              AND status IN ('pending','approved')
        ");
        $check->execute([$user_id, $clinic_id, $appointment_date, $service_id, $pet_id]);

        if ($check->rowCount() > 0) {
            $_SESSION['booking_msg'] = 'error';
            $_SESSION['booking_error_text'] = 'You already have a booking for this service on this date.';
            header("Location: book_appointment.php?clinic_id=$clinic_id");
            exit;
        }

        // Time computation
        $end_time = date("H:i", strtotime($start_time . " +1 hour"));

        // Insert appointment
        $insert = $pdo->prepare("
            INSERT INTO appointments
            (clinic_id, pet_id, owner_id, service_id, doctor_id, residence, phone, message,
             updated_by, appointment_date, appointment_start, appointment_end, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, 'pending')
        ");

        $insert->execute([
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

        // 🐶 Pet name
        $petStmt = $pdo->prepare("SELECT pet_name FROM pets WHERE pet_id = ?");
        $petStmt->execute([$pet_id]);
        $pet_name = $petStmt->fetchColumn();

        // 🔔 Employee notification
        $admin_message = "New appointment booked by $owner_name for $pet_name on $appointment_date.";

        $notif = $pdo->prepare("
            INSERT INTO notifications
            (user_id, role, clinic_id, message, subject, link, schedule_date, sms, number, status, created_at)
            VALUES (?, 'employee', ?, ?, 'Book Appointment', NULL, ?, NULL, ?, 'unread', NOW())
        ");
        $notif->execute([
            $user_id,
            $clinic_id,
            $admin_message,
            $appointment_date,
            $phone
        ]);

        // 📩 BOOKING CONFIRMATION SMS (SEND IMMEDIATELY)
        date_default_timezone_set('Asia/Manila');
        $schedule_date = date('Y-m-d H:i:s'); // ✅ SEND NOW

        $sms_message =
            "Hi {$owner_name}, your booking is received. " .
            "If you want to cancel, please cancel it before clinic approval. – VetCareSys";

        $smsStmt = $pdo->prepare("
            INSERT INTO notifications
            (user_id, role, clinic_id, message, subject, link, status, schedule_date, sms, number, created_at)
            VALUES
            (?, 'pet_owner', ?, ?, 'BOOKING_SMS', 'appointments.php',
             'unread', ?, 2, ?, NOW())
        ");

        $smsStmt->execute([
            $user_id,
            $clinic_id,
            $sms_message,
            $schedule_date,
            $phone
        ]);

        $_SESSION['booking_msg'] = 'success';
        header('Location: book_appointment.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['booking_msg'] = 'error';
        $_SESSION['booking_error_text'] = 'Database error: ' . $e->getMessage();
        header('Location: book_appointment.php');
        exit;
    }
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
           a.created_at,
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

// ❌ Cancelled Appointments
$cancelledStmt = $pdo->prepare("
    SELECT a.appointment_id, a.status, a.appointment_date,
           a.appointment_start, a.appointment_end,
           p.pet_name, c.clinic_name, s.service_name
    FROM appointments a
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services s ON a.service_id = s.service_id
    WHERE a.owner_id = ? AND a.status = 'cancelled'
    ORDER BY a.appointment_date DESC
");
$cancelledStmt->execute([$user_id]);
$cancelledAppointments = $cancelledStmt->fetchAll(PDO::FETCH_ASSOC);
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

    <?php
    if (isset($_SESSION['booking_msg'])) {

        if ($_SESSION['booking_msg'] === 'success') {
            $icon = 'success';
            $title = 'Appointment booked!';
            $text = 'Please wait for approval.';
        } elseif ($_SESSION['booking_msg'] === 'cancelled') {
            $icon = 'warning';
            $title = 'Appointment Cancelled';
            $text = 'Your appointment has been successfully cancelled.';
        } else {
            $icon = 'error';
            $title = 'Booking failed.';
            $text = $_SESSION['booking_error_text'] ?? 'Please try again later.';
        }
        ?>
        <script>
            Swal.fire({
                icon: '<?= $icon ?>',
                title: '<?= $title ?>',
                text: '<?= $text ?>'
            });
        </script>
        <?php
        unset($_SESSION['booking_msg']);
        unset($_SESSION['booking_error_text']);
    }
    ?>

    <?php include 'navbar.php' ?>
    <?php include 'appointment_booking_header.php' ?>
    <!-- Pending Appointments -->
    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"></i> Pending Appointments</h5>
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
                            <?php foreach ($pendingAppointments as $appt): ?>
                                <tr>
                                    <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                    <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                    <td><span class="badge bg-warning"><?= ucfirst($appt['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-btn"
                                            data-id="<?= $appt['appointment_id'] ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <form method="POST" action="cancel_appointment.php" class="d-inline">
                                            <input type="hidden" name="appointment_id"
                                                value="<?= $appt['appointment_id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="bi bi-x-circle"></i> Cancel
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
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
                <h5 class="mb-0"></i> Approved Appointments</h5>
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
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($approvedAppointments as $appt):
                                $dt = new DateTime($appt['appointment_date']); // full datetime from appointment_date                              
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($appt['pet_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['clinic_name']); ?></td>
                                    <td><?= htmlspecialchars($appt['service_name']); ?></td>
                                    <td><?= date("M d, Y", strtotime($appt['appointment_date'])) ?></td>
                                    <td><span class="badge bg-primary"><?= ucfirst($appt['status']); ?></span></td>
                                    <td>
                                        <button class="btn btn-sm btn-info view-btn"
                                            data-id="<?= $appt['appointment_id'] ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                        <?php if ($appt['status'] === 'approved'): ?>
                                            <form method="POST" action="cancel_appointments.php" class="d-inline">
                                                <input type="hidden" name="appointment_id"
                                                    value="<?= $appt['appointment_id']; ?>">
                                                <button type="button" class="btn btn-sm btn-danger cancel-approved-btn"
                                                    data-id="<?= $appt['appointment_id']; ?>">
                                                    Cancel
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <!-- Cancelled Appointments -->
    <div class="container my-4">
        <div class="card shadow-sm">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">
                    <i class="bi bi-x-circle"></i> Cancelled Appointments
                </h5>
            </div>

            <div class="card-body">
                <div class="table-responsive">
                    <table id="cancelledTable" class="table table-striped table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pet</th>
                                <th>Clinic</th>
                                <th>Service</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (!empty($cancelledAppointments)): ?>
                                <?php foreach ($cancelledAppointments as $appt): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($appt['pet_name']) ?></td>
                                        <td><?= htmlspecialchars($appt['clinic_name']) ?></td>
                                        <td><?= htmlspecialchars($appt['service_name']) ?></td>
                                        <td><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></td>
                                        <td><?= date('h:i A', strtotime($appt['appointment_start'])) ?> –
                                            <?= date('h:i A', strtotime($appt['appointment_end'])) ?>
                                        </td>
                                        <td><span class="badge bg-danger">Cancelled</span></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info view-btn"
                                                data-id="<?= $appt['appointment_id'] ?>">
                                                View
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

    <!-- View Appointment Modal -->
    <div class="modal fade" id="viewAppointmentModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-eye"></i> View Appointment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" id="view_owner" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="text" id="view_email" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Clinic</label>
                            <input type="text" id="view_clinic" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Service</label>
                            <input type="text" id="view_service" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Pet</label>
                            <input type="text" id="view_pet" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Doctor</label>
                            <input type="text" id="view_doctor" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Appointment Date</label>
                            <input type="text" id="view_date" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Time</label>
                            <input type="text" id="view_time" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Residence</label>
                            <input type="text" id="view_residence" class="form-control" readonly>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" id="view_phone" class="form-control" readonly>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Message</label>
                            <textarea id="view_message" class="form-control" rows="3" readonly></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Status</label>
                            <input type="text" id="view_status" class="form-control fw-bold" readonly>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'booking_modal.php' ?>
    <?php include 'footer.php'; ?>

    <script src="js/doctor_schedule_loader.js"></script>
    <script src="js/profile_toggle.js"></script>
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
    <script src="js/clinic_selector.js"></script>
    <script src="js/validation.js"></script>
    <script src="js/clinic_services.js"></script>
    <script src="js/appointment_refresh.js"></script>
    <script src="js/clinic_schedule.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#cancelledTable').DataTable({
                language: {
                    emptyTable: "No cancelled appointments found"
                },
                pageLength: 5,
                order: [[3, 'desc']]
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#approvedTable').DataTable({
                language: {
                    emptyTable: "No approved appointments found"
                },
                pageLength: 5,
                order: [[3, 'desc']]
            });
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#pendingTable').DataTable({
                language: {
                    emptyTable: "No pending appointments found"
                },
                pageLength: 5,
                order: [[3, 'desc']]
            });
        });
    </script>

    <script>
        document.querySelectorAll('.cancel-approved-btn').forEach(button => {
            button.addEventListener('click', function () {

                const appointmentId = this.dataset.id;

                Swal.fire({
                    title: 'Cancel Appointment?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, cancel it',
                    cancelButtonText: 'No'
                }).then((result) => {
                    if (result.isConfirmed) {

                        // Create form dynamically
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'cancel_appointments.php';

                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'appointment_id';
                        input.value = appointmentId;

                        form.appendChild(input);
                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            });
        });
    </script>

    <script>
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;

                fetch('ajax_view_appointment.php?appointment_id=' + id)
                    .then(res => res.json())
                    .then(data => {

                        document.getElementById('view_owner').value = data.owner_name;
                        document.getElementById('view_email').value = data.email;
                        document.getElementById('view_clinic').value = data.clinic_name;
                        document.getElementById('view_service').value = data.service_name;
                        document.getElementById('view_pet').value = data.pet_name;
                        document.getElementById('view_doctor').value = data.doctor_name ?? 'Any Available Doctor';
                        document.getElementById('view_date').value = data.appointment_date;
                        document.getElementById('view_time').value =
                            data.appointment_start + ' - ' + data.appointment_end;
                        document.getElementById('view_residence').value = data.residence;
                        document.getElementById('view_phone').value = data.phone;
                        document.getElementById('view_message').value = data.message;
                        document.getElementById('view_status').value = data.status.toUpperCase();

                        new bootstrap.Modal(
                            document.getElementById('viewAppointmentModal')
                        ).show();
                    });
            });
        });
    </script>

</body>

</html>