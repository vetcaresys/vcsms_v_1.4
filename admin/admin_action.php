<?php
session_start();
include '../config.php';
require '../mail.php'; // ✅ PHPMailer setup

// ✅ Only admins allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

if (isset($_GET['id']) && isset($_GET['action'])) {
    $id = intval($_GET['id']); // clinic_id
    $action = $_GET['action'];

    if (!in_array($action, ['approve', 'reject'])) {
        $_SESSION['message'] = "⚠️ Invalid action.";
        header("Location: index.php");
        exit;
    }

    try {
        // ✅ APPROVE ACTION
        if ($action === 'approve') {
            $status = 'approved';
            $stmt = $pdo->prepare("UPDATE clinics SET status = ?, resubmit_token = NULL WHERE clinic_id = ?");
            $stmt->execute([$status, $id]);
        }

        // ✅ REJECT ACTION
        elseif ($action === 'reject') {
            $status = 'rejected';
            $token  = bin2hex(random_bytes(16)); // generate re-submission token

            // ✅ Check which fields are missing
            $stmt_check = $pdo->prepare("SELECT clinic_name, address, contact_info, logo, business_permit FROM clinics WHERE clinic_id = ?");
            $stmt_check->execute([$id]);
            $clinicData = $stmt_check->fetch(PDO::FETCH_ASSOC);

            $missing = [];
            foreach ($clinicData as $field => $value) {
                if (empty($value)) {
                    $missing[] = ucfirst(str_replace('_', ' ', $field));
                }
            }

            $remarks = count($missing) > 0
                ? "Missing required fields: " . implode(', ', $missing)
                : "Incomplete or invalid clinic details.";

            // ✅ Update with rejection token only
            $stmt = $pdo->prepare("UPDATE clinics SET status = ?, resubmit_token = ? WHERE clinic_id = ?");
            $stmt->execute([$status, $token, $id]);
        }

        // ✅ Proceed if record was updated
        if ($stmt->rowCount() > 0) {
            // ✅ Fetch clinic + owner info
            $sql = "
                SELECT c.clinic_name, u.name AS owner_name, u.email, c.resubmit_token
                FROM clinics c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.clinic_id = ?
            ";
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute([$id]);
            $clinic = $stmt2->fetch(PDO::FETCH_ASSOC);

            if ($clinic) {
                $ownerName  = htmlspecialchars($clinic['owner_name']);
                $ownerEmail = htmlspecialchars($clinic['email']);
                $clinicName = htmlspecialchars($clinic['clinic_name']);
                $resubmitToken = $clinic['resubmit_token'] ?? null;

                // ✅ Email subject and body
                $subject = "Clinic Application " . ucfirst($status);

                if ($status === 'approved') {
                    $body = "
                        <h2>Good news, {$ownerName}!</h2>
                        <p>Your clinic <strong>{$clinicName}</strong> has been <span style='color:green;'>approved</span>.</p>
                        <p>You may now log in and manage your clinic on 
                        <a href='http://localhost/vcsms_v_1.4/'>VetCareSys</a>.</p>
                        <br><p>Thank you,<br>VetCareSys Team</p>
                    ";
                    $altBody = "Hello {$ownerName},\n\nYour clinic '{$clinicName}' has been approved.\n\nLogin here: http://localhost/vcsms_v_1.4/\n\nVetCareSys Team";
                } 
                else {
                    // ✅ Rejection + resubmission link
                    $editLink = "http://localhost/vcsms_v_1.4/clinic/edit_clinic.php?token={$resubmitToken}";
                    $body = "
                        <h2>Hello, {$ownerName}</h2>
                        <p>We reviewed your clinic <strong>{$clinicName}</strong> and found missing or incomplete details.</p>
                        <p><strong>Reason:</strong> {$remarks}</p>
                        <p>To fix the issues and re-submit your details, please click the link below:</p>
                        <p><a href='{$editLink}' 
                              style='background:#dc3545;color:#fff;padding:10px 15px;border-radius:5px;text-decoration:none;'>
                              Edit & Re-Submit Clinic Info
                        </a></p>
                        <p>If you have questions, contact the system administrator.</p>
                        <br><p>Thank you,<br>VetCareSys Team</p>
                    ";
                    $altBody = "Hello {$ownerName},\n\nYour clinic '{$clinicName}' was rejected.\n\n{$remarks}\n\nRe-submit here: {$editLink}\n\nVetCareSys Team";
                }

                // ✅ Send email
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($ownerEmail, $ownerName);
                    $mail->Subject = $subject;
                    $mail->Body    = $body;
                    $mail->AltBody = $altBody;
                    $mail->isHTML(true);

                    if ($mail->send()) {
                        $_SESSION['message'] = "Clinic '{$clinicName}' {$status} ✅ and email sent to {$ownerEmail}.";
                    } else {
                        $_SESSION['message'] = "Clinic '{$clinicName}' {$status} ✅ but email not sent.";
                        error_log("Mailer Error: " . $mail->ErrorInfo);
                    }
                } catch (Exception $e) {
                    $_SESSION['message'] = "Clinic '{$clinicName}' {$status} ✅ but email failed: " . $e->getMessage();
                }
            }
        } else {
            $_SESSION['message'] = "No changes made. Clinic may already be {$status}.";
        }

    } catch (PDOException $e) {
        $_SESSION['message'] = "Database error: " . $e->getMessage();
    }

    header("Location: index.php");
    exit;
} else {
    $_SESSION['message'] = "⚠️ Invalid request.";
    header("Location: index.php");
    exit;
}
?>
