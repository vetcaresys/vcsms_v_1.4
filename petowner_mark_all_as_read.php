<?php
session_start();
require 'config.php'; // Assuming config.php is one level above
$user_id = $_GET['user_id'] ?? null;
header('Content-Type: application/json');
/* Check if admin is logged in and the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    http_response_code(403); // Forbidden
    exit;
}*/

try {
    // Update the notification status to 'read' for all notifications 
    // targeted at the admin role or the specific admin user ID.
    // Use a transaction for safety if your environment supports it.
    $sql = "
        UPDATE notifications
        SET status = 'read'
        WHERE status = 'unread'
        AND (role = 'pet_owner')
        AND user_id = ?
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($user_id ? [$user_id] : []);

    // Success response
    http_response_code(200);

} catch (PDOException $e) {
    // Log the error and return a server error status
    error_log("Mark All As Read Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
}
?>