<?php
session_start();
require '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pet_owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$owner_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT 
        a.appointment_id,
        a.appointment_date,
        a.appointment_start,
        a.appointment_end,
        a.status,
        c.clinic_name,
        cs.service_name,
        p.pet_name,
        s.name AS doctor_name,
        d.specialization
    FROM appointments a
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services cs ON a.service_id = cs.service_id
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN staff s ON a.doctor_id = s.staff_id
    LEFT JOIN doctors d ON s.staff_id = d.staff_id
    WHERE p.owner_id = ?
    ORDER BY a.appointment_date ASC
");
$stmt->execute([$owner_id]);


$appointments = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    // Format time and date for display/JS consumption
    $appointmentDate = date('Y-m-d', strtotime($row['appointment_date']));
    $startTime = $row['appointment_start'] ? date('h:i A', strtotime($row['appointment_start'])) : '';
    $endTime = $row['appointment_end'] ? date('h:i A', strtotime($row['appointment_end'])) : '';
    $timeDisplay = trim($startTime . ($endTime ? ' - ' . $endTime : ''));

    $appointments[] = [
        'id' => $row['appointment_id'],
        'title' => $row['pet_name'] . ' - ' . $row['service_name'],
        'dateKey' => $appointmentDate, // New key for easy JS grouping
        'extendedProps' => [
            'clinic' => $row['clinic_name'],
            'service' => $row['service_name'],
            'pet' => $row['pet_name'],
            'doctor' => $row['doctor_name'] ?? 'Not assigned',
            'specialization' => $row['specialization'] ?? 'N/A',
            'time' => $timeDisplay,
            'status' => ucfirst($row['status'])
        ],
        'color' => match (strtolower($row['status'])) {
            'approved' => '#0d6efd',
            'completed' => '#198754',
            'cancelled' => '#dc3545',
            default => '#ffc107'
        }
    ];
}

header('Content-Type: application/json');
echo json_encode($appointments);
?>