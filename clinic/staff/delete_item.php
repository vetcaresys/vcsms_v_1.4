<?php
include '../../config.php';
session_start();

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../clinic/staff/login.php");
    exit;
}

if (isset($_GET['item_id'])) {
    $item_id = $_GET['item_id'];

    // 🔍 Get item before deletion for logging
    $stmt = $pdo->prepare("SELECT item_name, quantity FROM inventory WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($item) {
        $previous_quantity = (int)$item['quantity'];

        // 🗑 Delete the item
        $delete = $pdo->prepare("DELETE FROM inventory WHERE item_id = ?");
        $deleted = $delete->execute([$item_id]);

        if ($deleted) {
            // 🧾 Log deletion activity
            $log = $pdo->prepare("INSERT INTO inventory_activity_log 
                (item_id, staff_id, action_type, quantity_added, previous_quantity, new_quantity, remarks)
                VALUES (?, ?, 'delete', ?, ?, 0, ?)");
            $log->execute([
                $item_id,
                $_SESSION['staff_id'],
                -$previous_quantity, // negative to indicate deduction
                $previous_quantity,
                'Item deleted: ' . $item['item_name']
            ]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item deleted successfully!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to delete item.'];
        }
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item not found.'];
    }
}

header("Location: manage_inventory.php");
exit;
?>
