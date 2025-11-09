<?php
require 'config.php';

if (!isset($_GET['id'])) {
    exit;
}

$notif_id = intval($_GET['id']);

$stmt = $pdo->prepare("UPDATE notifications SET status='read' WHERE notif_id = ?");
$stmt->execute([$notif_id]);
