<?php
require '../config.php';

$clinic_id = $_GET['clinic_id'] ?? null;
$date = $_GET['date'] ?? null;

if (!$clinic_id || !$date) {
    echo json_encode([]);
    exit;
}

// Find the day name (e.g., Monday)
$day = date('l', strtotime($date));

// Fetch schedule for that day
$stmt = $pdo->prepare("
    SELECT cs.*, 
           (SELECT COUNT(*) 
            FROM appointments a 
            WHERE a.clinic_id = cs.clinic_id 
              AND DATE(a.appointment_date) = ?
              AND a.status IN ('pending','approved')) as booked_count
    FROM clinic_schedules cs
    WHERE cs.clinic_id = ? AND cs.day_of_week = ? AND cs.status = 'open'
");
$stmt->execute([$date, $clinic_id, $day]);
$schedule = $stmt->fetch(PDO::FETCH_ASSOC);

$slots = [];

if ($schedule) {
    if ($schedule['max_appointments'] == 0) {
        $slots[] = ["label" => "Unlimited slots available", "value" => "unlimited"];
    } else {
        $available = $schedule['max_appointments'] - $schedule['booked_count'];
        if ($available > 0) {
            $slots[] = [
                "label" => $available . " slot(s) available on " . $day,
                "value" => $available
            ];
        }
    }
}

echo json_encode($slots);
