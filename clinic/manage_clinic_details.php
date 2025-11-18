<?php
include '../config.php';
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}
$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
$name = htmlspecialchars($_SESSION['name']);

// ✅ One consistent definition for profile picture
$picPath = "../uploads/profiles/" . $user['profile_picture'];
$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';

// Get clinic
$stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
$stmt->execute([$user_id]);
$existingClinic = $stmt->fetch();

$msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clinic_name = $_POST['clinic_name'];
    $address = $_POST['address'];
    if (stripos($address, 'Misamis Occidental') === false) {
        $msg = "Address must include 'Misamis Occidental'.";
    }
    $contact_info = $_POST['contact_info'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    $logo_path = $existingClinic['logo'] ?? '';
    if (!empty($_FILES['logo']['name'])) {
        $upload_dir = "../uploads/logos/";
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . "_" . basename($_FILES["logo"]["name"]);
        $target_file = $upload_dir . $file_name;
        $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($file_type, ['jpg', 'jpeg', 'png', 'gif'])) {
            if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
                $logo_path = $file_name;
            } else {
                $msg = "Failed to upload logo.";
            }
        } else {
            $msg = "Invalid image type.";
        }
    }

    if (empty($msg)) {
        if ($existingClinic) {
            $sql = "UPDATE clinics SET clinic_name=?, address=?, contact_info=?, latitude=?, longitude=?";
            $params = [$clinic_name, $address, $contact_info, $latitude, $longitude];
            if ($logo_path) {
                $sql .= ", logo=?";
                $params[] = $logo_path;
            }
            $sql .= " WHERE user_id=?";
            $params[] = $user_id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $msg = "Clinic updated successfully!";
        } else {
            $stmt = $pdo->prepare("INSERT INTO clinics (user_id, clinic_name, address, contact_info, latitude, longitude, logo) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$user_id, $clinic_name, $address, $contact_info, $latitude, $longitude, $logo_path]);
            $msg = "Clinic registered successfully!";
        }

        // Refresh clinic info
        $stmt = $pdo->prepare("SELECT * FROM clinics WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existingClinic = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Clinic - VetCareSys</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/jpg" href="../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/manage_clinic_details.css">
</head>

<body class="bg-light">
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#topNav"><span
                    class="navbar-toggler-icon"></span></button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a href="index.php" class="nav-link text-white">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_clinic.php" class="nav-link text-white">Manage Clinic</a></li>
                    <li class="nav-item"><a href="manage_staff.php" class="nav-link text-white">Manage Staff</a></li>
                    <li class="nav-item"><a href="manage_clinic_schedules.php" class="nav-link text-white">Manage
                            Schedules</a></li>
                    <li class="nav-item"><a href="manage_services.php" class="nav-link text-white">Manage Services</a>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= $profilePic ?>" alt="Profile" width="32" height="32" class="rounded-circle me-2">
                        <strong><?= htmlspecialchars($name) ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">View
                                Profile</a></li>
                        <li><a class="dropdown-item" href="manage_clinic_details.php">Update Clinic Info</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="logout.php" id="logoutForm" class="m-0">
                                <button class="dropdown-item text-danger" type="submit" id="logoutBtn">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-hospital"></i>
                    <?= $existingClinic ? "Update Your Clinic" : "Register Your Clinic"; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Clinic Name</label>
                        <input type="text" class="form-control" name="clinic_name"
                            value="<?= $existingClinic['clinic_name'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clinic Address</label>
                        <input type="text" id="clinic_address" class="form-control" name="address"
                            placeholder="Ex: Ozamiz City, Misamis Occidental"
                            value="<?= $existingClinic['address'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contact Info</label>
                        <input type="text" class="form-control" name="contact_info"
                            value="<?= htmlspecialchars($existingClinic['contact_info'] ?? '') ?>" required
                            maxlength="11" pattern="^0\d{10}$"
                            title="Enter a valid 11-digit Philippine mobile number (e.g. 09123456789)" oninput="
                            // Remove anything that isn't a digit
                            this.value = this.value.replace(/[^0-9]/g, '');
                            // Keep max 11 digits
                            if (this.value.length > 11) this.value = this.value.slice(0, 11);
                            ">
                        <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3"><label class="form-label">Latitude</label><input type="text"
                                class="form-control" id="latitude" name="latitude"
                                value="<?= $existingClinic['latitude'] ?? '' ?>" readonly required></div>
                        <div class="col-md-6 mb-3"><label class="form-label">Longitude</label><input type="text"
                                class="form-control" id="longitude" name="longitude"
                                value="<?= $existingClinic['longitude'] ?? '' ?>" readonly required></div>
                    </div>
                    <button type="button" class="btn btn-outline-primary mb-3" onclick="geocodeAddress()"><i
                            class="bi bi-geo-alt-fill"></i> Locate from Address</button>
                    <div class="mb-3">
                        <label class="form-label">Upload Logo</label>
                        <input type="file" class="form-control" name="logo" accept="image/*">

                        <?php if (!empty($existingClinic['logo'])): ?>
                            <div class="mt-2">
                                <label class="form-label">Current Logo:</label><br>
                                <img src="../<?= htmlspecialchars($existingClinic['logo']) ?>" alt="Clinic Logo"
                                    style="max-width: 150px; max-height: 150px; border: 1px solid #ccc; padding: 5px; border-radius: 8px;">
                            </div>
                        <?php endif; ?>


                    </div>
                    <div id="map"></div>
                    <div class="mt-4"><button type="submit"
                            class="btn btn-success"><?= $existingClinic ? "Update Clinic" : "Register Clinic"; ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Profile Information</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <?php
                        $profilePic = !empty($user['profile_picture'])
                            ? "../uploads/profiles/" . htmlspecialchars($user['profile_picture'])
                            : "../assets/default-profile.png"; // fallback kung walay pic
                        ?>
                        <img src="<?= $profilePic ?>" alt="Profile Picture"
                            class="rounded-circle border border-3 border-primary mb-3" width="150" height="150"
                            style="object-fit: cover;">
                        <h5 class="fw-bold text-primary mb-0"><?= htmlspecialchars($user['name']) ?></h5>
                        <small class="text-muted">Clinic Staff</small>
                    </div>

                    <!-- Formal Info Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle">
                            <tbody>
                                <tr>
                                    <th style="width: 30%">Full Name</th>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= htmlspecialchars($user['contact_number'] ?? 'Not provided') ?></td>
                                </tr>
                                <tr>
                                    <th>Address</th>
                                    <td><?= htmlspecialchars($user['address'] ?? 'Not provided') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="text-center mt-4">
                        <button class="btn btn-outline-primary px-4" data-bs-target="#editUserModal"
                            data-bs-toggle="modal" data-bs-dismiss="modal">
                            <i class="bi bi-pencil-square me-1"></i> Edit Profile
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="update_user.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit User Information</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3"><label>Name</label><input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($user['name']) ?>" required></div>
                        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" class="form-control"
                                value="<?= htmlspecialchars($user['contact_number']) ?>" maxlength="11"
                                pattern="^0\d{10}$" title="Enter a valid 11-digit number (e.g. 09123456789)" oninput="
                                // remove any non-digit characters
                                this.value = this.value.replace(/[^0-9]/g, '');
                                // limit to 11 digits only
                                if (this.value.length > 11) this.value = this.value.slice(0, 11);" required>
                            <div class="form-text text-muted">Format: 09XXXXXXXXX (11 digits only)</div>
                        </div>
                        <div class="mb-3"><label>Address</label><input type="text" name="address" class="form-control"
                                value="<?= htmlspecialchars($user['address']) ?>"></div>
                        <div class="mb-3"><label>Profile Picture</label><input type="file" name="profile_picture"
                                class="form-control"></div>

                        <h6 class="text-primary">Change Password (optional)</h6>
                        <!-- Current Password -->
                        <div class="mb-3 position-relative">
                            <label>Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" id="currentPassword"
                                    placeholder="Enter current password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="currentPassword">
                                    <i class="bi bi-eye"></i>
                                </button>

                                <div class="d-flex justify-content-between align-items-center mt-1">
                                    <small class="text-muted">Type your current password to confirm changes.</small>
                                    <a href="../forgot_password.php" class="small">Forgot password?</a>
                                </div>
                            </div>
                        </div>

                        <!-- New Password -->
                        <div class="mb-3 position-relative">
                            <label>New Password</label>
                            <div class="input-group">
                                <input type="password" name="new_password" class="form-control" id="newPassword"
                                    minlength="6" placeholder="Enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="newPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Confirm New Password -->
                        <div class="mb-3 position-relative">
                            <label>Confirm New Password</label>
                            <div class="input-group">
                                <input type="password" name="confirm_password" class="form-control" id="confirmPassword"
                                    minlength="6" placeholder="Re-enter new password">
                                <button class="btn btn-outline-secondary toggle-pass" type="button"
                                    data-target="confirmPassword">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Changes</button></div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <?php if (!empty($_SESSION['update_msg'])): ?>
        <script>
            Swal.fire({
                icon: <?= (stripos($_SESSION['update_msg'], '❌') !== false || stripos($_SESSION['update_msg'], '⚠️') !== false) ? "'error'" : "'success'" ?>,
                title: 'Profile Update',
                text: <?= json_encode($_SESSION['update_msg']) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
        <?php unset($_SESSION['update_msg']); endif; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const defaultLat = <?php echo $existingClinic['latitude'] ?? '8.15'; ?>;
        const defaultLng = <?php echo $existingClinic['longitude'] ?? '123.84'; ?>;
        const map = L.map('map').setView([defaultLat, defaultLng], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        let marker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(map);

        marker.on('dragend', function (e) {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value = pos.lat;
            document.getElementById('longitude').value = pos.lng;
        });

        function geocodeAddress() {
            const addr = document.getElementById('clinic_address').value.trim();
            if (!addr || addr.length < 5) {
                alert("Please enter a valid address.");
                return;
            }
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(addr)}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lon = parseFloat(data[0].lon);
                        marker.setLatLng([lat, lon]);
                        map.setView([lat, lon], 16);
                        document.getElementById('latitude').value = lat;
                        document.getElementById('longitude').value = lon;
                    } else {
                        alert("Address not found. Try being more specific, or drag the pin manually.");
                    }
                })
                .catch(() => alert("Geocoding failed. Check internet connection."));
        }
    </script>

    <?php if (!empty($msg)): ?>
        <script>
            Swal.fire({
                icon: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'error'" : "'success'" ?>,
                title: <?= (stripos($msg, 'fail') !== false || stripos($msg, 'invalid') !== false) ? "'Error!'" : "'Success!'" ?>,
                text: <?= json_encode($msg) ?>,
                confirmButtonColor: '#3085d6'
            });
        </script>
    <?php endif; ?>

    <script>
        document.querySelector("form").addEventListener("submit", function (e) {
            const addressInput = document.getElementById("clinic_address").value.trim().toLowerCase();

            if (!addressInput.includes("misamis occidental")) {
                e.preventDefault(); // stop form submission
                Swal.fire({
                    icon: 'warning',
                    title: 'Invalid Address',
                    text: 'Please include "Misamis Occidental" in the clinic address to confirm it’s within the service area.',
                    confirmButtonColor: '#0d6efd'
                });
                return false;
            }
        });
    </script>

    <script>
        document.getElementById('logoutBtn').addEventListener('click', function (e) {
            e.preventDefault(); // Prevent form from submitting instantly

            Swal.fire({
                title: 'Are you sure you want to logout?',
                text: "You’ll be logged out of your current session.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, logout',
                cancelButtonText: 'No, stay here'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form only if confirmed
                    document.getElementById('logoutForm').submit();
                }
            });
        });
    </script>

    <!-- for the show password -->
    <script>
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const targetId = btn.getAttribute('data-target');
                const input = document.getElementById(targetId);
                const icon = btn.querySelector('i');

                if (input.type === 'password') {
                    input.type = 'text';
                    icon.classList.replace('bi-eye', 'bi-eye-slash');
                } else {
                    input.type = 'password';
                    icon.classList.replace('bi-eye-slash', 'bi-eye');
                }
            });
        });
    </script>

</body>

</html>