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
}

$clinic_id = $_SESSION['clinic_id'] ?? null;
if (!$clinic_id) {
    echo "No clinic selected.";
    exit;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/index.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dashboard-cards .dash-card {
            display: flex;
            align-items: center;
            gap: 18px;
            background: #ffffff;
            padding: 22px;
            border-radius: 14px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            transition: 0.25s ease;
            cursor: pointer;
        }

        .dashboard-cards .dash-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
        }

        .dash-card .dash-icon {
            width: 55px;
            height: 55px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }

        .dash-card .label {
            margin: 0;
            font-size: 13px;
            letter-spacing: .5px;
            font-weight: 600;
            color: #777;
        }

        .dash-card .value {
            font-size: 28px;
            font-weight: 800;
            margin: 0;
        }

        .welcome-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.06);
            transition: 0.25s ease;
        }

        .welcome-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.08);
        }

        .welcome-icon {
            width: 55px;
            height: 55px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
        }
    /* </style>

    <style> */
        .vcs-footer {
            background: #ffffff;
            border-top: 1px solid #e5e5e5;
            box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.04);
        }

        .footer-link {
            margin: 0 10px;
            text-decoration: none;
            color: #6c757d;
            font-weight: 500;
            transition: 0.2s;
        }

        .footer-link:hover {
            color: #0d6efd;
        }

        .footer-social {
            color: #6c757d;
            margin-left: 12px;
            font-size: 1.3rem;
            transition: 0.2s;
        }

        .footer-social:hover {
            color: #0d6efd;
            transform: translateY(-2px);
        }

        .vcs-footer h4 {
            font-size: 1.5rem;
            color: #0d6efd;
        }

        .vcs-footer p {
            font-size: 0.9rem;
        }

        #contact {
            background: #ffffff;
            /* solid white */
            position: relative;
            z-index: 2;
        }

        #contact form {
            background: #fff !important;
            border-radius: 12px;
        }

        .card {
            border-radius: 12px;
        }

        .table-wrap {
            max-height: 420px;
            overflow: auto;
        }

        .summary-badge {
            font-size: 1rem;
        }

        @media print {
            body * {
                visibility: hidden !important;
            }

            #printSection,
            #printSection * {
                visibility: visible !important;
            }

            #printSection {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
        }
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

    <?php if (!empty($_SESSION['update_msg'])): ?>
        <script>
            Swal.fire({
                icon: <?= (stripos($_SESSION['update_msg'], '❌') !== false || stripos($_SESSION['update_msg'], '⚠️') !== false) ? "'error'" : "'success'" ?>,
                title: 'Profile Update',
                text: <?= json_encode($_SESSION['update_msg']) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
        <?php unset($_SESSION['update_msg']); endif; ?>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_clinic.php" class="nav-link text-white">Manage Clinic</a></li>
                    <li class="nav-item"><a href="manage_staff.php" class="nav-link text-white">Manage Staff</a></li>
                    <li class="nav-item"><a href="manage_clinic_schedules.php" class="nav-link text-white">Manage
                            Schedules</a></li>
                    <li class="nav-item"><a href="manage_services.php" class="nav-link text-white">Manage Services</a>
                    </li>
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
                        <img src="<?= $profilePic ?>" alt="Profile" width="32" height="32" class="rounded-circle me-2">
                        <strong><?= htmlspecialchars($name) ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">View
                                Profile</a></li>
                        <li><a class="dropdown-item" href="manage_clinic_details.php">Update Clinic Info</a></li>
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
                            <button id="exportPdfBtn" class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-file-earmark-pdf"></i> Export PDF
                            </button>
                        </div>
                        <div id="exportSection" class="row g-3">

                            <!-- Table -->
                            <div class="col-lg-8">
                                <div id="printSection">
                                    <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="mb-0">Detailed Income Table</h5>
                                        </div>

                                        <div class="table-wrap">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-light sticky-top">
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
                                </div>
                            </div>

                            <!-- Chart -->
                            <div class="col-lg-4">
                                <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                    <h5 class="mb-3">Yearly Income (12 months)</h5>
                                    <canvas id="yearlyChart" height="220"></canvas>
                                </div>
                            </div>

                        </div>

                    </div>
                </div> <!-- MAIN BORDER WRAPPER END -->
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Profile Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <?php
                        $profilePic = !empty($user['profile_picture'])
                            ? "../uploads/profiles/" . htmlspecialchars($user['profile_picture'])
                            : "../assets/default-profile.png"; // fallback kung walay pic
                        ?>
                        <img src="<?= $profilePic ?>" alt="Profile Picture"
                            class="rounded-circle border border-3 border-primary mb-3" width="150" height="150"
                            style="object-fit: cover;">
                        <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                        <small class="text-muted">Clinic Staff</small>
                    </div>

                    <!-- Formal Info Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Full Name</th>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= htmlspecialchars($user['contact_number'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary px-4" data-bs-target="#editUserModal"
                            data-bs-toggle="modal" data-bs-dismiss="modal">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="update_user.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit User Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($user['name']) ?>" required></div>
                        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required></div>
                        <div class="mb-3">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($user['contact_number']) ?>" maxlength="11"
                                pattern="^0\d{10}$" title="Enter a valid 11-digit number (e.g. 09123456789)" oninput="
                                // remove any non-digit characters
                                this.value = this.value.replace(/[^0-9]/g, '');
                                // limit to 11 digits only
                                if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                            <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                        </div>
                        <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"
                                value="<?= htmlspecialchars($user['address']) ?>"></div>
                        <div class="mb-3"><label>Profile Picture</label><input type="file" name="profile_picture"
                                class="form-control"></div>

                        <h6 class="text-primary">Change Password (optional)</h6>
                        <!-- Current Password -->
                        <div class="mb-3 position-relative">
                            <label>Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" id="currentPassword"
                                    placeholder="Enter current password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="currentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Type your current password to confirm changes.</small>
                                    <a href="../forgot_password.php" class="small">Forgot password?</a>
                                </div>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3 position-relative">
                            <label>New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" id="newPassword"
                                    minlength="6" placeholder="Enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="newPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3 position-relative">
                            <label>Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                                    minlength="6" placeholder="Re-enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>

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

    <!-- about us -->
    <section id="about" class="py-5" style="background-color: #f8f9fb; position: relative; z-index: 5;">
        <div class="container">
            <h2 class="text-center mb-4 fw-bold text-primary">About VetCareSys</h2>
            <p class="text-center mb-5" style="color: #495057; font-weight: 500; opacity: 1;">
                VetCareSys is a web-based veterinary management system that helps clinics organize pet records,
                schedules, and staff with ease. Built for clinic owners in Misamis Occidental, it brings clarity,
                structure, and reliability into everyday clinic operations.
            </p>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
                        <i class="bi bi-journal-medical fs-1 text-primary mb-3"></i>
                        <h5 class="fw-bold">Record Management</h5>
                        <p style="color: #495057; opacity: 1;">Manage pet health records efficiently and securely.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
                        <i class="bi bi-people-fill fs-1 text-primary mb-3"></i>
                        <h5 class="fw-bold">Staff & Doctors</h5>
                        <p style="color: #495057; opacity: 1;">Organize roles, staff accounts, and doctor schedules.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 text-center p-4 bg-white">
                        <i class="bi bi-geo-alt-fill fs-1 text-primary mb-3"></i>
                        <h5 class="fw-bold">Clinic Mapping</h5>
                        <p style="color: #495057; opacity: 1;">Built-in GPS map locator for easier clinic discovery.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="vcs-footer mt-5">
        <div class="container py-4">

            <div class="row align-items-center gy-4">

                <!-- Logo + Tagline -->
                <div class="col-md-4 text-center text-md-start">
                    <h4 class="fw-bold mb-1">VetCareSys</h4>
                    <p class="text-muted small mb-0">Your trusted companion in clinic management.</p>
                </div>

                <!-- Quick Links -->
                <div class="col-md-4 text-center">
                    <a href="index.php" class="footer-link">Home</a>
                    <a href="#about" class="footer-link">About</a>
                    <a href="#contact" class="footer-link">Contact</a>
                </div>

                <!-- Social Icons -->
                <div class="col-md-4 text-center text-md-end">
                    <a href="#" class="footer-social"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="footer-social"><i class="bi bi-twitter"></i></a>
                    <a href="#" class="footer-social"><i class="bi bi-instagram"></i></a>
                </div>

            </div>

            <div class="text-center pt-3">
                <p class="text-muted small mb-0">&copy; 2025 VetCareSys. All rights reserved.</p>
            </div>

        </div>
    </footer>

    <!-- Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/message_alert.js"></script>
    <script src="js/calendar.js"></script>
    <script src="js/logout.js"></script>
    <script src="js/show_password.js"></script>
    <script src="js/notification.js"></script>
    <script src="js/report.js"></script>

    <script>
        document.getElementById('toggleSidebar').addEventListener('click', function () {
            document.getElementById('sidebarMenu').classList.toggle('active');
        });

        if (window.location.search.includes("msg=")) {
            history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
    <?php if (!empty($_GET['msg'])): ?>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Updated Successfully!',
                text: <?= json_encode($_GET['msg']) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
    <?php endif; ?>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            fetch("fetch_income_data.php")
                .then(res => res.json())
                .then(data => {

                    const dates = data.graph.map(item => item.date);
                    const income = data.graph.map(item => parseFloat(item.income));

                    const ctx = document.getElementById("incomeChart").getContext("2d");

                    new Chart(ctx, {
                        type: "line",
                        data: {
                            labels: dates,
                            datasets: [{
                                label: "Income (₱)",
                                data: income,
                                borderWidth: 3,
                                tension: 0.3,
                                fill: true,
                                backgroundColor: "rgba(13,110,253,0.15)",
                                borderColor: "rgba(13,110,253,1)",
                                pointBackgroundColor: "rgba(0, 86, 179, 1)",
                                pointRadius: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    ticks: { callback: value => "₱" + value }
                                }
                            }
                        }
                    });

                });
        });
    </script>
</body>

</html>