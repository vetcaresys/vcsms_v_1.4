<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

/* ============================
   GET USER INFORMATION
   ============================ */
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

$name = htmlspecialchars($_SESSION['name']);


/* ============================
   GET CLINIC INFORMATION
   ============================ */
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

$clinic_id = $clinic['clinic_id'] ?? null;
$_SESSION['clinic_id'] = $clinic_id;


/* ============================
   DASHBOARD STATS
   ============================ */
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


/* ============================
   INCOME COMPUTATION SECTION
   ============================ */

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
    </style>

    <style>
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

    #printSection, #printSection * {
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

        <!-- <div class="card mt-4">
            <div class="card-body">
                <h4 class="text-primary fw-bold mb-3"><i class="bi bi-graph-up"></i> Income Chart (Last 30 Days)</h4>
                <canvas id="incomeChart" height="120"></canvas>
            </div>
        </div> -->
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

                        <!-- Table -->
                        <div class="col-lg-8">
                            <div id="printSection">
                                <div class="card p-3 border-1" style="border:1px solid #dee2e6;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h5 class="mb-0">Detailed Income Table</h5>
                                        <div class="no-print">
                                            <button id="printBtn" class="btn btn-outline-secondary btn-sm me-2">
                                                <i class="bi bi-printer"></i> Print
                                            </button>
                                            <button id="exportPdfBtn" class="btn btn-outline-primary btn-sm">
                                                <i class="bi bi-file-earmark-pdf"></i> Export PDF
                                            </button>
                                        </div>
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

                </div> <!-- MAIN BORDER WRAPPER END -->

            </div>
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Check URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('sent') === '1') {
            Swal.fire({
                title: "Message Sent!",
                text: "Your inquiry has been successfully submitted.",
                icon: "success",
                confirmButtonColor: "#0d6efd"
            });
        }
    </script>

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

    <!-- JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

            // 💡 NEW: Listener for Mark All button
            document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
        });

        function loadAdminNotifications() {
            fetch("../clinic_fetch_notifications.php")
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
                    fetch("../clinic_mark_all_as_read.php", { method: 'POST' })
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
            const url = 'fetch_all_appointments.php';

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

    <!-- Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

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

        // Print
        document.getElementById('printBtn').addEventListener('click', () => {
            window.print();
        });

        // Export PDF (jsPDF + html2canvas)
        document.getElementById('exportPdfBtn').addEventListener('click', async () => {
            const optTitle = `Income_Report_${document.getElementById('startDate').value}_${document.getElementById('endDate').value}`;
            const element = document.querySelector('.container');
            // capture
            const canvas = await html2canvas(element, { scale: 2 });
            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            // Calculate width and height to fit A4
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            const imgProps = pdf.getImageProperties(imgData);
            const pdfHeight = (imgProps.height * pageWidth) / imgProps.width;
            pdf.addImage(imgData, 'PNG', 0, 0, pageWidth, pdfHeight);
            pdf.save(optTitle + '.pdf');
        });

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
                    scales: {
                        y: { beginAtZero: true, ticks: { callback: val => '₱' + val } }
                    }
                }
            });

        })();
    </script>

</body>

</html>