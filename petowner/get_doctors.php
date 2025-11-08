<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
    echo json_encode([]);
    exit;
}

$clinic_id = $_GET['clinic_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.staff_id AS doctor_id, 
            s.name, 
            d.specialization
        FROM staff s
        LEFT JOIN doctors d ON s.staff_id = d.staff_id
        WHERE s.clinic_id = ? AND s.role = 'doctor'
    ");
    $stmt->execute([$clinic_id]);
    $doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($doctors);
} catch (PDOException $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
?>
