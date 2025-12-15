<?php
session_start();
require '../config.php';

// Initialize form variables
$name = $email = $subject = $message = "";

// Handle inquiry form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_type']) && $_POST['form_type'] === 'inquiry') {

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    $errors = [];

    // Validation
    if (strlen($name) < 3) {
        $errors[] = "Name must be at least 3 characters.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($subject)) {
        $errors[] = "Subject is required.";
    }
    if (empty($message)) {
        $errors[] = "Message is required.";
    }

    // If no errors, insert into database
    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        $_SESSION['success'] = "Your inquiry has been sent successfully!";
        header("Location: inquiry_form.php");
        exit;
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

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
    <title>Send an Inquiry</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/navbar.css">
</head>
<body class="bg-light">

<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-body">
            <h3 class="card-title mb-4">Send an Inquiry</h3>

            <!-- Display errors -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Display success -->
            <?php if (isset($_SESSION['success'])): ?>
                <script>
                    Swal.fire({
                        title: 'Success!',
                        text: '<?= addslashes($_SESSION['success']) ?>',
                        icon: 'success',
                        timer: 2000,
                        showConfirmButton: false
                    });
                </script>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="form_type" value="inquiry">

                <div class="mb-3">
                    <label for="name" class="form-label">Name</label>
                    <input type="text" class="form-control" id="name" name="name" 
                           value="<?= htmlspecialchars($name) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?= htmlspecialchars($email) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="subject" class="form-label">Subject</label>
                    <input type="text" class="form-control" id="subject" name="subject" 
                           value="<?= htmlspecialchars($subject) ?>" required>
                </div>

                <div class="mb-3">
                    <label for="message" class="form-label">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5" required><?= htmlspecialchars($message) ?></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Send Inquiry</button>
            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="js/contact_validation.js"></script>
<script src="js/profile_toggle.js"></script>
<script src="js/profile_form_validation.js"></script>
<script src="js/dashboard_stats.js"></script>
<script src="js/appointment_calendar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
