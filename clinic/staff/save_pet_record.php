<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

$pet_id = $_POST['pet_id'];
$template_id = $_POST['template_id'];
$data = $_POST;
unset($data['pet_id'], $data['template_id'], $data['item_id'], $data['quantity_used']); // 🧹 remove non-record fields

$recordData = json_encode($data, JSON_UNESCAPED_UNICODE);
$staff_id = $_SESSION['role'] === 'staff' ? $_SESSION['staff_id'] : null;
$doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['staff_id'] : null;

// 💾 1. Insert pet record
$stmt = $pdo->prepare("INSERT INTO pet_records (pet_id, staff_id, doctor_id, template_id, data) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$pet_id, $staff_id, $doctor_id, $template_id, $recordData]);

// ✅ Get the new record ID (AFTER the insert)
$record_id = $pdo->lastInsertId();

// 💉 2. Handle medicine usage (if any)
if (!empty($_POST['item_id'])) {
    foreach ($_POST['item_id'] as $i => $item_id) {
        $quantity_used = (int)$_POST['quantity_used'][$i];

        if ($item_id && $quantity_used > 0) {
            // ➕ Insert usage record
            $stmt = $pdo->prepare("
                INSERT INTO record_inventory_usage (record_id, item_id, quantity_used)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$record_id, $item_id, $quantity_used]);

            // 🔻 Deduct from inventory + auto-update status
            $stmt2 = $pdo->prepare("
                UPDATE inventory 
                SET quantity = quantity - ?,
                    status = CASE 
                        WHEN quantity - ? <= 0 THEN 'out_of_stock'
                        WHEN quantity - ? <= reorder_level THEN 'low_stock'
                        ELSE 'available'
                    END
                WHERE item_id = ?
            ");
            $stmt2->execute([$quantity_used, $quantity_used, $quantity_used, $item_id]);
        }
    }
}

// 🟢 Redirect after everything is done
header('Location: manage_records.php?success=1');
exit;
?>
