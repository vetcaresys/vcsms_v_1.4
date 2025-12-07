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

// 🐾 Pets Count (system-wide)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pets");
$stmt->execute();
$pets = $stmt->fetchColumn();

// 👥 Pet Owners
$stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'pet_owner'");
$stmt->execute();
$owners = $stmt->fetchColumn();

// 📅 Appointments
$stmt = $pdo->prepare("
  SELECT 
    COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending,
    COALESCE(SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END), 0) AS approved,
    COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed
  FROM appointments
");
$stmt->execute();
$appointments = $stmt->fetch(PDO::FETCH_ASSOC);

//  Medical Records
$stmt = $pdo->prepare("SELECT COUNT(*) FROM pet_records");
$stmt->execute();
$records = $stmt->fetchColumn();

// Inventory (Low stock)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inventory WHERE quantity < 5");
$stmt->execute();
$lowStock = $stmt->fetchColumn();

// Unread Inquiries
$stmt = $pdo->prepare("SELECT COUNT(*) FROM inquiries WHERE status = 'unread'");
$stmt->execute();
$inquiries = $stmt->fetchColumn();
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

  <!-- Google Fonts -->
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
    .dash-card {
      display: flex;
      align-items: center;
      gap: 15px;
      padding: 20px;
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
      transition: transform 0.2s ease;
    }

    .dash-card:hover {
      transform: translateY(-3px);
    }

    .dash-icon {
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #fff;
      font-size: 1.5rem;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .label {
      margin: 0;
      font-size: 0.9rem;
      color: #6c757d;
    }

    .value {
      margin: 0;
      font-size: 1.8rem;
      font-weight: bold;
      color: #212529;
    }
  </style>

  <div class="container py-5">
    <h2 class="mb-4 text-primary"><i class="bi bi-speedometer2"></i> Staff Dashboard</h2>

    <div class="row g-4 dashboard-cards">
      <!-- 🐾 Registered Pets -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-primary">
            <i class="bi bi-heart-pulse"></i>
          </div>
          <div>
            <p class="label">Registered Pets</p>
            <h3 class="value" id="petsCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 👥 Pet Owners -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-success">
            <i class="bi bi-person-badge"></i>
          </div>
          <div>
            <p class="label">Pet Owners</p>
            <h3 class="value" id="ownersCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ⏳ Pending Appointments -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-warning text-dark">
            <i class="bi bi-clock-history"></i>
          </div>
          <div>
            <p class="label">Pending Appointments</p>
            <h3 class="value" id="pendingCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ✅ Approved Appointments -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-info">
            <i class="bi bi-check2-square"></i>
          </div>
          <div>
            <p class="label">Approved Appointments</p>
            <h3 class="value" id="approvedCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 🩺 Completed Appointments -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-success">
            <i class="bi bi-check-circle"></i>
          </div>
          <div>
            <p class="label">Completed Appointments</p>
            <h3 class="value" id="completedCount">0</h3>
          </div>
        </div>
      </div>

      <!-- 📄 Medical Records -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-danger">
            <i class="bi bi-file-earmark-medical"></i>
          </div>
          <div>
            <p class="label">Medical Records</p>
            <h3 class="value" id="recordsCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ⚠️ Low Stock -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-danger">
            <i class="bi bi-exclamation-triangle"></i>
          </div>
          <div>
            <p class="label">Low Stock Items</p>
            <h3 class="value" id="lowStockCount">0</h3>
          </div>
        </div>
      </div>

      <!-- ✉️ Unread Inquiries -->
      <div class="col-md-3">
        <div class="dash-card">
          <div class="dash-icon bg-secondary">
            <i class="bi bi-envelope"></i>
          </div>
          <div>
            <p class="label">Unread Inquiries</p>
            <h3 class="value" id="inquiriesCount">0</h3>
          </div>
        </div>
      </div>
    </div>
  </div>


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

  <div class="card shadow-sm mt-4">
    <div class="card-header d-flex justify-content-between align-items-center bg-light border-bottom">
      <h5 class="mb-0 text-primary fw-bold">
        <i class="bi bi-clock-history me-2"></i> Inventory Activity Log
      </h5>
    </div>

    <div class="card-body">
      <div class="table-responsive">
        <table id="inventoryLogTable" class="table table-striped table-bordered align-middle text-center">
          <thead class="table-dark text-uppercase">
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
            $logs = $pdo->query("
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
            ORDER BY l.date_action DESC
        ")->fetchAll();

            if ($logs):
              foreach ($logs as $log):

                $isConsumable = $log['is_consumable'] == 1;

                // NORMAL ITEMS (0 if consumable)
                $qtyAdded = $isConsumable ? 0 : $log['quantity_added'];
                $prevQty = $isConsumable ? 0 : $log['previous_quantity'];
                $newQty = $isConsumable ? 0 : $log['new_quantity'];

                // CONSUMABLE ITEMS (0 if normal)
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

                  <!-- NORMAL ITEM VALUES -->
                  <td><?= $qtyAdded ?></td>
                  <td><?= $prevQty ?></td>
                  <td><?= $newQty ?></td>

                  <!-- CONSUMABLE ITEM VALUES -->
                  <td><?= $usedML ?></td>
                  <td><?= $prevVol ?></td>
                  <td><?= $newVol ?></td>
                  <td><?= $totalVol ?></td>
                  <td><?= $volPerBottle ?></td>

                  <td class="text-muted"><?= htmlspecialchars($log['staff_name']) ?></td>
                </tr>

                <?php
              endforeach;
            else:
              ?>
              <tr>
                <td colspan="12" class="text-center text-muted py-3">
                  No activity logs available.
                </td>
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

  <script>
    async function loadDashboard() {
      try {
        const response = await fetch('dashboard.php'); // ✅ your only endpoint
        const data = await response.json();

        // Update counters
        document.getElementById('petsCount').textContent = data.pets || 0;
        document.getElementById('ownersCount').textContent = data.owners || 0;
        document.getElementById('pendingCount').textContent = data.appointments.pending || 0;
        document.getElementById('approvedCount').textContent = data.appointments.approved || 0;
        document.getElementById('completedCount').textContent = data.appointments.completed || 0;
        document.getElementById('recordsCount').textContent = data.records || 0;
        document.getElementById('lowStockCount').textContent = data.lowStock || 0;
        document.getElementById('inquiriesCount').textContent = data.inquiries || 0;

        // Pulse animation when data updates
        document.querySelectorAll('.summary-card').forEach(card => {
          card.classList.add('pulse');
          setTimeout(() => card.classList.remove('pulse'), 600);
        });

      } catch (error) {
        console.error("Dashboard load error:", error);
      }
    }

    // 🔁 Auto-refresh every 10 seconds
    loadDashboard();
    setInterval(loadDashboard, 10000);
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