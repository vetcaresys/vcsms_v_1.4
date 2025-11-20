<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

$mail->isSMTP();
$mail->Host       = 'smtp.gmail.com';
$mail->SMTPAuth   = true;
$mail->Username   = 'vetcaresys@gmail.com';
$mail->Password   = 'ddghlcrdfyroulbj'; // your app password
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port       = 587;

$mail->isHTML(true);

// Optional debugging line
// $mail->SMTPDebug = SMTP::DEBUG_SERVER;

return $mail;
