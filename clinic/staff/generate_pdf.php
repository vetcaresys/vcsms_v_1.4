<?php
session_start();
require_once('../../tcpdf/tcpdf.php');
include '../../config.php';

$record_id = $_GET['id'] ?? null;
if (!$record_id) die('Invalid record ID');

// ----------------------------------------------------------------------------------------
// 🔐 SESSION CLINIC
// ----------------------------------------------------------------------------------------
$clinic_id = $_SESSION['clinic_id'] ?? null;
if (!$clinic_id) die('No clinic session found.');

$stmtClinic = $pdo->prepare("SELECT clinic_name, logo FROM clinics WHERE clinic_id = ?");
$stmtClinic->execute([$clinic_id]);
$clinic = $stmtClinic->fetch(PDO::FETCH_ASSOC);

$clinic_name = $clinic['clinic_name'] ?? 'VetCareSys Veterinary Clinic';

$clinic_logo = !empty($clinic['logo'])
    ? "../../uploads/logos/" . htmlspecialchars($clinic['logo'])
    : "../../assets/logo.png";

// ----------------------------------------------------------------------------------------
// 🔍 FETCH RECORD
// ----------------------------------------------------------------------------------------
$stmt = $pdo->prepare("
    SELECT 
        pr.*, 
        p.pet_name, 
        p.breed, 
        p.birth_date, 
        u.name AS owner_name, 
        rt.template_name
    FROM pet_records pr
    JOIN pets p ON pr.pet_id = p.pet_id
    JOIN users u ON p.owner_id = u.user_id
    JOIN record_templates rt ON pr.template_id = rt.template_id
    WHERE pr.record_id = ?
");
$stmt->execute([$record_id]);
$record = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$record) die('Record not found');

$data = json_decode($record['data'], true);

// ----------------------------------------------------------------------------------------
// 🧴 FETCH USED ITEMS with consumable detection
// ----------------------------------------------------------------------------------------
$stmtItems = $pdo->prepare("
    SELECT 
        riu.quantity_used,
        i.item_name,
        i.is_consumable,
        i.volume_per_bottle_ml
    FROM record_inventory_usage riu
    JOIN inventory i ON riu.item_id = i.item_id
    WHERE riu.record_id = ?
");
$stmtItems->execute([$record_id]);
$usedItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

// ----------------------------------------------------------------------------------------
// 📝 PDF
// ----------------------------------------------------------------------------------------
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle('Pet Record - ' . $record['pet_name']);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 11);

// ----------------------------------------------------------------------------------------
// 📌 HEADER
// ----------------------------------------------------------------------------------------
$html = '
<div style="text-align:center;">
    <img src="' . $clinic_logo . '" width="80" height="80" style="object-fit:contain;"><br>
    <h2 style="color:#007bff;">' . htmlspecialchars($clinic_name) . '</h2>
    <p style="color:gray;">Official Pet Medical Record</p>
</div>

<hr>

<h4>Pet Information</h4>
<table border="1" cellpadding="6">
<tr><td><b>Pet Name:</b></td><td>' . htmlspecialchars($record['pet_name']) . '</td></tr>
<tr><td><b>Owner:</b></td><td>' . htmlspecialchars($record['owner_name']) . '</td></tr>
<tr><td><b>Breed:</b></td><td>' . htmlspecialchars($record['breed']) . '</td></tr>
<tr><td><b>Birthdate:</b></td><td>' . htmlspecialchars($record['birth_date']) . '</td></tr>
<tr><td><b>Record Type:</b></td><td>' . htmlspecialchars($record['template_name']) . '</td></tr>
<tr><td><b>Date Recorded:</b></td><td>' . date("M d, Y h:i A", strtotime($record['date_recorded'])) . '</td></tr>
</table>

<br><h4>Consultation / Medical Details</h4>
<table border="1" cellpadding="6">
';

// ----------------------------------------------------------------------------------------
// 📋 CONSULTATION FIELDS
// ----------------------------------------------------------------------------------------
if (!empty($data)) {
    foreach ($data as $label => $value) {
        $html .= '<tr>
                    <td><b>' . ucwords(str_replace('_', ' ', $label)) . ':</b></td>
                    <td>' . nl2br(htmlspecialchars($value)) . '</td>
                  </tr>';
    }
} else {
    $html .= '<tr><td colspan="2">No consultation data available.</td></tr>';
}

$html .= '</table>';

// ----------------------------------------------------------------------------------------
// 💉 MEDICINES / SUPPLIES USED (with ml + Bottle Equiv)
// ----------------------------------------------------------------------------------------
$html .= '<br><h4>Medicines / Supplies Used</h4>
<table border="1" cellpadding="6">
<tr><th>Item Name</th><th>Quantity Used</th></tr>';

if (!empty($usedItems)) {

    foreach ($usedItems as $item) {

        $displayQty = "";

        if ($item['is_consumable']) {

            $used_ml = floatval($item['quantity_used']);
            $bSize = floatval($item['volume_per_bottle_ml']);
            $bottle_equiv = $bSize > 0 ? round($used_ml / $bSize, 2) : null;

            $displayQty .= $used_ml . " ml";

            if ($bottle_equiv !== null) {
                $displayQty .= "<br><small>(≈ $bottle_equiv bottle(s))</small>";
            }

        } else {

            $displayQty = intval($item['quantity_used']) . " pcs";
        }

        $html .= '
        <tr>
            <td>' . htmlspecialchars($item['item_name']) . '</td>
            <td>' . $displayQty . '</td>
        </tr>';
    }

} else {
    $html .= '<tr><td colspan="2" style="text-align:center;">No items used for this record.</td></tr>';
}

$html .= '</table>';


// ----------------------------------------------------------------------------------------
// ✍ SIGNATURE
// ----------------------------------------------------------------------------------------
$html .= '
<br><br>
<p><b>Attending Veterinarian:</b> ____________________________</p>
<p><b>Date:</b> ____________________________</p>
<hr>

<p style="text-align:center;font-size:10px;color:gray;">
Generated by VetCareSys © ' . date('Y') . ' | For Veterinary Use Only
</p>
';

// ----------------------------------------------------------------------------------------
// PRINT
// ----------------------------------------------------------------------------------------
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('Pet_Record_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $record['pet_name']) . '.pdf', 'I');
