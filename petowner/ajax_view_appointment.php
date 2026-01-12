<?php
require '../config.php';

$appointment_id = $_GET['appointment_id'] ?? null;

$stmt = $pdo->prepare("
    SELECT 
        a.*, 
        u.name AS owner_name,
        u.email,
        p.pet_name,
        c.clinic_name,
        s.service_name,
        st.name AS doctor_name
    FROM appointments a
    LEFT JOIN users u ON a.owner_id = u.user_id
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services s ON a.service_id = s.service_id
    LEFT JOIN staff st ON a.doctor_id = st.staff_id
    WHERE a.appointment_id = ?
");

$stmt->execute([$appointment_id]);
echo json_encode($stmt->fetch(PDO::FETCH_ASSOC));
?>