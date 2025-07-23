<?php
// email/config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Make sure PHPMailer is installed via Composer

function getMailer() {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com';
        $mail->Password = 'your-app-password'; // Use App Password (NOT your Gmail password)
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('your-email@gmail.com', 'Admin Crescent Portal');
        return $mail;
    } catch (Exception $e) {
        die("Mailer Error: {$mail->ErrorInfo}");
    }
}
