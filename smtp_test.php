<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // load PHPMailer

// Replace these with the email you want to send to
$to_email = 'disha.boston@gmail.com';
$to_name = 'Disha Boston';

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'disha.trash.boston@gmail.com'; // your Gmail
    $mail->Password   = 'blbjzzomlfywwuhp'; // your Gmail app password
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;

    // Recipients
    $mail->setFrom('disha.trash.boston@gmail.com', 'Test Sender');
    $mail->addAddress($to_email, $to_name);

    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from PHPMailer';
    $mail->Body    = "Hello {$to_name},<br>This is a <b>test email</b> sent using PHPMailer and Gmail SMTP.";

    $mail->send();
    echo "Test email sent successfully to {$to_email}!";
} catch (Exception $e) {
    echo "Failed to send email. Error: {$mail->ErrorInfo}";
}
