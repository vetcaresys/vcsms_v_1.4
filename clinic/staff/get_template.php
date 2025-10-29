<?php
include '../../config.php';
$id = $_GET['id'] ?? null;
if (!$id) exit;
$stmt = $pdo->prepare("SELECT fields FROM record_templates WHERE template_id = ?");
$stmt->execute([$id]);
$template = $stmt->fetch(PDO::FETCH_ASSOC);
echo $template['fields'];
?>
