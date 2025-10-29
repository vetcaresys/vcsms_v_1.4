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
    header('Location: ../clinic/staff/login.php');
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

    $mapLink = "https://www.google.com/maps/search/?api=1&query=" . urlencode($clinicAddress);
    $statusText = $status ? "has been <strong>" . htmlspecialchars(ucfirst($status)) . "</strong>" : "details have been updated";

    // PHPMailer setup
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->SMTPAuth = true;
        $mail->Username = "loelynates@gmail.com";   // Gmail
        $mail->Password = "vuhk kttg xcrc hxwt";    // App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('loelynates@gmail.com', 'VetCareSys');
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
if (isset($_GET['update']) && isset($_GET['status'])) {
    $appointment_id = $_GET['update'];
    $new_status = $_GET['status'];

    $stmt = $pdo->prepare("UPDATE appointments SET status = ? WHERE appointment_id = ? AND clinic_id = ?");
    $stmt->execute([$new_status, $appointment_id, $clinic_id]);

    sendAppointmentEmail($pdo, $appointment_id, $clinic_id, $new_status);
}

// --- Handle POST edit from modal ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_start = $_POST['appointment_start'];
    $appointment_end = $_POST['appointment_end'];
    $doctor_id = $_POST['doctor_id'];

    $stmt = $pdo->prepare("UPDATE appointments 
        SET appointment_date = ?, appointment_start = ?, appointment_end = ?, doctor_id = ? 
        WHERE appointment_id = ? AND clinic_id = ?");
    $stmt->execute([$appointment_date, $appointment_start, $appointment_end, $doctor_id, $appointment_id, $clinic_id]);

    sendAppointmentEmail($pdo, $appointment_id, $clinic_id);
}

// --- Fetch all appointments for display ---
$stmt = $pdo->prepare("
    SELECT a.*, p.pet_name, p.owner_id, s.service_name, 
           u.name AS owner_name, u.email AS owner_email, u.contact_number AS owner_contact
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinic_services s ON a.service_id = s.service_id
    JOIN users u ON p.owner_id = u.user_id
    WHERE a.clinic_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$clinic_id]);
$appointments = $stmt->fetchAll();

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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- DataTables CDN -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="includes/css/manage_customer_appointment.css">

</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link text-white">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_petowner.php" class="nav-link text-white">Manage Client</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_pet_details.php" class="nav-link text-white">Pet Details</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_customer_appointment.php" class="nav-link text-white">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_records.php" class="nav-link text-white">Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_inventory.php" class="nav-link text-white">Inventory</a>
                    </li>
                </ul>
                <!-- Profile Dropdown -->
                <div class="dropdown ms-auto">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="../logout.php" class="m-0">
                                <button class="dropdown-item text-danger" type="submit">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

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

                    <!-- View Doctor Visits Button -->
                    <div class="mb-3 text-end">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#doctorVisitsModal">
                            View Doctor Visits
                        </button>
                    </div>
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
                                            data-id="<?= $appt['appointment_id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <a href="#" class="btn btn-sm btn-warning me-1" data-bs-toggle="modal"
                                            data-bs-target="#editAppointmentModal" data-id="<?= $appt['appointment_id'] ?>"
                                            data-date="<?= date('Y-m-d', strtotime($appt['appointment_date'])) ?>"
                                            data-start="<?= date('H:i', strtotime($appt['appointment_start'])) ?>"
                                            data-end="<?= date('H:i', strtotime($appt['appointment_end'])) ?>"
                                            data-doctor="<?= htmlspecialchars($appt['doctor_id'] ?? '') ?>">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>
                                        <a href="?update=<?= $appt['appointment_id']; ?>&status=approved"
                                            class="btn btn-sm btn-success me-1"
                                            onclick="return confirm('Approve this appointment?')">Approve</a>
                                    <?php elseif ($appt['status'] === 'approved'): ?>
                                        <a href="?update=<?= $appt['appointment_id']; ?>&status=completed"
                                            class="btn btn-sm btn-primary me-1"
                                            onclick="return confirm('Mark as completed?')">Complete</a>
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

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- View Profile -->
                <div id="viewProfile">
                    <div class="modal-header">
                        <h5 class="modal-title">My Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3"
                            width="100">
                        <h4><?= $name ?></h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($staff['contact_number']) ?></p>
                        <p><strong>Role:</strong> <?= htmlspecialchars($staff['role']) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
                    </div>
                </div>

                <!-- Edit Profile -->
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
                                    value="<?= htmlspecialchars($staff['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($staff['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= htmlspecialchars($staff['contact_number']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button> -->
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- 🩺 Doctor Visits Modal -->
    <div class="modal fade" id="doctorVisitsModal" tabindex="-1" aria-labelledby="doctorVisitsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="doctorVisitsModalLabel">Doctor Visit Schedules</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php
                    // 🩺 Fetch doctor visit schedules for this clinic
                    $stmt = $pdo->prepare("
                    SELECT dv.*, d.name AS doctor_name
                    FROM doctor_visits dv
                    JOIN staff d ON dv.doctor_id = d.staff_id
                    WHERE dv.clinic_id = ?
                    ORDER BY d.name, FIELD(dv.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), dv.start_time
                ");
                    $stmt->execute([$clinic_id]);
                    $doctorVisits = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    if (count($doctorVisits) > 0):
                        // Group by doctor
                        $grouped = [];
                        foreach ($doctorVisits as $visit) {
                            $grouped[$visit['doctor_name']][] = $visit;
                        }
                        ?>
                        <?php foreach ($grouped as $doctorName => $visits): ?>
                            <div class="card mb-3 border-0 shadow-sm">
                                <div class="card-header bg-light fw-bold">
                                    👨‍⚕️ Dr. <?= htmlspecialchars($doctorName) ?>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-striped mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>Day</th>
                                                <th>Start Time</th>
                                                <th>End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($visits as $v): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($v['day_of_week']) ?></td>
                                                    <td><?= date("h:i A", strtotime($v['start_time'])) ?></td>
                                                    <td><?= date("h:i A", strtotime($v['end_time'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info text-center">No doctor visit schedules found for this clinic.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
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
                                            <option value="">-- Select Doctor --</option>
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
                    $('#edit_appointment_date').val(a.appointment_date);
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
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50, 100],
                "order": [[3, "desc"]], // Sort by appointment date by default
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
</body>

</html>