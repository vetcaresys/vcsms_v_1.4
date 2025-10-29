<?php
include '../../config.php';
session_start();

// 🔐 Access Control
if (!isset($_SESSION['staff_id'])) {
  header("Location: ../clinic/staff/login.php");
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['restock'])) {
  $item_id = $_POST['item_id'];
  $add_quantity = (int)$_POST['add_quantity'];
  $purchase_date = $_POST['purchase_date'] ?? null;
  $batch_number = $_POST['batch_number'] ?? null;

  // 🧾 Get current item info
  $stmt = $pdo->prepare("SELECT quantity, reorder_level FROM inventory WHERE item_id = ?");
  $stmt->execute([$item_id]);
  $item = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($item) {
    $previous_quantity = $item['quantity'];
    $new_quantity = $previous_quantity + $add_quantity;

    // 🧮 Determine new stock status
    if ($new_quantity <= 0) {
      $status = 'out_of_stock';
    } elseif ($new_quantity <= $item['reorder_level']) {
      $status = 'low_stock';
    } else {
      $status = 'available';
    }

    // ✅ Update inventory table
    $update = $pdo->prepare("UPDATE inventory 
        SET quantity = ?, status = ?, purchase_date = ?, batch_number = ? 
        WHERE item_id = ?");
    $update->execute([$new_quantity, $status, $purchase_date, $batch_number, $item_id]);

    // 🧠 Log the restock activity
    $log = $pdo->prepare("INSERT INTO inventory_activity_log 
      (item_id, staff_id, action_type, quantity_added, previous_quantity, new_quantity, remarks)
      VALUES (?, ?, 'restock', ?, ?, ?, 'Item restocked')");
    $log->execute([
      $item_id,
      $_SESSION['staff_id'],
      $add_quantity,
      $previous_quantity,
      $new_quantity
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item restocked successfully!'];
  } else {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Item not found.'];
  }

  header("Location: manage_inventory.php");
  exit;
}
?>
