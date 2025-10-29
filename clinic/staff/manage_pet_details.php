<?php
// manage_pet_details.php
include '../../config.php';
session_start();

// -------------------- AUTH --------------------
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../clinic/staff/login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];
$staff_name = htmlspecialchars($_SESSION['name']);

// -------------------- STAFF INFO --------------------
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name'] ?? '');
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// -------------------- FETCH OWNERS --------------------
$owners = $pdo->query("SELECT user_id, name FROM users WHERE role = 'pet_owner'")->fetchAll();

// -------------------- HANDLE POST (Add Pet) --------------------
// Use PRG to avoid duplicate form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pet_name'])) {
    // sanitize/collect
    $owner_id = $_POST['owner_id'] ?? null;
    $pet_name = trim($_POST['pet_name'] ?? '');
    $breed = trim($_POST['breed'] ?? '');
    $birthdate = $_POST['birthdate'] ?? null;
    $description = trim($_POST['description'] ?? '');

    // default filename (always defined)
    $file_name = 'default.png';

    // prepare upload directory (absolute)
    $upload_dir = realpath(__DIR__ . '/../../uploads/pets/');
    if ($upload_dir === false) { // if folder doesn't exist yet
        $upload_dir = __DIR__ . '/../../uploads/pets/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
    } else {
        // ensure trailing slash
        $upload_dir = rtrim($upload_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    }

    // Validate and move uploaded file if provided
    if (!empty($_FILES['photo']['name']) && isset($_FILES['photo']['tmp_name']) && is_uploaded_file($_FILES['photo']['tmp_name'])) {
        $original_name = basename($_FILES['photo']['name']);
        $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        // basic validations
        if (in_array($ext, $allowed) && $_FILES['photo']['size'] <= 5 * 1024 * 1024) {
            // create safe unique name
            $safe_base = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', pathinfo($original_name, PATHINFO_FILENAME));
            $file_name = time() . "_" . $safe_base . "." . $ext;
            $target_file = $upload_dir . $file_name;

            if (!move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                // revert to default if move fails
                $file_name = 'default.png';
            }
        } else {
            // invalid file => keep default (optionally set flash message)
            $file_name = 'default.png';
        }
    }

    // Insert into DB (use prepared)
    $stmt = $pdo->prepare("INSERT INTO pets (owner_id, pet_name, breed, birth_date, description, photo) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$owner_id, $pet_name, $breed, $birthdate, $description, $file_name]);

    // Redirect with success to prevent repost on refresh (PRG)
    header("Location: " . $_SERVER['PHP_SELF'] . "?added=1");
    exit;
}

// -------------------- FETCH PETS --------------------
$pets = $pdo->query("
    SELECT p.*, u.name AS owner_name 
    FROM pets p 
    JOIN users u ON p.owner_id = u.user_id
    ORDER BY p.pet_id DESC
")->fetchAll();

// display success if PRG flag present
$success_message = isset($_GET['added']) ? "✅ Pet added successfully!" : "";
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Pet Details - VetCareSys</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">

    <style>
        /* 🌟 Global Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fb;
            color: #2e2e2e;
            line-height: 1.6;
        }

        /* 🧭 Navbar */
        .navbar {
            background: linear-gradient(90deg, #0d6efd, #007bff);
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            letter-spacing: 0.3px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        .navbar-brand img {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 50%;
            background: #fff;
            padding: 3px;
            margin-right: 10px;
            transition: transform 0.2s ease;
        }

        .navbar-brand img:hover {
            transform: scale(1.08);
        }

        /* Links */
        .nav-link {
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #ffc107 !important;
        }

        /* 🧾 Summary Cards */
        .summary-card {
            border: none;
            border-radius: 12px;
            background: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .summary-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }

        .summary-card h5 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .summary-card h2 {
            font-weight: 700;
            font-size: 2rem;
        }

        /* 💼 Tables */
        .table {
            border-radius: 10px;
            overflow: hidden;
            font-size: 0.95rem;
        }

        .table thead {
            background-color: #0d6efd;
            color: white;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        .table tbody tr:hover {
            background-color: #f2f7ff;
        }

        /* 🪄 Buttons */
        .btn {
            border-radius: 8px;
            font-family: 'Inter', sans-serif;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* 🧩 Modals */
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .modal-header {
            border-radius: 15px 15px 0 0;
            background: linear-gradient(90deg, #0d6efd, #007bff);
            color: white;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }

        /* 🧍 Form */
        .form-label {
            font-weight: 600;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        /* ⚡ Sweet alert pop */
        .swal2-popup {
            font-family: 'Inter', sans-serif !important;
            border-radius: 15px !important;
        }

        /* 🌈 Badges */
        .badge {
            font-size: 0.85rem;
            padding: 6px 10px;
            border-radius: 8px;
        }

        /* 🐾 Page Titles */
        h4.text-primary {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            color: #0d6efd !important;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        /* 📦 Footer vibe */
        .container-footer {
            text-align: center;
            margin-top: 50px;
            font-size: 0.9rem;
            color: #777;
        }

        /* 🧭 Datatables */
        div.dataTables_wrapper .dataTables_filter input {
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        div.dataTables_wrapper .dataTables_length select {
            border-radius: 6px;
        }

        /* 🧁 Animations */
        .card,
        .modal-content {
            transition: all 0.25s ease-in-out;
        }
    </style>


</head>


<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a href="index.php" class="nav-link text-white">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_petowner.php" class="nav-link text-white">Manage Client</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_pet_details.php" class="nav-link text-white">Pet Details</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_customer_appointment.php" class="nav-link text-white">Appointments</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_records.php" class="nav-link text-white">Medical Records</a>
                    </li>
                    <li class="nav-item">
                        <a href="manage_inventory.php" class="nav-link text-white">Inventory</a>
                    </li>
                </ul>
                <!-- Profile Dropdown -->
                <div class="dropdown ms-auto">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownUser">
                        <li>
                            <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person"></i> My Profile
                            </a>
                        </li>
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

    <!-- Main Content -->
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-primary">Manage Pet Details</h2>
                <p class="text-muted">Welcome, <?php echo $staff_name; ?>. Add a pet on behalf of a pet owner.</p>

                <?php if (!empty($success_message)): ?>
                    <script>
                        window.addEventListener('DOMContentLoaded', function () {
                            setTimeout(() => {
                                Swal.fire({
                                    title: "Success!",
                                    text: "<?php echo addslashes($success_message); ?>",
                                    icon: "success",
                                    confirmButtonColor: "#198754"
                                });
                            }, 300); // Small delay prevents flicker
                        });
                    </script>
                <?php endif; ?>

                <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addPetModal">
                    Add Pet
                </button>

                <!-- <h3 class="mt-4">All Pets Registered</h3> -->
                <table id="petsTable" class="table table-bordered table-hover align-middle">
                    <thead class="table-primary">
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Owner</th>
                            <th>Pet Name</th>
                            <th>Breed</th>
                            <th>Birthdate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pets as $index => $pet): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <?php
                                    $photoFile = !empty($pet['photo']) ? $pet['photo'] : 'default.png';
                                    $photoPath = "../uploads/pets/" . htmlspecialchars($photoFile);
                                    ?>
                                    <img src="../../uploads/pets/<?= htmlspecialchars($pet['photo'] ?: 'default.png') ?>"
                                        width="50" height="50" class="rounded-circle border bg-light"
                                        onerror="this.src='../../uploads/pets/default.png'">
                                </td>
                                <td><?= htmlspecialchars($pet['owner_name']) ?></td>
                                <td><?= htmlspecialchars($pet['pet_name']) ?></td>
                                <td><?= htmlspecialchars($pet['breed']) ?></td>
                                <td><?= htmlspecialchars($pet['birth_date']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-warning editBtn" data-id="<?= $pet['pet_id'] ?>"
                                        data-name="<?= htmlspecialchars($pet['pet_name']) ?>"
                                        data-breed="<?= htmlspecialchars($pet['breed']) ?>"
                                        data-birthdate="<?= $pet['birth_date'] ?>"
                                        data-description="<?= htmlspecialchars($pet['description']) ?>">
                                        Edit
                                    </button>
                                    <button class="btn btn-sm btn-danger">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <!-- View Profile -->
                <div id="viewProfile">
                    <div class="modal-header">
                        <h5 class="modal-title">My Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3"
                            width="100">
                        <h4><?= $name ?></h4>
                        <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
                        <p><strong>Contact:</strong> <?= htmlspecialchars($staff['contact_number']) ?></p>
                        <p><strong>Role:</strong> <?= htmlspecialchars($staff['role']) ?></p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
                    </div>
                </div>

                <!-- Edit Profile -->
                <div id="editProfile" style="display:none;">
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($staff['name']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($staff['email']) ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= htmlspecialchars($staff['contact_number']) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <!-- <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button> -->
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <!-- Add Pet Modal -->
    <div class="modal fade" id="addPetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Pet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Form fields (same as your current form) -->
                        <div class="mb-3">
                            <label class="form-label">Select Pet Owner</label>
                            <select name="owner_id" class="form-select" required>
                                <option value="">-- Select Owner --</option>
                                <?php foreach ($owners as $owner): ?>
                                    <option value="<?= $owner['user_id'] ?>"><?= htmlspecialchars($owner['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Pet Name</label>
                                <input type="text" name="pet_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Breed</label>
                                <input list="breedList" name="breed" class="form-control"
                                    placeholder="Type or select breed" required>
                                <datalist id="breedList">
                                    <option value="Golden Retriever">
                                    <option value="Labrador Retriever">
                                    <option value="Siberian Husky">
                                    <option value="German Shepherd">
                                    <option value="Persian Cat">
                                    <option value="Ragdoll Cat">
                                    <option value="Pomeranian">
                                    <option value="Shih Tzu">
                                    <option value="Goldfish">
                                    <option value="Parrot">
                                        <!-- you can add as many as you want -->
                                </datalist>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Add Pet</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Pet Modal -->
    <div class="modal fade" id="editPetModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editPetForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Pet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="pet_id" id="editPetId">
                        <div class="mb-3">
                            <label class="form-label">Pet Name</label>
                            <input type="text" name="pet_name" id="editPetName" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Breed</label>
                            <input list="breedList" name="breed" class="form-control" placeholder="Type or select breed"
                                required>
                            <datalist id="breedList">
                                <option value="Golden Retriever">
                                <option value="Labrador Retriever">
                                <option value="Siberian Husky">
                                <option value="German Shepherd">
                                <option value="Persian Cat">
                                <option value="Ragdoll Cat">
                                <option value="Pomeranian">
                                <option value="Shih Tzu">
                                <option value="Goldfish">
                                <option value="Parrot">
                                    <!-- you can add as many as you want -->
                            </datalist>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" id="editBirthdate" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" id="editDescription" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Photo</label>
                            <input type="file" name="photo" id="editPhoto" class="form-control" accept="image/*">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save Changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }

        $(document).ready(function () {
            // Initialize DataTable
            var table = $('#petsTable').DataTable({
                "pageLength": 10,
                "lengthMenu": [5, 10, 25, 50, 100],
                "order": [[0, "asc"]],
                "language": {
                    "search": "Search Pets:",
                    "lengthMenu": "Show _MENU_ entries"
                }
            });

            // Filter by Owner
            $('#ownerFilter').on('change', function () {
                table.column(2).search(this.value).draw();
            });

            // Filter by Breed
            $('#breedFilter').on('change', function () {
                table.column(4).search(this.value).draw();
            });

            // Handle Edit Button Click
            $(document).on('click', '.editBtn', function () {
                const petId = $(this).data('id');
                const name = $(this).data('name');
                const breed = $(this).data('breed');
                const birthdate = $(this).data('birthdate');
                const description = $(this).data('description');

                $('#editPetId').val(petId);
                $('#editPetName').val(name);
                $('#editBreed').val(breed);
                $('#editBirthdate').val(birthdate);
                $('#editDescription').val(description);

                $('#editPetModal').modal('show');
            });

            // SweetAlert for Delete confirmation
            $(document).on('click', '.btn-danger', function (e) {
                e.preventDefault();
                const row = $(this).closest('tr');
                const petName = row.find('td:nth-child(4)').text();

                Swal.fire({
                    title: "Are you sure?",
                    text: "You’re about to delete " + petName + ". This action cannot be undone!",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    confirmButtonText: "Yes, delete it!"
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: "Deleted!",
                            text: petName + " has been removed.",
                            icon: "success",
                            confirmButtonColor: "#198754"
                        });
                        // 👉 You can add actual deletion code here (AJAX or form submit)
                    }
                });
            });
        });
    </script>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
</body>

</html>