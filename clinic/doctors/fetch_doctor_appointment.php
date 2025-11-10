<?php
session_start();
require '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    exit;
}

$doctor_id = $_SESSION['staff_id'];
$clinic_id = $_SESSION['clinic_id'];

try {
    // 🔍 Fetch all appointments where this doctor is involved
    $stmt = $pdo->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.appointment_time,
            a.status,
            p.pet_name,
            u.name AS owner_name,
            s.service_name,
            c.clinic_name
        FROM appointments a
        JOIN pets p ON a.pet_id = p.pet_id
        JOIN users u ON p.user_id = u.user_id
        JOIN services s ON a.service_id = s.service_id
        JOIN clinics c ON a.clinic_id = c.clinic_id
        WHERE a.doctor_id = :doctor_id
           OR (a.clinic_id = :clinic_id AND a.status IN ('approved', 'pending'))
        ORDER BY a.appointment_date DESC
    ");
    $stmt->execute(['doctor_id' => $doctor_id, 'clinic_id' => $clinic_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $events = [];
    foreach ($appointments as $a) {
        $events[] = [
            'id' => $a['appointment_id'],
            'title' => $a['pet_name'] . " - " . $a['service_name'],
            'start' => $a['appointment_date'],
            'extendedProps' => [
                'time' => $a['appointment_time'],
                'clinic' => $a['clinic_name'],
                'status' => $a['status'],
                'owner' => $a['owner_name'],
                'service' => $a['service_name'],
            ]
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($events);
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
