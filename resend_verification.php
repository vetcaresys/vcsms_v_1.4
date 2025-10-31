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
    $verifyUrl = "http://localhost/vcsms_v_1.4/verify.php?email=" . urlencode($email) . "&token=$token" . $extraParams;

    // Change to your actual hosted logo path
    $clinicLogo = "http://localhost/vcsms_v_1.4/assets/img/VetCareSystemLogo.png"; 

    return "
    <div style='font-family: Arial, sans-serif; padding:20px; background:#f4f6f9;'>
        <div style='max-width:600px; margin:0 auto; background:#fff; border-radius:8px; overflow:hidden;'>
            <div style='background:#0984e3; color:white; text-align:center; padding:15px;'>
                <img src='$clinicLogo' alt='VetCareSys' style='max-height:60px;'><br>
                <h2 style='margin:10px 0;'>VetCareSys</h2>
            </div>
            <div style='padding:20px; color:#333;'>
                <p>Hi <strong>" . htmlspecialchars($name) . "</strong>,</p>
                <p>Thanks for registering with <b>VetCareSys</b>! Please confirm your account by clicking the button below:</p>
                <p style='text-align:center;'>
                    <a href='$verifyUrl' 
                       style='display:inline-block; background:#0984e3; color:white; padding:12px 20px; 
                              text-decoration:none; border-radius:6px; font-weight:bold;'>
                        Verify My Account
                    </a>
                </p>
                <p>If you did not create this account, you can safely ignore this email.</p>
                <p style='font-size:12px; color:#888; text-align:center;'>VetCareSys © " . date('Y') . "</p>
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
    $text = "Copy this link to verify: http://localhost/vcsms_v_1.4/verify.php?email=" . urlencode($email) . "&token=$token";

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
    $text = "Copy this link to verify: http://localhost/vcsms_v_1.4/verify.php?email=" . urlencode($email) . "&token=$token&staff=1";

    sendVerificationMail($email, $staff['name'], "Verify Your VetCareSys Staff Account", $body, $text);

    header("Location: login.php?msg=Verification email sent. Please check your inbox.");
    exit;
}

// 👉 Pwede pa nimo i-extend para sa doctors or other roles

header("Location: login.php?msg=Email not found.");
exit;
