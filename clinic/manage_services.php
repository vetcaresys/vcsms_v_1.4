<?php
session_start();
require '../config.php';

// Only allow clinic owners
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$alertType = null;

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = (!empty($user['profile_picture']) && file_exists($picPath))
    ? $picPath
    : "profile_default.jpg";

$name = htmlspecialchars($_SESSION['name']);

// Get clinic ID for this owner
$stmt = $pdo->prepare("SELECT clinic_id FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$clinic = $stmt->fetch();

if (!$clinic) {
    $errorMsg = "<div class='alert alert-danger'>You must register your clinic first.</div>";
} else {
    $clinic_id = $clinic['clinic_id'];

    // Safely get POST values
    $service_name_post = $_POST['service_name'] ?? '';
    $custom_service_post = trim($_POST['custom_service'] ?? '');
    $duration_post = trim($_POST['duration'] ?? '');
    $service_id_post = $_POST['service_id'] ?? '';

    // =========================
    // Add New Service
    // =========================
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {

        if ($service_name_post === 'Other' && empty($custom_service_post)) {
            $alertType = 'empty_custom';
        } else {
            $service_name = ($service_name_post === 'Other') ? $custom_service_post : $service_name_post;

            // Check duplicate service
            $check = $pdo->prepare("
                SELECT 1 FROM clinic_services 
                WHERE clinic_id = ? AND LOWER(service_name) = LOWER(?)
            ");
            $check->execute([$clinic_id, $service_name]);

            if ($check->rowCount() > 0) {
                $alertType = 'duplicate';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO clinic_services (clinic_id, service_name, duration)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$clinic_id, $service_name, $duration_post]);
                $alertType = 'added';
            }
        }
    }

    // =========================
    // Update Service
    // =========================
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_service'])) {

        if ($service_name_post === 'Other' && empty($custom_service_post)) {
            $alertType = 'empty_custom';
        } else {
            $service_name = ($service_name_post === 'Other') ? $custom_service_post : $service_name_post;

            // Check duplicate (exclude self)
            $check = $pdo->prepare("
                SELECT 1 FROM clinic_services 
                WHERE clinic_id = ? AND LOWER(service_name) = LOWER(?) AND service_id != ?
            ");
            $check->execute([$clinic_id, $service_name, $service_id_post]);

            if ($check->rowCount() > 0) {
                $alertType = 'duplicate';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE clinic_services 
                    SET service_name = ?, duration = ?
                    WHERE service_id = ? AND clinic_id = ?
                ");
                $stmt->execute([$service_name, $duration_post, $service_id_post, $clinic_id]);
                $alertType = 'updated';
            }
        }
    }

    // =========================
    // Delete Service
    // =========================
    elseif (isset($_GET['delete'])) {
        $service_id = $_GET['delete'];
        $stmt = $pdo->prepare("DELETE FROM clinic_services WHERE service_id = ? AND clinic_id = ?");
        $stmt->execute([$service_id, $clinic_id]);
        $alertType = 'deleted';
    }

    // =========================
    // Fetch All Services
    // =========================
    $services = $pdo->prepare("SELECT * FROM clinic_services WHERE clinic_id = ?");
    $services->execute([$clinic_id]);
    $serviceList = $services->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Services - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/manage_services.css">
</head>

<body class="bg-light">

    <?php
    $alertScript = ""; // prepare variable to hold JS alert scripts
    
    if (!$clinic) {
        $alertScript = "
        <script>
        document.addEventListener('DOMContentLoaded', () => {
            Swal.fire({
                icon: 'warning',
                title: 'No Clinic Found',
                text: 'You must register your clinic first before managing services.',
                confirmButtonColor: '#0d6efd'
            });
        });
        </script>
        ";
    } else {
        if (isset($alertType) && $alertType === 'duplicate') {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'error',
                    title: 'Duplicate Service',
                    text: 'This service already exists in your clinic.',
                    confirmButtonColor: '#dc3545'
                });
            });
            </script>
            ";
        } elseif ($alertType === 'added') {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Added!',
                    text: 'New service has been added successfully.',
                    confirmButtonColor: '#198754'
                });
            });
            </script>
            ";
        } elseif ($alertType === 'updated') {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Updated!',
                    text: 'Service details were updated successfully.',
                    confirmButtonColor: '#ffc107'
                });
            });
            </script>
            ";
        } elseif (isset($_GET['delete'])) {
            $alertScript = "
            <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    icon: 'success',
                    title: 'Service Deleted!',
                    text: 'The service has been removed.',
                    confirmButtonColor: '#dc3545'
                });
            });
            </script>
            ";
        }
    }

    ?>
    <?= $alertScript ?? '' ?>

    <?php include 'assets/body/navbar.php' ?>
    <div class="container py-4">
        <?php
        if (!empty($errorMsg)) {
            echo $errorMsg;
        }
        ?>

        <?php if (!isset($errorMsg)): ?>
            <div class="row g-4">
                <!-- Add Service Form (Left Column) -->
                <div class="col-lg-4">
                    <div class="card shadow h-100">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Service</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" class="row g-3">
                                <div class="col-12">
                                    <label class="form-label">Service Name</label>
                                    <select name="service_name" id="service_name" class="form-select" required
                                        onchange="toggleCustomService()">
                                        <option value="" disabled selected>Select a service</option>
                                        <option value="General Check-up">General Check-up</option>
                                        <option value="Vaccination">Vaccination</option>
                                        <option value="Deworming">Deworming</option>
                                        <option value="Grooming">Grooming</option>
                                        <option value="Dental Cleaning">Dental Cleaning</option>
                                        <option value="Spaying / Neutering">Spaying / Neutering</option>
                                        <option value="Surgery">Surgery</option>
                                        <option value="Emergency Treatment">Emergency Treatment</option>
                                        <option value="Ultrasound">Ultrasound</option>
                                        <option value="X-ray">X-ray</option>
                                        <option value="Laboratory Test">Laboratory Test</option>
                                        <option value="Other">Other (specify)</option>
                                    </select>

                                    <!-- Hidden custom input field -->
                                    <input type="text" name="custom_service" id="custom_service" class="form-control mt-2"
                                        placeholder="Enter custom service name" style="display:none;">
                                </div>

                                <div class="col-12">
                                    <label class="form-label">Duration</label>
                                    <input type="text" name="duration" class="form-control" placeholder="e.g., 30 minutes"
                                        required>
                                </div>
                                <div class="col-12 d-flex align-items-end">
                                    <button type="submit" name="add_service" class="btn btn-success w-100">
                                        <i class="bi bi-check-lg"></i> Add
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Current Services Table (Right Column) -->
                <div class="col-lg-8">
                    <div class="card shadow h-100">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-list-ul"></i> Current Services</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($serviceList)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0 align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Service</th>
                                                <th>Duration</th>
                                                <th style="width: 120px;">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($serviceList as $row): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($row['service_name']); ?></td>
                                                    <td><?= htmlspecialchars($row['duration']); ?></td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-2">
                                                            <!-- Edit button -->
                                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                                                data-bs-target="#editServiceModal<?= $row['service_id']; ?>">
                                                                <i class="bi bi-pencil-square"></i>
                                                            </button>

                                                            <!-- Delete button -->
                                                            <!-- <button type="button" class="btn btn-sm btn-danger"
                                                            onclick="confirmDelete(<?= $row['service_id']; ?>)">
                                                            <i class="bi bi-trash"></i>
                                                        </button> -->
                                                        </div>
                                                    </td>

                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="p-3 mb-0">No services added yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php include 'assets/body/edit_user_modal.php' ?>
    <?php include 'assets/body/edit_service_modal.php' ?>
    <?php include 'assets/body/footer_all.php' ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/services_alert.js"></script>
</body>

</html>