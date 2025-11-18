<?php
session_start();
require '../config.php';

// Redirect if not logged in or not a pet owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header('Location: ../login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture path
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'profile_default.jpg';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();
$name = htmlspecialchars($user['name']);

// Helper: Clinic logo
function getLogoPath($logo)
{
    return !empty($logo) ? "../uploads/logos/" . basename($logo) : "assets/default-clinic.jpg";
}

$contact = $user['contact_number'] ?? '';
if (!empty($contact)) {
    $contact = preg_replace('/\s+/', '', $contact); // remove spaces
    if (preg_match('/^09\d{9}$/', $contact)) {
        $contact = '+63' . substr($contact, 1);
    } elseif (preg_match('/^639\d{9}$/', $contact)) {
        $contact = '+' . $contact;
    }
} else {
    $contact = 'N/A';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    $errors = [];

    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (!empty($contact) && !preg_match('/^09\d{9}$/', $contact)) {
        $errors[] = "Contact number must be 11 digits starting with 09.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    // File validation (if uploaded)
    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = "Profile picture must be JPG or PNG.";
        }
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Profile picture must not exceed 2MB.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: pet_owner_dashboard.php"); // adjust filename
        exit;
    }

    // ✅ proceed with DB update if no errors

    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Pet Owner Dashboard - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="css/navbar.css">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>

    <link rel="stylesheet" href="css/index.css">

    <style>
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }

        .fc-daygrid-event {
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>

    <style>

    </style>
</head>

<body>

    <?php if (isset($_SESSION['success'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                title: 'Success!',
                text: '<?= addslashes($_SESSION['success']) ?>',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']);
    endif; ?>

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
                    <li class="nav-item"><a href="book_appointment.php" class="nav-link">Book Appointment</a></li>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                            <span id="notif_count"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.65rem; padding: 3px 6px;">
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-2" style="width: 320px; max-height: 400px;"
                            id="notif_list_container">

                            <li class="d-flex justify-content-between align-items-center mb-2 px-2">
                                <h6 class="mb-0">Notifications</h6>
                                <button id="mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none"
                                    style="font-size: 0.8rem;" disabled>
                                    Mark all as read
                                </button>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                                <li class="text-center text-muted">Loading...</li>
                            </div>
                        </ul>
                    </li>
                </ul>
                <div class="dropdown">
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

    <!-- Main content -->
    <div class="container mt-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-primary">Welcome, <?= $name ?>!</h2>
                <p class="card-text">Manage your pets, view records, and book appointments.</p>
            </div>
        </div>
    </div>

    <main class="container mt-4">
        <div class="row g-4" id="dashboardStats">
            <!-- Stats will be loaded here via AJAX -->
        </div>
    </main>

    <div class="containers mt-4">
    <div class="calendar">
        <header>
            <button id="prev">&#8592;</button>
            <h2 id="monthYear"></h2>
            <button id="next">&#8594;</button>
        </header>
        <div class="days" id="calendarDays"></div>
    </div>

    <div class="appointments">
        <h3 id="selectedDate">Select a date</h3>
        <div id="appointmentList"></div>
    </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">

                <!-- View Profile Section -->
                <div id="viewProfile">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">My Profile</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-center">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile Picture"
                            class="rounded-circle shadow-sm mb-3" width="110" height="110"
                            style="object-fit: cover; border: 3px solid #0d6efd;">
                        <h4 class="fw-bold mb-3"><?= htmlspecialchars($user['name']) ?></h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;">Email</th>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact</th>
                                        <td><?= htmlspecialchars($contact) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address</th>
                                        <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="toggleEdit(true)">
                            <i class="bi bi-pencil-square"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Edit Profile Section -->
                <div id="editProfile" style="display:none;">
                    <form id="editProfileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Edit Profile</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                                    maxlength="11" pattern="^09\d{9}$" placeholder="e.g., 09123456789"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
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

                            <!-- 🔵 CHANGE PASSWORD SECTION -->
                            <hr>
                            <h6 class="text-primary">Change Password (optional)</h6>

                            <!-- Current Password -->
                            <div class="mb-3 position-relative">
                                <label class="form-label">Current Password</label>
                                <div class="input-group">
                                    <input type="password" name="current_password" id="currentPassword"
                                        class="form-control" placeholder="Enter current password">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button"
                                        data-target="currentPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- New Password -->
                            <div class="mb-3 position-relative">
                                <label class="form-label">New Password</label>
                                <div class="input-group">
                                    <input type="password" name="new_password" id="newPassword" class="form-control"
                                        minlength="6" placeholder="Enter new password">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button"
                                        data-target="newPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Confirm Password -->
                            <div class="mb-3 position-relative">
                                <label class="form-label">Confirm New Password</label>
                                <div class="input-group">
                                    <input type="password" name="confirm_password" id="confirmPassword"
                                        class="form-control" minlength="6" placeholder="Re-enter new password">
                                    <button class="btn btn-outline-secondary toggle-pass" type="button"
                                        data-target="confirmPassword">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
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

    <div class="container mt-4">
        <div class="card-body">
            <?php include 'browse_clinics_section.php'; ?>
        </div>
    </div>


    <!-- Footer -->
    <footer class="mt-auto bg-dark text-white py-3">
        <div class="container text-center small">
            All Rights Reserved. &copy; 2025 VetCareSys
        </div>
    </footer>

    <script>
        document.querySelector('input[name="contact_number"]').addEventListener('input', function (e) {
            // Remove any non-numeric characters
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    </script>

    <script>
        function toggleEdit(showEdit) {
            if (showEdit) {
                document.getElementById('viewProfile').style.display = 'none';
                document.getElementById('editProfile').style.display = 'block';
            } else {
                document.getElementById('viewProfile').style.display = 'block';
                document.getElementById('editProfile').style.display = 'none';
            }
        }
    </script>

    <script>
        (() => {
            'use strict';
            const form = document.getElementById('editProfileForm');
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        })();
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function loadDashboardStats() {
            fetch("fetch_dashboard_stats.php")
                .then(res => res.json())
                .then(data => {
                    document.getElementById("dashboardStats").innerHTML = `
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">My Pets</h5>
                            <p class="display-6">${data.pets}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Upcoming</h5>
                            <p class="display-6 text-primary">${data.upcoming}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Completed</h5>
                            <p class="display-6 text-success">${data.completed}</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">Records</h5>
                            <p class="display-6 text-secondary">${data.records}</p>
                        </div>
                    </div>
                </div>
            `;
                });
        }

        // Load once
        loadDashboardStats();
        // Refresh every 10s
        setInterval(loadDashboardStats, 10000);
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
    const monthYear = document.getElementById('monthYear');
    const calendarDays = document.getElementById('calendarDays');
    const selectedDateDisplay = document.getElementById('selectedDate');
    const appointmentList = document.getElementById('appointmentList');

    let date = new Date();
    let selectedDate = null;
    let appointments = {}; // Stores appointments grouped by date (YYYY-MM-DD)
    let rawAppointments = []; // Stores the raw list of appointments from the PHP script

    // Function to fetch and process data from PHP
    async function fetchAppointments() {
        // IMPORTANT: Replace 'get_appointments.php' with the correct path to your PHP file.
        const url = 'fetch_appointments.php'; 
        
        try {
            const response = await fetch(url);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const fetchedData = await response.json();

            if (fetchedData.error) {
                console.error("Server Error:", fetchedData.error);
                return;
            }

            // Store the raw list for easy lookup in displayAppointments
            rawAppointments = fetchedData; 
            
            // Group appointments by date
            const newAppointments = {};
            rawAppointments.forEach(appt => {
                const dateKey = appt.dateKey; // YYYY-MM-DD from PHP
                
                if (!newAppointments[dateKey]) {
                    newAppointments[dateKey] = [];
                }
                newAppointments[dateKey].push(appt);
            });

            appointments = newAppointments;
            
            console.log("Appointments loaded from DB:", appointments);
            renderCalendar(); // Re-render to display the fetched appointments

        } catch (error) {
            console.error("Error fetching appointments:", error);
        }
    }

    function renderCalendar() {
        const year = date.getFullYear();
        const month = date.getMonth();
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const today = new Date();

        monthYear.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });
        calendarDays.innerHTML = '';

        // Day Names Row
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(name => {
            const dayNameDiv = document.createElement('div');
            dayNameDiv.textContent = name;
            dayNameDiv.style.fontWeight = 'bold';
            dayNameDiv.style.padding = '5px 15px';
            dayNameDiv.style.textAlign = 'center';
            calendarDays.appendChild(dayNameDiv);
        });
        
        // Empty cells for alignment
        for (let i = 0; i < firstDay.getDay(); i++) {
            const empty = document.createElement('div');
            empty.classList.add('day'); // To match height/style
            empty.style.minHeight = '100px'; 
            empty.style.background = '#f2f4f8'; 
            empty.style.cursor = 'default';
            empty.style.boxShadow = 'none';
            calendarDays.appendChild(empty);
        }
        
        // Day Cells
        for (let day = 1; day <= lastDay.getDate(); day++) {
            const dayDiv = document.createElement('div');
            dayDiv.classList.add('day');

            // Format date key to match PHP output: YYYY-MM-DD
            const monthString = (month + 1).toString().padStart(2, '0');
            const dayString = day.toString().padStart(2, '0');
            const fullDate = `${year}-${monthString}-${dayString}`; 

            // Highlight today
            if (
                day === today.getDate() &&
                month === today.getMonth() &&
                year === today.getFullYear()
            ) {
                dayDiv.classList.add('today');
            }

            // Highlight selected date
            if (fullDate === selectedDate) {
                dayDiv.classList.add('selected');
            }

            const number = document.createElement('div');
            number.classList.add('day-number');
            number.textContent = day;
            dayDiv.appendChild(number);

            // Mini appointment previews using fetched data
            const dayAppointments = appointments[fullDate] || [];
            dayAppointments.slice(0, 3).forEach(appt => {
                const mini = document.createElement('span');
                mini.classList.add('mini-appt');
                // Set the color based on the appointment status color from PHP
                mini.style.color = appt.color; 
                mini.textContent = "• " + appt.title;
                dayDiv.appendChild(mini);
            });

            if (dayAppointments.length > 3) {
                const more = document.createElement('span');
                more.classList.add('mini-appt');
                more.textContent = `+${dayAppointments.length - 3} more`;
                dayDiv.appendChild(more);
            }

            dayDiv.addEventListener('click', () => selectDate(fullDate));
            calendarDays.appendChild(dayDiv);
        }
        // Initial display for the current date if selectedDate is null
        if (!selectedDate) {
            selectDate(`${today.getFullYear()}-${(today.getMonth() + 1).toString().padStart(2, '0')}-${today.getDate().toString().padStart(2, '0')}`);
        }
    }

    function selectDate(dateKey) {
        // Clear previous selection highlight
        document.querySelectorAll('.day.selected').forEach(el => el.classList.remove('selected'));
        selectedDate = dateKey;
        
        // Find and highlight the new selected day in the current view
        const [_, month, day] = dateKey.split('-');
        const currentViewMonth = date.getMonth() + 1;
        if (parseInt(month) === currentViewMonth) {
            // Find the dayDiv that contains the day number
            const dayDivs = calendarDays.querySelectorAll('.day');
            for (const div of dayDivs) {
                const dayNumberEl = div.querySelector('.day-number');
                if (dayNumberEl && parseInt(dayNumberEl.textContent) === parseInt(day)) {
                    div.classList.add('selected');
                    break;
                }
            }
        }
        
        displayAppointments();
    }

    function displayAppointments() {
        const displayDate = new Date(selectedDate);
        selectedDateDisplay.textContent = `Appointments on ${displayDate.toDateString()}`;
        const dayAppointments = appointments[selectedDate] || [];
        appointmentList.innerHTML = '';

        if (dayAppointments.length === 0) {
            appointmentList.innerHTML = '<p class="no-appointments">No appointments yet.</p>';
        } else {
            dayAppointments.forEach(appt => {
                const div = document.createElement('div');
                div.className = 'appointment-card';
                
                // Set border color based on status from PHP
                div.style.borderLeftColor = appt.color;

                // Card Header (Pet Name - Service)
                const title = document.createElement('h4');
                title.textContent = appt.title;
                div.appendChild(title);

                // Time and Status
                const timeStatus = document.createElement('div');
                timeStatus.className = 'time-status';
                
                const timeSpan = document.createElement('span');
                timeSpan.textContent = appt.extendedProps.time;
                timeStatus.appendChild(timeSpan);
                
                const statusSpan = document.createElement('span');
                statusSpan.className = 'status-badge';
                statusSpan.textContent = appt.extendedProps.status;
                statusSpan.style.backgroundColor = appt.color; // Use the same color for the badge
                timeStatus.appendChild(statusSpan);

                div.appendChild(timeStatus);

                // Details
                const clinic = document.createElement('p');
                clinic.innerHTML = `<strong>Clinic:</strong> ${appt.extendedProps.clinic}`;
                div.appendChild(clinic);

                const doctor = document.createElement('p');
                doctor.innerHTML = `<strong>Doctor:</strong> ${appt.extendedProps.doctor} (${appt.extendedProps.specialization})`;
                div.appendChild(doctor);

                appointmentList.appendChild(div);
            });
        }
    }

    document.getElementById('prev').onclick = () => {
        date.setMonth(date.getMonth() - 1);
        // When changing months, keep the selected date if it exists in the new month
        // Otherwise, default the selection to the 1st of the month.
        const newDay = Math.min(parseInt(selectedDate.split('-')[2]), new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate());
        const monthString = (date.getMonth() + 1).toString().padStart(2, '0');
        const dayString = newDay.toString().padStart(2, '0');
        selectedDate = `${date.getFullYear()}-${monthString}-${dayString}`;

        renderCalendar();
        displayAppointments();
    };

    document.getElementById('next').onclick = () => {
        date.setMonth(date.getMonth() + 1);
        // When changing months, keep the selected date if it exists in the new month
        const newDay = Math.min(parseInt(selectedDate.split('-')[2]), new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate());
        const monthString = (date.getMonth() + 1).toString().padStart(2, '0');
        const dayString = newDay.toString().padStart(2, '0');
        selectedDate = `${date.getFullYear()}-${monthString}-${dayString}`;

        renderCalendar();
        displayAppointments();
    };

    // Start the process: fetch data, then render the calendar.
    fetchAppointments(); 
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