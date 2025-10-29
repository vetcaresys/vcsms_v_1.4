<?php
include '../../config.php';

$id = $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  echo "Invalid record ID";
  exit;
}

$stmt = $pdo->prepare("
  SELECT pr.*, rt.template_name, rt.fields AS template_fields, p.pet_name
  FROM pet_records pr
  JOIN record_templates rt ON pr.template_id = rt.template_id
  JOIN pets p ON pr.pet_id = p.pet_id
  WHERE pr.record_id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) {
  echo "<div class='text-danger'>Record not found.</div>";
  exit;
}

$templateFields = json_decode($record['template_fields'], true)['fields'];
$recordData = json_decode($record['data'], true);

echo "<input type='hidden' name='record_id' value='{$record['record_id']}'>";
echo "<input type='hidden' name='template_id' value='{$record['template_id']}'>";

echo "<div class='mb-3'>
        <label class='form-label'>Pet</label>
        <input type='text' class='form-control' value='" . htmlspecialchars($record['pet_name']) . "' readonly>
      </div>";

foreach ($templateFields as $field) {
  $label = $field['label'];
  $type = $field['type'];
  $name = strtolower(str_replace(' ', '_', $label));
  $value = htmlspecialchars($recordData[$name] ?? '');

  echo "<div class='mb-3'>";
  echo "<label class='form-label'>{$label}</label>";

  if ($type === 'textarea') {
    echo "<textarea name='{$name}' class='form-control'>{$value}</textarea>";
  } else {
    echo "<input type='{$type}' name='{$name}' class='form-control' value='{$value}'>";
  }

  echo "</div>";
}
?>
