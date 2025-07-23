<?php
include '../db.php';
require 'config.php';

$result = $conn->query("SELECT name, email, attendance FROM students WHERE attendance < 60");
$mail = getMailer();

while ($row = $result->fetch_assoc()) {
    if (!empty($row['email'])) {
        $mail->clearAllRecipients();
        $mail->addAddress($row['email']);
        $mail->Subject = "Low Attendance Alert";
        $mail->Body = "Dear {$row['name']},\n\nYour attendance is currently at {$row['attendance']}%. Please improve your attendance to avoid academic issues.\n\nRegards,\nAdmin";
        $mail->send();
    }
}
