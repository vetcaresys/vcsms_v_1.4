<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || $_SESSION['role'] !== 'staff') {
  header('Location: ../clinic/staff/login.php');
  exit;
}

$clinic_id = $_SESSION['clinic_id'];
$name = htmlspecialchars($_SESSION['name']);

// Count summaries
$countAvailable = $pdo->query("SELECT COUNT(*) FROM inventory WHERE clinic_id=$clinic_id AND status='available'")->fetchColumn();
$countLow = $pdo->query("SELECT COUNT(*) FROM inventory WHERE clinic_id=$clinic_id AND status='low_stock'")->fetchColumn();
$countOut = $pdo->query("SELECT COUNT(*) FROM inventory WHERE clinic_id=$clinic_id AND status='out_of_stock'")->fetchColumn();
$countAll = $pdo->query("SELECT COUNT(*) FROM inventory WHERE clinic_id=$clinic_id")->fetchColumn();

// Fetch inventory and categories
$stmt = $pdo->prepare("SELECT i.*, c.category_name 
                       FROM inventory i 
                       LEFT JOIN categories c ON i.category_id = c.category_id 
                       WHERE i.clinic_id = ?");
$stmt->execute([$clinic_id]);
$inventory = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM categories WHERE clinic_id = ?");
$stmt->execute([$clinic_id]);
$categories = $stmt->fetchAll();

// Profile data
$staff_id = $_SESSION['staff_id'];
$stmt = $pdo->prepare("SELECT * FROM staff WHERE staff_id = ?");
$stmt->execute([$staff_id]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);

$name = htmlspecialchars($staff['name']);
$profilePic = !empty($staff['profile_picture']) ? $staff['profile_picture'] : 'default.png';
$profilePicPath = "../../uploads/profiles/" . $profilePic . "?t=" . time();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Inventory Management - VetCareSys</title>
  <link rel="icon" type="image/jpg" href="../../assets/img/favicon-removebg-preview.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">
  <link rel="stylesheet" href="includes/css/manage_inventory.css">
</head>

<body>

  <?php include 'includes/body/navbar.php' ?>

  <?php if (isset($_SESSION['flash'])): ?>
    <script>
      Swal.fire({
        icon: '<?= $_SESSION['flash']['type'] ?>',
        title: '<?= $_SESSION['flash']['message'] ?>',
        timer: 2000,
        showConfirmButton: false
      });
    </script>
    <?php unset($_SESSION['flash']); endif; ?>

  <div class="container my-5">

    <!-- Summary Cards -->
    <div class="row g-3 mb-4 text-center">
      <div class="col-md-3">
        <div class="card summary-card available">
          <div class="card-body">
            <h5 class="text-success fw-bold">Available</h5>
            <h2><?= $countAvailable ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card low">
          <div class="card-body">
            <h5 class="text-warning fw-bold">Low Stock</h5>
            <h2><?= $countLow ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card out">
          <div class="card-body">
            <h5 class="text-danger fw-bold">Out of Stock</h5>
            <h2><?= $countOut ?></h2>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card summary-card total">
          <div class="card-body">
            <h5 class="text-primary fw-bold">Total Items</h5>
            <h2><?= $countAll ?></h2>
          </div>
        </div>
      </div>
    </div>
    
    <!-- category management section -->
    <div class="card shadow-sm mt-5">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="text-primary"><i class="bi bi-tags"></i> Categories</h4>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Add Category
          </button>
        </div>

        <table id="categoryTable" class="table table-bordered table-striped align-middle" style="width:100%">
          <thead class="table-primary">
            <tr>
              <th>ID</th>
              <th>Category Name</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($categories as $category): ?>
              <tr>
                <td><?= $category['category_id'] ?></td>
                <td><?= htmlspecialchars($category['category_name']) ?></td>
                <td>
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                    data-bs-target="#editCategoryModal<?= $category['category_id'] ?>" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="confirmDeleteCategory(<?= $category['category_id'] ?>)"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>

              <!-- Edit Category Modal -->
              <div class="modal fade" id="editCategoryModal<?= $category['category_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="update_category.php">
                      <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Category</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>

                      <div class="modal-body">
                        <input type="hidden" name="category_id" value="<?= $category['category_id'] ?>">

                        <label class="form-label">Category Name</label>
                        <input list="categoryList<?= $category['category_id'] ?>" name="category_name"
                          class="form-control" placeholder="Type or select category"
                          value="<?= htmlspecialchars($category['category_name']) ?>" required>

                        <datalist id="categoryList<?= $category['category_id'] ?>">
                          <option value="Vitamins">
                          <option value="Food">
                          <option value="Antibiotics">
                          <option value="Vaccines">
                          <option value="Accessories">
                          <option value="Grooming Supplies">
                          <option value="Medical Equipment">
                          <option value="Supplements">
                          <option value="Hygiene Products">
                          <option value="Toys">
                          <option value="Collars & Leashes">
                          <option value="Pet Beds">
                        </datalist>
                      </div>

                      <div class="modal-footer">
                        <button type="submit" name="update_category" class="btn btn-primary"><i class='bi bi-save'></i> Save</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <form method="POST" action="add_category.php">
            <div class="modal-header bg-success text-white">
              <h5 class="modal-title">Add New Category</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
              <label class="form-label">Category Name</label>
              <input list="categoryList" name="category_name" class="form-control" placeholder="Type or select category"
                required>
              <datalist id="categoryList">
                <option value="Vitamins">
                <option value="Food">
                <option value="Antibiotics">
                <option value="Vaccines">
                <option value="Accessories">
                <option value="Grooming Supplies">
                <option value="Medical Equipment">
                <option value="Supplements">
                <option value="Hygiene Products">
                <option value="Toys">
                <option value="Collars & Leashes">
                <option value="Pet Beds">
              </datalist>
            </div>
            <div class="modal-footer">
              <button type="submit" name="add_category" class="btn btn-primary"><i class="bi bi-plus-circle"></i> Add</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <br>
    <!-- Inventory Table -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="text-primary"><i class="bi bi-archive"></i> Inventory</h4>
          <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus-circle"></i> Add Item
          </button>
        </div>
        <table id="inventoryTable" class="table table-striped table-bordered align-middle" style="width:100%">
          <thead class="table-primary">
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Quantity (pcs)</th>
              <th>Total Volume (ml)</th>
              <th>Unit</th>
              <th>Expiration</th>
              <th>Cost</th>
              <th>Selling</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inventory as $item): ?>
              <tr class="<?= (strtotime($item['expiration_date']) - time() < 2592000) ? 'table-warning' : '' ?>">
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                <!-- Quantity (pcs) column -->
                <td>
                  <?php if ($item['is_consumable']): ?>
                    <?php
                    // compute remaining bottles based on ml
                    if ($item['volume_per_bottle_ml'] > 0) {
                      $remaining_bottles = $item['remaining_volume_ml'] / $item['volume_per_bottle_ml'];
                    } else {
                      $remaining_bottles = 0;
                    }
                    ?>

                    <?= $item['quantity'] ?> bottles
                    (<?= number_format($remaining_bottles, 2) ?> left)

                  <?php else: ?>
                    <?= $item['quantity'] ?>
                  <?php endif; ?>
                </td>

                <!-- Total Volume (ml) column -->
                <td>
                  <?php if ($item['is_consumable']): ?>
                    <?= number_format($item['remaining_volume_ml'], 2) ?> ml /
                    <?= number_format($item['total_volume_ml'], 2) ?> ml

                    <?php
                    $percent = 0;
                    if ($item['total_volume_ml'] > 0) {
                      $percent = ($item['remaining_volume_ml'] / $item['total_volume_ml']) * 100;
                    }
                    ?>

                    <div class="progress mt-1" style="height: 6px;">
                      <div class="progress-bar 
                <?= $percent <= 10 ? 'bg-danger' : ($percent <= 30 ? 'bg-warning' : 'bg-success') ?>"
                        style="width: <?= $percent ?>%">
                      </div>
                    </div>
                  <?php else: ?>
                    —
                  <?php endif; ?>
                </td>

                <td><?= htmlspecialchars($item['unit']) ?></td>

                <td><?= $item['expiration_date'] ?></td>

                <td>₱<?= number_format($item['cost_price'], 2) ?></td>
                <td>₱<?= number_format($item['selling_price'], 2) ?></td>
                <td>
                  <?php
                  $badge = [
                    'available' => 'success',
                    'low_stock' => 'warning',
                    'out_of_stock' => 'danger'
                  ][$item['status']] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?= $badge ?>"><?= ucfirst($item['status']) ?></span>
                </td>
                <td>
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                    data-bs-target="#editItemModal<?= $item['item_id'] ?>" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                  </button>

                  <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                    data-bs-target="#restockModal<?= $item['item_id'] ?>" data-bs-toggle="tooltip" data-bs-placement="top"
                    title="Restock">
                    <i class="bi bi-box-arrow-down"></i>
                  </button>

                  <!-- <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $item['item_id'] ?>)">🗑</button> -->
                </td>
              </tr>

              <!-- edit item modal -->
              <div class="modal fade" id="editItemModal<?= $item['item_id'] ?>" tabindex="-1">
                <div class="modal-dialog modal-lg">
                  <div class="modal-content">

                    <form method="POST" action="update_item.php">
                      <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">Edit Item - <?= htmlspecialchars($item['item_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>

                      <div class="modal-body row g-3">
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">

                        <!-- Item name -->
                        <div class="col-md-6">
                          <label class="form-label">Item Name</label>
                          <input type="text" name="item_name" class="form-control"
                            value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        </div>

                        <!-- Category -->
                        <div class="col-md-6">
                          <label class="form-label">Category</label>
                          <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                              <option value="<?= $cat['category_id'] ?>" <?= $cat['category_id'] == $item['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <!-- Stock quantity -->
                        <div class="col-md-4">
                          <label class="form-label">Quantity</label>
                          <input type="number" name="quantity" id="qty_edit_<?= $item['item_id'] ?>" class="form-control"
                            value="<?= $item['quantity'] ?>" min="0" required>
                        </div>

                        <!-- Unit -->
                        <div class="col-md-4">
                          <label class="form-label">Unit</label>
                          <input type="text" name="unit" class="form-control"
                            value="<?= htmlspecialchars($item['unit']) ?>" required>
                        </div>

                        <!-- Reorder level -->
                        <div class="col-md-4">
                          <label class="form-label">Reorder Level</label>
                          <input type="number" name="reorder_level" class="form-control"
                            value="<?= $item['reorder_level'] ?>">
                        </div>

                        <!-- consumable checkbox -->
                        <div class="col-md-12">
                          <div class="form-check mt-3">
                            <input class="form-check-input isConsumableEdit" type="checkbox"
                              id="is_consumable_edit_<?= $item['item_id'] ?>" name="is_consumable" value="1"
                              <?= $item['is_consumable'] ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold" for="is_consumable_edit_<?= $item['item_id'] ?>">
                              Is Consumable (mL-based)
                            </label>
                          </div>
                        </div>

                        <!-- consumable fields -->
                        <div class="col-md-4 consumableFieldsEdit consumable_<?= $item['item_id'] ?>"
                          style="display: <?= $item['is_consumable'] ? 'block' : 'none' ?>;">
                          <label class="form-label">Volume per bottle (ml)</label>
                          <input type="number" class="form-control" id="vol_bottle_edit_<?= $item['item_id'] ?>"
                            name="volume_per_bottle_ml" value="<?= $item['volume_per_bottle_ml'] ?>" min="1">
                        </div>

                        <div class="col-md-4 consumableFieldsEdit consumable_<?= $item['item_id'] ?>"
                          style="display: <?= $item['is_consumable'] ? 'block' : 'none' ?>;">
                          <label class="form-label">Total Volume (auto)</label>
                          <input type="number" class="form-control" id="total_vol_edit_<?= $item['item_id'] ?>"
                            name="total_volume_ml" value="<?= $item['total_volume_ml'] ?>" readonly>
                        </div>

                        <div class="col-md-4 consumableFieldsEdit consumable_<?= $item['item_id'] ?>"
                          style="display: <?= $item['is_consumable'] ? 'block' : 'none' ?>;">
                          <label class="form-label">Remaining Volume (auto)</label>
                          <input type="number" class="form-control" id="remain_vol_edit_<?= $item['item_id'] ?>"
                            name="remaining_volume_ml" value="<?= $item['remaining_volume_ml'] ?>" readonly>
                        </div>

                        <!-- Cost + Selling -->
                        <div class="col-md-6">
                          <label class="form-label">Cost Price</label>
                          <input type="number" step="0.01" name="cost_price" class="form-control"
                            value="<?= $item['cost_price'] ?>" required>
                        </div>

                        <!-- selling price -->
                        <div class="col-md-6">
                          <label class="form-label">Selling Price</label>
                          <input type="number" step="0.01" name="selling_price" class="form-control"
                            value="<?= $item['selling_price'] ?>" required>
                        </div>

                        <!-- Expiration -->
                        <div class="col-md-6">
                          <label class="form-label">Expiration Date</label>
                          <input type="date" name="expiration_date" class="form-control"
                            value="<?= $item['expiration_date'] ?>" required>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Location</label>
                          <input type="text" name="location" class="form-control"
                            value="<?= htmlspecialchars($item['location']) ?>">
                        </div>

                        <!-- notes -->
                        <div class="col-md-12">
                          <label class="form-label">Notes</label>
                          <textarea name="notes" class="form-control"><?= htmlspecialchars($item['notes']) ?></textarea>
                        </div>

                      </div>

                      <div class="modal-footer">
                        <button type="submit" name="update_item" class="btn btn-primary"><i class="bi bi-save"></i> Save Changes</button>
                      </div>

                    </form>
                  </div>
                </div>
              </div>

              <!-- AUTO-CALC FOR THIS SPECIFIC MODAL -->
              <script>
                document.addEventListener("DOMContentLoaded", function () {

                  let id = <?= $item['item_id'] ?>;

                  const qty = document.getElementById("qty_edit_" + id);
                  const perBottle = document.getElementById("vol_bottle_edit_" + id);
                  const total = document.getElementById("total_vol_edit_" + id);
                  const remain = document.getElementById("remain_vol_edit_" + id);
                  const consumableCheckbox = document.getElementById("is_consumable_edit_" + id);

                  // Toggle visibility
                  consumableCheckbox.addEventListener("change", function () {
                    document.querySelectorAll(".consumable_" + id).forEach(el => {
                      el.style.display = this.checked ? 'block' : 'none';
                    });
                  });

                  // Auto calculate
                  function recalc() {
                    let q = parseFloat(qty.value) || 0;
                    let vb = parseFloat(perBottle.value) || 0;

                    if (q > 0 && vb > 0) {
                      let totalCalc = q * vb;
                      total.value = totalCalc;
                      remain.value = totalCalc;
                    }
                  }

                  qty.addEventListener("input", recalc);
                  perBottle.addEventListener("input", recalc);
                });
              </script>

              <!-- re-stock -->
              <div class="modal fade" id="restockModal<?= $item['item_id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                  <div class="modal-content">
                    <form method="POST" action="restock_item.php">
                      <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Restock - <?= htmlspecialchars($item['item_name']) ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                      </div>

                      <div class="modal-body">
                        <input type="hidden" name="item_id" value="<?= $item['item_id'] ?>">

                        <div class="mb-3">
                          <label class="form-label">Current Quantity</label>
                          <input type="number" class="form-control" value="<?= $item['quantity'] ?>" readonly>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Add Quantity</label>
                          <input type="number" name="add_quantity" class="form-control" min="1" required>
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Purchase Date</label>
                          <input type="date" name="purchase_date" class="form-control">
                        </div>

                        <div class="mb-3">
                          <label class="form-label">Batch Number</label>
                          <input type="text" name="batch_number" class="form-control">
                        </div>
                      </div>

                      <div class="modal-footer">
                        <button type="submit" name="restock" class="btn btn-primary"><i class="bi bi-save"></i> Save Restock</button>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Expired Items Table -->
    <div class="card shadow-sm mt-5">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> Expired Items</h4>
        </div>

        <table id="expiredTable" class="table table-striped table-bordered align-middle" style="width:100%">
          <thead class="table-dark">
            <tr>
              <th>Item</th>
              <th>Category</th>
              <th>Quantity</th>
              <th>Unit</th>
              <th>Expiration</th>
              <th>Cost</th>
              <th>Selling</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // Fetch expired items
            $stmt = $pdo->prepare("SELECT i.*, c.category_name 
                               FROM inventory i 
                               LEFT JOIN categories c ON i.category_id = c.category_id 
                               WHERE i.clinic_id = ? AND i.expiration_date < CURDATE()");
            $stmt->execute([$clinic_id]);
            $expiredItems = $stmt->fetchAll();

            foreach ($expiredItems as $item): ?>
              <tr class="table-danger">
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= htmlspecialchars($item['unit']) ?></td>
                <td><?= $item['expiration_date'] ?></td>
                <td>₱<?= number_format($item['cost_price'], 2) ?></td>
                <td>₱<?= number_format($item['selling_price'], 2) ?></td>
                <td><span class="badge bg-danger">Expired</span></td>
                <td>
                  <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                    data-bs-target="#editItemModal<?= $item['item_id'] ?>" data-bs-toggle="tooltip"
                    data-bs-placement="top" title="Edit">
                    <i class="bi bi-pencil-square"></i>
                  </button>
                  <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $item['item_id'] ?>)"
                    data-bs-toggle="tooltip" data-bs-placement="top" title="Delete">
                    <i class="bi bi-trash"></i>
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Add Item Modal -->
  <div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="POST" action="add_item.php">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">Add New Inventory Item</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>

          <div class="modal-body row g-3">

            <div class="col-md-6">
              <label class="form-label">Item Name</label>
              <input type="text" name="item_name" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" class="form-select" required>
                <option value="">Select Category</option>
                <?php foreach ($categories as $category): ?>
                  <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label">Quantity</label>
              <input type="number" name="quantity" class="form-control" min="0" required>
            </div>

            <div class="col-md-4">
              <label class="form-label">Unit</label>
              <input type="text" name="unit" class="form-control" placeholder="e.g. pcs, bottle" required>
            </div>

            <div class="col-md-4 consumable-fields" style="display:none;">
              <label class="form-label">Volume per bottle (ml)</label>
              <input type="number" name="volume_per_bottle_ml" id="volume_per_bottle_ml" class="form-control" min="1">
            </div>

            <div class="col-md-4 consumable-fields" style="display:none;">
              <label class="form-label">Total Volume (auto)</label>
              <input type="number" name="total_volume_ml" id="total_volume_ml" class="form-control" readonly>
            </div>

            <div class="col-md-4 consumable-fields" style="display:none;">
              <label class="form-label">Remaining Volume (auto)</label>
              <input type="number" name="remaining_volume_ml" id="remaining_volume_ml" class="form-control" readonly>
            </div>

            <div class="col-md-4">
              <label class="form-label">Reorder Level</label>
              <input type="number" name="reorder_level" class="form-control" min="0">
            </div>

            <div class="col-md-6">
              <label class="form-label">Cost Price</label>
              <input type="number" step="0.01" name="cost_price" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Selling Price</label>
              <input type="number" step="0.01" name="selling_price" class="form-control" required>
            </div>

            <div class="col-md-6">
              <label class="form-label">Expiration Date</label>
              <input type="date" name="expiration_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
            </div>


            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Shelf A-1">
            </div>

            <div class="col-md-12">
              <div class="form-check mt-3">
                <input class="form-check-input" type="checkbox" id="is_consumable" name="is_consumable" value="1">
                <label class="form-check-label fw-bold" for="is_consumable">
                  Is Consumable (mL-based)
                </label>
              </div>
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

          </div>

          <div class="modal-footer">
            <button type="submit" name="add_item" class="btn btn-success"><i class="bi bi-save"></i> Save Item</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="container-footer">
    &copy; <?= date('Y') ?> VetCareSys — Empowering Veterinary Clinics.
  </div>

  <!-- di ni hilabtan -->
  <script>
    document.getElementById('is_consumable').addEventListener('change', function () {
      const show = this.checked;
      document.querySelectorAll('.consumable-fields').forEach(el => {
        el.style.display = show ? 'block' : 'none';
      });
    });

    // Auto calculate total & remaining volume
    document.addEventListener("input", function () {
      let qty = document.querySelector("[name='quantity']").value;
      let perBottle = document.querySelector("#volume_per_bottle_ml").value;

      if (qty > 0 && perBottle > 0) {
        let total = qty * perBottle;
        document.getElementById("total_volume_ml").value = total;
        document.getElementById("remaining_volume_ml").value = total;
      }
    });
  </script>

  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
  <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>

  <script>
    $(document).ready(function () {
      $('#inventoryTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 10
      });
    });

    function confirmDelete(id) {
      Swal.fire({
        title: 'Delete Item?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = 'delete_item.php?item_id=' + id;
        }
      });
    }

    function confirmDeleteCategory(id) {
      Swal.fire({
        title: 'Delete Category?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          const form = document.createElement('form');
          form.method = 'POST';
          form.action = 'delete_category.php';

          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'category_id';
          input.value = id;
          form.appendChild(input);

          document.body.appendChild(form);
          form.submit();
        }
      });
    }
  </script>
  <script>
    function toggleEdit(showEdit) {
      const viewProfile = document.getElementById('viewProfile');
      const editProfile = document.getElementById('editProfile');

      if (showEdit) {
        viewProfile.style.display = 'none';
        editProfile.style.display = 'block';
      } else {
        viewProfile.style.display = 'block';
        editProfile.style.display = 'none';
      }
    }
  </script>

  <!-- for the expired table item -->
  <script>
    $(document).ready(function () {
      $('#expiredTable').DataTable({
        dom: 'Bfrtip',
        buttons: ['copy', 'csv', 'excel', 'pdf', 'print'],
        pageLength: 10
      });
    });
  </script>
</body>

</html>