<?php
// manage_pet_details.php
include '../../config.php';
session_start();

$alert = null; // initialize so it's always defined

// Ensure staff is logged in
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../clinic/staff/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Staff selects which pet owner this pet belongs to
    $owner_id = isset($_POST['owner_id']) ? $_POST['owner_id'] : null;

    if ($owner_id === null) {
        echo "<p style='color:red;'>Please select a pet owner.</p>";
    } else {
        $pet_name = trim($_POST['pet_name']);
        $species = trim($_POST['species']);
        $breed = trim($_POST['breed']);
        $birth_date = !empty($_POST['birth_date']) ? $_POST['birth_date'] : null;
        $description = trim($_POST['description']);
        $status = "alive";
        $photo = null;

        // Handle photo upload
        if (!empty($_FILES['photo']['name'])) {
            $target_dir = "../../uploads/pets/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $photo_name = time() . "_" . basename($_FILES["photo"]["name"]);
            $target_file = $target_dir . $photo_name;

            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $target_file)) {
                $photo = $photo_name;
            }
        }

        // Insert into database using PDO
        $sql = "INSERT INTO pets (owner_id, pet_name, species, photo, breed, birth_date, description, status) 
                VALUES (:owner_id, :pet_name, :species, :photo, :breed, :birth_date, :description, :status)";

        $stmt = $pdo->prepare($sql);
        $success = $stmt->execute([
            ':owner_id' => $owner_id,
            ':pet_name' => $pet_name,
            ':species' => $species,
            ':photo' => $photo,
            ':breed' => $breed,
            ':birth_date' => $birth_date,
            ':description' => $description,
            ':status' => $status
        ]);

        if ($success) {
            $alert = ['type' => 'success', 'message' => 'Pet added successfully!'];
        } else {
            $alert = ['type' => 'error', 'message' => 'Error adding pet.'];
        }
    }
}

// -------------------- STAFF INFO --------------------
$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];
$staff_name = htmlspecialchars($_SESSION['name']);

