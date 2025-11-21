<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['clinic_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    $name = trim($_POST['category_name']);
    $category_id = $_POST['category_id'];
    $clinic_id = $_SESSION['clinic_id']; // 🔥 Secure filtering

    if ($name !== '') {
        // Update ONLY the category that belongs to this clinic
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET category_name = ?
            WHERE category_id = ? AND clinic_id = ?
        ");
        $stmt->execute([$name, $category_id, $clinic_id]);

        $_SESSION['flash'] = [
            'type' => 'success',
            'message' => 'Category updated successfully!'
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
