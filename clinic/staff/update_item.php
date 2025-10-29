<?php
include '../../config.php';
session_start();

// 🔐 Access Control
if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../clinic/staff/login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_item'])) {
    $item_id = $_POST['item_id'];
    $item_name = trim($_POST['item_name']);
    $category_id = $_POST['category_id'];
    $batch_number = $_POST['batch_number'] ?? null;
    $supplier_id = $_POST['supplier_id'] ?? null;
    $purchase_date = $_POST['purchase_date'] ?? null;
    $quantity = (int)$_POST['quantity'];
    $reorder_level = (int)$_POST['reorder_level'];
    $unit = trim($_POST['unit']);
    $cost_price = (float)$_POST['cost_price'];
    $selling_price = (float)$_POST['selling_price'];
    $expiration_date = $_POST['expiration_date'] ?? null;
    $location = trim($_POST['location']);
    $notes = trim($_POST['notes']);

    // 🔍 Get previous quantity
    $stmt_prev = $pdo->prepare("SELECT quantity FROM inventory WHERE item_id = ?");
    $stmt_prev->execute([$item_id]);
    $item_prev = $stmt_prev->fetch(PDO::FETCH_ASSOC);
    $previous_quantity = $item_prev ? (int)$item_prev['quantity'] : 0;

    // 🧮 Determine stock status
    if ($quantity <= 0) {
        $status = 'out_of_stock';
    } elseif ($quantity <= $reorder_level) {
        $status = 'low_stock';
    } else {
        $status = 'available';
    }

    // ✅ Update the inventory item
    $stmt = $pdo->prepare("
        UPDATE inventory SET 
            item_name = ?, 
            category_id = ?, 
            batch_number = ?, 
            supplier_id = ?, 
            purchase_date = ?, 
            quantity = ?, 
            reorder_level = ?, 
            unit = ?, 
            cost_price = ?, 
            selling_price = ?, 
            expiration_date = ?, 
            location = ?, 
            notes = ?, 
            status = ?
        WHERE item_id = ?
    ");

    $success = $stmt->execute([
        $item_name,
        $category_id,
        $batch_number,
        $supplier_id,
        $purchase_date,
        $quantity,
        $reorder_level,
        $unit,
        $cost_price,
        $selling_price,
        $expiration_date,
        $location,
        $notes,
        $status,
        $item_id
    ]);

    if ($success) {
        // 🧾 Log the update
        $quantity_changed = $quantity - $previous_quantity;

        $log = $pdo->prepare("INSERT INTO inventory_activity_log 
            (item_id, staff_id, action_type, quantity_added, previous_quantity, new_quantity, remarks)
            VALUES (?, ?, 'edit', ?, ?, ?, 'Item updated')");
        $log->execute([
            $item_id,
            $_SESSION['staff_id'],
            $quantity_changed,
            $previous_quantity,
            $quantity
        ]);

        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item updated successfully!'];
    } else {
        $errorInfo = $stmt->errorInfo();
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Failed to update item: ' . $errorInfo[2]];
    }

    header("Location: manage_inventory.php");
    exit;
}
?>
