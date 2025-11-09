<?php
require 'config.php';
header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing clinic ID']);
    exit;
}

$id = intval($_GET['id']);

// 🏥 Fetch clinic info
$stmt = $pdo->prepare("SELECT clinic_id, clinic_name, address, contact_info, latitude, longitude, logo 
                       FROM clinics WHERE clinic_id = ?");
$stmt->execute([$id]);
$clinic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$clinic) {
    echo json_encode(['error' => 'Clinic not found']);
    exit;
}

// 🖼 Fix logo path
if (!empty($clinic['logo'])) {
    if (strpos($clinic['logo'], 'uploads/logos/') === false) {
        $clinic['logo'] = 'uploads/logos/' . basename($clinic['logo']);
    }
} else {
    $clinic['logo'] = 'assets/default-clinic.jpg';
}

// 🕒 Fetch schedules
$stmt = $pdo->prepare("
    SELECT day_of_week, open_time, close_time 
    FROM clinic_schedules 
    WHERE clinic_id = ?
    ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$stmt->execute([$id]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 🔄 Group days with same schedule
$grouped = [];
foreach ($rows as $row) {
    $key = $row['open_time'] . '-' . $row['close_time'];
    $grouped[$key][] = $row['day_of_week'];
}

// 📅 Build readable grouped schedule (12-hour format)
$schedules = [];
foreach ($grouped as $time => $days) {
    [$open, $close] = explode('-', $time);
    $openFormatted = date("g:i A", strtotime($open));
    $closeFormatted = date("g:i A", strtotime($close));

    $first = reset($days);
    $last = end($days);
    $dayRange = (count($days) > 1) ? "$first – $last" : $first;

    $schedules[] = [
        'day_range' => $dayRange,
        'open_time' => $openFormatted,
        'close_time' => $closeFormatted
    ];
}

// 🧩 Fetch services
$stmt = $pdo->prepare("SELECT service_name, duration, price FROM clinic_services WHERE clinic_id = ?");
$stmt->execute([$id]);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ✅ Combine all data
echo json_encode([
    'clinic_id' => $clinic['clinic_id'],
    'clinic_name' => $clinic['clinic_name'],
    'address' => $clinic['address'],
    'contact_info' => $clinic['contact_info'],
    'latitude' => $clinic['latitude'],
    'longitude' => $clinic['longitude'],
    'logo' => $clinic['logo'],
    'schedules' => $schedules,
    'services' => $services
]);
?>