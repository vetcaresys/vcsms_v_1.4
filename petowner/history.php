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

// Helper: Clinic logo
function getLogoPath($logo)
{
    return !empty($logo) ? "../uploads/logos/" . basename($logo) : "assets/default-clinic.jpg";
}

// Handle form submission
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

    // Validate profile picture
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
        header("Location: pet_owner_dashboard.php");
        exit;
    }

    // Fetch notifications
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ====== FILTERS ======
$statusFilter = $_GET['status'] ?? 'all';
$petFilter = $_GET['pet'] ?? '';
$clinicFilter = $_GET['clinic'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 6;
$offset = ($page - 1) * $limit;

// ====== BASE SQL ======
$sql = "
    FROM appointments a
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN clinic_services cs ON a.service_id = cs.service_id
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN staff s ON a.doctor_id = s.staff_id
    WHERE a.owner_id = :owner_id
";

// ====== APPLY FILTERS ======
$params = ['owner_id' => $user_id];

if ($statusFilter !== 'all') {
    $sql .= " AND a.status = :status ";
    $params['status'] = $statusFilter;
}
if (!empty($petFilter)) {
    $sql .= " AND a.pet_id = :pet_id ";
    $params['pet_id'] = $petFilter;
}
if (!empty($clinicFilter)) {
    $sql .= " AND a.clinic_id = :clinic_id ";
    $params['clinic_id'] = $clinicFilter;
}
if (!empty($search)) {
    $sql .= " AND (
        p.pet_name LIKE :search1 OR
        cs.service_name LIKE :search2 OR
        c.clinic_name LIKE :search3 OR
        s.name LIKE :search4 OR
        a.message LIKE :search5
    )";
    foreach (range(1, 5) as $i) {
        $params["search$i"] = "%$search%";
    }
}

// ====== COUNT FOR PAGINATION ======
$countStmt = $pdo->prepare("SELECT COUNT(*) AS total $sql");
$countStmt->execute($params);
$totalRows = $countStmt->fetchColumn();
$totalPages = ceil($totalRows / $limit);

// ====== FETCH APPOINTMENTS ======
$finalSQL = "
    SELECT a.*, 
           p.pet_name,
           cs.service_name,
           c.clinic_name,
           s.name AS doctor_name
    $sql
    ORDER BY a.appointment_date DESC, a.appointment_start DESC
    LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($finalSQL);
$stmt->execute($params);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ====== LOAD PETS & CLINICS ======
$pets = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ?");
$pets->execute([$user_id]);
$pets = $pets->fetchAll(PDO::FETCH_ASSOC);

