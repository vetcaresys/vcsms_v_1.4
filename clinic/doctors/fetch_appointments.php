<?php
session_start();
include '../../config.php'; // uses $pdo

header('Content-Type: application/json');

// 🗓️ Fetch appointments
$sql = "
    SELECT 
        appointment_id,
        message,
        appointment_date,
        status
    FROM appointments
";

$stmt = $pdo->prepare($sql);
$stmt->execute(); // ✅ You missed this line!
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$data = [];

foreach ($appointments as $a) {
    $dateKey = $a['appointment_date'];
    if (!isset($data[$dateKey])) {
        $data[$dateKey] = [];
    }

    $displayText = "{$a['message']} ({$a['status']})";
    $data[$dateKey][] = $displayText;
}

// ✅ Return as JSON
echo json_encode($data);
?>
