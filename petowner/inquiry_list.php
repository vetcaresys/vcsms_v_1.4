<?php
session_start();
require '../config.php';

// Fetch all inquiries
$stmt = $pdo->query("SELECT * FROM inquiry ORDER BY created_at DESC");
$inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inquiry List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h3>Inquiries</h3>
    <table class="table table-bordered table-striped mt-3">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Subject</th>
                <th>Message</th>
                <th>Status</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($inquiries as $inq): ?>
                <tr>
                    <td><?= $inq['inquiry_id'] ?></td>
                    <td><?= htmlspecialchars($inq['name']) ?></td>
                    <td><?= htmlspecialchars($inq['email']) ?></td>
                    <td><?= htmlspecialchars($inq['subject']) ?></td>
                    <td><?= htmlspecialchars($inq['message']) ?></td>
                    <td><?= $inq['status'] ?></td>
                    <td><?= $inq['created_at'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
