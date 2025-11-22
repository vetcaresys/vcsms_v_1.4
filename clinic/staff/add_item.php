<?php
session_start();
include '../../config.php';

// 🔐 Access Control
if (!isset($_SESSION['staff_id'])) {
  header('Location: ../clinic/staff/login.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {

  $clinic_id      = $_SESSION['clinic_id'];
  $item_name      = trim($_POST['item_name']);
  $category_id    = $_POST['category_id'] ?: null;
  $quantity       = (int)$_POST['quantity'];
  $unit           = trim($_POST['unit']);
  $reorder_level  = (int)($_POST['reorder_level'] ?: 0);
  $cost_price     = (float)$_POST['cost_price'];
  $selling_price  = (float)$_POST['selling_price'];
  $expiration_date = $_POST['expiration_date'] ?: null;
  $location       = trim($_POST['location']);
  $notes          = trim($_POST['notes']);

  // 🧪 -- NEW: Consumable Fields --
  $is_consumable = isset($_POST['is_consumable']) ? 1 : 0;

  // Consumable: Required fields
  $volume_per_bottle_ml = $is_consumable ? (float)($_POST['volume_per_bottle_ml'] ?? 0) : null;

  // Auto-calc total volume (consumable only)
  if ($is_consumable) {
    $total_volume_ml = $quantity * $volume_per_bottle_ml;
    $remaining_volume_ml = $total_volume_ml;
  } else {
    $total_volume_ml = null;
    $remaining_volume_ml = null;
  }

  // 🚫 Validate expiration date
  if (!empty($expiration_date) && strtotime($expiration_date) < strtotime(date('Y-m-d'))) {
    $_SESSION['flash'] = [
      'type' => 'error',
      'message' => 'Expiration date cannot be earlier than today.'
    ];
    header("Location: manage_inventory.php");
    exit;
  }

  // 🚫 Negative price check
  if ($cost_price < 0 || $selling_price < 0) {
    $_SESSION['flash'] = [
      'type' => 'error',
      'message' => 'Cost and Selling Price cannot be negative.'
    ];
    header("Location: manage_inventory.php");
    exit;
  }

  // 🚫 Duplicate item name (clinic-based)
  $check = $pdo->prepare("
    SELECT COUNT(*) 
    FROM inventory 
    WHERE clinic_id = ? AND LOWER(item_name) = LOWER(?)
  ");
  $check->execute([$clinic_id, $item_name]);

  if ($check->fetchColumn() > 0) {
    $_SESSION['flash'] = [
      'type' => 'error',
      'message' => 'Item name already exists in your inventory.'
    ];
    header("Location: manage_inventory.php");
    exit;
  }

  // 🧮 Determine status
  if ($quantity <= 0) {
    $status = 'out_of_stock';
  } elseif ($quantity <= $reorder_level) {
    $status = 'low_stock';
  } else {
    $status = 'available';
  }

  try {

    // ✅ SAVE ITEM (Now includes Consumable Fields)
    $stmt = $pdo->prepare("
      INSERT INTO inventory 
        (clinic_id, item_name, category_id, quantity, unit, reorder_level,
         cost_price, selling_price, expiration_date, location, notes, status,
         is_consumable, total_volume_ml, remaining_volume_ml, volume_per_bottle_ml)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
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
      $status,
      $is_consumable,
      $total_volume_ml,
      $remaining_volume_ml,
      $volume_per_bottle_ml
    ]);

    // 🧠 Log activity
    $item_id = $pdo->lastInsertId();

    $log = $pdo->prepare("
      INSERT INTO inventory_activity_log 
        (item_id, staff_id, action_type, quantity_added, new_quantity, remarks)
      VALUES (?, ?, 'add', ?, ?, 'New item added to inventory')
    ");
    $log->execute([$item_id, $_SESSION['staff_id'], $quantity, $quantity]);

    $_SESSION['flash'] = [
      'type' => 'success',
      'message' => 'Item added successfully!'
    ];

  } catch (PDOException $e) {

    $_SESSION['flash'] = [
      'type' => 'error',
      'message' => 'Error adding item: ' . $e->getMessage()
    ];

  }

  header("Location: manage_inventory.php");
  exit;
}
?>
