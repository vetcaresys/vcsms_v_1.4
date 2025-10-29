<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

// Fetch available templates
$stmt = $pdo->query("SELECT template_id, template_name FROM record_templates");
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch pets for dropdown
$pets = $pdo->query("SELECT pet_id, pet_name FROM pets ORDER BY pet_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Pet Record - VetCareSys</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
      <h5 class="mb-0"><i class="bi bi-file-earmark-medical"></i> Add Pet Record</h5>
    </div>
    <div class="card-body">
      <form id="recordForm" action="save_pet_record.php" method="POST">
        <div class="mb-3">
          <label class="form-label">Select Pet</label>
          <select name="pet_id" class="form-select" required>
            <option value="">-- Select Pet --</option>
            <?php foreach ($pets as $p): ?>
              <option value="<?= $p['pet_id'] ?>"><?= htmlspecialchars($p['pet_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Form Type</label>
          <select id="templateSelect" name="template_id" class="form-select" required>
            <option value="">-- Select Record Type --</option>
            <?php foreach ($templates as $t): ?>
              <option value="<?= $t['template_id'] ?>"><?= htmlspecialchars($t['template_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div id="dynamicFields"></div>

        <button type="submit" class="btn btn-success mt-3">Save Record</button>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('templateSelect').addEventListener('change', async function() {
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
</body>
</html>
