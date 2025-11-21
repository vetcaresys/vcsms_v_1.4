<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['clinic_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $clinic_id = $_SESSION['clinic_id']; // 🔥 Add clinic ID

    if ($name !== '') {
        // Insert WITH clinic_id
        $stmt = $pdo->prepare("
            INSERT INTO categories (category_name, clinic_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$name, $clinic_id]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Category added successfully!'
        ];
    } else {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Category name cannot be empty.'
        ];
    }

    header("Location: manage_inventory.php");
    exit;
}
?>
