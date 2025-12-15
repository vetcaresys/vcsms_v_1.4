<?php
session_start();
require 'config.php';
require 'mail.php';

if (!isset($_SESSION['resend_email'])) {
    header("Location: login.php?msg=No email found to resend.");
    exit;
}

$email = $_SESSION['resend_email'];
unset($_SESSION['resend_email']); // para dili magsige resend

/**
 * 🔹 Helper function: Build one email template for all accounts
 */
function buildVerificationEmail($name, $email, $token, $extraParams = '') {
    $verifyUrl = "https://vetcaresys-001-site1.ntempurl.com/verify.php?email=" . urlencode($email) . "&token=$token" . $extraParams; 

    return "
    <div style='font-family: \"Inter\", \"Poppins\", Arial, sans-serif; padding:20px; background:#eef2f7;'>
        <div style='max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; 
                    box-shadow:0 4px 12px rgba(0,0,0,0.08); overflow:hidden;'>

            <div style='background:#0a84ff; color:white; text-align:center; padding:20px 15px;'>
                <h2 style='margin:0; font-size:24px; font-weight:600; font-family:\"Poppins\", sans-serif;'>
                    VetCareSys
                </h2>
                <p style='margin:5px 0 0; font-size:14px; opacity:0.9;'>Account Verification</p>
            </div>

            <div style='padding:25px; color:#333; font-size:15px; line-height:1.6;'>
                <p style='margin-bottom:10px;'>Hello <strong style=\"font-weight:600;\">"
                    . htmlspecialchars($name) . 
                "</strong>,</p>

                <p style='margin-bottom:15px;'>
                    Thanks for signing up at 
                    <strong style='color:#0a84ff;'>VetCareSys</strong>. 
                    Please confirm your email address by clicking the button below:
                </p>

                <div style='text-align:center; margin:25px 0;'>
                    <a href='$verifyUrl'
                       style='display:inline-block; padding:14px 28px; background:#0a84ff; 
                              color:white; text-decoration:none; border-radius:8px;
                              font-size:16px; font-weight:600; font-family:\"Poppins\", sans-serif;
                              letter-spacing:0.3px;'>
                        Verify My Account
                    </a>
                </div>

                <p style='margin-top:10px; color:#555;'>
                    If you did not create this account, kindly ignore this email.
                </p>

                <hr style='border:none; border-top:1px solid #e5e7eb; margin:25px 0;'>

                <p style='font-size:12px; color:#888; text-align:center;'>
                    VetCareSys © " . date('Y') . " — All Rights Reserved
                </p>
            </div>
        </div>
    </div>";
}


/**
 * 🔹 Reusable email sender
 */
function sendVerificationMail($toEmail, $toName, $subject, $bodyHtml, $bodyText) {
    $mail = require 'mail.php';
    $mail->isHTML(true);
    $mail->setFrom("loelynates@gmail.com", "VetCareSys");
    $mail->addAddress($toEmail, $toName);
    $mail->Subject = $subject;
    $mail->Body = $bodyHtml;
    $mail->AltBody = $bodyText;
    $mail->send();
}

/**
 * 🔹 USERS
 */
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    if ($user['is_verified']) {
        header("Location: login.php?msg=Your email is already verified.");
        exit;
    }

    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE users SET verification_token=? WHERE email=?");
    $stmt->execute([$token, $email]);

    $body = buildVerificationEmail($user['name'], $email, $token);
    $text = "Copy this link to verify: https://vetcaresys-001-site1.ntempurl.com/verify.php?email=" . urlencode($email) . "&token=$token";

    sendVerificationMail($email, $user['name'], "Verify Your VetCareSys Account", $body, $text);

    header("Location: login.php?msg=Verification email sent. Please check your inbox.");
    exit;
}

/**
 * 🔹 STAFF
 */
$stmt = $pdo->prepare("SELECT * FROM staff WHERE email = ?");
$stmt->execute([$email]);
$staff = $stmt->fetch();

if ($staff) {
    if ($staff['is_verified']) {
        header("Location: login.php?msg=Your staff email is already verified.");
        exit;
    }

    $token = bin2hex(random_bytes(16));
    $stmt = $pdo->prepare("UPDATE staff SET verification_token=? WHERE email=?");
    $stmt->execute([$token, $email]);

    $body = buildVerificationEmail($staff['name'], $email, $token, "&staff=1");
    $text = "Copy this link to verify: https://vetcaresys-001-site1.ntempurl.com/verify.php?email=" . urlencode($email) . "&token=$token&staff=1";

    sendVerificationMail($email, $staff['name'], "Verify Your VetCareSys Staff Account", $body, $text);

    header("Location: login.php?msg=Verification email sent. Please check your inbox.");
    exit;
}

// 👉 Pwede pa nimo i-extend para sa doctors or other roles

header("Location: login.php?msg=Email not found.");
exit;
