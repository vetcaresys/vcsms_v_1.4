<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../clinic/staff/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['use_consumable'])) {

    $item_id      = $_POST['item_id'];
    $use_volume   = (float) $_POST['use_volume'];
    $notes        = trim($_POST['notes']);
    $staff_id     = $_SESSION['staff_id'];

    // Fetch item
    $stmt = $pdo->prepare("SELECT * FROM inventory WHERE item_id = ?");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item not found.'];
        header("Location: manage_inventory.php");
        exit;
    }

    if (!$item['is_consumable']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'This item is not consumable.'];
        header("Location: manage_inventory.php");
        exit;
    }

    $remaining_ml       = (float) $item['remaining_volume_ml'];
    $total_ml           = (float) $item['total_volume_ml'];
    $per_bottle_ml      = (float) $item['volume_per_bottle_ml'];
    $current_bottles    = (int) $item['quantity'];

    // ❌ Cannot use more than available ml
    if ($use_volume > $remaining_ml) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot use more than remaining volume.'];
        header("Location: manage_inventory.php");
        exit;
    }

    // Deduct ml
    $new_remaining_ml = $remaining_ml - $use_volume;

    // 🍾 Auto-compute how many bottles remain
    $new_remaining_bottles = $per_bottle_ml > 0 ? $new_remaining_ml / $per_bottle_ml : 0;

    // 🍾 Auto-update quantity (rounded down bottles)
    $new_bottle_count = floor($new_remaining_bottles);

    // Status
    if ($new_remaining_ml <= 0) {
        $status = "out_of_stock";
        $new_remaining_ml = 0;
        $new_bottle_count = 0;
    } elseif ($new_remaining_ml <= ($total_ml * 0.30)) {
        $status = "low_stock";
    } else {
        $status = "available";
    }

    try {

        // Update volume + bottle count
        $update = $pdo->prepare("
            UPDATE inventory
            SET remaining_volume_ml = ?, quantity = ?, status = ?
            WHERE item_id = ?
        ");

        $update->execute([
            $new_remaining_ml,
            $new_bottle_count,
            $status,
            $item_id
        ]);

        // Log the usage
        $log = $pdo->prepare("
            INSERT INTO inventory_activity_log 
                (item_id, staff_id, action_type, volume_used, new_remaining_volume, remarks)
            VALUES (?, ?, 'use', ?, ?, ?)
        ");

        $log->execute([
            $item_id,
            $staff_id,
            $use_volume,
            $new_remaining_ml,
            $notes ?: 'Consumable used'
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Consumable updated successfully!'];

    } catch (PDOException $e) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }

    header("Location: manage_inventory.php");
    exit;
}
?>