$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name'] ?? '');
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Pet Details - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

    <?php if ($alert): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $alert['type']; ?>',
                title: '<?php echo ucfirst($alert['type']); ?>',
                text: '<?php echo $alert['message']; ?>',
                confirmButtonColor: '#0d6efd'
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_SESSION['alert'])): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $_SESSION['alert']['type']; ?>',
                title: '<?php echo ucfirst($_SESSION['alert']['type']); ?>',
                text: '<?php echo $_SESSION['alert']['message']; ?>',
                confirmButtonColor: '#0d6efd',
                timer: 2000, // auto-close after 2s
                showConfirmButton: false
            });
        </script>
        <?php unset($_SESSION['alert']); ?>
    <?php endif; ?>


    <?php include 'includes/body/navbar.php' ?>

    <!-- Add Pet Button -->
    <div class="container my-5 text-left">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPetModal">
            <i class="bi bi-plus-circle"></i> Add Pet
        </button>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="addPetModal" tabindex="-1" aria-labelledby="addPetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addPetModalLabel"><i class="bi bi-plus-circle"></i> Add Pet</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" enctype="multipart/form-data">

                        <!-- Pet Owner Dropdown -->
                        <div class="mb-3">
                            <label for="owner_id" class="form-label">Pet Owner</label>
                            <select class="form-select" id="owner_id" name="owner_id" required>
                                <option value=""> Select Owner </option>
                                <?php
                                $owners = $pdo->query("SELECT user_id, name FROM users WHERE role = 'pet_owner'")
                                    ->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($owners as $owner) {
                                    echo "<option value='{$owner['user_id']}'>" . htmlspecialchars($owner['name']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <!-- Pet Name -->
                        <div class="mb-3">
                            <label for="pet_name" class="form-label">Pet Name</label>
                            <input type="text" class="form-control" id="pet_name" name="pet_name" required>
                        </div>

                        <!-- Species -->
                        <div class="mb-3">
                            <label for="species" class="form-label">Species</label>
                            <select class="form-select" id="species" name="species" required
                                onchange="toggleSpeciesInput(this)">
                                <option value="">Select Species</option>
                                <option value="Dog">Dog</option>
                                <option value="Cat">Cat</option>
                                <option value="Bird">Bird</option>
                                <option value="Fish">Fish</option>
                                <option value="Others">Others</option>
                            </select>
                            <!-- Hidden input for custom species -->
                            <input type="text" class="form-control mt-2 d-none" id="species_other" name="species_other"
                                placeholder="Enter species">
                        </div>

                        <!-- Breed -->
                        <div class="mb-3">
                            <label for="breed" class="form-label">Breed</label>
                            <select class="form-select" id="breed" name="breed" onchange="toggleBreedInput(this)">
                                <option value="">Select Breed</option>
                                <option value="Labrador">Labrador</option>
                                <option value="Persian">Persian</option>
                                <option value="Parrot">Parrot</option>
                                <option value="Goldfish">Goldfish</option>
                                <option value="Others">Others</option>
                            </select>
                            <!-- Hidden input for custom breed -->
                            <input type="text" class="form-control mt-2 d-none" id="breed_other" name="breed_other"
                                placeholder="Enter breed">
                        </div>

                        <!-- Birth Date -->
                        <div class="mb-3">
                            <label for="birth_date" class="form-label">Birth Date</label>
                            <input type="date" class="form-control" id="birth_date" name="birth_date">
                        </div>

                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>

                        <!-- Photo Upload -->
                        <div class="mb-3">
                            <label for="photo" class="form-label">Photo</label>
                            <input class="form-control" type="file" id="photo" name="photo">
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="btn btn-success w-100">
                            <i class="bi bi-save"></i> Save Pet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5">
        <div class="card shadow-sm p-4">
            <h4 class="text-primary mb-4"><i class="bi bi-list-ul"></i> Registered Pets</h4>
            <div class="table-responsive">
                <table id="petsTable" class="table table-striped table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>Pet ID</th>
                            <th>Owner</th>
                            <th>Pet Name</th>
                            <th>Species</th>
                            <th>Breed</th>
                            <th>Birth Date</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Photo</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch all pets with owner info
                        $sql = "SELECT p.*, u.name AS owner_name 
                            FROM pets p 
                            LEFT JOIN users u ON p.owner_id = u.user_id";
                        $stmt = $pdo->query($sql);
                        $pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

                        foreach ($pets as $pet) {
                            $pet_id = htmlspecialchars($pet['pet_id']);
                            echo "<tr>";
                            echo "<td>{$pet_id}</td>";
                            echo "<td>" . htmlspecialchars($pet['owner_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($pet['pet_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($pet['species']) . "</td>";
                            echo "<td>" . htmlspecialchars($pet['breed']) . "</td>";
                            echo "<td>" . htmlspecialchars($pet['birth_date']) . "</td>";
                            echo "<td>" . htmlspecialchars($pet['description']) . "</td>";
                            echo "<td><span class='badge bg-" .
                                ($pet['status'] === 'alive' ? "success" : "secondary") . "'>" .
                                htmlspecialchars($pet['status']) . "</span></td>";
                            echo "<td>";       
                            if (!empty($pet['photo'])) {
                                echo "<img src='../../uploads/pets/" . htmlspecialchars($pet['photo']) . "' 
                                       alt='Pet Photo' class='img-thumbnail' style='width:60px;height:60px;'>";
                            } else {
                                echo "<span class='text-muted'>No photo</span>";
                            }
                            echo "</td>";

                            // Edit Button
                            echo "<td>
                                <button class='btn btn-warning btn-sm' data-bs-toggle='modal' data-bs-target='#editPet{$pet_id}'>
                                    <i class='bi bi-pencil-square'></i>
                                </button>
                            </td>";
                            echo "</tr>";

                            // Edit Modal
                            echo "
                            <div class='modal fade' id='editPet{$pet_id}' tabindex='-1' aria-labelledby='editPetLabel{$pet_id}' aria-hidden='true'>
                              <div class='modal-dialog modal-lg'>
                                <div class='modal-content shadow'>
                                  <div class='modal-header bg-warning text-white'>
                                    <h5 class='modal-title' id='editPetLabel{$pet_id}'><i class='bi bi-pencil-square'></i> Edit Pet</h5>
                                    <button type='button' class='btn-close btn-close-white' data-bs-dismiss='modal'></button>
                                  </div>
                                  <div class='modal-body'>
                                    <form method='POST' action='update_pet.php' enctype='multipart/form-data'>
                                      <input type='hidden' name='pet_id' value='{$pet_id}'>
                        
                                      <div class='mb-3'>
                                        <label class='form-label'>Pet Name</label>
                                        <input type='text' class='form-control' name='pet_name' value='" . htmlspecialchars($pet['pet_name']) . "' required>
                                      </div>
                        
                                      <div class='mb-3'>
                                        <label class='form-label'>Species</label>
                                        <input type='text' class='form-control' name='species' value='" . htmlspecialchars($pet['species']) . "' required>
                                      </div>
                        
                                      <div class='mb-3'>
                                        <label class='form-label'>Breed</label>
                                        <input type='text' class='form-control' name='breed' value='" . htmlspecialchars($pet['breed']) . "'>
                                      </div>
                        
                                      <div class='mb-3'>
                                        <label class='form-label'>Birth Date</label>
                                        <input type='date' class='form-control' name='birth_date' 
                                            value='" . (!empty($pet['birth_date']) ? date('Y-m-d', strtotime($pet['birth_date'])) : '') . "'>
                                      </div>

                                      <div class='mb-3'>
                                        <label class='form-label'>Description</label>
                                        <textarea class='form-control' name='description' rows='3'>" . htmlspecialchars($pet['description']) . "</textarea>
                                      </div>
                        
                                      <div class='mb-3'>
                                        <label class='form-label'>Photo</label>
                                        <input class='form-control' type='file' name='photo'>
                                        " . (!empty($pet['photo']) ? "<small class='text-muted'>Current: " . htmlspecialchars($pet['photo']) . "</small>" : "") . "
                                      </div>
                        
                                      <button type='submit' class='btn btn-success w-100'>
                                        <i class='bi bi-save'></i> Update Pet
                                      </button>
                                    </form>
                                  </div>
                                </div>
                              </div>
                            </div>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {
            $('#petsTable').DataTable({
                pageLength: 5,
                lengthMenu: [5, 10, 25, 50],
                order: [[0, 'asc']]
            });
        });
    </script>

    <script>
        function toggleSpeciesInput(select) {
            const otherInput = document.getElementById('species_other');
            if (select.value === 'Others') {
                otherInput.classList.remove('d-none');
                otherInput.required = true;
            } else {
                otherInput.classList.add('d-none');
                otherInput.required = false;
                otherInput.value = '';
            }
        }

        function toggleBreedInput(select) {
            const otherInput = document.getElementById('breed_other');
            if (select.value === 'Others') {
                otherInput.classList.remove('d-none');
                otherInput.required = true;
            } else {
                otherInput.classList.add('d-none');
                otherInput.required = false;
                otherInput.value = '';
            }
        }
    </script>

</body>

</html>