<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id'])) {
  header('Location: ../clinic/staff/login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
  $clinic_id = $_SESSION['clinic_id'];
  $item_name = trim($_POST['item_name']);
  $category_id = $_POST['category_id'] ?: null;
  $quantity = (int)$_POST['quantity'];
  $unit = trim($_POST['unit']);
  $reorder_level = (int)($_POST['reorder_level'] ?: 0);
  $cost_price = (float)$_POST['cost_price'];
  $selling_price = (float)$_POST['selling_price'];
  $expiration_date = $_POST['expiration_date'] ?: null;
  $location = trim($_POST['location']);
  $notes = trim($_POST['notes']);

  // 🧮 Determine status
  if ($quantity <= 0) {
    $status = 'out_of_stock';
  } elseif ($quantity <= $reorder_level) {
    $status = 'low_stock';
  } else {
    $status = 'available';
  }

  try {
    // ✅ Insert item into inventory
    $stmt = $pdo->prepare("
      INSERT INTO inventory 
        (clinic_id, item_name, category_id, quantity, unit, reorder_level, cost_price, selling_price, expiration_date, location, notes, status) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
      $clinic_id, 
      $item_name, 
      $category_id, 
      $quantity, 
      $unit, 
      $reorder_level, 
      $cost_price, 
      $selling_price, 
      $expiration_date, 
      $location, 
      $notes, 
      $status
    ]);

    // 🧠 Log the add action (✅ INSIDE the same block)
    $item_id = $pdo->lastInsertId();
    $log = $pdo->prepare("
      INSERT INTO inventory_activity_log 
        (item_id, staff_id, action_type, quantity_added, new_quantity, remarks)
      VALUES (?, ?, 'add', ?, ?, 'New item added to inventory')
    ");
    $log->execute([$item_id, $_SESSION['staff_id'], $quantity, $quantity]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Item added successfully!'];
  } catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Error adding item: ' . $e->getMessage()];
  }

  header("Location: manage_inventory.php");
  exit;
}
?>
