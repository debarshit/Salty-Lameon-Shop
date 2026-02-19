<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

function sendPasswordResetEmail($email, $token) {
    $resetLink = "https://thesaltylameon.com/reset-password.php?token=" . urlencode($token);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = $_ENV['SMTP_HOST'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $_ENV['SMTP_USER'];
    $mail->Password   = $_ENV['SMTP_PASS'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('no-reply@thesaltylameon.com', 'Rashmi Ramesh');
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