<?php
session_start();
require '../config.php';

// ✅ Only allow clinic_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = htmlspecialchars($_SESSION['name']);

// ✅ Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

// ✅ Get clinic info
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    $errorMsg = "You must register your clinic first.";
} else {
    $clinic_id = $clinic['clinic_id'];

    // ✅ Handle Add Schedule
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
        $days = $_POST['days'] ?? [];
        $open = $_POST['open_time'];
        $close = $_POST['close_time'];

        if (!empty($days)) {
            foreach ($days as $day) {
                // check if schedule exists for that day
                $check = $pdo->prepare("SELECT * FROM clinic_schedules WHERE clinic_id = ? AND day_of_week = ?");
                $check->execute([$clinic_id, $day]);

                if ($check->rowCount() === 0) {
                    $insert = $pdo->prepare("INSERT INTO clinic_schedules 
                        (clinic_id, day_of_week, open_time, close_time, status)
                        VALUES (?, ?, ?, ?, 'open')");
                    $insert->execute([$clinic_id, $day, $open, $close]);
                }
            }
            $msg = "Schedules updated successfully!";
        }
    }

    // ✅ Handle Delete
    if (isset($_GET['delete'])) {
        $schedule_id = $_GET['delete'];
        $del = $pdo->prepare("DELETE FROM clinic_schedules WHERE schedule_id = ? AND clinic_id = ?");
        $del->execute([$schedule_id, $clinic_id]);
        $msg = "Schedule deleted.";
    }

    // ✅ Handle Update Schedule
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_schedule'])) {
        $schedule_id = $_POST['schedule_id'];
        $open = $_POST['open_time'];
        $close = $_POST['close_time'];
        $status = $_POST['status'];

        $update = $pdo->prepare("UPDATE clinic_schedules 
            SET open_time = ?, close_time = ?, status = ?
            WHERE schedule_id = ? AND clinic_id = ?");
        $update->execute([$open, $close, $status, $schedule_id, $clinic_id]);

        $msg = "Schedule updated successfully!";
    }

    // ✅ Fetch schedules (ordered by day)
    $schedules = $pdo->prepare("
    SELECT * 
FROM clinic_schedules 
WHERE clinic_id = ?
ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
    $schedules->execute([$clinic_id]);
    $scheduleRows = $schedules->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Clinic Schedules - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_clinic_schedules.css">
</head>

<body class="bg-light">

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_clinic_sched_alert.php' ?>
    <?php include 'assets/body/manage_sched_content.php' ?>
    <?php include 'assets/body/edit_sched_modal.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Are you sure?',
                text: "This schedule will be permanently deleted.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = "?delete=" + id;
                }
            });
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>