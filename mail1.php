<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/PHPMailer.php';
require __DIR__ . '/PHPMailer/SMTP.php';
require __DIR__ . '/PHPMailer/Exception.php';

// Set Indian Standard Time
date_default_timezone_set('Asia/Kolkata');

// Form Data Extraction & Sanitization
$name    = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone   = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$email   = isset($_POST['email']) ? trim($_POST['email']) : '';
$service = isset($_POST['service']) ? trim($_POST['service']) : '';
$budget  = isset($_POST['budget']) ? trim($_POST['budget']) : '';
$notes   = isset($_POST['notes']) ? trim($_POST['notes']) : (isset($_POST['message']) ? trim($_POST['message']) : '');
$pageUrl = isset($_POST['url']) && !empty($_POST['url']) ? trim($_POST['url']) : (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Socio Ninjas Website');
$dateStr = date('d M Y, h:i A') . ' IST';

// Determine if request is AJAX
$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
       || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
       || (isset($_POST['ajax']) && $_POST['ajax'] === '1');

// Basic validation: Name or phone should be present
if (empty($name) && empty($phone)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8', true, 400);
        echo json_encode(['status' => 'error', 'message' => 'Please provide your name or phone number.']);
        exit();
    }
    header("Location: index.html");
    exit();
}

$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'info@cognivicdigital.com';
    $mail->Password   = 'Info@987--';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;
    $mail->CharSet    = 'UTF-8';

    // Email Addresses
    $mail->setFrom('info@cognivicdigital.com', 'Socio Ninjas Website');
    $mail->addAddress('socioninjas@gmail.com', 'Socio Ninjas');
    $mail->addAddress('manishkushwahaf7@gmail.com', 'Admin Lead');

    if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mail->addReplyTo($email, $name ?: 'Prospective Client');
    }

    // Email Subject
    $serviceTag = !empty($service) ? "[$service]" : "[Inquiry]";
    $clientTag  = !empty($name) ? "from $name" : "New Lead";
    $mail->Subject = "Socio Ninjas Lead $serviceTag $clientTag";

    // Clean display strings
    $safeName    = htmlspecialchars($name ?: 'Not provided');
    $safePhone   = htmlspecialchars($phone ?: 'Not provided');
    $safeEmail   = htmlspecialchars($email ?: 'Not provided');
    $safeService = htmlspecialchars($service ?: 'Digital Marketing');
    $safeBudget  = htmlspecialchars($budget ?: 'Not specified');
    $safeNotes   = nl2br(htmlspecialchars($notes ?: 'No additional notes provided.'));
    $safePage    = htmlspecialchars($pageUrl);

    // HTML Email Body
    $mail->isHTML(true);
    $mail->Body = '
    <!DOCTYPE html>
    <html>
    <head>
      <meta charset="utf-8">
      <title>New Lead — Socio Ninjas</title>
    </head>
    <body style="margin: 0; padding: 24px; background-color: #080c0a; font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, Helvetica, Arial, sans-serif; color: #e4e4e7;">
      <div style="max-width: 620px; margin: 0 auto; background: #111714; border: 1px solid #79b434; border-radius: 14px; overflow: hidden; box-shadow: 0 14px 40px rgba(0,0,0,0.6);">
        
        <div style="background: linear-gradient(135deg, #111714 0%, #1a2717 100%); padding: 28px 24px; border-bottom: 2px solid #79b434;">
          <h1 style="margin: 0; font-size: 24px; font-weight: 800; letter-spacing: 1.5px; color: #ffffff;">SOCIO <span style="color: #79b434;">NINJAS</span></h1>
          <p style="margin: 6px 0 0; font-size: 13px; color: #a1a1aa; text-transform: uppercase; letter-spacing: 1px;">⚡ New Inbound Client Inquiry</p>
        </div>

        <div style="padding: 28px 24px;">
          <table style="width: 100%; border-collapse: collapse; font-size: 14px; line-height: 1.6;">
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; width: 36%; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Client Name</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 600; font-size: 15px;">' . $safeName . '</td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">WhatsApp / Phone</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a;">
                <a href="tel:' . urlencode($phone) . '" style="color: #00f2fe; text-decoration: none; font-weight: 600;">' . $safePhone . '</a>
                &nbsp;|&nbsp;
                <a href="https://wa.me/' . preg_replace('/[^0-9]/', '', $phone) . '" target="_blank" style="color: #25d366; text-decoration: none;">Chat on WhatsApp &rarr;</a>
              </td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Email Address</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a;">
                <a href="mailto:' . $safeEmail . '" style="color: #e4e4e7; text-decoration: none;">' . $safeEmail . '</a>
              </td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Service Requested</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #ffffff; font-weight: 600;">' . $safeService . '</td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Monthly Budget</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #fbbf24; font-weight: 600;">' . $safeBudget . '</td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Details / Website / Goals</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #f4f4f5; background: rgba(0,0,0,0.25); border-radius: 6px; padding-left: 8px;">' . $safeNotes . '</td>
            </tr>
            <tr>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Submitted On Page</td>
              <td style="padding: 11px 0; border-bottom: 1px solid #27272a; color: #a1a1aa; font-size: 13px; word-break: break-all;">' . $safePage . '</td>
            </tr>
            <tr>
              <td style="padding: 11px 0; color: #8ed638; font-weight: 600; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">Timestamp</td>
              <td style="padding: 11px 0; color: #a1a1aa; font-size: 13px;">' . $dateStr . '</td>
            </tr>
          </table>
        </div>

        <div style="background: #090e0c; padding: 18px 24px; text-align: center; border-top: 1px solid #1f2923;">
          <p style="margin: 0; font-size: 12px; color: #71717a;">
            © ' . date('Y') . ' Socio Ninjas • Official Agency Lead Notification • Delhi, India
          </p>
        </div>

      </div>
    </body>
    </html>';

    // Plaintext Alternative
    $mail->AltBody = "SOCIO NINJAS — NEW INBOUND LEAD\n" .
                     "===================================\n\n" .
                     "Name: $name\n" .
                     "Phone: $phone\n" .
                     "Email: $email\n" .
                     "Service: $service\n" .
                     "Budget: $budget\n" .
                     "Details / Goals: $notes\n" .
                     "Page: $pageUrl\n" .
                     "Time: $dateStr\n";

    $mail->send();

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'status'   => 'success',
            'message'  => 'Your inquiry has been successfully sent!',
            'redirect' => 'thank-you.html'
        ]);
        exit();
    }

    header("Location: thank-you.html");
    exit();

} catch (Exception $e) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode([
            'status'  => 'error',
            'message' => 'Mailer Error: ' . $mail->ErrorInfo
        ]);
        exit();
    }

    echo "<script>
        alert('Thank you for reaching out! Your message was recorded. Connecting you directly on WhatsApp.');
        window.location.href = 'thank-you.html';
    </script>";
    exit();
}