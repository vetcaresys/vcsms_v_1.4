<?php
// clinic/fetch_income_range.php
include '../config.php';
session_start();
header('Content-Type: application/json');

$clinic_id = $_SESSION['clinic_id'] ?? null;
if (!$clinic_id) {
    echo json_encode(['error' => 'No clinic configured.']);
    exit;
}

$start = $_GET['start'] ?? null;
$end   = $_GET['end'] ?? null;

if (!$start || !$end) {
    echo json_encode(['error' => 'Start and end dates are required.']);
    exit;
}

// Normalize date range
$startDate = date('Y-m-d 00:00:00', strtotime($start));
$endDate   = date('Y-m-d 23:59:59', strtotime($end));

/*
    FIELDS RETURNED:

    date_used
    record_id
    pet_name
    owner_name
    item_name
    quantity_used
    unit_price
    income
    staff_name
    notes (formatted)
*/

$sql = "
SELECT
    DATE(r.date_used) AS date_used,
    r.record_id,
    p.pet_name,
    u.name AS owner_name,
    i.item_name,
    r.quantity_used,
    i.selling_price AS unit_price,

    CASE
        WHEN i.is_consumable = 1 AND i.volume_per_bottle_ml IS NOT NULL AND i.volume_per_bottle_ml > 0
            THEN (i.selling_price / i.volume_per_bottle_ml) * r.quantity_used
        ELSE i.selling_price * r.quantity_used
    END AS income,

    s.name AS staff_name,
    pr.data AS notes_raw

FROM record_inventory_usage r
JOIN inventory i ON i.item_id = r.item_id
JOIN pet_records pr ON pr.record_id = r.record_id
LEFT JOIN pets p ON p.pet_id = pr.pet_id
LEFT JOIN users u ON u.user_id = p.owner_id
LEFT JOIN staff s ON s.staff_id = pr.staff_id

WHERE s.clinic_id = :clinic_id
  AND r.date_used BETWEEN :start AND :end

ORDER BY r.date_used ASC, r.usage_id ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ':clinic_id' => $clinic_id,
    ':start'     => $startDate,
    ':end'       => $endDate
]);

$rows = [];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    /*
        --------------------------------------------------
        🟦 FORMAT NOTES (JSON → READABLE TEXT)
        --------------------------------------------------
    */

    $notes = "";

    if (!empty($row['notes_raw'])) {

        $decoded = json_decode($row['notes_raw'], true);

        // If JSON is valid
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {

            $formatted = "";

            foreach ($decoded as $key => $val) {

                // skip blank values
                if ($val === "" || $val === null) continue;

                // Make key readable
                $label = ucwords(
                    str_replace(
                        [
                            '_', '(kg)', '(°c)', '(°C)', '(ml)', '(mL)', 'v/', 'V/'
                        ],
                        [
                            ' ', ' (kg)', ' (°C)', ' (°C)', ' (mL)', ' (mL)', ' ', ' '
                        ],
                        $key
                    )
                );

                // If value is array
                if (is_array($val)) {
                    $val = implode(', ', $val);
                }

                $formatted .= "<strong>$label:</strong> $val<br>";
            }

            $notes = $formatted ?: "(No notes)";
        }
        else {
            // Not valid JSON → return short plain text
            $notes = substr(strip_tags($row['notes_raw']), 0, 200);
        }
    }

    // Build final row
    $rows[] = [
        'date_used'     => $row['date_used'],
        'record_id'     => $row['record_id'],
        'pet_name'      => $row['pet_name'],
        'owner_name'    => $row['owner_name'],
        'item_name'     => $row['item_name'],
        'quantity_used' => $row['quantity_used'],
        'unit_price'    => (float)$row['unit_price'],
        'income'        => round((float)$row['income'], 2),
        'staff_name'    => $row['staff_name'],
        'notes'         => $notes
    ];
}


// Compute total summary
$total = 0;
foreach ($rows as $r) {
    $total += $r['income'];
}

echo json_encode([
    'rows' => $rows,
    'summary' => [
        'total_income' => round($total, 2),
        'rows_count'   => count($rows),
        'start'        => $start,
        'end'          => $end
    ]
]);
?>
