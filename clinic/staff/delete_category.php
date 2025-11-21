<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id']) || !isset($_SESSION['clinic_id'])) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = $_POST['category_id'];
    $clinic_id = $_SESSION['clinic_id']; // 🔥 Protect each clinic

    try {
        // Delete ONLY if the category belongs to this clinic
        $stmt = $pdo->prepare("
            DELETE FROM categories 
            WHERE category_id = ? AND clinic_id = ?
        ");
        $stmt->execute([$category_id, $clinic_id]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Category deleted successfully!'
            ];
        } else {
            // Someone tried deleting a category not belonging to this clinic
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Cannot delete category — it does not belong to your clinic.'
            ];
        }

    } catch (PDOException $e) {
        $_SESSION['flash'] = [
            'type' => 'error',
            'message' => 'Cannot delete category — it may be used in inventory.'
        ];
    }

    header("Location: manage_inventory.php");
    exit;
}
?>
