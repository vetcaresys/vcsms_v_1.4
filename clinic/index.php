<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

$name = htmlspecialchars($_SESSION['name']);


// get clinic information
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

$clinic_id = $clinic['clinic_id'] ?? null;
$_SESSION['clinic_id'] = $clinic_id;

// dashboard stats
$totalAppointments = 0;
$activeStaff = 0;
$servicesOffered = 0;
$totalClients = 0;

if ($clinic_id) {

    // Staff Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM staff WHERE clinic_id = ?");
    $stmt->execute([$clinic_id]);
    $activeStaff = $stmt->fetchColumn();

    // Appointment Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE clinic_id = ?");
    $stmt->execute([$clinic_id]);
    $totalAppointments = $stmt->fetchColumn();

    // Services Offered Count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM clinic_services WHERE clinic_id = ?");
    $stmt->execute([$clinic_id]);
    $servicesOffered = $stmt->fetchColumn();

    // Unique Clients
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT p.owner_id)
        FROM appointments a
        INNER JOIN pets p ON a.pet_id = p.pet_id
        INNER JOIN users u ON p.owner_id = u.user_id
        WHERE a.clinic_id = ? AND u.role = 'pet_owner'
    ");
    $stmt->execute([$clinic_id]);
    $totalClients = $stmt->fetchColumn();
}


// income computation section

$dailyIncome = 0;
$weeklyIncome = 0;
$monthlyIncome = 0;

