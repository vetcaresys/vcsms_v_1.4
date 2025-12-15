<?php
session_start();
require '../config.php';

// 🔐 Only pet owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// =======================
// Fetch User Info
// =======================
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Profile picture
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

// =======================
// Handle Profile Update Form
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact_number']);
    $address = trim($_POST['address']);

    $errors = [];

    if (strlen($name) < 3)
        $errors[] = "Name must be at least 3 characters.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    if (!empty($contact) && !preg_match('/^09\d{9}$/', $contact))
        $errors[] = "Contact number must be 11 digits starting with 09.";
    if (empty($address))
        $errors[] = "Address is required.";

    if (!empty($_FILES['profile_picture']['name'])) {
        $allowed = ['jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed))
            $errors[] = "Profile picture must be JPG or PNG.";
        if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024)
            $errors[] = "Profile picture must not exceed 2MB.";
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header("Location: pet_owner_dashboard.php");
        exit;
    }
}

// =======================
// Fetch Pet Info
// =======================
$pet_id = $_GET['pet_id'] ?? null;
if (!$pet_id) {
    header("Location: manage_pets.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pets WHERE pet_id = ? AND owner_id = ?");
$stmt->execute([$pet_id, $user_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet)
    die("Unauthorized access.");

$pet_name = htmlspecialchars($pet['pet_name']);
$pet_photo = !empty($pet['photo']) ? $pet['photo'] : 'default.png';
$pet_photo_path = "../uploads/pets/" . $pet_photo;

// =======================
// Fetch Appointments
// =======================
$stmt = $pdo->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.status,
        cs.service_name,
        u.name AS doctor_name
    FROM appointments a
    LEFT JOIN clinic_services cs ON a.service_id = cs.service_id
    LEFT JOIN users u ON a.doctor_id = u.user_id
    WHERE a.pet_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$pet_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =======================
// Fetch Medical Records
// =======================
$stmt = $pdo->prepare("
    SELECT 
        pr.record_id,
        pr.date_recorded,
        rt.template_name,
        u.name AS doctor_name,
        c.clinic_name
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id
    LEFT JOIN record_templates rt ON pr.template_id = rt.template_id
    LEFT JOIN users u ON pr.doctor_id = u.user_id
    LEFT JOIN clinics c ON pr.clinic_id = c.clinic_id
    WHERE pr.pet_id = ?
      AND p.owner_id = ?
    ORDER BY pr.date_recorded DESC
");
$stmt->execute([$pet_id, $user_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= $pet_name ?> - Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
    <style>
        .pet-photo {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 50%;
        }

        @media (max-width: 576px) {
            .pet-photo {
                width: 100px;
                height: 100px;
            }
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>

<body class="bg-light">
    <?php include 'navbar.php'; ?>

    <div class="container mt-4">

        <a href="manage_pets.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Pets
        </a>

        <!-- PET INFO -->
        <div class="card mb-4 shadow-sm">
            <div class="card-body d-flex align-items-center gap-4">
                <img src="<?= $pet_photo_path ?>" class="pet-photo" onerror="this.src='../uploads/pets/default.png'">
                <div>
                    <h3 class="text-primary"><?= $pet_name ?></h3>
                    <p class="mb-1"><strong>Species:</strong> <?= htmlspecialchars($pet['species']) ?></p>
                    <p class="mb-1"><strong>Breed:</strong> <?= htmlspecialchars($pet['breed']) ?></p>
                    <p class="mb-0"><strong>Status:</strong> <?= ucfirst($pet['status']) ?></p>
                </div>
            </div>
        </div>

        <!-- TABS -->
        <ul class="nav nav-tabs flex-column flex-sm-row">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#appointments">
                    Appointments (<?= count($appointments) ?>)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#records">
                    Medical Records (<?= count($records) ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content bg-white border border-top-0 p-3">

            <!-- APPOINTMENTS -->
            <div class="tab-pane fade show active table-responsive" id="appointments">
                <?php if ($appointments): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Service</th>
                                <th>Doctor</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $a): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($a['appointment_date'])) ?></td>
                                    <td><?= htmlspecialchars($a['service_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($a['doctor_name'] ?? 'N/A') ?></td>
                                    <td><?= ucfirst($a['status']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No appointments found.</div>
                <?php endif; ?>
            </div>

            <!-- MEDICAL RECORDS -->
            <div class="tab-pane fade table-responsive" id="records">
                <?php if ($records): ?>
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Clinic</th>
                                <th>Created By</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r): ?>
                                <tr>
                                    <td><?= date('M d, Y', strtotime($r['date_recorded'])) ?></td>
                                    <td><?= htmlspecialchars($r['template_name']) ?></td>
                                    <td><?= htmlspecialchars($r['clinic_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($r['doctor_name'] ?? 'N/A') ?></td>
                                    <td>
                                        <a href="view_record_detail.php?record_id=<?= $r['record_id'] ?>"
                                            class="btn btn-sm btn-outline-primary d-inline d-sm-none">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="view_record_detail.php?record_id=<?= $r['record_id'] ?>"
                                            class="btn btn-sm btn-outline-primary d-none d-sm-inline">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info">No medical records found.</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <br><br>
    <?php include 'footer.php'; ?>

    <script src="js/contact_validation.js"></script>
    <script src="js/profile_toggle.js"></script>
    <script src="js/profile_form_validation.js"></script>
    <script src="js/dashboard_stats.js"></script>
    <script src="js/appointment_calendar.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>