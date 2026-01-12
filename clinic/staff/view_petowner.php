<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id'])) {
    echo json_encode(["error" => "Unauthorized access"]);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(["error" => "Invalid request"]);
    exit;
}

$user_id = (int) $_GET['id'];

$stmt = $pdo->prepare("
    SELECT name, email, contact_number, address, created_at
    FROM users
    WHERE user_id = ? AND role = 'pet_owner'
");
$stmt->execute([$user_id]);
$owner = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$owner) {
    echo json_encode(["error" => "Pet owner not found"]);
    exit;
}

echo json_encode($owner);
?>