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
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';

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
    <link rel="stylesheet" href="assets/manage_clinic_schedules.css">
</head>

<body class="bg-light">

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_clinic_sched_alert.php' ?>
    <?php include 'assets/body/profile_modal.php' ?>
    <?php include 'assets/body/manage_sched_content.php' ?>
    <?php include 'assets/body/edit_sched_modal.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>

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

    <script src="assets/js/logout.js"></script>
    <script src="assets/js/show_password.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html>