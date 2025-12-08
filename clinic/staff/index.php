<?php
session_start();
include '../../config.php';

// 🔒 Ensure only staff can access
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
  header('Location: ../clinic/staff/login.php');
  exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

// 🧍 Fetch staff info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// 🐾 Pets Count (Clinic-specific)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT p.pet_id)
    FROM pets p
    JOIN appointments a ON a.pet_id = p.pet_id
    WHERE a.clinic_id = ?
");
$stmt->execute([$clinic_id]);
$pets = $stmt->fetchColumn();


// 👥 Pet Owners (Clinic-specific)
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT u.user_id)
    FROM users u
    JOIN appointments a ON a.owner_id = u.user_id
    WHERE a.clinic_id = ?
");
$stmt->execute([$clinic_id]);
$owners = $stmt->fetchColumn();


// 📅 Appointments (Clinic-specific)
$stmt = $pdo->prepare("
    SELECT 
        COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
        COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
        COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed
    FROM appointments
    WHERE clinic_id = ?
");
$stmt->execute([$clinic_id]);
$appointments = $stmt->fetch(PDO::FETCH_ASSOC);


// 📝 Medical Records (Clinic-specific)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_records WHERE clinic_id = ?");
$stmt->execute([$clinic_id]);
$records = $stmt->fetchColumn();

// 🧺 Total Inventory Items (Clinic-specific)
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inventory 
    WHERE clinic_id = ?
");
$stmt->execute([$clinic_id]);
$totalItems = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Clinic Staff Dashboard - VetCareSys</title>
  <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <!-- Buttons for print/export -->
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="includes/css/index.css">
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

  <?php include 'includes/body/navbar.php' ?>
  <style>
    .stat-card {
      border-radius: 14px;
      transition: 0.2s ease;
    }

    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    }

    .icon-box {
      width: 55px;
      height: 55px;
      font-size: 26px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 12px;
    }
  </style>

  <div class="container py-5">
    <h2 class="mb-4 text-primary"><i class="bi bi-speedometer2"></i> Staff Dashboard</h2>
    <div class="row g-4 dashboard-cards">

      <!-- Pets -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-primary text-white me-3">
              <i class="bi bi-heart-pulse"></i>
            </div>
            <div>
              <h6 class="text-muted">Registered Pets</h6>
              <h3 class="fw-bold"><?= $pets ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Pet Owners -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-success text-white me-3">
              <i class="bi bi-people-fill"></i>
            </div>
            <div>
              <h6 class="text-muted">Pet Owners</h6>
              <h3 class="fw-bold"><?= $owners ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Pending Appointments -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-warning text-white me-3">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <div>
              <h6 class="text-muted">Pending Appointments</h6>
              <h3 class="fw-bold"><?= $appointments['pending'] ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Approved Appointments -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-info text-white me-3">
              <i class="bi bi-calendar-check"></i>
            </div>
            <div>
              <h6 class="text-muted">Approved Appointments</h6>
              <h3 class="fw-bold"><?= $appointments['approved'] ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Completed Appointments -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-success text-white me-3">
              <i class="bi bi-check2-circle"></i>
            </div>
            <div>
              <h6 class="text-muted">Completed Appointments</h6>
              <h3 class="fw-bold"><?= $appointments['completed'] ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Medical Records -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-warning text-white me-3">
              <i class="bi bi-file-medical"></i>
            </div>
            <div>
              <h6 class="text-muted">Medical Records</h6>
              <h3 class="fw-bold"><?= $records ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- Total Inventory Items -->
      <div class="col-md-4">
        <div class="card shadow-sm border-0 stat-card">
          <div class="card-body d-flex align-items-center">
            <div class="icon-box bg-dark text-white me-3">
              <i class="bi bi-box-seam"></i>
            </div>
            <div>
              <h6 class="text-muted">Inventory Items</h6>
              <h3 class="fw-bold"><?= $totalItems ?></h3>
            </div>
          </div>
        </div>
      </div>

      <!-- calendar -->
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

      <!-- inventory log -->
      <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center bg-white border-bottom">
          <h5 class="mb-0 text-primary fw-bold">
            <i class="bi bi-clock-history me-2"></i> Inventory Activity Log
          </h5>
        </div>

        <div class="card-body">
          <div class="table-responsive">
            <table id="inventoryLogTable" class="table table-striped table-bordered align-middle text-center">
              <thead class="table-primary text-uppercase">
                <tr>
                  <th>Date</th>
                  <th>Item</th>
                  <th>Action</th>

                  <!-- Normal Item Fields -->
                  <th>Qty Added</th>
                  <th>Previous Qty</th>
                  <th>New Qty</th>

                  <!-- Consumable Fields -->
                  <th>Volume Used (ml)</th>
                  <th>Previous Volume (ml)</th>
                  <th>New Volume (ml)</th>
                  <th>Total Volume (ml)</th>
                  <th>Volume per Bottle (ml)</th>

                  <th>Performed By</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // ✅ Filter logs to show ONLY the logged-in clinic's inventory
                $stmt = $pdo->prepare("
                        SELECT 
                            l.*, 
                            i.item_name, 
                            i.is_consumable,
                            i.total_volume_ml,
                            i.volume_per_bottle_ml,
                            s.name AS staff_name
                        FROM inventory_activity_log l
                        JOIN inventory i ON l.item_id = i.item_id
                        JOIN staff s ON l.staff_id = s.staff_id
                        WHERE i.clinic_id = ?
                        ORDER BY l.date_action DESC
                    ");
                $stmt->execute([$clinic_id]);
                $logs = $stmt->fetchAll();

                if ($logs):
                  foreach ($logs as $log):

                    $isConsumable = $log['is_consumable'] == 1;

                    // Normal item fields
                    $qtyAdded = $isConsumable ? 0 : $log['quantity_added'];
                    $prevQty = $isConsumable ? 0 : $log['previous_quantity'];
                    $newQty = $isConsumable ? 0 : $log['new_quantity'];

                    // Consumable item fields
                    $usedML = $log['volume_used'] ?? 0;
                    $prevVol = $log['previous_volume_ml'] ?? 0;
                    $newVol = $log['new_remaining_volume'] ?? 0;
                    $totalVol = $log['total_volume_ml'] ?? 0;
                    $volPerBottle = $log['volume_per_bottle_ml'] ?? 0;
                    ?>
                    <tr>
                      <td><?= date('M d, Y h:i A', strtotime($log['date_action'])) ?></td>
                      <td class="fw-semibold"><?= htmlspecialchars($log['item_name']) ?></td>
                      <td>
                        <span
                          class="badge 
                                    <?= $log['action_type'] == 'add' ? 'bg-success' : ($log['action_type'] == 'use' ? 'bg-info' : 'bg-danger') ?>">
                          <?= ucfirst($log['action_type']) ?>
                        </span>
                      </td>

                      <!-- Normal Item Values -->
                      <td><?= $qtyAdded ?></td>
                      <td><?= $prevQty ?></td>
                      <td><?= $newQty ?></td>

                      <!-- Consumable Item Values -->
                      <td><?= $usedML ?></td>
                      <td><?= $prevVol ?></td>
                      <td><?= $newVol ?></td>
                      <td><?= $totalVol ?></td>
                      <td><?= $volPerBottle ?></td>

                      <td class="fw-semibold"><?= htmlspecialchars($log['staff_name']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="13" class="text-center text-muted">No activity logs found</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <style>
        /* Compact Clean Table Style */
        #inventoryLogTable {
          font-size: 0.85rem;
        }

        #inventoryLogTable thead th {
          padding: 8px 6px !important;
          font-size: 0.78rem;
          letter-spacing: 0.5px;
        }

        #inventoryLogTable tbody td {
          padding: 6px 6px !important;
        }

        #inventoryLogTable.table-bordered> :not(caption)>*>* {
          border-width: 1px;
        }

        #inventoryLogTable tbody tr {
          height: 38px;
        }

        #inventoryLogTable td,
        #inventoryLogTable th {
          vertical-align: middle !important;
        }

        /* badge smaller */
        #inventoryLogTable .badge {
          font-size: 0.70rem;
          padding: 4px 6px;
        }
      </style>

    </div> <!-- END OF MAIN PAGE CONTENT -->

    <footer class="footer-light">
      <div class="container text-center small">
        All Rights Reserved. &copy; 2025 VetCareSys
      </div>
    </footer>

    <style>
      .footer-light {
        background: #f8f9fa;
        color: #333;
        padding: 12px 0;
        border-top: 1px solid #ddd;
      }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

    <script>
      function toggleEdit(isEditing) {
        const viewProfile = document.getElementById("viewProfile");
        const editProfile = document.getElementById("editProfile");

        if (isEditing) {
          viewProfile.style.display = "none";
          editProfile.style.display = "block";
        } else {
          editProfile.style.display = "none";
          viewProfile.style.display = "block";
        }
      }
    </script>

    <script>
      const urlParams = new URLSearchParams(window.location.search);
      if (urlParams.has('profile_updated')) {
        Swal.fire({
          icon: 'success',
          title: 'Profile Updated!',
          text: 'Your profile information has been successfully saved.',
          confirmButtonColor: '#0d6efd',
          timer: 2000,
          showConfirmButton: false
        });

        // Clean URL (remove ?profile_updated=1 after showing alert)
        window.history.replaceState({}, document.title, window.location.pathname);
      }
    </script>

    <style>
      /* 🫀 Small pulse effect when data updates */
      @keyframes pulse {
        0% {
          transform: scale(1);
          box-shadow: 0 0 0 rgba(13, 110, 253, 0.4);
        }

        50% {
          transform: scale(1.03);
          box-shadow: 0 0 15px rgba(13, 110, 253, 0.3);
        }

        100% {
          transform: scale(1);
          box-shadow: 0 0 0 rgba(13, 110, 253, 0);
        }
      }

      .pulse {
        animation: pulse 0.6s ease;
      }
    </style>

    <script>
      $(document).ready(function () {
        $('#inventoryLogTable').DataTable({
          dom: 'frtip', // simpler if you don’t use Buttons extension
          pageLength: 5,
          lengthMenu: [5, 10, 25, 50],
          order: [[0, 'asc']],
          language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ entries"
          }
        });
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