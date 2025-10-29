<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
  echo '<div class="text-danger">No clinic selected.</div>';
  exit;
}

$clinic_id = $_GET['clinic_id'];

// Fetch clinic schedule
$stmt = $pdo->prepare("
  SELECT day_of_week, open_time, close_time, status
  FROM clinic_schedules
  WHERE clinic_id = ?
  ORDER BY FIELD(day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')
");
$stmt->execute([$clinic_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$schedules) {
  echo '<div class="text-warning">No schedule found for this clinic.</div>';
  exit;
}

echo '<div class="p-2">';
echo '<h6 class="fw-semibold text-primary mb-2"><i class="bi bi-clock"></i> Clinic Weekly Schedule</h6>';
echo '<ul class="list-group">';

foreach ($schedules as $s) {
  $day = htmlspecialchars($s['day_of_week']);
  $open = $s['open_time'] ? date("h:i A", strtotime($s['open_time'])) : '—';
  $close = $s['close_time'] ? date("h:i A", strtotime($s['close_time'])) : '—';
  
  $isOpen = strtolower($s['status']) === 'open';
  $statusBadge = $isOpen
    ? "<span class='badge bg-success ms-2'>Open</span>"
    : "<span class='badge bg-secondary ms-2'>Closed</span>";

  echo "
    <li class='list-group-item d-flex justify-content-between align-items-center'>
      <strong>$day</strong>
      <div>
        $open - $close $statusBadge
      </div>
    </li>
  ";
}

echo '</ul>';
echo '<small class="text-muted d-block mt-2 fst-italic">Pet owners can choose only the appointment date. Exact time will be assigned by the clinic staff.</small>';
echo '</div>';
?>
