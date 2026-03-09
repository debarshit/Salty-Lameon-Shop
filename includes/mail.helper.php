<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendPasswordResetEmail($email, $token) {
    $resetLink = "https://thesaltylameon.com/reset-password?token=" . urlencode($token);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->Timeout = 15;

$mail->SMTPDebug = 2; 
$mail->Debugoutput = 'error_log';

    $mail->setFrom('rashmi@thesaltylameon.com', 'Rashmi Ramesh');
    $mail->addAddress($email);

    $mail->isHTML(true);
    $mail->Subject = 'Reset Your Password';
    $mail->Body = "
        <p>You requested a password reset.</p>
        <p>
            <a href='{$resetLink}'>Click here to reset your password</a>
        </p>
        <p>This link will expire in 1 hour.</p>
    ";

    $mail->send();
}