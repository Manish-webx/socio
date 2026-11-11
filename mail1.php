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

/* ==========================================================================
   SPAM PROTECTION
   Spam is silently dropped with a fake "success" so bots don't adapt.
   ========================================================================== */
function sn_spam_exit($reason, $isAjax) {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    @file_put_contents(sys_get_temp_dir() . '/socioninjas_spam.log',
        date('Y-m-d H:i:s') . " | $ip | $reason\n", FILE_APPEND | LOCK_EX);
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'message' => 'Your inquiry has been successfully sent!', 'redirect' => 'thank-you.html']);
        exit();
    }
    header("Location: thank-you.html");
    exit();
}

// 1. Only POST requests
if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    sn_spam_exit('not POST', $isAjax);
}

// 2. Honeypot: hidden field that humans never see or fill
if (!empty($_POST['company_website'])) {
    sn_spam_exit('honeypot filled', $isAjax);
}

// 3. JavaScript token: set by script.js; plain bots posting directly won't have it
$year = (int) date('Y');
$validTokens = ['sn-human-' . ($year - 1), 'sn-human-' . $year, 'sn-human-' . ($year + 1)];
if (!isset($_POST['sn_token']) || !in_array($_POST['sn_token'], $validTokens, true)) {
    sn_spam_exit('missing JS token', $isAjax);
}

// 4. Time trap: humans need at least 4 seconds to fill the form
$fillTime = isset($_POST['sn_fill_ms']) ? (int) $_POST['sn_fill_ms'] : 0;
if ($fillTime < 4000) {
    sn_spam_exit("too fast ({$fillTime}ms)", $isAjax);
}

// 5. Field validation
$phoneDigits = preg_replace('/\D/', '', $phone);
if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 13) {
    sn_spam_exit('invalid phone', $isAjax);
}
if (mb_strlen($name) < 2 || mb_strlen($name) > 60 || preg_match('/https?:|www\.|[<>{}\[\]@\/\\\\]|\d{3,}/i', $name)) {
    sn_spam_exit('invalid name', $isAjax);
}
if (mb_strlen($notes) > 1500) {
    sn_spam_exit('notes too long', $isAjax);
}

// 6. Content filters: too many links, foreign scripts, spam keywords
$allText = $name . ' ' . $notes . ' ' . $email;
if (preg_match_all('/https?:\/\/|www\./i', $allText) > 2) {
    sn_spam_exit('too many links', $isAjax);
}
if (preg_match('/[\x{0400}-\x{04FF}\x{4E00}-\x{9FFF}\x{3040}-\x{30FF}\x{AC00}-\x{D7AF}]/u', $allText)) {
    sn_spam_exit('foreign script', $isAjax);
}
$spamWords = [
    'viagra', 'cialis', 'casino', 'crypto', 'bitcoin', 'forex', 'loan offer', 'porn', 'escort service', 'backlink', 'guest post', 'seo service', 'rank your website', 'first page of google',
    'web traffic', 'increase traffic', 'buy followers', 'unsubscribe', 'click here', 'investment opportunity',
    'make money', 'earn money', 'work from home', 'gambling', 'betting', 'lottery', 'telegram', 'bit.ly',
];
$lowerText = mb_strtolower($allText);
foreach ($spamWords as $word) {
    if (strpos($lowerText, $word) !== false) {
        sn_spam_exit("keyword: $word", $isAjax);
    }
}

// 7. Rate limit: max 3 submissions per IP per 15 minutes
$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$rateFile = sys_get_temp_dir() . '/socioninjas_rate_' . md5($ip) . '.json';
$now = time();
$hits = [];
if (is_file($rateFile)) {
    $hits = json_decode((string) @file_get_contents($rateFile), true);
    if (!is_array($hits)) $hits = [];
}
$hits = array_values(array_filter($hits, function ($t) use ($now) { return $t > $now - 900; }));
if (count($hits) >= 3) {
    sn_spam_exit('rate limited', $isAjax);
}
$hits[] = $now;
@file_put_contents($rateFile, json_encode($hits), LOCK_EX);

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