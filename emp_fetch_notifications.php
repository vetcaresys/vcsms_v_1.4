<?php
session_start();
require 'config.php';

/* Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}*/

try {
    $stmt = $pdo->prepare("
        SELECT notif_id, subject, message, link, status, created_at
        FROM notifications
        WHERE role = 'employee' OR role = 'staff'  -- MODIFIED WHERE CLAUSE
        ORDER BY 
            CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
            created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
