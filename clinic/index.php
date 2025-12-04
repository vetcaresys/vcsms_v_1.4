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
    <link rel="stylesheet" href="assets/css/index.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link rel="stylesheet" href="assets/css/footer.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

    <?php include 'assets/body/alert_popup.php' ?>
    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/dashboard.php' ?>
    <?php include 'assets/body/profile_modal.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/inquiry_form.php' ?>
    <?php include 'assets/body/about_us.php' ?>
    <?php include 'assets/body/footer.php' ?>

    <script src="assets/js/message_alert.js"></script>
    <script src="assets/js/calendar.js"></script>
    <script src="assets/js/logout.js"></script>
    <script src="assets/js/show_password.js"></script>
    <script src="assets/js/report.js"></script>
    <script src="assets/js/sidebar_toggle.js"></script>
    <script src="assets/js/income_chart.js"></script>
    <script src="assets/js/notifications.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>