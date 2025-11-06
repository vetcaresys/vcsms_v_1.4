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
        p.pet_name
    FROM appointments a
    LEFT JOIN clinics c ON a.clinic_id = c.clinic_id
    LEFT JOIN clinic_services cs ON a.service_id = cs.service_id
    LEFT JOIN pets p ON a.pet_id = p.pet_id
    WHERE a.owner_id = ?
    ORDER BY a.appointment_date ASC
");
$stmt->execute([$owner_id]);
$appointments = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $startTime = $row['appointment_start'] ? date('H:i', strtotime($row['appointment_start'])) : '';
    $endTime = $row['appointment_end'] ? date('H:i', strtotime($row['appointment_end'])) : '';

    $appointments[] = [
        'id' => $row['appointment_id'],
        'title' => $row['pet_name'] . ' - ' . $row['service_name'],
        'start' => $row['appointment_date'],
        'extendedProps' => [
            'clinic' => $row['clinic_name'],
            'service' => $row['service_name'],
            'pet' => $row['pet_name'],
            'time' => trim($startTime . ' - ' . $endTime),
            'status' => $row['status']
        ],
        'color' => match($row['status']) {
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
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css' rel='stylesheet' />
    <style>
        #calendar {
            max-width: 100%;
            margin: 0 auto;
        }

        .fc-daygrid-event {
            font-size: 0.85rem;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card shadow-sm p-3">
                <h4 class="text-primary fw-bold mb-3">My Appointment Calendar</h4>
                <div id="calendar"></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm p-3">
                <h5 class="text-primary fw-bold mb-3">Appointment Details</h5>
                <div id="appointmentDetails" class="text-muted">
                    <em>Select a date to view details.</em>
                </div>
            </div>
        </div>
    </div>


    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');
            var detailsEl = document.getElementById('appointmentDetails');

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                themeSystem: 'bootstrap5',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: ''
                },
                events: 'fetch_appointments.php',
                eventColor: '#dc3545', // red for appointments
                eventClick: function (info) {
                    const event = info.event;
                    const date = new Date(event.start).toLocaleDateString();
                    detailsEl.innerHTML = `
                <div class="border p-3 rounded bg-light">
                    <h6 class="fw-bold text-danger mb-2">${event.title}</h6>
                    <p><strong>Date:</strong> ${date}</p>
                    <p><strong>Status:</strong> ${event.extendedProps.status}</p>
                </div>
            `;
                },
                dateClick: function (info) {
                    const clickedDate = info.dateStr;
                    const eventsOnDay = calendar.getEvents().filter(e => e.startStr === clickedDate);
                    if (eventsOnDay.length === 0) {
                        detailsEl.innerHTML = `
                    <div class="alert alert-info">No appointments on ${clickedDate}.</div>
                `;
                    } else {
                        let html = `<h6 class="fw-bold text-primary mb-2">Appointments on ${clickedDate}:</h6>`;
                        eventsOnDay.forEach(e => {
                            html += `
                        <div class="border p-2 rounded mb-2">
                            <strong>${e.title}</strong><br>
                            Status: <span class="badge bg-${getStatusColor(e.extendedProps.status)}">${e.extendedProps.status}</span>
                        </div>`;
                        });
                        detailsEl.innerHTML = html;
                    }
                }
            });

            calendar.render();

            function getStatusColor(status) {
                switch (status.toLowerCase()) {
                    case 'pending': return 'warning';
                    case 'confirmed': return 'primary';
                    case 'completed': return 'success';
                    case 'cancelled': return 'danger';
                    default: return 'secondary';
                }
            }
        });
    </script>

</body>

</html>