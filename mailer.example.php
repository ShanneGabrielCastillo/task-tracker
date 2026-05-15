<?php
// mailer.php — Email sending helper for Task Tracker
// ─────────────────────────────────────────────────────────────────────────
// HOW TO CONFIGURE:
//   Option A (recommended) — Gmail App Password
//     1. Enable 2-Step Verification on your Google account
//     2. Go to Google Account → Security → App Passwords
//     3. Generate a password for "Mail" / "Windows Computer"
//     4. Fill in MAIL_USER and MAIL_PASS below
//
//   Option B — Mailtrap (free testing inbox, no real emails sent)
//     Host: sandbox.smtp.mailtrap.io  Port: 2525
//     Get credentials from mailtrap.io
//
//   Option C — PHP mail() fallback (works on some XAMPP setups with sendmail)
//     Set MAIL_DRIVER = 'mail' below
// ─────────────────────────────────────────────────────────────────────────

define('MAIL_DRIVER', 'smtp');          // 'smtp' or 'mail'
define('MAIL_HOST',   'smtp.gmail.com');
define('MAIL_PORT',   587);
define('MAIL_USER',   'your_email@gmail.com');   // ← your Gmail address
define('MAIL_PASS',   'your_app_password_here'); // ← 16-char Gmail App Password
define('MAIL_FROM',   'your_email@gmail.com');   // ← your Gmail address
define('MAIL_NAME',   'Task Tracker');

/**
 * sendMail($to, $subject, $htmlBody)
 * Sends an HTML email. Returns true on success, false on failure.
 * Uses a minimal raw SMTP implementation — no Composer required.
 */
function sendMail(string $to, string $subject, string $htmlBody): bool {
    if (MAIL_DRIVER === 'mail') {
        // Native PHP mail() — works if sendmail is configured
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . MAIL_NAME . " <" . MAIL_FROM . ">\r\n";
        return mail($to, $subject, $htmlBody, $headers);
    }

    // ── Raw SMTP over TLS (no library needed) ────────────────────────────
    $host = MAIL_HOST;
    $port = MAIL_PORT;
    $user = MAIL_USER;
    $pass = MAIL_PASS;

    // Build RFC 2822 message
    $boundary = md5(uniqid());
    $msg  = "Date: " . date('r') . "\r\n";
    $msg .= "From: " . MAIL_NAME . " <" . MAIL_FROM . ">\r\n";
    $msg .= "To: <" . $to . ">\r\n";
    $msg .= "Subject: " . $subject . "\r\n";
    $msg .= "MIME-Version: 1.0\r\n";
    $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
    $msg .= "\r\n";
    $msg .= $htmlBody;

    try {
        // Connect
        $sock = fsockopen("tcp://{$host}", $port, $errno, $errstr, 10);
        if (!$sock) throw new \RuntimeException("Connect failed: $errstr");

        $read = function() use ($sock) {
            $r = '';
            while ($line = fgets($sock, 512)) {
                $r .= $line;
                if (substr($line, 3, 1) === ' ') break;
            }
            return $r;
        };
        $send = function(string $cmd) use ($sock, $read) {
            fwrite($sock, $cmd . "\r\n");
            return $read();
        };

        $read(); // banner
        $send("EHLO localhost");
        $send("STARTTLS");

        // Upgrade to TLS
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

        $send("EHLO localhost");
        $send("AUTH LOGIN");
        $send(base64_encode($user));
        $resp = $send(base64_encode($pass));
        if (strpos($resp, '235') === false) throw new \RuntimeException("Auth failed");

        $send("MAIL FROM:<{$user}>");
        $send("RCPT TO:<{$to}>");
        $send("DATA");
        fwrite($sock, $msg . "\r\n.\r\n");
        $resp = $read();
        $send("QUIT");
        fclose($sock);

        return strpos($resp, '250') !== false;
    } catch (\Throwable $e) {
        error_log('[mailer] ' . $e->getMessage());
        return false;
    }
}

/**
 * otpEmailHtml($otp, $expiresMinutes)
 * Returns a clean HTML email body for the OTP.
 */
function otpEmailHtml(string $otp, int $expiresMinutes = 10): string {
    return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:40px 20px;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0"
             style="background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,.08);overflow:hidden;">
        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#6366f1,#8b5cf6);padding:32px 40px;text-align:center;">
            <div style="font-size:28px;margin-bottom:8px;">📋</div>
            <h1 style="margin:0;color:#fff;font-size:1.3rem;font-weight:700;letter-spacing:-.3px;">Task Tracker</h1>
          </td>
        </tr>
        <!-- Body -->
        <tr>
          <td style="padding:36px 40px;">
            <h2 style="margin:0 0 8px;font-size:1.2rem;color:#0f172a;">Password Reset Request</h2>
            <p style="margin:0 0 24px;color:#64748b;font-size:.9rem;line-height:1.6;">
              We received a request to reset your password. Use the verification code below.
              If you did not request this, you can safely ignore this email.
            </p>
            <!-- OTP box -->
            <div style="background:#eef2ff;border:2px dashed #6366f1;border-radius:12px;
                        padding:24px;text-align:center;margin-bottom:24px;">
              <p style="margin:0 0 6px;font-size:.75rem;font-weight:700;color:#6366f1;
                         text-transform:uppercase;letter-spacing:.8px;">Your verification code</p>
              <div style="font-size:2.4rem;font-weight:800;letter-spacing:12px;color:#0f172a;
                           font-family:monospace;">' . $otp . '</div>
            </div>
            <p style="margin:0 0 6px;color:#64748b;font-size:.85rem;text-align:center;">
              ⏱ This code expires in <strong>' . $expiresMinutes . ' minutes</strong>.
            </p>
            <p style="margin:0;color:#94a3b8;font-size:.8rem;text-align:center;">
              Never share this code with anyone.
            </p>
          </td>
        </tr>
        <!-- Footer -->
        <tr>
          <td style="background:#f8fafc;padding:20px 40px;text-align:center;
                     border-top:1px solid #e2e8f0;">
            <p style="margin:0;color:#94a3b8;font-size:.75rem;">
              Task Tracker · Automated security email
            </p>
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>';
}
