<?php
// Correct database connection
require "../config.php";  // NOT config11.php

$doctor_id = $_GET['doctor_id'] ?? null;
$clinic_id = $_GET['clinic_id'] ?? null;

if (!$doctor_id || !$clinic_id) {
    echo "<span class='text-danger'>Invalid doctor or clinic selected.</span>";
    exit;
}

$sql = "SELECT day_of_week, start_time, end_time
        FROM doctor_visits
        WHERE doctor_id = ? AND clinic_id = ?
        ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')";

$stmt = $pdo->prepare($sql);   // Use PDO
$stmt->execute([$doctor_id, $clinic_id]);
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($result)) {
    echo "<span class='text-danger'>No schedule available for this doctor at this clinic.</span>";
    exit;
}

echo "<ul class='list-group'>";
foreach ($result as $row) {
    echo "<li class='list-group-item'>
            <strong>{$row['day_of_week']}</strong> — 
            " . date("h:i A", strtotime($row['start_time'])) . "
            to " . date("h:i A", strtotime($row['end_time'])) . "
          </li>";
}
echo "</ul>";
?>
