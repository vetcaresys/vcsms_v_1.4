<?php
session_start();
require '../config.php';

// ✅ Only allow clinic_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$name = htmlspecialchars($_SESSION['name'] ?? '');

// Get user info and navbar
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$name = htmlspecialchars($_SESSION['name']);



// ✅ Get clinic info
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch();

$staffMembers = [];

if (!$clinic) {
    $_SESSION['error'] = "You must register your clinic first before adding staff.";
    header("Location: ../clinic/manage_clinic.php");
    exit;
}

$clinic_id = $clinic['clinic_id'];

// ✅ ADD STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    $staff_name = trim($_POST['name']);
    $staff_role = $_POST['role'];
    $contact = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password_raw = $_POST['password'];

    $errors = [];

    if (strlen($staff_name) < 3)
        $errors[] = "Name must be at least 3 characters.";
    if (!in_array($staff_role, ['staff', 'doctor']))
        $errors[] = "Invalid role.";
    if (!preg_match('/^09\d{9}$/', $contact))
        $errors[] = "Invalid contact number format.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "Invalid email format.";
    if (strlen($password_raw) < 6 || !preg_match('/[A-Za-z]/', $password_raw) || !preg_match('/[0-9]/', $password_raw))
        $errors[] = "Password must be at least 6 characters long and include letters & numbers.";

    // Handle profile picture
    $fileName = null;
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetDir . $fileName);
    }

    if (empty($errors)) {
        $password = password_hash($password_raw, PASSWORD_DEFAULT);

        // Check if email exists
        $check = $pdo->prepare("SELECT 1 FROM staff WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $_SESSION['error'] = "Email already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO staff (clinic_id, name, role, contact_number, email, password, profile_picture)
                                   VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$clinic_id, $staff_name, $staff_role, $contact, $email, $password, $fileName]);
            $_SESSION['success'] = "Staff added successfully!";
        }
    } else {
        $_SESSION['error'] = implode(" ", $errors);
    }

    header("Location: manage_staff.php");
    exit;
}

// ✅ UPDATE STAFF
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_staff'])) {
    $id = $_POST['staff_id'];
    $staff_name = trim($_POST['name']);
    $staff_role = $_POST['role'];
    $contact = trim($_POST['contact_number']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = "UPDATE staff SET name = ?, role = ?, contact_number = ?, email = ?";
    $params = [$staff_name, $staff_role, $contact, $email];

    if (!empty($password)) {
        $sql .= ", password = ?";
        $params[] = password_hash($password, PASSWORD_DEFAULT);
    }

    // Optional profile picture
    if (!empty($_FILES['profile_picture']['name'])) {
        $targetDir = "../uploads/profiles/";
        if (!is_dir($targetDir))
            mkdir($targetDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetDir . $fileName);
        $sql .= ", profile_picture = ?";
        $params[] = $fileName;
    }

    $sql .= " WHERE staff_id = ? AND clinic_id = ?";
    $params[] = $id;
    $params[] = $clinic_id;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $_SESSION['success'] = "Staff updated successfully!";
    header("Location: manage_staff.php");
    exit;
}

// ✅ DELETE STAFF
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM staff WHERE staff_id = ? AND clinic_id = ?");
    $stmt->execute([$id, $clinic_id]);

    $_SESSION['success'] = "Staff deleted successfully!";
    header("Location: manage_staff.php");
    exit;
}

// ✅ FETCH STAFF LIST
$staffList = $pdo->prepare("SELECT * FROM staff WHERE clinic_id = ?");
$staffList->execute([$clinic_id]);
$staffMembers = $staffList->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Staff - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/manage_staff.css">
</head>

<body class="bg-light d-flex flex-column min-vh-100">

    <?php include 'assets/body/navbar.php' ?>
    <?php include 'assets/body/manage_staff_content.php' ?>
    <?php include 'assets/body/manage_staff_alert.php' ?>
    <?php include 'assets/body/profile_modal.php' ?>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/add_staff_modal.php' ?>
    <?php include 'assets/body/add_staff_modal.php' ?>

    <script src="assets/js/staff_form_validation.js"></script>
    <script src="assets/js/delete_staff.js"></script>
    <script src="assets/js/logout.js"></script>
    <script src="assets/js/staff_password_toggle.js"></script>
    <script src="assets/js/show_password.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script></script>
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