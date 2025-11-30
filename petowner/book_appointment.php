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

    <?php include 'booking_alert.php' ?>
    <?php include 'navbar.php' ?>
    <?php include 'user_profile_modal.php' ?>
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
        // for the get_doctors.php
        document.getElementById('clinicSelect').addEventListener('change', function () {
            const clinicId = this.value;
            const doctorSelect = document.getElementById('doctorSelect');

            if (!clinicId) {
                doctorSelect.innerHTML = '<option value="">-- Optional: Select Doctor --</option>';
                doctorSelect.disabled = true;
                return;
            }

            fetch('get_doctors.php?clinic_id=' + clinicId)
                .then(res => res.json())
                .then(data => {
                    const doctorSelect = document.getElementById('doctorSelect');
                    doctorSelect.innerHTML = '<option value="">Select Doctor</option>';

                    if (data.length > 0) {
                        doctorSelect.disabled = false;
                        data.forEach(doctor => {
                            const opt = document.createElement('option');
                            opt.value = doctor.doctor_id; // make sure this matches your PHP output
                            opt.textContent = doctor.name + " (" + doctor.specialization + ")";
                            doctorSelect.appendChild(opt);
                        });
                    } else {
                        doctorSelect.disabled = true;
                        doctorSelect.innerHTML = '<option value="">No doctors available</option>';
                    }
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
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

            // 💡 NEW: Listener for Mark All button
            document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
        });

        function loadAdminNotifications() {
            fetch(`../petowner_fetch_notifications.php?user_id=` + `<?= ($user_id) ?>`)
                .then(res => res.json())
                .then(data => {
                    const list = document.getElementById("notif_list");
                    const count = document.getElementById("notif_count");
                    const markAllBtn = document.getElementById("mark_all_btn"); // Get the button

                    list.innerHTML = "";
                    let unreadCount = 0;

                    if (!data || data.length === 0) {
                        list.innerHTML = `<li class="text-center text-muted py-3">No notifications</li>`;
                        count.textContent = "";
                        markAllBtn.disabled = true; // Disable button if no notifs
                        return;
                    }

                    // Locate the loadAdminNotifications function and replace the following loop:
                    data.forEach(n => {
                        if (n.status === "unread") unreadCount++;

                        list.innerHTML += `
                        <li>
                            <a href="${n.link ?? '#'}" class="dropdown-item d-flex justify-content-between align-items-start notif-item ${n.status === "unread" ? 'bg-light' : ''}"
                            data-id="${n.notif_id}">
                                <div class="w-100"> 
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <small class="text-secondary fw-semibold" style="font-size: 0.75rem;">
                                            <i class="bi bi-calendar"></i> ${n.display_date}
                                        </small>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            <i class="bi bi-clock"></i> ${n.display_time}
                                        </small>
                                    </div>

                                    <span style="
                                        font-size: 0.85rem; 
                                        max-width: 100%; 
                                        display: block; 
                                        overflow: hidden; 
                                        text-overflow: ellipsis; 
                                        white-space: nowrap;
                                        /* Conditional Style: Use font-weight: bold (700) if unread, normal (400) if read */
                                        font-weight: ${n.status === "unread" ? '700' : '400'};
                                    ">
                                    ${n.status === "unread" ? `<span class="badge bg-danger ms-2">New</span>` : ""}

                                        ${n.subject}

                                    </span>
                                    
                                    <small class="text-muted" style="font-size: 0.78rem;">${n.message}</small>
                                </div>
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-0"></li>
                    `;
                    });

                    count.textContent = unreadCount > 0 ? unreadCount : "";
                    markAllBtn.disabled = (unreadCount === 0); // Enable button only if there are unread notifications
                });
        }

        // 💡 NEW: Function to mark all notifications as read
        function markAllAsRead() {
            Swal.fire({
                title: 'Mark all as read?',
                text: "All current unread notifications will be marked as read.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Mark All'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Fetch the new PHP endpoint to update the database
                    fetch(`../petowner_mark_all_as_read.php?user_id=` + `<?= ($user_id) ?>`, { method: 'POST' })
                        .then(response => {
                            if (response.ok) {
                                Swal.fire('Success!', 'All notifications marked as read.', 'success');
                                // Reload the notifications immediately after success
                                loadAdminNotifications();
                            } else {
                                Swal.fire('Error!', 'Could not mark all as read.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Fetch error:', error);
                            Swal.fire('Error!', 'Network or server issue.', 'error');
                        });
                }
            });
        }

        // Mark as read when opening a notification (Your original function, updated for clarity)
        document.addEventListener("click", function (e) {
            if (e.target.closest(".notif-item")) {
                const notifItem = e.target.closest(".notif-item");
                const id = notifItem.dataset.id;

                // Only send the request if it's currently marked as unread
                if (notifItem.classList.contains('bg-light')) {
                    fetch(`../mark_as_read.php?id=${id}`);
                    // Simple visual update after click
                    notifItem.classList.remove('bg-light');
                    notifItem.querySelector('.badge')?.remove();
                    loadAdminNotifications(); // Reload count
                }
            }
        });
    </script>

    <!-- for the show password -->
    <script>
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
    </script>
</body>

</html>