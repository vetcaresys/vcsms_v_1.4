<?php
require '../config.php';
session_start();

$clinic_id = $_SESSION['clinic_id'];

$data = json_decode(file_get_contents("php://input"), true);
$start = $data['start'];
$end = $data['end'];

$sql = "
SELECT 
    DATE(il.created_at) AS date_used,
    il.log_id AS record_id,
    p.pet_name AS pet,
    o.owner_name AS owner,
    i.item_name AS item,
    il.quantity AS qty,
    il.unit_price,
    (il.quantity * il.unit_price) AS income,
    s.name AS staff,
    il.notes
FROM inventory_logs il
JOIN inventory i ON il.inventory_id = i.inventory_id
LEFT JOIN pets p ON il.pet_id = p.pet_id
LEFT JOIN owners o ON p.owner_id = o.owner_id
LEFT JOIN staff s ON il.staff_id = s.staff_id
WHERE il.clinic_id = ?
AND DATE(il.created_at) BETWEEN ? AND ?
ORDER BY il.created_at ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$clinic_id, $start, $end]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary
$total = array_sum(array_column($rows, 'income'));
$count = count($rows);

$days = max(1, (strtotime($end) - strtotime($start)) / 86400 + 1);
$average = $total / $days;

// Highest day
$daily = [];
foreach ($rows as $r) {
    $daily[$r['date_used']] = ($daily[$r['date_used']] ?? 0) + $r['income'];
}

$highest_day = null;
$highest_amount = 0;
foreach ($daily as $day => $amt) {
    if ($amt > $highest_amount) {
        $highest_amount = $amt;
        $highest_day = $day;
    }
}

echo json_encode([
    "total" => $total,
    "count" => $count,
    "average" => $average,
    "days" => $days,
    "highest_day" => $highest_day,
    "highest_amount" => $highest_amount,
    "rows" => $rows
]);
?>