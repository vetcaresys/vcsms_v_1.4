<?php
include '../config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'clinic_owner') {
    header("Location: ../login.php");
    exit;
}

$clinic_id = $_SESSION['clinic_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$clinic_id) {
    die("Clinic not found.");
}

// GET INVENTORY CHANGES THIS WEEK
$query = $pdo->prepare("
    SELECT ia.*, i.item_name
    FROM inventory_activity_log ia
    INNER JOIN inventory i ON ia.item_id = i.item_id
    WHERE i.clinic_id = ?
    AND WEEK(ia.date_action) = WEEK(NOW())
");
$query->execute([$clinic_id]);
$logs = $query->fetchAll(PDO::FETCH_ASSOC);

// Generate report content
$report_html = "<h2>Weekly Inventory Report</h2>";
$report_html .= "<p>Clinic ID: {$clinic_id}</p>";
$report_html .= "<p>Generated on: " . date('Y-m-d H:i:s') . "</p><br>";

$report_html .= "
<table border='1' cellpadding='8' cellspacing='0'>
    <tr>
        <th>Item Name</th>
        <th>Action</th>
        <th>Qty Added</th>
        <th>Previous Qty</th>
        <th>New Qty</th>
        <th>Remarks</th>
        <th>Date</th>
    </tr>
";

foreach ($logs as $row) {
    $report_html .= "
    <tr>
        <td>{$row['item_name']}</td>
        <td>{$row['action_type']}</td>
        <td>{$row['quantity_added']}</td>
        <td>{$row['previous_quantity']}</td>
        <td>{$row['new_quantity']}</td>
        <td>{$row['remarks']}</td>
        <td>{$row['date_action']}</td>
    </tr>";
}

$report_html .= "</table>";

// Save to file
$filename = "weekly_report_" . time() . ".html";
$filepath = "../uploads/reports/" . $filename;

file_put_contents($filepath, $report_html);

// Save record in DB table: reports
$save = $pdo->prepare("
    INSERT INTO reports (generated_by, clinic_id, report_type, generated_at, file_path)
    VALUES (?, ?, 'weekly_inventory', NOW(), ?)
");
$save->execute([$user_id, $clinic_id, $filepath]);

// Download file
header("Content-Type: text/html");
header("Content-Disposition: attachment; filename=$filename");
echo $report_html;
exit;
?>
