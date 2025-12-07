<?php
require '../config.php';

if (!isset($_GET['doctor_id']) || !isset($_GET['clinic_id'])) {
    echo "No schedule found.";
    exit;
}

$doctor_id = $_GET['doctor_id'];
$clinic_id = $_GET['clinic_id'];

$stmt = $pdo->prepare("
    SELECT day_of_week, start_time, end_time
    FROM doctor_visits
    WHERE doctor_id = ? AND clinic_id = ?
    ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$stmt->execute([$doctor_id, $clinic_id]);

$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($schedules)) {
    echo "No visits scheduled for this doctor.";
    exit;
}

$html = "<ul class='list-group'>";
foreach ($schedules as $s) {
    $html .= "
        <li class='list-group-item'>
            <strong>{$s['day_of_week']}</strong> — 
            " . date("h:i A", strtotime($s['start_time'])) . "
            to
            " . date("h:i A", strtotime($s['end_time'])) . "
        </li>
    ";
}
$html .= "</ul>";

echo $html;
?>
