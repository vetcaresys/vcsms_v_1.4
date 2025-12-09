<?php
include '../../config.php';
session_start();

$id = $_GET['id'];

$stmt = $pdo->prepare("DELETE FROM doctor_visits WHERE visit_id = ?");
$stmt->execute([$id]);

$_SESSION['visit_success'] = "Visitation deleted successfully!";
header("Location: visitation.php");
exit;
?>
