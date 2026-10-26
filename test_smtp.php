<?php
require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);
$mail->SMTPDebug = 2; // Output debug info
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@cognivicdigital.com';
    $mail->Password   = 'Info@987--';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->Timeout    = 15;

    $mail->setFrom('info@cognivicdigital.com', 'Test');
    $mail->addAddress('socioninjas@gmail.com');
    $mail->Subject = 'Hostinger Test';
    $mail->Body    = 'Test email body';

    echo "Attempting to send...\n";
    $mail->send();
    echo "\nRESULT: Mail sent successfully!\n";
} catch (Exception $e) {
    echo "\nRESULT: Mail failed!\n";
    echo "Exception: " . $e->getMessage() . "\n";
    echo "ErrorInfo: " . $mail->ErrorInfo . "\n";
}
