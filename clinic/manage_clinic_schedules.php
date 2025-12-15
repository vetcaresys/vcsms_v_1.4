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
$alertType = null;
$alertMsg = null;

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

        $duplicateDays = [];
        $addedDays = [];

        if (!empty($days)) {
            foreach ($days as $day) {

                // 🔎 Check if schedule exists
                $check = $pdo->prepare("
                SELECT 1 FROM clinic_schedules 
                WHERE clinic_id = ? AND day_of_week = ?
            ");
                $check->execute([$clinic_id, $day]);

                if ($check->rowCount() > 0) {
                    $duplicateDays[] = $day;
                } else {
                    $insert = $pdo->prepare("
                    INSERT INTO clinic_schedules 
                    (clinic_id, day_of_week, open_time, close_time, status)
                    VALUES (?, ?, ?, ?, 'open')
                ");
                    $insert->execute([$clinic_id, $day, $open, $close]);
                    $addedDays[] = $day;
                }
            }

            if (!empty($duplicateDays) && empty($addedDays)) {
                $alertType = "duplicate";
                $alertMsg = "Schedule already exists for: " . implode(", ", $duplicateDays);
            } elseif (!empty($duplicateDays)) {
                $alertType = "partial";
                $alertMsg = "Some schedules were added. Duplicate days skipped: " . implode(", ", $duplicateDays);
            } else {
                $alertType = "success";
                $alertMsg = "Schedules added successfully!";
            }
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

        $update = $pdo->prepare("
        UPDATE clinic_schedules 
        SET open_time = ?, close_time = ?, status = ?
        WHERE schedule_id = ? AND clinic_id = ?
    ");
        $update->execute([$open, $close, $status, $schedule_id, $clinic_id]);

        // ✅ Unified alert system
        if ($update->rowCount() > 0) {
            $alertType = "success";
            $alertMsg = "Schedule updated successfully!";
        } else {
            $alertType = "warning";
            $alertMsg = "No changes were made to the schedule.";
        }
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

    <?php if (!empty($alertType)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: '<?= $alertType === "success" ? "success" : ($alertType === "partial" ? "warning" : "error") ?>',
                    title: '<?= $alertType === "success" ? "Success" : ($alertType === "partial" ? "Warning" : "Duplicate Found") ?>',
                    text: '<?= $alertMsg ?>',
                    confirmButtonColor: '#3085d6'
                });
            });
        </script>
    <?php endif; ?>

    <?php include 'assets/body/navbar.php' ?>
    <div class="container py-4">
        <?php if (!isset($errorMsg)): ?>
            <!-- Add Schedule Form -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-plus"></i> Add Weekly Schedule</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Select Days</label>
                            <select name="days[]" class="form-select" multiple required>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                            <small class="text-muted">Hold CTRL (Windows) or CMD (Mac) to select multiple days.</small>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Opening Time</label>
                            <input type="time" name="open_time" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Closing Time</label>
                            <input type="time" name="close_time" class="form-control" required>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button type="submit" name="add_schedule" class="btn btn-success">
                                <i class="bi bi-check-lg"></i> Save Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Current Schedules Table -->
            <div class="card shadow">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-calendar-week"></i> Current Weekly Schedules</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($scheduleRows)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Day</th>
                                        <th>Opening Time</th>
                                        <th>Closing Time</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($scheduleRows as $row): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?= $row['day_of_week'] ?></span></td>
                                            <td><?= date("g:i A", strtotime($row['open_time'])) ?></td>
                                            <td><?= date("g:i A", strtotime($row['close_time'])) ?></td>
                                            <td>
                                                <span class="badge <?= $row['status'] === 'open' ? 'bg-success' : 'bg-danger' ?>">
                                                    <?= ucfirst($row['status']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <!-- Edit button triggers modal -->
                                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                    data-bs-target="#editModal<?= $row['schedule_id'] ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="p-3 mb-0">No schedules set yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
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