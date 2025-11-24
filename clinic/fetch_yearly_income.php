<?php
// clinic/fetch_yearly_income.php
include '../config.php';
session_start();
header('Content-Type: application/json');

$clinic_id = $_SESSION['clinic_id'] ?? null;
if (!$clinic_id) {
    echo json_encode(['error' => 'No clinic']);
    exit;
}

// Use last 12 months including current month
$stmt = $pdo->prepare("
    SELECT
        DATE_FORMAT(r.date_used, '%Y-%m') AS ym,
        MONTH(r.date_used) AS month_num,
        SUM(
            CASE
                WHEN i.is_consumable = 1 AND i.volume_per_bottle_ml IS NOT NULL AND i.volume_per_bottle_ml > 0
                    THEN (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                ELSE i.selling_price * r.quantity_used
            END
        ) AS total_income
    FROM record_inventory_usage r
    JOIN inventory i ON i.item_id = r.item_id
    JOIN pet_records pr ON pr.record_id = r.record_id
    JOIN staff s ON s.staff_id = pr.staff_id
    WHERE s.clinic_id = :clinic_id
      AND r.date_used >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
    GROUP BY DATE_FORMAT(r.date_used, '%Y-%m')
    ORDER BY DATE_FORMAT(r.date_used, '%Y-%m') ASC
");
$stmt->execute([':clinic_id' => $clinic_id]);

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Build 12-month array (ensure months with zero are included)
$labels = [];
$values = [];
// generate months list starting 11 months ago -> this month
$cur = new DateTime(date('Y-m-01'));
$start = (new DateTime())->modify('-11 months')->modify('first day of this month');

$map = [];
foreach ($results as $r) { $map[$r['ym']] = (float)$r['total_income']; }

$period = new DatePeriod($start, new DateInterval('P1M'), 12);
foreach ($period as $dt) {
    $ym = $dt->format('Y-m');
    $labels[] = $dt->format('M Y'); // e.g., "Nov 2024"
    $values[] = isset($map[$ym]) ? round($map[$ym],2) : 0.00;
}

echo json_encode(['months' => $labels, 'incomes' => $values]);
?>