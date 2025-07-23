<?php
require 'config.php';

$to = $_POST['to'];
$subject = $_POST['subject'];
$body = $_POST['body'];
$cc = $_POST['cc'] ?? '';

$mail = getMailer();
$mail->addAddress($to);
if (!empty($cc)) $mail->addCC($cc);

$mail->Subject = $subject;
$mail->Body = $body;
$mail->isHTML(false); // Change to true if HTML mail

// Attach PDF if present
if (!empty($_FILES['attachment']['name'])) {
    $mail->addAttachment($_FILES['attachment']['tmp_name'], $_FILES['attachment']['name']);
}

if ($mail->send()) {
    echo "<script>alert('Email sent successfully'); window.location='../admin.php';</script>";
} else {
    echo "<script>alert('Email failed');</script>";
}
