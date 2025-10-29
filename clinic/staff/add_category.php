<?php
session_start();
include '../../config.php';

if (!isset($_SESSION['staff_id'])) exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
  $name = trim($_POST['category_name']);
  if ($name !== '') {
    $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
    $stmt->execute([$name]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Category added successfully!'];
  } else {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Category name cannot be empty.'];
  }
  header("Location: manage_inventory.php");
  exit;
}
?>
