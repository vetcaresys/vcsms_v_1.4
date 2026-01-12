<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

$staff_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];
$role = $_SESSION['role'];
$name = htmlspecialchars($_SESSION['name']);

// 🖼️ Profile info
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();

// 🐾 Fetch existing pet records
$stmt = $pdo->prepare("
    SELECT 
        pr.record_id, 
        pr.date_recorded, 
        p.pet_name, 
        u.name AS owner_name, 
        rt.template_name, 
        p.birth_date
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    JOIN record_templates rt ON pr.template_id = rt.template_id
    WHERE pr.clinic_id = ?
    ORDER BY pr.date_recorded DESC
");
$stmt->execute([$clinic_id]);
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);


// 🧩 Fetch available templates for modal dropdown
$templates = $pdo->query("SELECT template_id, template_name FROM record_templates")->fetchAll(PDO::FETCH_ASSOC);

// 🐕 Fetch all pets
$stmt = $pdo->prepare("
    SELECT DISTINCT p.pet_id, p.pet_name
    FROM pets p
    JOIN appointments a ON a.pet_id = p.pet_id
    WHERE a.clinic_id = ?
    ORDER BY p.pet_name ASC
");
$stmt->execute([$clinic_id]);
$pets = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Pet Records - VetCareSys</title>
    <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <!-- Google Fonts -->
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="includes/css/manage_records.css">
</head>

<body class="bg-light">

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                title: 'Record Saved!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            }).then(() => {
                // Remove ?success from URL after showing alert
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('success');
                    window.history.replaceState({}, document.title, url.pathname);
                }
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'duplicate'): ?>
        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Duplicate Record',
                text: 'This pet already has a medical record for today. Only one record per pet per day is allowed.',
                confirmButtonColor: '#dc3545'
            }).then(() => {
                // Clean URL
                if (window.history.replaceState) {
                    const url = new URL(window.location);
                    url.searchParams.delete('error');
                    window.history.replaceState({}, document.title, url.pathname);
                }
            });
        </script>
    <?php endif; ?>



    <?php include 'includes/body/navbar.php' ?>

    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-primary"><i class="bi bi-clipboard2-pulse"></i> Manage Pet Records</h2>
                <p class="text-muted">Review and manage pet medical records from your clinic.</p>

                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRecordModal">
                    <i class="bi bi-plus-circle"></i> Add Record
                </button>

                <!-- Record Table -->
                <div class="card-body">

                    <table id="recordsTable" class="table table-striped table-hover table-bordered align-middle">
                        <thead class="table-primary">
                            <tr>
                                <th>Pet Owner</th>
                                <th>Pet Name</th>
                                <th>Age</th>
                                <th>Record Type</th>
                                <!-- <th>Date Recorded</th> -->
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $r):
                                $birth = new DateTime($r['birth_date']);
                                $age = $birth->diff(new DateTime())->y . " yrs";
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['owner_name']) ?></td>
                                    <td><?= htmlspecialchars($r['pet_name']) ?></td>
                                    <td><?= $age ?></td>
                                    <td><?= htmlspecialchars($r['template_name']) ?></td>
                                    <!-- <td><?= date("M d, Y h:i A", strtotime($r['date_recorded'])) ?></td> -->
                                    <td>
                                        <button class="btn btn-primary btn-sm viewRecordBtn me-2"
                                            data-id="<?= $r['record_id'] ?>" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="View Record">
                                            <i class="bi bi-eye"></i>
                                        </button>
                                        <button class="btn btn-warning btn-sm editRecordBtn"
                                            data-id="<?= $r['record_id'] ?>" data-bs-toggle="tooltip"
                                            data-bs-placement="top" title="Edit Record">
                                            <i class="bi bi-pencil-square"></i>
                                        </button>
                                        <script>
                                            // Initialize tooltips
                                            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
                                            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                                                return new bootstrap.Tooltip(tooltipTriggerEl)
                                            })
                                        </script>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Record Modal -->
    <div class="modal fade" id="addRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form action="save_pet_record.php" method="POST">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Add New Pet Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">

                        <!-- PET -->
                        <div class="mb-3">
                            <label class="form-label">Select Pet</label>
                            <select name="pet_id" class="form-select" required>
                                <option value="">Select Pet</option>
                                <?php foreach ($pets as $p): ?>
                                    <option value="<?= $p['pet_id'] ?>"><?= htmlspecialchars($p['pet_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- TEMPLATE -->
                        <div class="mb-3">
                            <label class="form-label">Record Template</label>
                            <select name="template_id" id="templateSelect" class="form-select" required>
                                <option value="">Choose Record Type</option>
                                <?php foreach ($templates as $t): ?>
                                    <option value="<?= $t['template_id'] ?>">
                                        <?= htmlspecialchars($t['template_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="dynamicFields"></div>

                        <hr>
                        <h5 class="mt-4">Medicines / Supplies Used</h5>
                        <p class="text-muted">Select items used during this treatment.</p>

                        <!-- MEDICINES -->
                        <div id="medicineContainer">
                            <div class="row mb-2 medicine-row">
                                <div class="col-md-6">
                                    <label class="form-label">Item</label>

                                    <!-- UPDATED DROPDOWN -->
                                    <select name="item_id[]" class="form-select" onchange="checkConsumable(this)">
                                        <option value="">Select Item</option>

                                        <?php
                                        $items = $pdo->prepare("
                                        SELECT item_id, item_name, quantity, is_consumable, 
                                               remaining_volume_ml, volume_per_bottle_ml
                                        FROM inventory
                                        WHERE clinic_id = ?
                                    ");
                                        $items->execute([$clinic_id]);

                                        foreach ($items as $i):

                                            $label = $i['is_consumable']
                                                ? htmlspecialchars($i['item_name']) . " (Remaining: {$i['remaining_volume_ml']} ml)"
                                                : htmlspecialchars($i['item_name']) . " (Remaining: {$i['quantity']} pcs)";
                                            ?>

                                            <option value="<?= $i['item_id'] ?>"
                                                data-consumable="<?= $i['is_consumable'] ?>"
                                                data-remaining="<?= $i['remaining_volume_ml'] ?>"
                                                data-perbottle="<?= $i['volume_per_bottle_ml'] ?>">
                                                <?= $label ?>
                                            </option>

                                        <?php endforeach; ?>
                                    </select>

                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Quantity Used</label>
                                    <input type="number" name="quantity_used[]" class="form-control qty-input" min="1"
                                        value="1">
                                </div>

                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger removeRow w-100">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addMedicineRow">
                            <i class="bi bi-plus-circle"></i> Add Another Item
                        </button>

                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Record
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <!-- View Record Modal -->
    <div class="modal fade" id="viewRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-eye"></i> View Pet Record</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="recordDetails">
                    <div class="text-center text-muted">Loading record details...</div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Record Modal -->
    <div class="modal fade" id="editRecordModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <form id="editRecordForm" method="POST" action="update_pet_record.php">
                    <div class="modal-header bg-warning text-white">
                        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Pet Record</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="editRecordContent">
                        <div class="text-center text-muted">Loading record data...</div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Use Consumable -->
    <div class="modal fade" id="useConsumableModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="consumableName">Use Consumable</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" name="consumable_item_id" id="consumable_item_id">

                        <div class="mb-3">
                            <label>Remaining Volume (ml)</label>
                            <input type="text" class="form-control" id="consumableRemaining" readonly>
                        </div>

                        <div class="mb-3">
                            <label>Volume to Use (ml)</label>
                            <input type="number" name="use_volume" class="form-control" required min="1">
                        </div>

                        <div class="mb-3">
                            <label>Notes (optional)</label>
                            <textarea name="notes" class="form-control"></textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" onclick="applyConsumableUsage()" class="btn btn-success">
                            <i class="bi bi-save"></i> Save Usage
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function applyConsumableUsage() {
            const item_id = document.getElementById("consumable_item_id").value;
            const used_ml = document.querySelector("input[name='use_volume']").value;

            // find the correct medicine-row
            const rows = document.querySelectorAll('.medicine-row');
            rows.forEach(row => {
                if (row.querySelector("select").value == item_id) {
                    let hidden = row.querySelector("input[name='consumable_used_ml[]']");
                    if (!hidden) {
                        hidden = document.createElement("input");
                        hidden.type = "hidden";
                        hidden.name = "consumable_used_ml[]";
                        row.appendChild(hidden);
                    }
                    hidden.value = used_ml;
                }
            });

            bootstrap.Modal.getInstance(document.getElementById("useConsumableModal")).hide();
        }
    </script>

    <script>
        function checkConsumable(selectElement) {

            const option = selectElement.selectedOptions[0];
            const isConsumable = option.dataset.consumable;

            if (isConsumable == "1") {

                // Show ml modal
                document.getElementById("consumable_item_id").value = option.value;
                document.getElementById("consumableRemaining").value = option.dataset.remaining;
                document.getElementById("consumableName").innerText = option.text;

                new bootstrap.Modal(document.getElementById("useConsumableModal")).show();

                // Disable normal quantity field
                selectElement.closest(".medicine-row")
                    .querySelector("input[name='quantity_used[]']")
                    .disabled = true;

            } else {

                // Non consumable → allow quantity input
                selectElement.closest(".medicine-row")
                    .querySelector("input[name='quantity_used[]']")
                    .disabled = false;
            }
        }
    </script>

    <script>
        $(document).ready(function () {
            $('#recordsTable').DataTable({
                "pageLength": 5,
                "lengthMenu": [5, 10, 25, 50, 100],
                "ordering": false, // Use PHP ordering (latest first)
                "language": {
                    "search": "Search Records:",
                    "lengthMenu": "Show _MENU_ entries"
                }
            });
        });
    </script>

    <script>
        document.querySelectorAll('.editRecordBtn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const modalBody = document.getElementById('editRecordContent');
                modalBody.innerHTML = "<div class='text-center text-muted'>Loading...</div>";
                const modal = new bootstrap.Modal(document.getElementById('editRecordModal'));
                modal.show();

                try {
                    const response = await fetch('edit_pet_record.php?id=' + id);
                    const html = await response.text();
                    modalBody.innerHTML = html;
                } catch (err) {
                    modalBody.innerHTML = "<div class='text-danger'>Failed to load record data.</div>";
                }
            });
        });
    </script>

    <script>
        document.querySelectorAll('.viewRecordBtn').forEach(btn => {
            btn.addEventListener('click', async function () {
                const id = this.dataset.id;
                const modalBody = document.getElementById('recordDetails');
                modalBody.innerHTML = "<div class='text-center text-muted'>Loading...</div>";

                const modal = new bootstrap.Modal(document.getElementById('viewRecordModal'));
                modal.show();

                try {
                    const response = await fetch('view_pet_record.php?id=' + id);
                    const html = await response.text();
                    modalBody.innerHTML = html;
                } catch (error) {
                    modalBody.innerHTML = "<div class='text-danger'>Failed to load record details.</div>";
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('templateSelect').addEventListener('change', async function () {
            const templateId = this.value;
            const container = document.getElementById('dynamicFields');
            container.innerHTML = '';

            if (!templateId) return;

            const res = await fetch('get_template.php?id=' + templateId);
            const data = await res.json();

            data.fields.forEach(field => {
                const wrapper = document.createElement('div');
                wrapper.classList.add('mb-3');
                wrapper.innerHTML = `
      <label class="form-label">${field.label}</label>
      ${field.type === 'textarea'
                        ? `<textarea name="${field.label.toLowerCase().replace(/ /g, '_')}" class="form-control"></textarea>`
                        : `<input type="${field.type}" name="${field.label.toLowerCase().replace(/ /g, '_')}" class="form-control">`}
    `;
                container.appendChild(wrapper);
            });
        });
    </script>

    <?php if (isset($_GET['success'])): ?>
        <script>
            Swal.fire({
                title: 'Record Saved!',
                icon: 'success',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>

    <script>
        const tableRows = Array.from(document.querySelectorAll('#recordsTable tbody tr'));
        let currentEntries = 10; // default

        function updateTableDisplay() {
            const filter = document.getElementById('recordSearch').value.toLowerCase();
            const visibleRows = tableRows.filter(row => row.innerText.toLowerCase().includes(filter));

            visibleRows.forEach((row, i) => {
                row.style.display = i < currentEntries ? '' : 'none';
            });
        }

        document.getElementById('entriesSelect').addEventListener('change', function () {
            currentEntries = parseInt(this.value);
            updateTableDisplay();
        });

        document.getElementById('recordSearch').addEventListener('keyup', function () {
            updateTableDisplay();
        });

        document.getElementById('resetSearch').addEventListener('click', function () {
            document.getElementById('recordSearch').value = '';
            updateTableDisplay();
        });

        // Initialize table display
        updateTableDisplay();
    </script>

    <script>
        document.getElementById('recordSearch').addEventListener('keyup', function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll('#recordsTable tbody tr');

            rows.forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });
        });

        document.getElementById('resetSearch').addEventListener('click', function () {
            document.getElementById('recordSearch').value = '';
            const rows = document.querySelectorAll('#recordsTable tbody tr');
            rows.forEach(row => row.style.display = '');
        });


        let searchTimeout;
        document.getElementById('recordSearch').addEventListener('keyup', function () {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const filter = this.value.toLowerCase();
                const rows = document.querySelectorAll('#recordsTable tbody tr');
                rows.forEach(row => {
                    const text = row.innerText.toLowerCase();
                    row.style.display = text.includes(filter) ? '' : 'none';
                });
            }, 300);
        });
    </script>

    <script>
        function toggleEdit(showEdit) {
            const viewProfile = document.getElementById('viewProfile');
            const editProfile = document.getElementById('editProfile');

            if (showEdit) {
                viewProfile.style.display = 'none';
                editProfile.style.display = 'block';
            } else {
                viewProfile.style.display = 'block';
                editProfile.style.display = 'none';
            }
        }
    </script>

    <script>
        document.getElementById('addMedicineRow').addEventListener('click', function () {
            const container = document.getElementById('medicineContainer');
            const newRow = container.querySelector('.medicine-row').cloneNode(true);
            newRow.querySelectorAll('input, select').forEach(el => el.value = '');
            container.appendChild(newRow);
        });

        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('removeRow')) {
                e.target.closest('.medicine-row').remove();
            }
        });
    </script>
</body>

</html>