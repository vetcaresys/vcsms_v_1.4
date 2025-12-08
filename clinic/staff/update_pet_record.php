<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    http_response_code(403);
    exit('Unauthorized access');
}

$clinic_id = $_SESSION['clinic_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$record_id = $_POST['record_id'] ?? null;
$template_id = $_POST['template_id'] ?? null;

if (!$record_id || !$template_id) {
    die('Missing parameters');
}

// 🛑 Verify that the record exists AND belongs to this clinic
$check = $pdo->prepare("
    SELECT pr.record_id
    FROM pet_records pr
    WHERE pr.record_id = ?
      AND pr.clinic_id = ?   -- 🔥 Important filter
");
$check->execute([$record_id, $clinic_id]);

if (!$check->fetch()) {
    http_response_code(403);
    exit('Record not found or access denied');
}

// Retrieve template structure
$stmt = $pdo->prepare("SELECT fields FROM record_templates WHERE template_id = ?");
$stmt->execute([$template_id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$template) {
    die("Invalid template.");
}

$fields = json_decode($template['fields'], true)['fields'];

// Collect updated field data
$data = [];
foreach ($fields as $field) {
    $name = strtolower(str_replace(' ', '_', $field['label']));
    $data[$name] = $_POST[$name] ?? '';
}

$jsonData = json_encode($data, JSON_UNESCAPED_UNICODE);

// ✅ Update record with clinic restriction
$stmt = $pdo->prepare("
    UPDATE pet_records 
    SET data = ?, date_recorded = NOW()
    WHERE record_id = ?
      AND clinic_id = ?   -- 🔥 Security FIX
");

if ($stmt->execute([$jsonData, $record_id, $clinic_id])) {
    header('Location: manage_records.php?success=updated');
    exit;
} else {
    echo "Failed to update record.";
}
?>
