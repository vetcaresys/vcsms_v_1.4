<?php
session_start(); // ✅ MUST be at the top before using $_SESSION
include '../../config.php';

// ✅ Check if user is logged in and has a clinic assigned
if (!isset($_SESSION['clinic_id'])) {
    echo "<div class='text-danger text-center mt-4'>⚠️ Session expired or unauthorized access.<br>Please log in again.</div>";
    exit;
}

$clinic_id = $_SESSION['clinic_id'];

// ✅ Fetch the clinic info (name + logo)
$stmtClinic = $pdo->prepare("SELECT clinic_name, logo FROM clinics WHERE clinic_id = ?");
$stmtClinic->execute([$clinic_id]);
$clinic = $stmtClinic->fetch(PDO::FETCH_ASSOC);

$clinic_name = $clinic['clinic_name'] ?? 'VetCareSys Veterinary Clinic';
$clinic_logo = !empty($clinic['logo']) 
    ? "../../uploads/logos/" . htmlspecialchars($clinic['logo']) 
    : "../../assets/logo.png";

// ✅ Fetch record details safely
$record_id = $_GET['id'] ?? null;
if (!$record_id) {
    echo "<div class='text-danger text-center mt-4'>Invalid record ID.</div>";
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        pr.*, 
        COALESCE(p.pet_name, 'Unknown Pet') AS pet_name,
        COALESCE(p.breed, '—') AS breed,
        COALESCE(p.birth_date, '—') AS birth_date,
        COALESCE(u.name, '—') AS owner_name,
        COALESCE(rt.template_name, '—') AS template_name
    FROM pet_records pr
    LEFT JOIN pets p ON pr.pet_id = p.pet_id
    LEFT JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN record_templates rt ON pr.template_id = rt.template_id
    WHERE pr.record_id = ?
");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
    echo "<div class='text-danger text-center mt-4'>Record not found.</div>";
    exit;
}

$data = json_decode($record['data'], true);
?>

<div id="printSection">
  <div class="text-center mb-3">
  <img src="<?= $clinic_logo ?>" alt="Clinic Logo" width="80" height="80" style="object-fit:contain;">
    <h4 class='text-primary fw-bold mt-2 mb-0'>VetCareSys Veterinary Clinic</h4>
    <small class='text-muted'>Official Pet Medical Record</small>
  </div>

  <!-- 🐾 Basic Pet Info -->
  <h5 class="fw-bold mb-2 text-primary">Pet Information</h5>
  <table class="table table-bordered table-sm">
    <tr><th>Pet Name</th><td><?= htmlspecialchars($record['pet_name']) ?></td></tr>
    <tr><th>Owner</th><td><?= htmlspecialchars($record['owner_name']) ?></td></tr>
    <tr><th>Breed</th><td><?= htmlspecialchars($record['breed']) ?></td></tr>
    <tr><th>Birthdate</th><td><?= htmlspecialchars($record['birth_date']) ?></td></tr>
    <tr><th>Record Type</th><td><?= htmlspecialchars($record['template_name']) ?></td></tr>
    <tr><th>Date Recorded</th><td><?= date("M d, Y h:i A", strtotime($record['date_recorded'])) ?></td></tr>
  </table>

  <!-- 🩺 Consultation Details -->
  <h6 class="fw-bold mt-4 text-success">Consultation / Medical Details</h6>
  <table class="table table-striped table-bordered table-sm">
    <thead class="table-light">
      <tr><th>Field</th><th>Value</th></tr>
    </thead>
    <tbody>
      <?php if (!empty($data)): ?>
        <?php foreach ($data as $label => $value): ?>
          <tr>
            <td class="fw-semibold"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $label))) ?></td>
            <td><?= nl2br(htmlspecialchars($value)) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="2" class="text-muted text-center">No consultation data available.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>

  <!-- Signature -->
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

