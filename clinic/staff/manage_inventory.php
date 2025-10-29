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

$stmt = $pdo->query("SELECT * FROM categories");
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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">

  <!-- Google Fonts -->
  <link
    href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Poppins:wght@500;600;700&display=swap"
    rel="stylesheet">

  <style>
    /* 🌟 Global Styles */
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fb;
      color: #2e2e2e;
      line-height: 1.6;
      background: linear-gradient(135deg, #f0f4ff, #ffffff);
      min-height: 100vh;
    }

    /* 🧭 Navbar */
    .navbar {
      background: linear-gradient(90deg, #0d6efd, #007bff);
      font-family: 'Poppins', sans-serif;
      font-weight: 500;
      letter-spacing: 0.3px;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
      font-weight: 700;
      font-size: 1.25rem;
      letter-spacing: 0.5px;
      display: flex;
      align-items: center;
    }

    .navbar-brand img {
      width: 38px;
      height: 38px;
      object-fit: cover;
      border-radius: 50%;
      background: #fff;
      padding: 3px;
      margin-right: 10px;
      transition: transform 0.2s ease;
    }

    .navbar-brand img:hover {
      transform: scale(1.08);
    }

    /* Links */
    .nav-link {
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .nav-link:hover {
      color: #ffc107 !important;
    }

    /* 🧾 Summary Cards */
    .summary-card {
      border: none;
      border-radius: 12px;
      background: #fff;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .summary-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .summary-card h5 {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .summary-card h2 {
      font-weight: 700;
      font-size: 2rem;
    }

    /* 💼 Tables */
    .table {
      border-radius: 10px;
      overflow: hidden;
      font-size: 0.95rem;
    }

    .table thead {
      background-color: #0d6efd;
      color: white;
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    .table tbody tr:hover {
      background-color: #f2f7ff;
    }

    /* 🪄 Buttons */
    .btn {
      border-radius: 8px;
      font-family: 'Inter', sans-serif;
      font-weight: 500;
      transition: all 0.2s ease;
    }

    .btn:hover {
      opacity: 0.9;
      transform: translateY(-1px);
    }

    /* 🧩 Modals */
    .modal-content {
      border-radius: 15px;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .modal-header {
      border-radius: 15px 15px 0 0;
      background: linear-gradient(90deg, #0d6efd, #007bff);
      color: white;
    }

    .modal-title {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
    }

    /* 🧍 Form */
    .form-label {
      font-weight: 600;
      color: #333;
    }

    .form-control {
      border-radius: 8px;
      border: 1px solid #ccc;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* ⚡ Sweet alert pop */
    .swal2-popup {
      font-family: 'Inter', sans-serif !important;
      border-radius: 15px !important;
    }

    /* 🌈 Badges */
    .badge {
      font-size: 0.85rem;
      padding: 6px 10px;
      border-radius: 8px;
    }

    /* 🐾 Page Titles */
    h4.text-primary {
      font-family: 'Poppins', sans-serif;
      font-weight: 600;
      color: #0d6efd !important;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    /* 📦 Footer vibe */
    .container-footer {
      text-align: center;
      margin-top: 50px;
      font-size: 0.9rem;
      color: #777;
    }

    /* 🧭 Datatables */
    div.dataTables_wrapper .dataTables_filter input {
      border-radius: 8px;
      border: 1px solid #ddd;
    }

    div.dataTables_wrapper .dataTables_length select {
      border-radius: 6px;
    }

    /* 🧁 Animations */
    .card,
    .modal-content {
      transition: all 0.25s ease-in-out;
    }

    @media (max-width: 576px) {
      .navbar-brand img {
        width: 28px;
        height: 28px;
      }

      .dropdown-toggle strong {
        display: none;
        /* hide name on very small screens */
      }
    }
  </style>
</head>

<body>

  <!-- 🌟 Navbar -->
  <nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
      <a class="navbar-brand fw-bold" href="#">VetCareSys</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#topNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="topNav">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
          <li class="nav-item">
            <a href="index.php" class="nav-link text-white">Dashboard</a>
          </li>
          <li class="nav-item">
            <a href="manage_petowner.php" class="nav-link text-white">Manage Client</a>
          </li>
          <li class="nav-item">
            <a href="manage_pet_details.php" class="nav-link text-white">Pet Details</a>
          </li>
          <li class="nav-item">
            <a href="manage_customer_appointment.php" class="nav-link text-white">Appointments</a>
          </li>
          <li class="nav-item">
            <a href="manage_records.php" class="nav-link text-white">Medical Records</a>
          </li>
          <li class="nav-item">
            <a href="manage_inventory.php" class="nav-link text-white">Inventory</a>
          </li>
        </ul>

        <!-- Profile -->
        <div class="dropdown ms-auto">
          <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle"
            id="dropdownUser" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle me-2" width="35"
              height="35">
            <strong><?= $name ?></strong>
          </a>
          <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#profileModal"><i
                  class="bi bi-person"></i> My Profile</a></li>
            <li>
              <hr class="dropdown-divider">
            </li>
            <li>
              <form method="POST" action="../logout.php" class="m-0">
                <button class="dropdown-item text-danger" type="submit"><i class="bi bi-box-arrow-right"></i>
                  Logout</button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </nav>

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
    <!-- ===========================
     CATEGORY MANAGEMENT SECTION
============================ -->
    <div class="card shadow-sm mt-5">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h4 class="text-primary"><i class="bi bi-tags"></i> Categories</h4>
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="bi bi-plus-circle"></i> Add Category
          </button>
        </div>

        <table id="categoryTable" class="table table-bordered table-striped align-middle" style="width:100%">
          <thead class="table-dark">
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
                    data-bs-target="#editCategoryModal<?= $category['category_id'] ?>">✏️</button>
                  <button class="btn btn-sm btn-danger"
                    onclick="confirmDeleteCategory(<?= $category['category_id'] ?>)">🗑</button>
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
                        <button type="submit" name="update_category" class="btn btn-success">💾 Save</button>
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

    <!-- Profile Modal -->
    <div class="modal fade" id="profileModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">

          <!-- View Profile -->
          <div id="viewProfile">
            <div class="modal-header">
              <h5 class="modal-title">My Profile</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
              <img src="<?= htmlspecialchars($profilePicPath) ?>" alt="Profile" class="rounded-circle mb-3" width="100">
              <h4><?= $name ?></h4>
              <p><strong>Email:</strong> <?= htmlspecialchars($staff['email']) ?></p>
              <p><strong>Contact:</strong> <?= htmlspecialchars($staff['contact_number']) ?></p>
              <p><strong>Role:</strong> <?= htmlspecialchars($staff['role']) ?></p>
            </div>
            <div class="modal-footer">
              <button class="btn btn-primary" onclick="toggleEdit(true)">Edit Profile</button>
            </div>
          </div>

          <!-- Edit Profile -->
          <div id="editProfile" style="display:none;">
            <form action="update_profile.php" method="POST" enctype="multipart/form-data">
              <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <div class="mb-3">
                  <label class="form-label">Full Name</label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($staff['name']) ?>"
                    required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Email</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($staff['email']) ?>"
                    required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Contact Number</label>
                  <input type="text" name="contact_number" class="form-control"
                    value="<?= htmlspecialchars($staff['contact_number']) ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Profile Picture</label>
                  <input type="file" name="profile_picture" class="form-control">
                </div>
              </div>
              <div class="modal-footer">
                <!-- <button type="button" class="btn btn-secondary" onclick="toggleEdit(false)">Cancel</button> -->
                <button type="submit" class="btn btn-success">Save Changes</button>
              </div>
            </form>
          </div>

        </div>
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
              <button type="submit" name="add_category" class="btn btn-success">Add</button>
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
          <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addItemModal">
            <i class="bi bi-plus-circle"></i> Add Item
          </button>
        </div>
        <table id="inventoryTable" class="table table-striped table-bordered align-middle" style="width:100%">
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
            <?php foreach ($inventory as $item): ?>
              <tr class="<?= (strtotime($item['expiration_date']) - time() < 2592000) ? 'table-warning' : '' ?>">
                <td><?= htmlspecialchars($item['item_name']) ?></td>
                <td><?= htmlspecialchars($item['category_name'] ?? 'Uncategorized') ?></td>
                <td><?= $item['quantity'] ?></td>
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
                    data-bs-target="#editItemModal<?= $item['item_id'] ?>">✏️</button>

                  <?php if ($item['status'] !== 'available'): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                      data-bs-target="#restockModal<?= $item['item_id'] ?>">🔄 Restock</button>
                  <?php endif; ?>

                  <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $item['item_id'] ?>)">🗑</button>
                </td>
              </tr>

              <!-- Edit Modal -->
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

                        <div class="col-md-6">
                          <label class="form-label">Item Name</label>
                          <input type="text" name="item_name" class="form-control"
                            value="<?= htmlspecialchars($item['item_name']) ?>" required>
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Category</label>
                          <select name="category_id" class="form-select" required>
                            <?php foreach ($categories as $cat): ?>
                              <option value="<?= $cat['category_id'] ?>" <?= ($cat['category_id'] == $item['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category_name']) ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </div>

                        <div class="col-md-4">
                          <label class="form-label">Quantity</label>
                          <input type="number" name="quantity" class="form-control" value="<?= $item['quantity'] ?>"
                            required>
                        </div>

                        <div class="col-md-4">
                          <label class="form-label">Unit</label>
                          <input type="text" name="unit" class="form-control"
                            value="<?= htmlspecialchars($item['unit']) ?>">
                        </div>

                        <div class="col-md-4">
                          <label class="form-label">Reorder Level</label>
                          <input type="number" name="reorder_level" class="form-control"
                            value="<?= $item['reorder_level'] ?>">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Cost Price</label>
                          <input type="number" step="0.01" name="cost_price" class="form-control"
                            value="<?= $item['cost_price'] ?>">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Selling Price</label>
                          <input type="number" step="0.01" name="selling_price" class="form-control"
                            value="<?= $item['selling_price'] ?>">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Expiration Date</label>
                          <input type="date" name="expiration_date" class="form-control"
                            value="<?= $item['expiration_date'] ?>">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Location</label>
                          <input type="text" name="location" class="form-control"
                            value="<?= htmlspecialchars($item['location']) ?>">
                        </div>

                        <div class="col-md-12">
                          <label class="form-label">Notes</label>
                          <textarea name="notes" class="form-control"><?= htmlspecialchars($item['notes']) ?></textarea>
                        </div>
                      </div>
                      <div class="modal-footer">
                        <button type="submit" name="update_item" class="btn btn-success">💾 Save Changes</button>
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
  </div>

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
            <button type="submit" name="restock" class="btn btn-success">✅ Save Restock</button>
          </div>
        </form>
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
                <option value="">-- Select Category --</option>
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
              <input type="date" name="expiration_date" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label">Location</label>
              <input type="text" name="location" class="form-control" placeholder="e.g. Shelf A-1">
            </div>

            <div class="col-md-12">
              <label class="form-label">Notes</label>
              <textarea name="notes" class="form-control" rows="2"></textarea>
            </div>

          </div>

          <div class="modal-footer">
            <button type="submit" name="add_item" class="btn btn-success">💾 Save Item</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="container-footer">
    &copy; <?= date('Y') ?> VetCareSys — Empowering Veterinary Clinics.
  </div>

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

</body>

</html>