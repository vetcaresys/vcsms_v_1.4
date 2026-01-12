<?php
include '../../config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Pet ID not provided']);
    exit;
}

$pet_id = intval($_GET['id']);
$stmt = $pdo->prepare("SELECT p.*, u.name AS owner_name FROM pets p LEFT JOIN users u ON p.owner_id = u.user_id WHERE p.pet_id = ?");
$stmt->execute([$pet_id]);
$pet = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pet) {
    echo json_encode(['error' => 'Pet not found']);
    exit;
}

echo json_encode([
    'owner_name' => $pet['owner_name'],
    'pet_name' => $pet['pet_name'],
    'species' => $pet['species'],
    'breed' => $pet['breed'],
    'birth_date' => $pet['birth_date'],
    'description' => $pet['description'],
    'status' => $pet['status'],
    'photo' => $pet['photo']
]);
?>