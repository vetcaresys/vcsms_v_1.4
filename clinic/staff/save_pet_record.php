<?php
session_start();
include '../../config.php';

// 🔐 Access Control: Only staff or doctor can access
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

// ✅ Validate required POST fields
if (empty($_POST['pet_id']) || empty($_POST['template_id'])) {
    die('Pet ID and Template ID are required.');
}

$pet_id = (int)$_POST['pet_id'];
$template_id = (int)$_POST['template_id'];

// Prepare record data: remove fields that are not part of the record
$data = $_POST;
unset($data['pet_id'], $data['template_id'], $data['item_id'], $data['quantity_used']);

// Encode remaining form data as JSON
$recordData = json_encode($data, JSON_UNESCAPED_UNICODE);

// Determine staff or doctor ID
$staff_id = $_SESSION['role'] === 'staff' ? $_SESSION['staff_id'] : null;
$doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['staff_id'] : null;

try {
    $pdo->beginTransaction();

    // 💾 1. Insert pet record
    $stmt = $pdo->prepare("
        INSERT INTO pet_records (pet_id, staff_id, doctor_id, template_id, data)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$pet_id, $staff_id, $doctor_id, $template_id, $recordData]);

    $record_id = $pdo->lastInsertId();

    // 💊 2. Handle medicine usage
    if (!empty($_POST['item_id']) && is_array($_POST['item_id'])) {
        foreach ($_POST['item_id'] as $i => $item_id) {
            $item_id = (int)$item_id;
            $quantity_used = isset($_POST['quantity_used'][$i]) ? (int)$_POST['quantity_used'][$i] : 0;

            if ($item_id > 0 && $quantity_used > 0) {
                // ➕ Insert usage record
                $stmt = $pdo->prepare("
                    INSERT INTO record_inventory_usage (record_id, item_id, quantity_used)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$record_id, $item_id, $quantity_used]);

                // 🔻 Update inventory quantity and status
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

    $pdo->commit();

    // 🟢 Redirect after successful insert
    header('Location: manage_records.php?success=1');
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error saving pet record: " . $e->getMessage());
    die('An error occurred while saving the record.');
}
?>
