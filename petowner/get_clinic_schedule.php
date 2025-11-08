<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
  echo '<div class="text-danger">No clinic selected.</div>';
  exit;
}

$clinic_id = $_GET['clinic_id'];

// Fetch clinic schedule from your existing table
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

// Group consecutive open days with the same time range
$grouped = [];
$current = null;

foreach ($schedules as $row) {
  if (strtolower($row['status']) !== 'open') continue;

  $day = $row['day_of_week'];
  $time = "{$row['open_time']}-{$row['close_time']}";

  if (!$current) {
    $current = ['days' => [$day], 'time' => $time];
  } elseif ($current['time'] === $time) {
    $current['days'][] = $day;
  } else {
    $grouped[] = $current;
    $current = ['days' => [$day], 'time' => $time];
  }
}

if ($current) $grouped[] = $current;

// Output neatly formatted description
echo '<div class="p-2">';
echo '<h6 class="fw-semibold text-primary mb-2"><i class="bi bi-clock"></i> Clinic Weekly Schedule</h6>';
echo '<ul class="list-group">';

foreach ($grouped as $g) {
  $days = $g['days'];
  $time = explode('-', $g['time']);
  $open = date("h:i A", strtotime($time[0]));
  $close = date("h:i A", strtotime($time[1]));

  // Display combined day range or comma-separated list
  if (count($days) > 2) {
    $dayLabel = reset($days) . ' – ' . end($days);
  } else {
    $dayLabel = implode(', ', $days);
  }

  echo "
    <li class='list-group-item d-flex justify-content-between align-items-center'>
      <strong>{$dayLabel}</strong>
      <span>{$open} – {$close}</span>
    </li>
  ";
}

echo '</ul>';
echo '<small class="text-muted d-block mt-2 fst-italic">Pet owners can choose only the appointment date. Exact time will be assigned by the clinic staff.</small>';
echo '</div>';
?>
