<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || !in_array($_SESSION['role'], ['staff', 'doctor'])) {
    header('Location: ../../login.php');
    exit;
}

// 🛑 Validate required inputs
if (empty($_POST['pet_id']) || empty($_POST['template_id'])) {
    die('Pet ID and Template ID are required.');
}

$pet_id = (int)$_POST['pet_id'];
$template_id = (int)$_POST['template_id'];

// Extract dynamic template fields
$data = $_POST;
unset($data['pet_id'], $data['template_id'], $data['item_id'], $data['quantity_used'], $data['consumable_used_ml']);

$recordData = json_encode($data, JSON_UNESCAPED_UNICODE);

// Determine staff type
$staff_id  = $_SESSION['role'] === 'staff'  ? $_SESSION['staff_id'] : null;
$doctor_id = $_SESSION['role'] === 'doctor' ? $_SESSION['staff_id'] : null;

try {

    $pdo->beginTransaction();

    // 1️⃣ Insert main pet record
    $stmt = $pdo->prepare("
        INSERT INTO pet_records (pet_id, staff_id, doctor_id, template_id, data)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$pet_id, $staff_id, $doctor_id, $template_id, $recordData]);

    $record_id = $pdo->lastInsertId();

    // 2️⃣ Handle item usage
    if (!empty($_POST['item_id']) && is_array($_POST['item_id'])) {

        foreach ($_POST['item_id'] as $i => $item_id) {

            $item_id = (int)$item_id;
            if ($item_id <= 0) continue;

            // Fetch inventory item
            $stmtItem = $pdo->prepare("SELECT * FROM inventory WHERE item_id = ?");
            $stmtItem->execute([$item_id]);
            $item = $stmtItem->fetch(PDO::FETCH_ASSOC);
            if (!$item) continue;

            $is_consumable = $item['is_consumable'] == 1;

            // Determine usage based on item type
            $use_ml = 0;
            $use_qty = 0;

            if ($is_consumable) {
                $use_ml = isset($_POST['consumable_used_ml'][$i]) ? floatval($_POST['consumable_used_ml'][$i]) : 0;
                if ($use_ml <= 0) continue;
            } else {
                $use_qty = isset($_POST['quantity_used'][$i]) ? intval($_POST['quantity_used'][$i]) : 0;
                if ($use_qty <= 0) continue;
            }

            // Log into record_inventory_usage table
            $stmtUsage = $pdo->prepare("
                INSERT INTO record_inventory_usage (record_id, item_id, quantity_used)
                VALUES (?, ?, ?)
            ");
            $stmtUsage->execute([$record_id, $item_id, $is_consumable ? $use_ml : $use_qty]);


            // =============================
            // 📦 UPDATE INVENTORY STOCK
            // =============================
            if ($is_consumable) {

                // Prevent overuse
                if ($use_ml > $item['remaining_volume_ml']) {
                    $use_ml = $item['remaining_volume_ml'];
                }

                $new_remaining = max(0, $item['remaining_volume_ml'] - $use_ml);

                // Update database
                $stmtUpdate = $pdo->prepare("
                    UPDATE inventory
                    SET remaining_volume_ml = ?,
                        status = CASE
                            WHEN ? <= 0 THEN 'out_of_stock'
                            WHEN ? <= (total_volume_ml * 0.30) THEN 'low_stock'
                            ELSE 'available'
                        END
                    WHERE item_id = ?
                ");
                $stmtUpdate->execute([$new_remaining, $new_remaining, $new_remaining, $item_id]);


                // Log consumable usage
                $log = $pdo->prepare("
                    INSERT INTO inventory_usage (inventory_id, used_ml, staff_id)
                    VALUES (?, ?, ?)
                ");
                $log->execute([$item_id, $use_ml, $_SESSION['staff_id']]);

            } else {

                // Prevent overuse
                if ($use_qty > $item['quantity']) {
                    $use_qty = $item['quantity'];
                }

                $new_qty = max(0, $item['quantity'] - $use_qty);

                $stmtUpdate = $pdo->prepare("
                    UPDATE inventory
                    SET quantity = ?,
                        status = CASE
                            WHEN ? <= 0 THEN 'out_of_stock'
                            WHEN ? <= reorder_level THEN 'low_stock'
                            ELSE 'available'
                        END
                    WHERE item_id = ?
                ");
                $stmtUpdate->execute([$new_qty, $new_qty, $new_qty, $item_id]);
            }
        }
    }

    // Commit everything
    $pdo->commit();

    header("Location: manage_records.php?success=1");
    exit;

} catch (Exception $e) {

    $pdo->rollBack();
    error_log("⚠ ERROR SAVING PET RECORD: " . $e->getMessage());

    die("An error occurred while saving the record.");
}

?>
