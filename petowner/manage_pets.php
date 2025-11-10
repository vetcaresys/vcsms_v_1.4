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

    $stmt = $pdo->prepare("INSERT INTO pets (owner_id, pet_name, photo, breed, birth_date, description, status, date_of_death) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$user_id, $pet_name, $photo_path, $breed, $birth_date, $description, $status, $date_of_death]);

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
        $stmt = $pdo->prepare("UPDATE pets SET pet_name=?, breed=?, birth_date=?, description=?, status=?, date_of_death=?, photo=? WHERE pet_id=? AND owner_id=?");
        $stmt->execute([$pet_name, $breed, $birth_date, $description, $status, $date_of_death, $photo_path, $pet_id, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE pets SET pet_name=?, breed=?, birth_date=?, description=?, status=?, date_of_death=? WHERE pet_id=? AND owner_id=?");
        $stmt->execute([$pet_name, $breed, $birth_date, $description, $status, $date_of_death, $pet_id, $user_id]);
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

$profilePic = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.png';
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

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="css/manage_pets.css">

</head>

<body class="bg-light">

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
        <div class="container">

            <a class="navbar-brand fw-bold" href="index.php">VetCareSys</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="topNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a href="index.php" class="nav-link">Dashboard</a></li>
                    <li class="nav-item"><a href="manage_pets.php" class="nav-link">Manage Pets</a></li>
                    <li class="nav-item"><a href="book_appointment.php" class="nav-link">Book Appointment</a></li>
                </ul>
                 <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link position-relative" href="#" id="notifDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-bell-fill" style="font-size: 1.35rem;"></i>
                            <span id="notif_count"
                                class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                style="font-size: 0.65rem; padding: 3px 6px;">
                            </span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end p-2"
                            style="width: 320px; max-height: 400px;" id="notif_list_container">
                            
                            <li class="d-flex justify-content-between align-items-center mb-2 px-2">
                                <h6 class="mb-0">Notifications</h6>
                                <button id="mark_all_btn" class="btn btn-link btn-sm p-0 text-decoration-none" style="font-size: 0.8rem;" disabled>
                                    Mark all as read
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>

                            <div id="notif_list" style="max-height: 350px; overflow-y: auto;">
                                <li class="text-center text-muted">Loading...</li>
                            </div>
                        </ul>
                    </li>
                </ul>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
                        id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2"
                            width="35" height="35">
                        <strong><?= $name ?></strong>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow text-center text-lg-start"
                        aria-labelledby="dropdownUser">
                        <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                                <i class="bi bi-person"></i> Profile</a></li>
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

    <!-- Main content -->
    <div class="container mt-4">
        <?php if (!empty($msg))
            echo $msg; ?>

        <div class="d-flex justify-content-end mb-3">
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addPetModal">
                <i class="bi bi-plus-circle"></i> Add Pet
            </button>
        </div>

        <div class="modal fade" id="addPetModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Add New Pet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Pet Name</label>
                                <input type="text" name="pet_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Breed</label>
                                <select name="breed" class="form-select" id="breedSelect" required
                                    onchange="toggleOtherBreed()">
                                    <option value="">Select Breed</option>
                                    <option value="Aspin">Aspin (Asong Pinoy)</option>
                                    <option value="Labrador Retriever">Labrador Retriever</option>
                                    <option value="German Shepherd">German Shepherd</option>
                                    <option value="Golden Retriever">Golden Retriever</option>
                                    <option value="Shih Tzu">Shih Tzu</option>
                                    <option value="Pomeranian">Pomeranian</option>
                                    <option value="Chihuahua">Chihuahua</option>
                                    <option value="Siberian Husky">Siberian Husky</option>
                                    <option value="Pug">Pug</option>
                                    <option value="Beagle">Beagle</option>
                                    <option value="Dachshund">Dachshund</option>
                                    <option value="Rottweiler">Rottweiler</option>
                                    <option value="Pitbull">Pitbull</option>
                                    <option value="Bulldog">Bulldog</option>
                                    <option value="Mixed Breed">Mixed Breed</option>
                                    <option value="Other">Other (specify)</option>
                                </select>
                            </div>

                            <div class="col-md-6" id="otherBreedInput" style="display: none;">
                                <label class="form-label">Specify Breed</label>
                                <input type="text" name="other_breed" class="form-control" placeholder="Enter breed">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="add_pet" class="btn btn-success">
                                <i class="bi bi-save"></i> Save Pet
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pets List -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-list-ul"></i> Your Pets</h5>
            </div>
            <div class="card-body">
                <?php if (count($pets) > 0): ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="searchInput" class="form-control"
                                placeholder="🔍 Search pets by name, breed, or description...">
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">-- Filter by Status --</option>
                                <option value="alive">Alive</option>
                                <option value="deceased">Deceased</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="petsTable" class="table table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Photo</th>
                                    <th>Name</th>
                                    <th>Breed</th>
                                    <th>Age</th>
                                    <th>Status</th>
                                    <th>Description</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pets as $pet):
                                    $birth_date = new DateTime($pet['birth_date']);
                                    $today = new DateTime();
                                    $age = $today->diff($birth_date)->y . " years";
                                    ?>
                                    <tr>
                                        <td>
                                            <?php if ($pet['photo']): ?>
                                                <img src="../uploads/pets/<?= htmlspecialchars($pet['photo'] ?: 'default.png') ?>"
                                                    width="80" height="80" class="rounded border bg-light"
                                                    onerror="this.src='../uploads/pets/default.png'">
                                            <?php else: ?>
                                                <span class="text-muted">No photo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($pet['pet_name']); ?></td>
                                        <td><?= htmlspecialchars($pet['breed']); ?></td>
                                        <td><?= $age; ?></td>
                                        <td><?= htmlspecialchars($pet['description']); ?></td>
                                        <td>
                                            <?= htmlspecialchars($pet['status']); ?>
                                            <?php if ($pet['status'] === 'deceased' && $pet['date_of_death']): ?>
                                                <br><small>Died: <?= htmlspecialchars($pet['date_of_death']); ?></small>
                                            <?php endif; ?>
                                        </td>

                                        <td class="action-cell">
                                            <div class="action-buttons">
                                                <button class="btn-action edit" data-bs-toggle="modal"
                                                    data-bs-target="#editPetModal<?= $pet['pet_id']; ?>">
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <button class="btn-action delete"
                                                    onclick="return confirmDelete(event, <?= $pet['pet_id']; ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">You haven’t added any pets yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php foreach ($pets as $pet): ?>
        <div class="modal fade" id="editPetModal<?= $pet['pet_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Pet</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-3">
                            <input type="hidden" name="pet_id" value="<?= $pet['pet_id']; ?>">

                            <div class="col-md-6">
                                <label class="form-label">Pet Name</label>
                                <input type="text" name="pet_name" class="form-control"
                                    value="<?= htmlspecialchars($pet['pet_name']); ?>" required>
                            </div>
                            <?php
                            $breeds = [
                                "Aspin",
                                "Labrador Retriever",
                                "German Shepherd",
                                "Golden Retriever",
                                "Shih Tzu",
                                "Pomeranian",
                                "Chihuahua",
                                "Siberian Husky",
                                "Pug",
                                "Beagle",
                                "Dachshund",
                                "Rottweiler",
                                "Pitbull",
                                "Bulldog",
                                "Mixed Breed"
                            ];

                            $currentBreed = $pet['breed'];
                            $isOther = !in_array($currentBreed, $breeds);
                            ?>

                            <div class="col-md-6">
                                <label class="form-label">Breed</label>
                                <select name="breed" class="form-select breedSelect" required
                                    onchange="toggleOtherBreedEdit(this)">
                                    <option value="">Select Breed</option>

                                    <?php foreach ($breeds as $b): ?>
                                        <option value="<?= $b ?>" <?= $currentBreed == $b ? 'selected' : '' ?>>
                                            <?= $b ?>
                                        </option>
                                    <?php endforeach; ?>

                                    <option value="Other" <?= $isOther ? 'selected' : '' ?>>Other (specify)</option>
                                </select>
                            </div>

                            <div class="col-md-6 otherBreedInput" style="display: <?= $isOther ? 'block' : 'none' ?>;">
                                <label class="form-label">Specify Breed</label>
                                <input type="text" name="other_breed" class="form-control"
                                    value="<?= $isOther ? htmlspecialchars($currentBreed) : '' ?>" placeholder="Enter breed"
                                    <?= $isOther ? 'required' : '' ?>>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Birth Date</label>
                                <input type="date" name="birth_date" class="form-control"
                                    value="<?= htmlspecialchars($pet['birth_date']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Upload New Photo</label>
                                <input type="file" name="photo" class="form-control" accept="image/*">
                                <br>
                                <?php if ($pet['photo']): ?>
                                    <small>Current: <img src="../uploads/pets/<?= $pet['photo']; ?>" width="50"></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" required onchange="toggleDeathDate(this)">
                                    <option value="alive">Alive</option>
                                    <option value="deceased">Deceased</option>
                                </select>
                            </div>
                            <div class="col-md-6 deceased-date" style="display:none;">
                                <label class="form-label">Date of Death</label>
                                <input type="date" name="date_of_death" class="form-control">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="3"
                                    required><?= htmlspecialchars($pet['description']); ?></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" name="update_pet" class="btn btn-success">
                                <i class="bi bi-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">

                <!-- View Profile Section -->
                <div id="viewProfile">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">My Profile</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body text-center">
                        <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile Picture"
                            class="rounded-circle shadow-sm mb-3" width="110" height="110"
                            style="object-fit: cover; border: 3px solid #0d6efd;">
                        <h4 class="fw-bold mb-3"><?= htmlspecialchars($user['name']) ?></h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped align-middle">
                                <tbody>
                                    <tr>
                                        <th style="width: 35%;">Email</th>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact</th>
                                        <td><?= htmlspecialchars($contact) ?></td>
                                    </tr>
                                    <tr>
                                        <th>Address</th>
                                        <td><?= htmlspecialchars($user['address'] ?? 'N/A') ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button class="btn btn-primary" onclick="toggleEdit(true)">
                            <i class="bi bi-pencil-square"></i> Edit Profile
                        </button>
                    </div>
                </div>

                <!-- Edit Profile Section -->
                <div id="editProfile" style="display:none;">
                    <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Edit Profile</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>

                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-control"
                                    value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact_number" class="form-control"
                                    value="<?= htmlspecialchars($user['contact_number']) ?>" inputmode="numeric"
                                    maxlength="11" pattern="^09\d{9}$" placeholder="e.g., 09123456789"
                                    oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                                <div class="invalid-feedback">
                                    Contact must be 11 digits and start with 09.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address"
                                    class="form-control"><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>
                                <input type="file" name="profile_picture" class="form-control">
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Edit Profile
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }

        function toggleDeathDate(select) {
            const deathDateField = select.closest('.row').querySelector('.deceased-date');
            if (select.value === 'deceased') {
                deathDateField.style.display = 'block';
            } else {
                deathDateField.style.display = 'none';
                deathDateField.querySelector('input').value = '';
            }
        }
    </script>
    <script>
        function confirmDelete(event, petId) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '?delete=' + petId;
                }
            });
        }
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.getElementById("searchInput");
            const statusFilter = document.getElementById("statusFilter");
            const table = document.getElementById("petsTable");
            const rows = table.getElementsByTagName("tr");

            function filterTable() {
                const searchTerm = searchInput.value.toLowerCase();
                const statusValue = statusFilter.value.toLowerCase();

                for (let i = 1; i < rows.length; i++) { // skip header row
                    const cells = rows[i].getElementsByTagName("td");
                    if (cells.length === 0) continue;

                    const name = cells[1].innerText.toLowerCase();
                    const breed = cells[2].innerText.toLowerCase();
                    const desc = cells[4]?.innerText.toLowerCase() || "";
                    const status = cells[5].innerText.toLowerCase();

                    let matchesSearch = name.includes(searchTerm) || breed.includes(searchTerm) || desc.includes(searchTerm);
                    let matchesStatus = statusValue === "" || status.includes(statusValue);

                    if (matchesSearch && matchesStatus) {
                        rows[i].style.display = "";
                    } else {
                        rows[i].style.display = "none";
                    }
                }
            }

            searchInput.addEventListener("keyup", filterTable);
            statusFilter.addEventListener("change", filterTable);
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

    <!-- others selection in the breed part -->
    <script>
        function toggleOtherBreed() {
            const breedSelect = document.getElementById("breedSelect");
            const otherInput = document.getElementById("otherBreedInput");

            if (breedSelect.value === "Other") {
                otherInput.style.display = "block";
                otherInput.querySelector("input").required = true;
            } else {
                otherInput.style.display = "none";
                otherInput.querySelector("input").required = false;
                otherInput.querySelector("input").value = "";
            }
        }
    </script>

    <script>
        function toggleOtherBreedEdit(select) {
            const modalBody = select.closest('.modal-body');
            const otherContainer = modalBody.querySelector('.otherBreedInput');
            const input = otherContainer.querySelector('input');

            if (select.value === "Other") {
                otherContainer.style.display = "block";
                input.required = true;
            } else {
                otherContainer.style.display = "none";
                input.required = false;
                input.value = ""; // clears previously typed text kay nipili na siya ug existing breed
            }
        }
    </script>
<script>
         document.addEventListener("DOMContentLoaded", function() {
        loadAdminNotifications();
        
        // Load when bell icon is clicked
        document.getElementById("notifDropdown").addEventListener("click", loadAdminNotifications);
        
        // 💡 NEW: Listener for Mark All button
        document.getElementById("mark_all_btn").addEventListener("click", markAllAsRead);
    });

    function loadAdminNotifications() {
        fetch(`../petowner_fetch_notifications.php?user_id=` + `<?=($user_id) ?>`)
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

                data.forEach(n => {
                    if (n.status === "unread") unreadCount++;

                    list.innerHTML += `
                        <li>
                            <a href="${n.link ?? '#'}" class="dropdown-item d-flex justify-content-between align-items-start notif-item ${n.status === "unread" ? 'bg-light' : ''}"
                            data-id="${n.notif_id}">
                                <div>
                                    <strong>${n.subject}</strong><br>
                                    <small class="text-muted">${n.message}</small>
                                </div>
                                ${n.status === "unread" ? `<span class="badge bg-danger ms-2">New</span>` : ""}
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
                fetch(`../petowner_mark_all_as_read.php?user_id=` + `<?=($user_id) ?>`, { method: 'POST' })
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
    document.addEventListener("click", function(e) {
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