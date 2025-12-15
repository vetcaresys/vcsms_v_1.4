<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['clinic_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {

    $name = trim($_POST['category_name']);
    $clinic_id = $_SESSION['clinic_id'];

    if ($name === '') {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'Category name cannot be empty.'
        ];
        header("Location: manage_inventory.php");
        exit;
    }

    // ✅ Check ONLY within this clinic
    $check = $pdo->prepare("
        SELECT 1 
        FROM categories 
        WHERE clinic_id = ? 
        AND LOWER(category_name) = LOWER(?)
    ");
    $check->execute([$clinic_id, $name]);

    if ($check->fetch()) {
        $_SESSION['flash'] = [
            'type' => 'warning',
            'message' => 'This category already exists in your clinic.'
        ];
        header("Location: manage_inventory.php");
        exit;
    }

    // ✅ Insert category
    $stmt = $pdo->prepare("
        INSERT INTO categories (category_name, clinic_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$name, $clinic_id]);

    $_SESSION['flash'] = [
        'type' => 'success',
        'message' => 'Category added successfully!'
    ];

    header("Location: manage_inventory.php");
    exit;
}
