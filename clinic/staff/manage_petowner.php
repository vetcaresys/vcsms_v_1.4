<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../clinic/staff/login.php');
    exit;
}

$name = htmlspecialchars($_SESSION['name']);
$clinic_id = $_SESSION['clinic_id'];

// start sa profile
$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

// Get staff info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();
//end sa profile

// ✅ Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $owner_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $contact = trim($_POST['contact']);
    $address = trim($_POST['address']);

    try {
        // Check if email already exists
        $check = $pdo->prepare("SELECT 1 FROM users WHERE email = ?");
        $check->execute([$email]);

        if ($check->rowCount() > 0) {
            $_SESSION['message'] = "❌ Email is already registered.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, contact_number, address) 
                                   VALUES (?, ?, ?, 'pet_owner', ?, ?)");
            if ($stmt->execute([$owner_name, $email, $password, $contact, $address])) {
                $_SESSION['message'] = "✅ Pet owner registered successfully!";
            } else {
                $_SESSION['message'] = "❌ Failed to register.";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "❌ Database error: " . $e->getMessage();
    }

    // Redirect back so refresh won't resubmit form
    header("Location: manage_petowner.php");
    exit;
}

// ✅ Fetch all pet owners
// (Remove `created_at` if wala sa imong table, use name/email/id instead)
$ownersStmt = $pdo->prepare("SELECT * FROM users WHERE role = 'pet_owner' ORDER BY user_id DESC");
$ownersStmt->execute();
$owners = $ownersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Register Pet Owner - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="includes/css/manage_petowner.css">
</head>

<body class="bg-light">

    <?php if (!empty($_SESSION['message'])): ?>
        <script>
            Swal.fire({
                icon: "<?= strpos($_SESSION['message'], '✅') !== false ? 'success' : 'error' ?>",
                title: "<?= strpos($_SESSION['message'], '✅') !== false ? 'Success' : 'Error' ?>",
                text: "<?= $_SESSION['message'] ?>",
                confirmButtonColor: "#3085d6"
            });
        </script>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <?php include 'includes/body/navbar.php' ?>

    <!-- Add Pet Owner Button -->
    <div class="container my-5 text-start">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#registerOwnerModal">
            <i class="bi bi-plus-circle"></i> Add Pet Owner
        </button>
    </div>

    <div class="container my-5">
        <div class="card shadow-sm p-4">
            <h4 class="text-primary mb-4"><i class="bi bi-list-ul"></i> Registered Pet Owner</h4>
            <div class="table-responsive">
                <table id="ownersTable" class="table table-hover">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>ID</th> <!-- hidden -->
                            <th>#</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Address</th>
                            <th>Date Registered</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($owners)): ?>
                            <?php foreach ($owners as $index => $o): ?>
                                <tr>
                                    <td><?= $o['user_id'] ?></td> <!-- real ID -->
                                    <td><?= $index + 1 ?></td>
                                    <td class="fw-semibold"><?= htmlspecialchars($o['name']) ?></td>
                                    <td><?= htmlspecialchars($o['email']) ?></td>
                                    <td><?= htmlspecialchars($o['contact_number']) ?></td>
                                    <td><?= htmlspecialchars($o['address']) ?></td>
                                    <td><span
                                            class="badge bg-light text-dark"><?= htmlspecialchars($o['created_at'] ?? '') ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button class="btn btn-info btn-sm viewOwnerBtn" data-id="<?= $o['user_id'] ?>"
                                                title="View">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#editOwnerModal" data-id="<?= $o['user_id'] ?>"
                                                data-name="<?= htmlspecialchars($o['name']) ?>"
                                                data-email="<?= htmlspecialchars($o['email']) ?>"
                                                data-contact="<?= htmlspecialchars($o['contact_number']) ?>"
                                                data-address="<?= htmlspecialchars($o['address']) ?>" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-3">
                                    No pet owners registered yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Register Pet Owner Modal -->
    <div class="modal fade" id="registerOwnerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content shadow">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Register Pet Owner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if (!empty($message)): ?>
                        <div
                            class="alert <?php echo strpos($message, '✅') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $message; ?>
                        </div>
                    <?php endif; ?>

                    <form id="registerOwnerForm" method="POST" novalidate>

                        <div class="mb-3">
                            <label class="form-label">Full Name
                                <span class="text-danger error-msg"></span>
                            </label>
                            <input type="text" name="name" class="form-control" placeholder="Enter full name" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email Address
                                <span class="text-danger error-msg"></span>
                            </label>
                            <input type="email" name="email" class="form-control" placeholder="Enter email" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Password
                                <span class="text-danger error-msg"></span>
                            </label>
                            <input type="password" name="password" class="form-control" placeholder="Enter password"
                                required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number
                                <span class="text-danger error-msg"></span>
                            </label>
                            <input type="text" name="contact" class="form-control" maxlength="11"
                                placeholder="09xxxxxxxxx" required
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Address
                                <span class="text-danger error-msg"></span>
                            </label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Enter complete address"
                                required></textarea>
                        </div>

                        <button type="submit" class="btn btn-success float-end">
                            <i class="bi bi-save"></i> Register
                        </button>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editOwnerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="editOwnerForm" method="POST" action="edit_petowner.php">
                    <div class="modal-header bg-warning">
                        <h5 class="modal-title">Edit Pet Owner</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" maxlength="11"
                                pattern="\d{11}" title="Contact number must be 11 digits (e.g., 09xxxxxxxxx)" required
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea name="address" id="edit_address" class="form-control"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success float-end">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Pet Owner Modal -->
    <div class="modal fade" id="viewOwnerModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-lines-fill"></i> Pet Owner Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <table class="table table-bordered">
                        <tr>
                            <th width="30%">Full Name</th>
                            <td id="view_name"></td>
                        </tr>
                        <tr>
                            <th>Email</th>
                            <td id="view_email"></td>
                        </tr>
                        <tr>
                            <th>Contact Number</th>
                            <td id="view_contact"></td>
                        </tr>
                        <tr>
                            <th>Address</th>
                            <td id="view_address"></td>
                        </tr>
                        <tr>
                            <th>Date Registered</th>
                            <td id="view_created"></td>
                        </tr>
                    </table>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-light">
        <div class="container text-center small">
            All Rights Reserved. &copy; 2025 VetCareSys
        </div>
    </footer>

    <style>
        .footer-light {
            background: #f8f9fa;
            color: #333;
            padding: 12px 0;
            border-top: 1px solid #ddd;
        }
    </style>

    <script>
        function toggleEdit(isEdit) {
            document.getElementById('viewProfile').style.display = isEdit ? 'none' : 'block';
            document.getElementById('editProfile').style.display = isEdit ? 'block' : 'none';
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        document.querySelectorAll(".viewOwnerBtn").forEach(btn => {
            btn.addEventListener("click", () => {
                let userId = btn.getAttribute("data-id");

                fetch("view_petowner.php?id=" + userId)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            Swal.fire("Error", data.error, "error");
                            return;
                        }

                        document.getElementById("view_name").textContent = data.name;
                        document.getElementById("view_email").textContent = data.email;
                        document.getElementById("view_contact").textContent = data.contact_number;
                        document.getElementById("view_address").textContent = data.address;
                        document.getElementById("view_created").textContent = data.created_at;

                        new bootstrap.Modal(
                            document.getElementById("viewOwnerModal")
                        ).show();
                    });
            });
        });
    </script>

    <script>
        var editModal = document.getElementById('editOwnerModal');
        editModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;

            document.getElementById('edit_user_id').value = button.getAttribute('data-id');
            document.getElementById('edit_name').value = button.getAttribute('data-name');
            document.getElementById('edit_email').value = button.getAttribute('data-email');
            document.getElementById('edit_contact').value = button.getAttribute('data-contact');
            document.getElementById('edit_address').value = button.getAttribute('data-address');
        });
    </script>

    <script>
        $(document).ready(function () {
            $('#ownersTable').DataTable({
                order: [[0, 'desc']], // sort by user_id
                columnDefs: [
                    { targets: 0, visible: false } // hide ID column
                ]
            });
        });
    </script>
    <script>
        document.getElementById("registerOwnerForm").addEventListener("submit", function (e) {
            e.preventDefault(); // stop form for validation check

            let name = this.querySelector("[name='name']").value.trim();
            let email = this.querySelector("[name='email']").value.trim();
            let password = this.querySelector("[name='password']").value.trim();
            let contact = this.querySelector("[name='contact']").value.trim();
            let address = this.querySelector("[name='address']").value.trim();

            if (!name || !email || !password || !contact || !address) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Fields",
                    text: "Please fill in all fields before submitting.",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            // All good → submit the form
            this.submit();
        });


        $('form[action="delete_petowner.php"]').on('submit', function (e) {
            e.preventDefault();
            let form = this;
            Swal.fire({
                title: "Are you sure?",
                text: "This pet owner will be permanently deleted.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>

    <!-- <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
    <script>
        document.getElementById("registerOwnerForm").addEventListener("submit", function (e) {
            e.preventDefault(); // stop form for validation check

            let name = this.querySelector("[name='name']").value.trim();
            let email = this.querySelector("[name='email']").value.trim();
            let password = this.querySelector("[name='password']").value.trim();
            let contact = this.querySelector("[name='contact']").value.trim();
            let address = this.querySelector("[name='address']").value.trim();

            if (!name || !email || !password || !contact || !address) {
                Swal.fire({
                    icon: "warning",
                    title: "Missing Fields",
                    text: "Please fill in all fields before submitting.",
                    confirmButtonColor: "#d33"
                });
                return;
            }

            // Check valid email format
            let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailPattern.test(email)) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Email",
                    text: "Please enter a valid email address.",
                });
                return;
            }

            // Check password length
            if (password.length < 6) {
                Swal.fire({
                    icon: "error",
                    title: "Weak Password",
                    text: "Password must be at least 6 characters long.",
                });
                return;
            }

            // Check contact is numeric and 11 digits (Philippines format)
            if (!/^\d{11}$/.test(contact)) {
                Swal.fire({
                    icon: "error",
                    title: "Invalid Contact",
                    text: "Contact number must be 11 digits (e.g., 09xxxxxxxxx).",
                });
                return;
            }

            // If all good → submit form
            this.submit();
        });
    </script>

    <!-- validation for the "register pet owner" -->
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const form = document.getElementById("registerOwnerForm");

            const validators = {
                name: value => value.length >= 3 || "Full name must be at least 3 characters.",
                email: value => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value) || "Enter a valid email.",
                password: value => value.length >= 6 || "Password must be at least 6 characters.",
                contact: value => /^\d{11}$/.test(value) || "Contact must be exactly 11 digits.",
                address: value => value.length >= 5 || "Please enter a valid address."
            };

            Object.keys(validators).forEach(field => {
                const input = form[field];
                const errorSpan = input.previousElementSibling.querySelector(".error-msg");

                input.addEventListener("input", () => {
                    const result = validators[field](input.value.trim());
                    errorSpan.textContent = result === true ? "" : result;
                });
            });

            form.addEventListener("submit", e => {
                let valid = true;

                Object.keys(validators).forEach(field => {
                    const input = form[field];
                    const errorSpan = input.previousElementSibling.querySelector(".error-msg");
                    const result = validators[field](input.value.trim());

                    if (result !== true) {
                        errorSpan.textContent = result;
                        valid = false;
                    }
                });

                if (!valid) e.preventDefault();
            });
        });
    </script>
</body>

</html>