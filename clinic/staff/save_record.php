<?php
// save_record.php
include '../config.php'; // adjust path kung naa sa lain folder
session_start();

// Check if user is logged in (optional, depende sa imong setup)
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Example: ang imong form nagpadala ug arrays
    // item_name[], quantity[], expiration_date[], category_id[]

    $clinic_id = $_SESSION['clinic_id']; // gikan sa imong session

    $item_names = $_POST['item_name'];
    $quantities = $_POST['quantity'];
    $exp_dates  = $_POST['expiration_date'];
    $categories = $_POST['category_id'];

    // Prepared statement para malikayan SQL injection
    $sql = "INSERT INTO inventory (clinic_id, item_name, quantity, expiration_date, category_id, status) 
            VALUES (?, ?, ?, ?, ?, 'available')";
    $stmt = $pdo->prepare($sql);

    // Loop sa tanan submitted rows
    for ($i = 0; $i < count($item_names); $i++) {
        $item_name = trim($item_names[$i]);
        $quantity = (int) $quantities[$i];
        $exp_date = !empty($exp_dates[$i]) ? $exp_dates[$i] : null;
        $category = !empty($categories[$i]) ? $categories[$i] : null;

        if (!empty($item_name) && $quantity > 0) {
            $stmt->execute([$clinic_id, $item_name, $quantity, $exp_date, $category]);
        }
    }

    // Redirect balik sa inventory page with success message
    header("Location: manage_inventory.php?success=1");
    exit;
} else {
    echo "Invalid request.";
}
