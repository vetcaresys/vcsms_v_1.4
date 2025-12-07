<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

$clinicStmt = $pdo->prepare("SELECT clinic_name FROM clinics WHERE clinic_id = ?");
$clinicStmt->execute([$clinic_id]);
$clinic = $clinicStmt->fetch(PDO::FETCH_ASSOC);

// Store in session so pwede gamiton anywhere
$_SESSION['clinic_name'] = $clinic['clinic_name'] ?? 'N/A';

// Get doctor info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch(PDO::FETCH_ASSOC);

$docInfoStmt = $pdo->prepare("SELECT * FROM doctors WHERE staff_id = ?");
$docInfoStmt->execute([$doctor_id]);
$doctorInfo = $docInfoStmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($doctor['name']);
$profilePic = !empty($doctor['profile_picture']) ? $doctor['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Doctor Dashboard - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }

        .fc-daygrid-event {
            font-size: 0.9rem;
            font-weight: 500;
        }
    </style>
    <link rel="stylesheet" href="css/index.css">
</head>

<body class="bg-light">

    <?php if (isset($_SESSION['success'])): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Login Successful',
                text: 'Welcome back!',
                timer: 1500,
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['success']); endif; ?>

    <?php include 'includes/navbar.php' ?>

    <?php include 'includes/dashboard.php' ?>

    <?php include 'includes/add_edit_profile_modal.php' ?>

    <?php include 'includes/footer.php' ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

            // 💡 NEW: Listener for Mark All button
            document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
        });

        function loadAdminNotifications() {
            fetch("../../doc_fetch_notifications.php")
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
                    fetch("../../doc_mark_all_as_read.php", { method: 'POST' })
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
                    fetch(`../../mark_as_read.php?id=${id}`);
                    // Simple visual update after click
                    notifItem.classList.remove('bg-light');
                    notifItem.querySelector('.badge')?.remove();
                    loadAdminNotifications(); // Reload count
                }
            }
        });
    </script>

    <!-- for the calendar -->
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
            const url = '../fetch_all_appointments.php';

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

                    console.log("here" + appt.extendedProps.time);

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

</body>

</html>