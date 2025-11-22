<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['clinic_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['category_name']);
    $clinic_id = $_SESSION['clinic_id'];

    if ($name !== '') {

        // ✔ Check if category already exists for this clinic
        $check = $pdo->prepare("
            SELECT COUNT(*) 
            FROM categories 
            WHERE clinic_id = ? AND LOWER(category_name) = LOWER(?)
        ");
        $check->execute([$clinic_id, $name]);

        if ($check->fetchColumn() > 0) {
            // ❌ Already exists for this clinic
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'This category already exists in your clinic.'
            ];
            header("Location: manage_inventory.php");
            exit;
        }

        // ✔ Check if category exists globally (DB constraint)
        $globalCheck = $pdo->prepare("
            SELECT COUNT(*) 
            FROM categories 
            WHERE LOWER(category_name) = LOWER(?)
        ");
        $globalCheck->execute([$name]);

        if ($globalCheck->fetchColumn() > 0) {
            // ❌ Prevent SQL integrity error
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'This category name already exists for another clinic.'
            ];
            header("Location: manage_inventory.php");
            exit;
        }

        // ✔ Insert category
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
