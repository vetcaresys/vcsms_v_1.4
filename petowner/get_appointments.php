<?php
session_start();
require '../config.php';

$user_id = $_SESSION['user_id'] ?? 0;

// Fetch appointments of this pet owner
$stmt = $pdo->prepare("
    SELECT 
        a.appointment_id,
        a.status,
        a.appointment_date,
        a.appointment_start,
        a.appointment_end,
        a.updated_at,
        p.pet_name,
        c.clinic_name,
        s.service_name
    FROM appointments a
    JOIN pets p ON a.pet_id = p.pet_id
    JOIN clinics c ON a.clinic_id = c.clinic_id
    JOIN clinic_services s ON a.service_id = s.service_id
    WHERE p.owner_id = ?
    ORDER BY a.appointment_date DESC
");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($appointments as $appt) {
    $statusClass = [
        'pending' => 'warning',
        'approved' => 'primary',
        'completed' => 'success',
        'cancelled' => 'danger'
    ][$appt['status']] ?? 'secondary';
    echo '<tr data-updated="'.$appt['updated_at'].'">';
    echo '<td>'.htmlspecialchars($appt['pet_name']).'</td>';
    echo '<td>'.htmlspecialchars($appt['clinic_name']).'</td>';
    echo '<td>'.htmlspecialchars($appt['service_name']).'</td>';
    echo '<td>'.date("M d, Y", strtotime($appt['appointment_date'])).'<br><small class="text-muted">'.date("h:i A", strtotime($appt['appointment_start'])).' - '.date("h:i A", strtotime($appt['appointment_end'])).'</small></td>';
    echo '<td><span class="badge bg-'.$statusClass.'">'.ucfirst($appt['status']).'</span></td>';
    echo '<td>';
    if(in_array($appt['status'], ['pending','approved'])) {
        echo '<button class="btn btn-sm btn-danger cancel-btn" data-id="'.$appt['appointment_id'].'"><i class="bi bi-x-circle"></i> Cancel</button>';
    } else {
        echo '<span class="text-muted">N/A</span>';
    }
    echo '</td>';
    echo '</tr>';
}
