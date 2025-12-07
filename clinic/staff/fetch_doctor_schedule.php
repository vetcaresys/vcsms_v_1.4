<?php
require '../../config.php';

$doctor_id = $_POST['doctor_id'];
$clinic_id = $_POST['clinic_id'];

$stmt = $pdo->prepare("
    SELECT day_of_week, start_time, end_time 
    FROM doctor_visits
    WHERE doctor_id = ? AND clinic_id = ?
    ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$stmt->execute([$doctor_id, $clinic_id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "<p class='text-danger'>No visitation schedule found.</p>";
    exit;
}

echo "<ul class='list-group'>";
foreach ($rows as $r) {
    echo "
        <li class='list-group-item'>
            <strong>{$r['day_of_week']}</strong> — 
            " . date("h:i A", strtotime($r['start_time'])) . "
            to 
            " . date("h:i A", strtotime($r['end_time'])) . "
        </li>
    ";
}
echo "</ul>";
