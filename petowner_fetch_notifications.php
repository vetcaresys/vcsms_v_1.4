<?php
session_start();
require 'config.php';
$user_id = $_GET['user_id'] ?? null;
header('Content-Type: application/json');
/* Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}*/

try {
    $stmt = $pdo->prepare("
        SELECT 
            notif_id, 
            subject, 
            message, 
            link, 
            status, 
            -- ✅ Date for the top left
            DATE_FORMAT(created_at, '%M %e, %Y') AS display_date,
            -- ✅ Time for the top right
            DATE_FORMAT(created_at, '%h:%i %p') AS display_time
        FROM notifications
        WHERE user_id = ? AND (role = 'pet_owner')
        ORDER BY 
            CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
            created_at DESC
        LIMIT 20
    ");
    $stmt->execute($user_id ? [$user_id] : []);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
