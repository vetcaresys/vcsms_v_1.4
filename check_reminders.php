<?php
session_start();
require 'config.php'; // This file provides the $pdo object

// --- Configuration ---
$apiKey = '836eff6e31b18cbd39e1e33c3b24c29f'; // **CRITICAL: Replace with your actual Semaphore API KEY**
$senderName = 'VetCareSys'; // Your preferred sender name.

// --- 1. Reminder Generation Function (Creates the SMS records) ---
function generate_reminders($pdo) {
    global $current_datetime;
    $current_datetime = date('Y-m-d H:i:s'); // Ensure this is available

    // ... [Your existing Reminder Generation logic goes here] ...
    // This part should only run once a day, or when needed.
    // However, if it must run every 10 seconds, it's fine as long as it's fast.

    echo "--- Running Reminder Generation ---\n";

    $sql_select = "
        SELECT 
            notif_id, user_id, role, message, subject, link, schedule_date, number 
        FROM 
            `notifications` 
        WHERE 
            DATE(`schedule_date`) = DATE(NOW() + INTERVAL 1 DAY) 
        AND 
            `sms` = '1'
    ";

    try {
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute();
        $results = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

        if (count($results) > 0) {
            echo "Found " . count($results) . " new reminders to generate for tomorrow.\n";

            $sql_insert = "
                INSERT INTO `notifications` 
                (`user_id`, `role`,`clinic_id`, `message`, `subject`, `link`, `status`,`schedule_date`, `sms`, `number`, `created_at`) 
                VALUES 
                (?, ?, ?,?, ?, ?, 'unread',?, '2',?, ?)
            ";
            $stmt_insert = $pdo->prepare($sql_insert);
            
            $sql_update = "
                UPDATE `notifications` 
                SET `sms` = '0' 
                WHERE `notif_id` = ?
            ";
            $stmt_update = $pdo->prepare($sql_update);


            foreach($results as $row) {
                // Fetch User Name
                $stmt_user = $pdo->prepare("SELECT `name` FROM `users` WHERE `user_id` = :user_id");
                $stmt_user->bindParam(':user_id', $row['user_id'], PDO::PARAM_INT);
                $stmt_user->execute();
                $user = $stmt_user->fetch();
                
                $user_name = isset($user['name']) ? $user['name'] : 'User';
                $reminder_message = "REMINDER: Hi '{$user_name}' ,Your scheduled notification about '{$row['subject']}' is tomorrow.";
                $reminder_subject = "Reminder: " . $row['subject'];

                // Insert the NEW Reminder Notification (sms=2)
                $stmt_insert->execute([
                    $row['user_id'], $row['role'],$row['clinic_id'], $reminder_message, $reminder_subject, $row['link'], 
                    $row['schedule_date'], $row['number'], $current_datetime
                ]);

                // Update the Original Notification's SMS flag (sms=0)
                $stmt_update->execute([$row['notif_id']]);
            }
        } else {
            echo "No new reminders to generate today.\n";
        }
    } catch (PDOException $e) {
        echo "🚨 Database Error in Generation: " . $e->getMessage() . "\n";
    }
}

// --- 2. SMS Sending Function (Sends the created SMS records) ---
function send_sms_reminders($pdo) {
    global $apiKey, $senderName; // Use global variables for config

    echo "--- Running SMS Sending ---\n";

    // Select all notifications with sms = '2' (ready to send) AND status = 'unread'
    $sql_select = "
        SELECT notif_id, message, number 
        FROM notifications 
        WHERE sms = '2' AND status = 'unread'
        LIMIT 100 
    ";

    try {
        $stmt_select = $pdo->prepare($sql_select);
        $stmt_select->execute();
        $pending_notifications = $stmt_select->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($pending_notifications)) {
            echo "✅ No pending SMS notifications (sms=2) to send.\n";
            return;
        }

        echo "Found " . count($pending_notifications) . " reminders ready for SMS.\n";
        
        // Prepare the update statement once: Set sms=0 AND status='sent' on success
        $sql_update = "
            UPDATE notifications 
            SET sms = '0', status = 'read'
            WHERE notif_id = :id
        ";
        $stmt_update = $pdo->prepare($sql_update);

        foreach ($pending_notifications as $notification) {
            $notif_id = $notification['notif_id'];
            $recipient_number = $notification['number'];
            $sms_message = $notification['message'];

            // Strict check for valid PH mobile number format (09xxxxxxxxx or 639xxxxxxxxx)
            // If the number format is invalid, mark it as done (sms=0) to stop perpetual retries.
            if (!preg_match('/^(09|639)\d{9}$/', $recipient_number)) {
                 echo "⚠️ Skipped ID $notif_id: Invalid phone number format ($recipient_number). Marking as done (sms=0).\n";
                 //stmt_update->execute([':id' => $notif_id]); 
                 //continue; 
            }

            // --- cURL Implementation for Semaphore API ---
            $ch = curl_init();
            $parameters = array(
                'apikey' => $apiKey, 
                'number' => $recipient_number,
                'message' => $sms_message,
                'sendername' => $senderName
            );
            
            curl_setopt( $ch, CURLOPT_URL, 'https://semaphore.co/api/v4/messages' );
            curl_setopt( $ch, CURLOPT_POST, 1 );
            curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $parameters ) );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            
            $output = curl_exec( $ch );
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close ($ch);

            if ($http_code >= 200 && $http_code < 300) {
                echo "✅ SMS sent to $recipient_number (ID $notif_id). Marking as sent (sms=0, status=sent).\n";
                // Execute update: sms=0, status=sent
                $stmt_update->execute([':id' => $notif_id]); 
            } else {
                // If the error is 500, it's a server side error (likely bad payload/format)
                // If it's a temporary network error, retaining sms='2' allows retry.
                echo "❌ SMS failed for ID $notif_id. HTTP Code: $http_code. Response: $output. Retaining sms='2' for retry.\n";
                
                // OPTIONAL: You might want to log permanent failures (like repeated 500s) to another table
                // and mark them as sms='0'/'failed' after 3-5 retries.
            }
        }

    } catch (PDOException $e) {
        echo "🚨 Database Error in Sending: " . $e->getMessage() . "\n";
    }
}

// --- Execute the two separate, independent jobs ---
generate_reminders($pdo);
send_sms_reminders($pdo);
// No need to wrap in a single function anymore; just execute the separate jobs.

?>