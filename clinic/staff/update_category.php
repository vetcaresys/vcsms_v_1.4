<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id'])) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
  $id = $_POST['category_id'];
  $name = trim($_POST['category_name']);
  if ($name !== '') {
    $stmt = $pdo->prepare("UPDATE categories SET category_name=? WHERE category_id=?");
    $stmt->execute([$name, $id]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category updated successfully!'];
  } else {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Category name cannot be empty.'];
  }
  header("Location: manage_inventory.php");
  exit;
}
?>
