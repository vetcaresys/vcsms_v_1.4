<?php
include '../../config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT is_consumable, remaining_volume_ml, item_name FROM inventory WHERE item_id = ?");
$stmt->execute([$_GET['id']]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'is_consumable' => (bool)$item['is_consumable'],
    'remaining_volume_ml' => $item['remaining_volume_ml'],
    'item_name' => $item['item_name']
]);
?>
