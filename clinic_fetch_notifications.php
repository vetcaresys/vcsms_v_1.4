<?php
session_start();
require 'config.php';
$clinic_id = $_SESSION['clinic_id'];

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

        WHERE (role = 'employee' OR role = 'staff')
        AND clinic_id=?   -- MODIFIED WHERE CLAUSE
        ORDER BY 
            CASE WHEN status = 'unread' THEN 0 ELSE 1 END,
            created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$clinic_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($notifications);
} catch (Exception $e) {
    echo json_encode(["error" => $e->getMessage()]);
}
