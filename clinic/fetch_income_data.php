<?php
include '../config.php';
session_start();

$clinic_id = $_SESSION['clinic_id'] ?? null;

if (!$clinic_id) {
    echo json_encode(["error" => "No clinic_id"]);
    exit;
}

function computeIncome($pdo, $clinic_id, $dateFilter)
{
    $sql = "
        SELECT 
            SUM(
                CASE 
                    WHEN i.is_consumable = 1 THEN 
                        (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                    ELSE 
                        i.selling_price * r.quantity_used
                END
            ) AS income
        FROM record_inventory_usage r
        JOIN inventory i ON i.item_id = r.item_id
        JOIN pet_records pr ON pr.record_id = r.record_id
        JOIN staff s ON s.staff_id = pr.staff_id
        WHERE s.clinic_id = ?
        $dateFilter
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$clinic_id]);
    return $stmt->fetchColumn() ?? 0;
}

// DAILY, WEEKLY, MONTHLY
$daily = computeIncome($pdo, $clinic_id, "AND DATE(r.date_used) = CURDATE()");
$weekly = computeIncome($pdo, $clinic_id, "AND r.date_used >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$monthly = computeIncome($pdo, $clinic_id, 
    "AND DATE_FORMAT(r.date_used, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')");

// 30-DAY LINE GRAPH DATA
$stmt = $pdo->prepare("
    SELECT 
        DATE(r.date_used) AS date,
        SUM(
            CASE 
                WHEN i.is_consumable = 1 THEN 
                    (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
                ELSE 
                    i.selling_price * r.quantity_used
            END
        ) AS income
    FROM record_inventory_usage r
    JOIN inventory i ON i.item_id = r.item_id
    JOIN pet_records pr ON pr.record_id = r.record_id
    JOIN staff s ON s.staff_id = pr.staff_id
    WHERE s.clinic_id = ?
    AND r.date_used >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(r.date_used)
    ORDER BY date ASC
");
$stmt->execute([$clinic_id]);
$graphData = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    "daily" => $daily,
    "weekly" => $weekly,
    "monthly" => $monthly,
    "graph" => $graphData
]);
?>
