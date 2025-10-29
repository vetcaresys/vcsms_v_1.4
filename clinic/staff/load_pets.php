<?php
include '../../config.php';
session_start();

if (isset($_GET['owner_id'])) {
    $owner_id = $_GET['owner_id'];

    $stmt = $pdo->prepare("SELECT pet_id, pet_name FROM pets WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $pets = $stmt->fetchAll();

    echo '<option value="">-- Select Pet --</option>';
    foreach ($pets as $p) {
        echo "<option value='{$p['pet_id']}'>{$p['pet_name']}</option>";
    }
}
?>
