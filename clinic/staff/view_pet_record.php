<?php
session_start();
include '../../config.php';

// 🔐 Ensure staff or doctor session exists
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    echo "<div class='text-danger text-center mt-4'>⚠️ Unauthorized Access.</div>";
    exit;
}

// Clinic authentication
if (!isset($_SESSION['clinic_id'])) {
    echo "<div class='text-danger text-center mt-4'>⚠️ No clinic assigned.</div>";
    exit;
}

$clinic_id = $_SESSION['clinic_id'];

// Fetch clinic name/logo
$stmtClinic = $pdo->prepare("SELECT clinic_name, logo FROM clinics WHERE clinic_id = ?");
$stmtClinic->execute([$clinic_id]);
$clinic = $stmtClinic->fetch(PDO::FETCH_ASSOC);

$clinic_name = $clinic['clinic_name'] ?? 'VetCareSys Veterinary Clinic';
$clinic_logo = !empty($clinic['logo'])
    ? "../../uploads/logos/" . htmlspecialchars($clinic['logo'])
    : "../../assets/logo.png";

// Validate record ID
$record_id = $_GET['id'] ?? null;
if (!$record_id) {
    echo "<div class='text-danger text-center mt-4'>Invalid record ID.</div>";
    exit;
}

// 🔥 SECURE: fetch record ONLY if it belongs to same clinic
$stmt = $pdo->prepare("
    SELECT 
        pr.*, 
        p.pet_name,
        p.breed,
        p.birth_date,
        u.name AS owner_name,
        rt.template_name
    FROM pet_records pr
    LEFT JOIN pets p ON pr.pet_id = p.pet_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN record_templates rt ON pr.template_id = rt.template_id
    WHERE pr.record_id = ?
      AND pr.clinic_id = ?   -- 🔥 SECURITY FIX
");
$stmt->execute([$record_id, $clinic_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo "<div class='text-danger text-center mt-4'>
            Record not found or you do not have access.
          </div>";
    exit;
}

$data = json_decode($record['data'], true);

// Fetch USED items
$stmtItems = $pdo->prepare("
    SELECT 
        riu.quantity_used,
        i.item_name,
        i.is_consumable,
        i.volume_per_bottle_ml
    FROM record_inventory_usage riu
    JOIN inventory i ON riu.item_id = i.item_id
    WHERE riu.record_id = ?
");
$stmtItems->execute([$record_id]);
$usedItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- HTML CONTENT BELOW (unchanged) -->
<div id="printSection">

    <div class="text-center mb-3">
        <h4 class='text-primary fw-bold mt-2 mb-0'><?= htmlspecialchars($clinic_name) ?></h4>
        <small class='text-muted'>Official Pet Medical Record</small>
    </div>

    <!-- 🐾 BASIC PET INFO -->
    <h5 class="fw-bold mb-2 text-primary">Pet Information</h5>
    <table class="table table-bordered table-sm">
        <tr><th>Pet Name</th><td><?= htmlspecialchars($record['pet_name']) ?></td></tr>
        <tr><th>Owner</th><td><?= htmlspecialchars($record['owner_name']) ?></td></tr>
        <tr><th>Breed</th><td><?= htmlspecialchars($record['breed']) ?></td></tr>
        <tr><th>Birthdate</th><td><?= htmlspecialchars($record['birth_date']) ?></td></tr>
        <tr><th>Record Type</th><td><?= htmlspecialchars($record['template_name']) ?></td></tr>
        <tr><th>Date Recorded</th><td><?= date("M d, Y h:i A", strtotime($record['date_recorded'])) ?></td></tr>
    </table>

    <!-- 🩺 CONSULTATION DETAILS -->
    <h6 class="fw-bold mt-4 text-success">Consultation / Medical Details</h6>
    <table class="table table-striped table-bordered table-sm">
        <thead><tr><th>Field</th><th>Value</th></tr></thead>
        <tbody>
        <?php if (!empty($data)): ?>
            <?php foreach ($data as $label => $value): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $label))) ?></td>
                    <td><?= nl2br(htmlspecialchars($value)) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" class="text-center text-muted">No consultation data available.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- 🧴 MEDICINES USED -->
    <h6 class="fw-bold mt-4 text-info">Medicines / Supplies Used</h6>
    <table class="table table-bordered table-striped table-sm">
        <thead><tr><th>Item Name</th><th>Quantity / Volume Used</th></tr></thead>
        <tbody>
        <?php if (!empty($usedItems)): ?>
            <?php foreach ($usedItems as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['item_name']) ?></td>
                    <td>
                        <?php if ($item['is_consumable']): ?>
                            <?php 
                                $used_ml = floatval($item['quantity_used']); 
                                $bSize = floatval($item['volume_per_bottle_ml']);
                                $bottle_equiv = $bSize > 0 ? round($used_ml / $bSize, 2) : null;
                            ?>
                            <strong><?= $used_ml ?> ml</strong>
                            <?php if ($bottle_equiv): ?>
                                <br><small class="text-muted">(≈ <?= $bottle_equiv ?> bottle<?= $bottle_equiv > 1 ? 's' : '' ?>)</small>
                            <?php endif; ?>
                        <?php else: ?>
                            <strong><?= intval($item['quantity_used']) ?> pcs</strong>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" class="text-center text-muted">No items used for this record.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <div class="text-end mt-4">
        <p><strong>Attending Veterinarian:</strong> ____________________________</p>
        <p>Date: ____________________________</p>
    </div>
</div>

<div class="no-print text-end mt-3">
    <a href="generate_pdf.php?id=<?= $record_id ?>" target="_blank" class="btn btn-outline-danger btn-sm">
        <i class="bi bi-file-earmark-pdf"></i> PDF
    </a>
</div>
