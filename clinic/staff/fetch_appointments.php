<?php
require '../../config.php';

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Missing appointment ID']);
    exit;
}

$id = $_GET['id'];

$stmt = $pdo->prepare("
    SELECT 
        a.*, 
        p.pet_name, p.species, p.breed, 
        s.service_name, 
        c.clinic_name, c.address AS clinic_address, 
        u.name AS owner_name, u.email AS owner_email, u.contact_number AS owner_contact,
        d.name AS doctor_name,
        ub.name AS updated_by_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinic_services s ON a.service_id = s.service_id
    JOIN clinics c ON a.clinic_id = c.clinic_id
    JOIN users u ON p.owner_id = u.user_id
    LEFT JOIN staff d ON a.doctor_id = d.staff_id
    LEFT JOIN users ub ON a.updated_by = ub.user_id
    WHERE a.appointment_id = ?
");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($data ?: []);
?>