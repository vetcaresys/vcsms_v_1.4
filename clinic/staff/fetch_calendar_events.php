<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$clinic_id = $_SESSION['clinic_id'];
$events = [];

// Fetch appointment + pet + service info
$stmt = $pdo->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_start,
        a.appointment_end,
        a.status,
        p.pet_name,
        s.service_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinic_services s ON a.service_id = s.service_id
    WHERE a.clinic_id = ?
");
$stmt->execute([$clinic_id]);

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Combine date and time for FullCalendar
    $start = date('Y-m-d\TH:i:s', strtotime($row['appointment_date'] . ' ' . $row['appointment_start']));
    $end   = date('Y-m-d\TH:i:s', strtotime($row['appointment_date'] . ' ' . $row['appointment_end']));

    // Color codes per status
    $color = match ($row['status']) {
        'approved'  => '#0d6efd', // blue
        'completed' => '#198754', // green
        'pending'   => '#ffc107', // yellow
        'cancelled' => '#dc3545', // red
        default     => '#6c757d'
    };

    $events[] = [
        'id'    => $row['appointment_id'],
        'title' => $row['pet_name'] . ' - ' . $row['service_name'] . ' (' . ucfirst($row['status']) . ')',
        'start' => $start,
        'end'   => $end,
        'color' => $color,
    ];
}

// Optional: Fetch staff-added notes
$stmt2 = $pdo->prepare("SELECT note_date, note_text FROM calendar_notes WHERE clinic_id = ?");
$stmt2->execute([$clinic_id]);
while ($note = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $events[] = [
        'title' => '📝 ' . $note['note_text'],
        'start' => $note['note_date'],
        'color' => '#6f42c1'
    ];
}

header('Content-Type: application/json');
echo json_encode($events);