$clinics = $pdo->query("SELECT clinic_id, clinic_name FROM clinics")->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Activity History - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/navbar.css">
    <link rel="stylesheet" href="css/index.css">

    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        body {
            background: #f5f6fa;
        }

        .history-item {
            border-left: 4px solid #0d6efd;
            padding: 12px 15px;
            border-radius: 6px;
            background: white;
            margin-bottom: 10px;
        }

        .history-type {
            font-weight: 600;
            text-transform: capitalize;
        }

        .empty-history {
            text-align: center;
            padding: 40px;
            color: gray;
            font-size: 1.1rem;
        }
    </style>

    <style>
        .history-item {
            border-left: 4px solid #0d6efd;
            padding: 12px 15px;
            border-radius: 6px;
            background: white;
            margin-bottom: 10px;
            word-wrap: break-word;
        }

        .history-type {
            font-weight: 600;
            text-transform: capitalize;
        }

        /* Make filters stack nicely on mobile */
        @media (max-width: 767px) {
            .row.g-2>[class*='col-'] {
                flex: 0 0 100%;
                max-width: 100%;
            }

            input[name="search"] {
                width: 100% !important;
            }

            .history-item i {
                font-size: 0.9rem;
            }

            .history-item {
                padding: 10px;
                font-size: 0.9rem;
            }

            .history-item .text-muted.small {
                font-size: 0.78rem;
            }

            .nav-tabs .nav-link {
                font-size: 0.9rem;
                padding: 8px 10px;
            }

            /* Pagination buttons smaller */
            .pagination .page-link {
                padding: 5px 8px;
                font-size: 0.85rem;
            }
        }

        .history-item span.badge {
            white-space: nowrap;
        }

        @media (max-width: 480px) {
            .pagination .page-item:not(:first-child):not(:last-child) {
                display: none;
                /* only show Prev and Next */
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

    <?php include 'navbar.php'; ?>

    <!-- PAGE CONTENT -->
    <div class="container mt-4">
        <h2 class="text-primary fw-bold mb-3">
            <i class="bi bi-clock-history"></i> Activity History
        </h2>

        <div class="card shadow-sm">
            <div class="card-body">

                <!-- TABS -->
                <ul class="nav nav-tabs mb-3 flex-wrap">
                    <?php
                    $tabs = ['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
                    foreach ($tabs as $key => $label):
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?= ($statusFilter === $key ? 'active' : '') ?>" href="?status=<?= $key ?>">
                                <?= $label ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- FILTERS -->
                <form method="GET" class="row g-2 mb-3">

                    <div class="col-md-3">
                        <select name="pet" class="form-select">
                            <option value="">All Pets</option>
                            <?php foreach ($pets as $p): ?>
                                <option value="<?= $p['pet_id'] ?>" <?= ($petFilter == $p['pet_id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($p['pet_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <select name="clinic" class="form-select">
                            <option value="">All Clinics</option>
                            <?php foreach ($clinics as $c): ?>
                                <option value="<?= $c['clinic_id'] ?>" <?= ($clinicFilter == $c['clinic_id'] ? 'selected' : '') ?>>
                                    <?= htmlspecialchars($c['clinic_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <input type="text" name="search" placeholder="Search Pets..."
                            value="<?= htmlspecialchars($search) ?>" class="form-control">
                    </div>

                    <div class="col-md-2">
                        <button class="btn btn-primary w-100">
                            <i class="bi bi-search"></i> Filter
                        </button>
                    </div>

                </form>

                <!-- APPOINTMENT LIST -->
                <?php if ($totalRows == 0): ?>
                    <div class="empty-history">
                        <i class="bi bi-inboxes"></i><br>
                        No appointment history found.
                    </div>
                <?php else: ?>
                    <?php foreach ($appointments as $a): ?>
                        <div class="history-item shadow-sm">

                            <div class="history-type text-primary">
                                <?= ucfirst($a['status']) ?> Appointment
                            </div>

                            <div><strong><?= $a['pet_name'] ?></strong> • <?= $a['service_name'] ?></div>

                            <div class="text-muted small">
                                <i class="bi bi-hospital"></i> <?= $a['clinic_name'] ?>
                            </div>

                            <?php if ($a['doctor_name']): ?>
                                <div class="text-muted small">
                                    <i class="bi bi-person-video2"></i>
                                    Dr. <?= $a['doctor_name'] ?>
                                </div>
                            <?php endif; ?>

                            <div class="text-muted small mt-1">
                                <i class="bi bi-clock"></i>
                                <?= date("F d, Y", strtotime($a['appointment_date'])) ?>
                                • <?= date("h:i A", strtotime($a['appointment_start'])) ?>
                                - <?= date("h:i A", strtotime($a['appointment_end'])) ?>
                            </div>

                            <?php if (!empty($a['message'])): ?>
                                <div class="mt-2">
                                    <i class="bi bi-chat-left-text"></i>
                                    <?= htmlspecialchars($a['message']) ?>
                                </div>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- PAGINATION -->
                <?php if ($totalPages > 1): ?>
                    <nav>
                        <ul class="pagination justify-content-center mt-3">

                            <!-- Prev -->
                            <li class="page-item <?= ($page <= 1 ? 'disabled' : '') ?>">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($_GET) ?>">
                                    Previous
                                </a>
                            </li>

                            <!-- Numbers -->
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?= ($i == $page ? 'active' : '') ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($_GET) ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next -->
                            <li class="page-item <?= ($page >= $totalPages ? 'disabled' : '') ?>">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($_GET) ?>">
                                    Next
                                </a>
                            </li>

                        </ul>
                    </nav>
                <?php endif; ?>

            </div>
        </div>
    </div>
    <br><br>

    <?php include 'footer.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/profile_toggle.js"></script>
</body>

</html>