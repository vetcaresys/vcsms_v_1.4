<?php
include '../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Method Not Allowed');
}

$record_id = $_POST['record_id'];
$template_id = $_POST['template_id'];

if (!$record_id || !$template_id) {
  die('Missing parameters');
}

$stmt = $pdo->prepare("SELECT fields FROM record_templates WHERE template_id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
$fields = json_decode($template['fields'], true)['fields'];

$data = [];
foreach ($fields as $field) {
  $name = strtolower(str_replace(' ', '_', $field['label']));
  $data[$name] = $_POST[$name] ?? '';
}

$jsonData = json_encode($data);

$stmt = $pdo->prepare("UPDATE pet_records SET data = ?, date_recorded = NOW() WHERE record_id = ?");
if ($stmt->execute([$jsonData, $record_id])) {
  header('Location: manage_records.php?success=updated');
} else {
  echo "Failed to update record.";
}
?>
