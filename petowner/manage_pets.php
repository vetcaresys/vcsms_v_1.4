<?php
session_start();
require '../config.php';

// Only allow pet_owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ Flash message system
$msg = "";
if (isset($_SESSION['flash'])) {
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Fetch user info
$stmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$name = htmlspecialchars($user['name']);
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();

// Handle add pet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_pet'])) {
    $pet_name = $_POST['pet_name'];
    $species = ($_POST['species'] === "Other") ? $_POST['other_species'] : $_POST['species'];
    $breed = ($_POST['breed'] === "Other") ? $_POST['other_breed'] : $_POST['breed'];
    $birth_date = $_POST['birth_date'];
    $description = $_POST['description'];
    $status = "alive";
    $date_of_death = !empty($_POST['date_of_death']) ? $_POST['date_of_death'] : null;
    $photo_path = '';

    if (!empty($_FILES['photo']['name'])) {
        $upload_dir = "../uploads/pets/";
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES["photo"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif']) && move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_path = $file_name; // ✅ store only filename
        }
    }

    $stmt = $pdo->prepare("INSERT INTO pets (owner_id, pet_name, species, photo, breed, birth_date, description, status, date_of_death) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$user_id, $pet_name, $species, $photo_path, $breed, $birth_date, $description, $status, $date_of_death]);

    $_SESSION['flash'] = "<script>
        Swal.fire({
        icon: 'success',
        title: 'Pet Added!',
        text: 'Your pet was added successfully.',
        confirmButtonColor: '#3085d6'
        });
    </script>";
    header("Location: manage_pets.php");
    exit;
}

// Handle delete pet
if (isset($_GET['delete'])) {
    $pet_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM pets WHERE pet_id = ? AND owner_id = ?");
    $stmt->execute([$pet_id, $user_id]);

    $_SESSION['flash'] = "<script>
        Swal.fire({
        icon: 'info',
        title: 'Deleted!',
        text: 'Pet deleted successfully.',
        confirmButtonColor: '#3085d6'
        });
    </script>";
    header("Location: manage_pets.php");
    exit;
}

// Handle update pet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pet'])) {
    $pet_id = $_POST['pet_id'];
    $pet_name = $_POST['pet_name'];
    $species = ($_POST['species'] === "Other") ? $_POST['other_species'] : $_POST['species'];
    $breed = ($_POST['breed'] === "Other") ? $_POST['other_breed'] : $_POST['breed'];
    $birth_date = $_POST['birth_date'];
    $description = $_POST['description'];
    $status = "alive";
    $date_of_death = !empty($_POST['date_of_death']) ? $_POST['date_of_death'] : null;
    $photo_path = null;

    if (!empty($_FILES['photo']['name'])) {
        $upload_dir = "../uploads/pets/";
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES["photo"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif']) && move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
            $photo_path = $file_name; // ✅ store only filename
        }
    }

    if ($photo_path) {
        $stmt = $pdo->prepare("UPDATE pets SET pet_name=?, species=?, breed=?, birth_date=?, description=?, status=?, date_of_death=?, photo=? WHERE pet_id=? AND owner_id=?");
        $stmt->execute([$pet_name, $species, $breed, $birth_date, $description, $status, $date_of_death, $photo_path, $pet_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE pets SET pet_name=?, species=?, breed=?, birth_date=?, description=?, status=?, date_of_death=? WHERE pet_id=? AND owner_id=?");
        $stmt->execute([$pet_name, $species, $breed, $birth_date, $description, $status, $date_of_death, $pet_id, $user_id]);
    }

    $_SESSION['flash'] = "<script>
        Swal.fire({
        icon: 'success',
        title: 'Updated!',
        text: 'Pet updated successfully.',
        confirmButtonColor: '#3085d6'
        });
    </script>";
    header("Location: manage_pets.php");
    exit;
}

// Load pets
$stmt = $pdo->prepare("SELECT * FROM pets WHERE owner_id = ?");
$stmt->execute([$user_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch user info again
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'profile_default.jpg';
$profilePicPath = "../uploads/profiles/" . $profilePic . "?t=" . time();
$name = htmlspecialchars($user['name']);

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Pets - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/manage_pets.css">

</head>

<body class="bg-light">

    <?php include 'navbar.php' ?>

    <!-- Main content -->
    <div class="container mt-4">
        <?php if (!empty($msg))
            echo $msg; ?>

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPetModal">
                <i class="bi bi-plus-circle"></i> Add Pet
            </button>
        </div>

        <?php include 'add_pet.php' ?>
        <?php include 'pets_table.php' ?>

    </div>

    <?php include 'edit_pet_modal.php' ?>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <?php include 'view_profile_section.php' ?>
                <?php include 'edit_profile_section.php' ?>
            </div>
        </div>
    </div>

    <div class="my-5"></div>
    <?php include 'footer.php'; ?>

    <script src="js/profile_toggle.js"></script>
    <script src="js/delete_confirm.js"></script>
    <script src="js/pet_search_filter.js"></script>
    <script src="js/logout_confirm.js"></script>
    <script src="js/add_pet_dynamic_fields.js"></script>
    <script src="js/breed_selector_edit.js"></script>
    <script src="js/password_toggle.js"></script>
    <script src="js/edit_pet_form_logic.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.5/xlsx.full.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            loadAdminNotifications();

            // Load when bell icon is clicked
            document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);

            // 💡 NEW: Listener for Mark All button
            document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
        });

        function loadAdminNotifications() {
            fetch(`../petowner_fetch_notifications.php?user_id=` + `<?= ($user_id) ?>`)
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
                    fetch(`../petowner_mark_all_as_read.php?user_id=` + `<?= ($user_id) ?>`, { method: 'POST' })
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