if ($clinic_id) {

    // DAILY INCOME
    $stmt = $pdo->prepare("
    SELECT 
        SUM(
            CASE 
                WHEN i.is_consumable = 1 THEN 
                    (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                ELSE 
                    i.selling_price * r.quantity_used
            END
        ) AS income
    FROM record_inventory_usage r
    JOIN inventory i ON i.item_id = r.item_id
    JOIN pet_records pr ON pr.record_id = r.record_id
    JOIN staff s ON s.staff_id = pr.staff_id
    WHERE s.clinic_id = ?
    AND DATE(r.date_used) = CURDATE()
");

    $stmt->execute([$clinic_id]);
    $dailyIncome = $stmt->fetchColumn() ?? 0;


    // WEEKLY INCOME
    $stmt = $pdo->prepare("
    SELECT 
        SUM(
            CASE 
                WHEN i.is_consumable = 1 THEN 
                    (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                ELSE 
                    i.selling_price * r.quantity_used
            END
        ) AS income
    FROM record_inventory_usage r
    JOIN inventory i ON i.item_id = r.item_id
    JOIN pet_records pr ON pr.record_id = r.record_id
    JOIN staff s ON s.staff_id = pr.staff_id
    WHERE s.clinic_id = ?
    AND r.date_used >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");

    $stmt->execute([$clinic_id]);
    $weeklyIncome = $stmt->fetchColumn() ?? 0;


    // MONTHLY INCOME
    $stmt = $pdo->prepare("
    SELECT 
        SUM(
            CASE 
                WHEN i.is_consumable = 1 THEN 
                    (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                ELSE 
                    i.selling_price * r.quantity_used
            END
        ) AS income
    FROM record_inventory_usage r
    JOIN inventory i ON i.item_id = r.item_id
    JOIN pet_records pr ON pr.record_id = r.record_id
    JOIN staff s ON s.staff_id = pr.staff_id
    WHERE s.clinic_id = ?
    AND DATE_FORMAT(r.date_used, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')
");

    $stmt->execute([$clinic_id]);
    $monthlyIncome = $stmt->fetchColumn() ?? 0;

    // YEARLY INCOME
    $stmt = $pdo->prepare("
SELECT 
    SUM(
        CASE 
            WHEN i.is_consumable = 1 THEN 
                (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
            ELSE 
                i.selling_price * r.quantity_used
        END
    ) AS income
FROM record_inventory_usage r
JOIN inventory i ON i.item_id = r.item_id
JOIN pet_records pr ON pr.record_id = r.record_id
JOIN staff s ON s.staff_id = pr.staff_id
WHERE s.clinic_id = ?
AND YEAR(r.date_used) = YEAR(CURDATE())
");

    $stmt->execute([$clinic_id]);
    $yearlyIncome = $stmt->fetchColumn() ?? 0;
}

// Default date range (last 30 days)
$start_default = date('Y-m-d', strtotime('-29 days'));
$end_default = date('Y-m-d');
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Owner Dashboard - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/footer.css">

    <!-- Chart.js (only once) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

</head>

<body>
    <?php if (isset($_GET['sent']) && $_GET['sent'] == 1): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Inquiry Sent!',
                text: 'Thank you for contacting us.',
            }).then(() => {
                document.getElementById('inquiry-section')
                    ?.scrollIntoView({ behavior: 'smooth' });
            });
        </script>
    <?php endif; ?>

    <?php include 'assets/body/navbar.php' ?>
    <!-- Main Content -->
    <div class="container py-5">
        <h2 class="mb-4 text-primary"><i class="bi bi-speedometer2"></i> Dashboard</h2>

        <div class="row g-4 dashboard-cards">

            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-primary">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <div>
                        <p class="label">Total Appointments</p>
                        <h3 class="value"><?= $totalAppointments ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-success">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <p class="label">Active Staff</p>
                        <h3 class="value"><?= $activeStaff ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-danger">
                        <i class="bi bi-hospital"></i>

                    </div>
                    <div>
                        <p class="label">Services Offered</p>
                        <h3 class="value"><?= $servicesOffered ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-warning text-dark">
                        <i class="bi bi-person-heart"></i>
                    </div>
                    <div>
                        <p class="label">Total Clients</p>
                        <h3 class="value"><?= $totalClients ?></h3>
                    </div>
                </div>
            </div>

            <!-- Daily Income -->
            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-info">
                        <i class="bi bi-cash-stack"></i>
                    </div>
                    <div>
                        <p class="label">Daily Income</p>
                        <h3 class="value">₱<?= number_format($dailyIncome, 2) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Weekly Income -->
            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-secondary">
                        <i class="bi bi-calendar-week"></i>
                    </div>
                    <div>
                        <p class="label">Weekly Income</p>
                        <h3 class="value">₱<?= number_format($weeklyIncome, 2) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Monthly Income -->
            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-dark">
                        <i class="bi bi-calendar-month"></i>
                    </div>
                    <div>
                        <p class="label">Monthly Income</p>
                        <h3 class="value">₱<?= number_format($monthlyIncome, 2) ?></h3>
                    </div>
                </div>
            </div>

            <!-- Yearly Income -->
            <div class="col-md-3">
                <div class="dash-card">
                    <div class="dash-icon bg-primary">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div>
                        <p class="label">Yearly Income</p>
                        <h3 class="value">₱<?= number_format($yearlyIncome, 2) ?></h3>
                    </div>
                </div>
            </div>

        </div>

        <div class="welcome-card mt-4 p-4">
            <div class="d-flex align-items-center gap-3">
                <div class="welcome-icon bg-primary text-white">
                    <i class="bi bi-stars"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1">Welcome, <?= htmlspecialchars($name) ?>!</h5>
                    <p class="text-muted mb-0">Navigation bar above to manage your clinic’s details, staff,
                        schedules, and services.</p>
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

        <div class="containers mt-4">

            <div class="container">

                <!-- MAIN BORDER WRAPPER -->
                <div class="card p-4 shadow-sm" style="border: 1px solid #dee2e6; border-radius: 12px;">

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h3 class="mb-0 text-primary">
                            <i class="bi bi-file-earmark-text"></i> Income Report (Full)
                        </h3>
                    </div>

                    <!-- Filters -->
                    <div class="card p-3 mb-4 no-print" style="border: 1px solid #d0d0d0;">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">From</label>
                                <input type="date" id="startDate" class="form-control" value="<?= $start_default ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To</label>
                                <input type="date" id="endDate" class="form-control" value="<?= $end_default ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Quick Range</label>
                                <select id="quickRange" class="form-select">
                                    <option value="">Custom</option>
                                    <option value="7">Last 7 days</option>
                                    <option value="30" selected>Last 30 days</option>
                                    <option value="90">Last 90 days</option>
                                    <option value="365">Last 365 days</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button id="applyBtn" class="btn btn-primary w-100">Apply</button>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <div class="card p-3 border-primary border-2">
                                <small class="text-muted">Total Income</small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h4 class="mb-0" id="totalIncome">₱0.00</h4>
                                    <span class="badge bg-success summary-badge" id="totalCount">0 items</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border-primary border-2">
                                <small class="text-muted">Highest Day</small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0" id="highestDay">—</h5>
                                    <span class="text-muted" id="highestAmount">₱0.00</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card p-3 border-primary border-2">
                                <small class="text-muted">Average per Day</small>
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0" id="averageDay">₱0.00</h5>
                                    <span class="text-muted" id="daysCount">0 days</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Table + Yearly Chart -->
                    <div class="row g-3">
                        <div class="no-print">
                            <button id="printBtn" class="btn btn-outline-secondary btn-sm me-2">
                                <i class="bi bi-printer"></i> Print
                            </button>
                        </div>
                        <!-- Table first -->
                        <div class="col-12" id="exportSection">
                            <div id="printSection">
                                <h5>Detailed Income Table</h5>
                                <table class="table table-bordered" style="width:100%; border-collapse: collapse;">
                                    <thead>
                                        <tr>
                                            <th>Date Used</th>
                                            <th>Record ID</th>
                                            <th>Pet</th>
                                            <th>Owner</th>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Income (₱)</th>
                                            <th>Staff</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reportTableBody"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Chart below table -->
                        <div class="col-12 mt-4">
                            <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                <h5 class="mb-3 text-center">Yearly Income (12 months)</h5>

                                <!-- CENTERED CHART WRAPPER -->
                                <div style="
            display: flex;
            justify-content: center;
            align-items: center;
            height: 400px;
        ">
                                    <div style="width: 150%; max-width: 850px;">
                                        <canvas id="yearlyChart"></canvas>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>
                </div> <!-- MAIN BORDER WRAPPER END -->
            </div>
        </div>
    </div>

    <?php include 'assets/body/edit_user_modal.php' ?>
    <!-- inquiry form -->
    <section id="contact" class="py-5">
        <div class="container">
            <h2 class="text-center mb-4">Have a Question?</h2>
            <p class="text-center text-muted mb-4">Send us your inquiry and we'll get back to you soon!</p>
            <form action="submit_inquiry.php" method="POST" class="mx-auto p-4 shadow rounded">
                <div class="mb-3">
                    <label for="name" class="form-label">Your Name</label>
                    <input type="text" name="name" id="name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" id="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" name="subject" id="subject" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea name="message" id="message" class="form-control" rows="5" required></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Send Inquiry</button>
            </form>
        </div>
    </section>
    <?php include 'assets/body/about_us.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script src="assets/js/message_alert.js"></script>
    <script src="assets/js/calendar.js"></script>

    <script>
        document.getElementById('printBtn').addEventListener('click', async () => {
            const printSection = document.getElementById('printSection');
            const chart = document.getElementById('yearlyChart');

            // Convert chart canvas to image
            const chartImg = chart.toDataURL("image/png");

            // Clone the print section
            const clonedSection = printSection.cloneNode(true);

            // Append chart image below the table
            const imgEl = document.createElement('img');
            imgEl.src = chartImg;
            imgEl.style.width = '100%';
            imgEl.style.marginTop = '20px';
            clonedSection.appendChild(imgEl);

            // Open print window
            const w = window.open("", "", "height=900,width=1000");

            w.document.write("<html><head><title>Print</title>");
            w.document.write("<link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'>");
            w.document.write(`<style>
        body { padding: 10px; font-size: 12px; }
        table { width: 100% !important; border-collapse: collapse !important; }
        th, td { border: 1px solid #000 !important; padding: 5px; text-align: left; }
        tr { page-break-inside: avoid !important; }
        thead { display: table-header-group !important; }
        img { display: block; page-break-inside: avoid; }
        .no-print { display: none !important; }
    </style>`);
            w.document.write("</head><body>");
            w.document.write(clonedSection.outerHTML);
            w.document.write("</body></html>");

            w.document.close();
            w.focus();
            w.print();
        });
    </script>
    <script>
        // Helpers
        function fmt(num) {
            return new Intl.NumberFormat('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(num);
        }

        async function fetchRange(start, end) {
            const params = new URLSearchParams({ start, end });
            const res = await fetch(`fetch_income_range.php?${params.toString()}`);
            const data = await res.json();
            return data;
        }

        async function fetchYearly() {
            const res = await fetch('fetch_yearly_income.php');
            return await res.json();
        }

        async function loadReport(start, end) {
            document.getElementById('reportTableBody').innerHTML = '<tr><td colspan="10" class="text-center p-3">Loading...</td></tr>';
            const data = await fetchRange(start, end);

            if (data.error) {
                alert(data.error);
                return;
            }

            // populate table rows
            const tbody = document.getElementById('reportTableBody');
            tbody.innerHTML = '';
            let total = 0;
            let count = 0;
            const dailyTotals = {};

            data.rows.forEach(row => {
                count++;
                total += parseFloat(row.income);

                // sum per day
                dailyTotals[row.date_used] = (dailyTotals[row.date_used] || 0) + parseFloat(row.income);

                const tr = document.createElement('tr');
                tr.innerHTML = `
                <td>${row.date_used}</td>
                <td>${row.record_id}</td>
                <td>${row.pet_name || ''}</td>
                <td>${row.owner_name || ''}</td>
                <td>${row.item_name || ''}</td>
                <td>${row.quantity_used}</td>
                <td>₱${fmt(row.unit_price)}</td>
                <td>₱${fmt(row.income)}</td>
                <td>${row.staff_name || ''}</td>
                <td>${row.notes || ''}</td>
            `;
                tbody.appendChild(tr);
            });

            // summary
            document.getElementById('totalIncome').textContent = '₱' + fmt(total);
            document.getElementById('totalCount').textContent = `${count} rows`;

            // highest day
            let highestDay = null, highestAmt = 0;
            Object.entries(dailyTotals).forEach(([d, amt]) => {
                if (amt > highestAmt) { highestAmt = amt; highestDay = d; }
            });

            document.getElementById('highestDay').textContent = highestDay ?? '—';
            document.getElementById('highestAmount').textContent = '₱' + fmt(highestAmt);

            // avg per day
            const days = Object.keys(dailyTotals).length || 1;
            document.getElementById('averageDay').textContent = '₱' + fmt(total / days);
            document.getElementById('daysCount').textContent = `${days} days`;
        }

        // quick range logic
        document.getElementById('quickRange').addEventListener('change', (e) => {
            const val = e.target.value;
            if (!val) return;
            const days = parseInt(val);
            const end = new Date();
            const start = new Date();
            start.setDate(end.getDate() - (days - 1));
            document.getElementById('startDate').value = start.toISOString().slice(0, 10);
            document.getElementById('endDate').value = end.toISOString().slice(0, 10);
        });

        // apply button
        document.getElementById('applyBtn').addEventListener('click', () => {
            const s = document.getElementById('startDate').value;
            const e = document.getElementById('endDate').value;
            if (!s || !e) return alert('Please select a date range');
            loadReport(s, e);
        });

        // initial load
        (async function () {
            const s = document.getElementById('startDate').value;
            const e = document.getElementById('endDate').value;
            await loadReport(s, e);

            // Yearly chart
            const yearly = await fetchYearly();
            const ctx = document.getElementById('yearlyChart').getContext('2d');
            const months = yearly.months || [];
            const incomes = yearly.incomes || [];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: months,
                    datasets: [{
                        label: 'Income (₱)',
                        data: incomes,
                        borderRadius: 6,
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false, // <-- important for filling container height
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: val => '₱' + val } }
                    }
                }
            });
        })();
    </script>
    <script src="assets/js/sidebar_toggle.js"></script>
    <script src="assets/js/income_chart.js"></script>
</body>

</html>