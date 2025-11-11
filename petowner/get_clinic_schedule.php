<?php
require '../config.php';

if (!isset($_GET['clinic_id'])) {
    echo '<div class="text-danger">No clinic selected.</div>';
    exit;
}

$clinic_id = $_GET['clinic_id'];

// Fetch clinic schedule
$stmt = $pdo->prepare("
    SELECT day_of_week, status, open_time, close_time
    FROM clinic_schedules
    WHERE clinic_id = ?
");
$stmt->execute([$clinic_id]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Default all days as closed
$weekDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
$scheduleMap = array_fill_keys($weekDays, ['status'=>'Closed','time'=>'']);

// Fill scheduleMap with actual status and time
foreach ($schedules as $row) {
    $dayShort = substr($row['day_of_week'], 0, 3);
    if (strtolower($row['status']) === 'open') {
        $open = date("h:i A", strtotime($row['open_time']));
        $close = date("h:i A", strtotime($row['close_time']));
        $time = "{$open} – {$close}";
        $scheduleMap[$dayShort] = ['status'=>'Open','time'=>$time];
    }
}

// Display compact icon schedule
echo '<div class="p-2">';
echo '<h6 class="fw-semibold text-primary mb-2"><i class="bi bi-clock"></i> Weekly Status</h6>';
echo '<ul class="list-group list-group-horizontal text-center">';

foreach ($weekDays as $day) {
    $status = $scheduleMap[$day]['status'];
    $time = $scheduleMap[$day]['time'];
    $class = $status === 'Open' ? 'text-success' : 'text-danger';
    $icon = $status === 'Open' ? '✅' : '❌';

    // Add tooltip for time if open
    $tooltip = $time ? "data-bs-toggle='tooltip' title='{$time}'" : "";

    echo "
        <li class='list-group-item flex-fill' {$tooltip}>
            <small><strong>{$day}</strong><br><span class='{$class}'>{$icon}</span></small>
        </li>
    ";
}

echo '</ul>';
echo '</div>';

// Activate Bootstrap tooltips
echo "<script>
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
  return new bootstrap.Tooltip(tooltipTriggerEl)
})
</script>";
?>
