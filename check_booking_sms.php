<?php
require 'config.php';
date_default_timezone_set('Asia/Manila');

// 🔐 NOTE: Move to .env in production
$apiKey = '836eff6e31b18cbd39e1e33c3b24c29f';
$senderName = 'VetCareSys';

echo "--- Booking SMS Cron Running ---\n";

// Fetch pending booking SMS
$sql = "
    SELECT notif_id, message, number
    FROM notifications
    WHERE sms = 2
      AND status = 'unread'
      AND schedule_date <= NOW()
    LIMIT 50
";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No booking SMS to send.\n";
    exit;
}

// Prepared update (mark as sent)
$update = $pdo->prepare("
    UPDATE notifications
    SET sms = 0, status = 'read'
    WHERE notif_id = ?
");

foreach ($rows as $row) {

    $notif_id = $row['notif_id'];
    $message  = $row['message'];

    // 🔹 SYSTEM FORMAT: 09xxxxxxxxx
    $number = preg_replace('/\s+/', '', $row['number']);

    // ❌ If not valid 09 number → stop retrying
    if (!preg_match('/^09\d{9}$/', $number)) {
        echo "⚠️ Invalid stored number skipped: {$row['number']}\n";
        $update->execute([$notif_id]);
        continue;
    }

    // ✅ CONVERT HERE ONLY → Semaphore format
    $semaphoreNumber = '639' . substr($number, 1);

    // --- SEND SMS ---
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://semaphore.co/api/v4/messages',
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'apikey'     => $apiKey,
            'number'     => $semaphoreNumber,
            'message'    => $message,
            'sendername' => $senderName
        ]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $update->execute([$notif_id]);
        echo "✅ Sent booking SMS to {$semaphoreNumber}\n";
    } else {
        echo "❌ Failed SMS ({$httpCode}): {$response}\n";
        // sms remains = 2 → will retry next cron run
    }
}
?>
