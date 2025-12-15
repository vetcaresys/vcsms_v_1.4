<?php
session_start();
require '../config.php';

// 🔐 Only pet owners can access
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

// Clinic logo helper
function getLogoPath($logo)
{
    return !empty($logo) ? "../uploads/logos/" . basename($logo) : "assets/default-clinic.jpg";
}

// Format contact number
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

    // File validation
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

    // Example: fetch notifications (can be moved elsewhere)
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// =======================
// Fetch Record Details
// =======================
$record_id = $_GET['record_id'] ?? null;
if (!$record_id)
    die("Invalid record.");

$stmt = $pdo->prepare("
    SELECT 
        pr.*,
        p.pet_name,
        p.owner_id,
        rt.template_name,
        c.clinic_name,
        u.name AS created_by
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id
    LEFT JOIN record_templates rt ON pr.template_id = rt.template_id
    LEFT JOIN clinics c ON pr.clinic_id = c.clinic_id
    LEFT JOIN users u ON pr.doctor_id = u.user_id
    WHERE pr.record_id = ?
");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

// ❌ Block access if not owner
if (!$record || $record['owner_id'] != $user_id) {
    die("Unauthorized access.");
}

// Decode medical data
$data = json_decode($record['data'], true);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
    <title>Medical Record - <?= htmlspecialchars($record['pet_name']) ?></title>

    <style>
        @media (max-width: 576px) {
            .card-body {
                padding: 1rem;
            }
        }

        .table td,
        .table th {
            word-wrap: break-word;
            vertical-align: top;
        }
    </style>
</head>

<body class="bg-light">

    <?php include 'navbar.php'; ?>

    <div class="container mt-4">

        <a href="manage_pets.php" class="btn btn-secondary mb-3">
            <i class="bi bi-arrow-left"></i> Back to Pets
        </a>

        <div class="card shadow-sm">
            <div class="card-body">

                <h3 class="text-primary mb-3"><?= htmlspecialchars($record['template_name'] ?? 'Medical Record') ?></h3>

                <p><strong>Pet:</strong> <?= htmlspecialchars($record['pet_name']) ?></p>
                <p><strong>Clinic:</strong> <?= htmlspecialchars($record['clinic_name'] ?? 'N/A') ?></p>
                <p><strong>Created By:</strong> <?= htmlspecialchars($record['created_by'] ?? 'N/A') ?></p>
                <p><strong>Date:</strong> <?= date('F j, Y', strtotime($record['date_recorded'])) ?></p>

                <hr>

                <div class="accordion" id="medicalDetailsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#collapseOne">
                                Medical Details
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show"
                            data-bs-parent="#medicalDetailsAccordion">
                            <div class="accordion-body">
                                <!-- table here -->
                                <?php if (!empty($data)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <?php foreach ($data as $key => $value): ?>
                                                <tr>
                                                    <th style="width:30%"><?= ucwords(str_replace('_', ' ', $key)) ?></th>
                                                    <td><?= is_array($value) ? htmlspecialchars(json_encode($value)) : htmlspecialchars($value) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No detailed data available.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
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