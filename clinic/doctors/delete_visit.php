<?php
include '../../config.php';
session_start();

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    header('Location: ../../login.php');
    exit;
}

$visit_id = $_POST['visit_id'] ?? null;
if ($visit_id) {
    $stmt = $pdo->prepare("DELETE FROM doctor_visits WHERE visit_id = ?");
    $stmt->execute([$visit_id]);
}

header('Location: visitation.php');
exit;
?>
