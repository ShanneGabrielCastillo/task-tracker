<?php
// forgot_password.php — Step 1: Request OTP
// No sessions needed — state is tracked in the database.

require_once 'db.php';
require_once 'mailer.php';

header('ngrok-skip-browser-warning: true');

// Safe migrations
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL DEFAULT ''");
$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id          INT          AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    otp_hash    VARCHAR(255) NOT NULL,
    reset_token VARCHAR(64)  NOT NULL DEFAULT '',
    expires_at  DATETIME     NOT NULL,
    used        TINYINT(1)   NOT NULL DEFAULT 0,
    verified    TINYINT(1)   NOT NULL DEFAULT 0,
    attempts    TINYINT      NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");
$conn->query("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS reset_token VARCHAR(64) NOT NULL DEFAULT ''");
$conn->query("ALTER TABLE password_resets ADD COLUMN IF NOT EXISTS verified TINYINT(1) NOT NULL DEFAULT 0");

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');

    if ($identifier === '') {
        $error = 'Please enter your username or email address.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id, email FROM users WHERE username = ? OR email = ? LIMIT 1'
        );
        $stmt->bind_param('ss', $identifier, $identifier);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || empty($row['email'])) {
            $success = 'If that account exists and has an email on file, a reset code has been sent.';
        } else {
            $user_id = (int) $row['id'];
            $email   = $row['email'];

            // Rate-limit: max 3 per 15 min
            $stmt = $conn->prepare(
                'SELECT COUNT(*) AS cnt FROM password_resets
                 WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $cnt = (int) $stmt->get_result()->fetch_assoc()['cnt'];
            $stmt->close();

            if ($cnt >= 3) {
                $error = 'Too many reset requests. Please wait 15 minutes.';
            } else {
                // Invalidate old OTPs
                $del = $conn->prepare('UPDATE password_resets SET used = 1 WHERE user_id = ? AND used = 0');
                $del->bind_param('i', $user_id);
                $del->execute();
                $del->close();

                // Generate OTP + secure token
                $otp         = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $otp_hash    = password_hash($otp, PASSWORD_DEFAULT);
                $reset_token = bin2hex(random_bytes(32)); // 64-char hex token
                $expires     = date('Y-m-d H:i:s', strtotime('+10 minutes'));

                $ins = $conn->prepare(
                    'INSERT INTO password_resets (user_id, otp_hash, reset_token, expires_at)
                     VALUES (?, ?, ?, ?)'
                );
                $ins->bind_param('isss', $user_id, $otp_hash, $reset_token, $expires);
                $ins->execute();
                $reset_id = (int) $conn->insert_id;
                $ins->close();

                $sent = sendMail(
                    $email,
                    'Your Task Tracker verification code',
                    otpEmailHtml($otp, 10)
                );

                if ($sent) {
                    // Mask email for display
                    $at   = strpos($email, '@');
                    $hint = $at !== false
                        ? substr($email, 0, min(2, $at)) . str_repeat('*', max(0, $at - 2)) . substr($email, $at)
                        : '***';

                    // Pass reset_id and token in URL — no session needed
                    header('Location: verify_otp.php?rid=' . $reset_id . '&tok=' . urlencode($reset_token) . '&hint=' . urlencode($hint));
                    exit;
                } else {
                    $error = 'Failed to send the verification email. Please try again later.';
                    $conn->prepare('DELETE FROM password_resets WHERE id = ?')
                         ->bind_param('i', $reset_id) || null;
                    $d = $conn->prepare('DELETE FROM password_resets WHERE id = ?');
                    $d->bind_param('i', $reset_id); $d->execute(); $d->close();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-split-body">
<div class="auth-split">
    <div class="auth-panel-left">
        <div class="blob blob-1"></div><div class="blob blob-2"></div><div class="blob blob-3"></div>
        <div class="auth-panel-left-inner">
            <div class="auth-brand">
                <div class="auth-brand-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                </div>
                <span class="auth-brand-name">Task Tracker</span>
            </div>
            <div class="auth-panel-copy">
                <h2 class="auth-panel-headline">Forgot your<br>password?</h2>
                <p class="auth-panel-sub">Enter your username or email and we'll send you a verification code to get back in.</p>
            </div>
            <div class="auth-features">
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-green"></span> Secure OTP verification</div>
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-blue"></span> Expires in 10 minutes</div>
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-purple"></span> One-time use only</div>
            </div>
        </div>
    </div>
    <div class="auth-panel-right">
        <div class="auth-form-card">
            <div class="auth-mobile-brand">
                <div class="auth-brand-icon small">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                </div>
                <span>Task Tracker</span>
            </div>
            <div class="auth-form-header">
                <h1 class="auth-form-title">Reset Password</h1>
                <p class="auth-form-subtitle">Enter your username or email to receive a verification code</p>
            </div>
            <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>
            <?php if ($success !== ''): ?>
            <div class="auth-alert auth-alert-success" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>
            <?php if ($success === ''): ?>
            <form method="POST" action="forgot_password.php" class="auth-form" novalidate>
                <div class="auth-field">
                    <label for="identifier" class="auth-label">Username or Email</label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </span>
                        <input type="text" id="identifier" name="identifier" class="auth-input"
                               placeholder="Enter your username or email"
                               value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>"
                               required autofocus autocomplete="username" style="padding-left:42px;">
                    </div>
                </div>
                <button type="submit" class="auth-submit-btn" id="send-btn">
                    <span class="btn-text">Send Verification Code</span>
                    <span class="btn-spinner" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
                    <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>
            <?php endif; ?>
            <p class="auth-switch">Remember your password? <a href="index.php" class="auth-switch-link">Back to Sign In</a></p>
        </div>
    </div>
</div>
<script>
(function(){var f=document.querySelector('.auth-form'),b=document.getElementById('send-btn');if(!f||!b)return;f.addEventListener('submit',function(){b.querySelector('.btn-text').textContent='Sending...';b.querySelector('.btn-spinner').style.display='inline-flex';b.querySelector('.btn-arrow').style.display='none';b.disabled=true;});})();
</script>
</body>
</html>
