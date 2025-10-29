<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id'])) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = $_POST['category_id'];
  try {
    $stmt = $pdo->prepare("DELETE FROM categories WHERE category_id=?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category deleted successfully!'];
  } catch (PDOException $e) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Cannot delete category—it may be used in inventory.'];
  }
  header("Location: manage_inventory.php");
  exit;
}
?>
