<?php
session_start();
require 'config.php'; // This file provides the $pdo object

// --- The Core Reminder Function ---
function run_reminder_check($pdo) {
    
    // 1. Calculate Tomorrow's Date (YYYY-MM-DD)
    $tomorrow_date = date('Y-m-d');
    
    // NOTE: We do NOT use strtotime('+1 day') here. We need to check if the 
    // date in the DB is tomorrow's date. See SQL fix below.
    
    $current_datetime = date('Y-m-d H:i:s');
    
    // 2. Query the Database for Notifications Scheduled for Tomorrow
    $sql_select = "
        SELECT 
            notif_id, 
            user_id, 
            role, 
            message, 
            subject, 
            link, 
            schedule_date 
        FROM 
            `notifications` 
        WHERE 
            -- FIX 1: Use DATE() function to compare only the date part of the column
            DATE(`schedule_date`) = DATE(NOW() + INTERVAL 1 DAY) 
        AND 
            -- Flag to indicate this is an original notification that needs a reminder
            `sms` = '1' 
    ";

    $stmt_select = $pdo->prepare($sql_select);
    // No explicit binding is needed here as the date logic is entirely within the SQL query
    $stmt_select->execute();
    $results = $stmt_select->fetchAll(PDO::FETCH_ASSOC);

    if (count($results) > 0) {
        
        // Prepare statements outside the loop for efficiency
        
        // FIX 2: Correct PDO placeholders and variable names for bindValue/execute
        $sql_insert = "
            INSERT INTO `notifications` 
            (`user_id`, `role`, `message`, `subject`, `link`, `status`, `sms`, `created_at`) 
            VALUES 
            (?, ?, ?, ?, ?, 'unread', '0', ?)
        ";
        $stmt_insert = $pdo->prepare($sql_insert);
        
        // FIX 3: Prepare the update statement
        $sql_update = "
            UPDATE `notifications` 
            SET `sms` = '0' -- Set flag to 0 so it won't be reminded again
            WHERE `notif_id` = ?
        ";
        $stmt_update = $pdo->prepare($sql_update);


        foreach($results as $row) {
            
            // Generate Reminder Details
            $reminder_message = "REMINDER: Your scheduled notification about '" . $row['subject'] . "' is tomorrow.";
            $reminder_subject = "Reminder: " . $row['subject'];

            // 4. Insert the NEW Reminder Notification using PDO execute
            $stmt_insert->execute([
                $row['user_id'], 
                $row['role'], 
                $reminder_message, 
                $reminder_subject, 
                $row['link'], 
                $current_datetime
            ]);

            // 5. Update the Original Notification's SMS flag
            $stmt_update->execute([$row['notif_id']]);
        }
        
    }
}

// Execute the function every time the script runs
run_reminder_check($pdo);

?>