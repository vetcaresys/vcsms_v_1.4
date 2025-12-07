<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
    echo json_encode([]);
    exit;
}

$clinic_id = $_GET['clinic_id'];

// Fetch doctors assigned to this clinic
$stmt = $pdo->prepare("
    SELECT s.staff_id, s.name, d.specialization
    FROM staff s
    LEFT JOIN doctors d ON d.staff_id = s.staff_id
    WHERE s.role = 'doctor' 
      AND s.clinic_id = ?
");
$stmt->execute([$clinic_id]);

$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($doctors);
?>
