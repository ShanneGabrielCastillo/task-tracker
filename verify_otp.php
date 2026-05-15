<?php
// verify_otp.php — Step 2: Verify OTP
// State is carried via URL params (rid + tok) — no sessions required.
// On success: marks record as verified, redirects to reset_password.php with same params.

require_once 'db.php';
require_once 'mailer.php';

header('ngrok-skip-browser-warning: true');

// Read URL params
$reset_id    = isset($_GET['rid'])  ? (int) $_GET['rid']          : 0;
$reset_token = isset($_GET['tok'])  ? trim($_GET['tok'])           : '';
$email_hint  = isset($_GET['hint']) ? trim($_GET['hint'])          : '';

// Validate params exist
if ($reset_id <= 0 || $reset_token === '') {
    header('Location: forgot_password.php');
    exit;
}

// Fetch the reset record — verify token matches (prevents guessing)
$stmt = $conn->prepare(
    'SELECT id, user_id, otp_hash, expires_at, used, verified, attempts
     FROM password_resets
     WHERE id = ? AND reset_token = ?'
);
$stmt->bind_param('is', $reset_id, $reset_token);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rec) {
    // Invalid or tampered link
    header('Location: forgot_password.php');
    exit;
}

$user_id = (int) $rec['user_id'];
$error   = '';
$success = '';

// ── RESEND ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {

    $stmt = $conn->prepare(
        'SELECT COUNT(*) AS cnt FROM password_resets
         WHERE user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $cnt = (int) $stmt->get_result()->fetch_assoc()['cnt'];
    $stmt->close();

    if ($cnt >= 3) {
        $error = 'Too many resend requests. Please wait 15 minutes.';
    } else {
        // Invalidate current record
        $upd = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();

        // Fetch email
        $stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $email = $stmt->get_result()->fetch_assoc()['email'] ?? '';
        $stmt->close();

        if ($email) {
            $otp         = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_hash    = password_hash($otp, PASSWORD_DEFAULT);
            $new_token   = bin2hex(random_bytes(32));
            $expires     = date('Y-m-d H:i:s', strtotime('+10 minutes'));

            $ins = $conn->prepare(
                'INSERT INTO password_resets (user_id, otp_hash, reset_token, expires_at) VALUES (?, ?, ?, ?)'
            );
            $ins->bind_param('isss', $user_id, $otp_hash, $new_token, $expires);
            $ins->execute();
            $new_id = (int) $conn->insert_id;
            $ins->close();

            sendMail($email, 'Your new Task Tracker verification code', otpEmailHtml($otp, 10));

            // Redirect to new OTP page with new params
            header('Location: verify_otp.php?rid=' . $new_id . '&tok=' . urlencode($new_token) . '&hint=' . urlencode($email_hint));
            exit;
        } else {
            $error = 'Could not resend — no email on file.';
        }
    }
}

// ── VERIFY OTP ────────────────────────────────────────────────────────────
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $digits    = [];
    $allFilled = true;
    foreach (['d1','d2','d3','d4','d5','d6'] as $k) {
        $v = trim($_POST[$k] ?? '');
        if ($v === '' || !ctype_digit($v)) $allFilled = false;
        $digits[] = $v;
    }
    $otp_input = implode('', $digits);

    if (!$allFilled || strlen($otp_input) !== 6) {
        $error = 'Please enter all 6 digits of your verification code.';

    } elseif ((int)$rec['used'] === 1) {
        $error = 'This code is no longer valid. Please request a new one.';

    } elseif (strtotime($rec['expires_at']) < time()) {
        $upd = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();
        $error = 'Your code has expired. Please request a new one.';

    } elseif ((int)$rec['attempts'] >= 5) {
        $upd = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
        $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();
        $error = 'Too many incorrect attempts. Please request a new code.';

    } elseif (!password_verify($otp_input, $rec['otp_hash'])) {
        $upd = $conn->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?');
        $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();
        $remaining = 5 - ((int)$rec['attempts'] + 1);
        $error = 'Incorrect code. ' . ($remaining > 0
            ? $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' remaining.'
            : 'No attempts remaining — please request a new code.');

    } else {
        // ✅ OTP correct — mark as verified in DB
        $upd = $conn->prepare('UPDATE password_resets SET verified = 1 WHERE id = ?');
        $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();

        // Redirect to reset page — token in URL proves identity, no session needed
        header('Location: reset_password.php?rid=' . $reset_id . '&tok=' . urlencode($reset_token));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Code — Task Tracker</title>
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
                <h2 class="auth-panel-headline">Check your<br>inbox.</h2>
                <p class="auth-panel-sub">We sent a 6-digit verification code to your registered email. Enter it to continue.</p>
            </div>
            <div class="auth-features">
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-green"></span> Code expires in 10 minutes</div>
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-blue"></span> Max 5 attempts allowed</div>
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
                <h1 class="auth-form-title">Enter your code</h1>
                <p class="auth-form-subtitle">We sent a 6-digit code to <strong><?php echo htmlspecialchars($email_hint ?: 'your email'); ?></strong></p>
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

            <!-- OTP form — passes rid+tok as hidden fields so POST keeps them -->
            <form method="POST" action="verify_otp.php?rid=<?php echo $reset_id; ?>&tok=<?php echo urlencode($reset_token); ?>&hint=<?php echo urlencode($email_hint); ?>" id="otp-form" novalidate>
                <div class="otp-label">Verification Code</div>
                <div class="otp-inputs">
                    <input class="otp-box" type="text" name="d1" id="d1" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" autofocus>
                    <input class="otp-box" type="text" name="d2" id="d2" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input class="otp-box" type="text" name="d3" id="d3" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <span class="otp-sep">—</span>
                    <input class="otp-box" type="text" name="d4" id="d4" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input class="otp-box" type="text" name="d5" id="d5" maxlength="1" inputmode="numeric" pattern="[0-9]">
                    <input class="otp-box" type="text" name="d6" id="d6" maxlength="1" inputmode="numeric" pattern="[0-9]">
                </div>
                <div class="otp-timer" id="otp-timer">Code expires in <span id="countdown">10:00</span></div>
                <button type="submit" class="auth-submit-btn" id="verify-btn">
                    <span class="btn-text">Verify Code</span>
                    <span class="btn-spinner" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
                    <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>

            <form method="POST" action="verify_otp.php?rid=<?php echo $reset_id; ?>&tok=<?php echo urlencode($reset_token); ?>&hint=<?php echo urlencode($email_hint); ?>" style="margin-top:16px;text-align:center;">
                <input type="hidden" name="action" value="resend">
                <p class="auth-switch" style="margin:0;">
                    Didn't receive it?
                    <button type="submit" id="resend-btn" style="background:none;border:none;cursor:pointer;color:var(--brand);font-weight:600;font-size:.875rem;font-family:inherit;padding:0;" disabled>
                        Resend code <span id="resend-timer">(60s)</span>
                    </button>
                </p>
            </form>
            <p class="auth-switch" style="margin-top:12px;"><a href="forgot_password.php" class="auth-switch-link">← Try a different account</a></p>
        </div>
    </div>
</div>
<script>
(function(){
    var boxes=Array.from(document.querySelectorAll('.otp-box')),form=document.getElementById('otp-form'),sub=false;
    boxes.forEach(function(box,i){
        box.addEventListener('input',function(){
            box.value=box.value.replace(/\D/g,'').slice(-1);
            if(box.value&&i<boxes.length-1)boxes[i+1].focus();
            if(!sub&&boxes.every(function(b){return b.value!=='';})){sub=true;form.submit();}
        });
        box.addEventListener('keydown',function(e){
            if(e.key==='Backspace'&&!box.value&&i>0)boxes[i-1].focus();
            if(e.key==='ArrowLeft'&&i>0)boxes[i-1].focus();
            if(e.key==='ArrowRight'&&i<boxes.length-1)boxes[i+1].focus();
        });
        box.addEventListener('paste',function(e){
            e.preventDefault();
            var p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            p.split('').forEach(function(c,j){if(boxes[j])boxes[j].value=c;});
            if(p.length>0)boxes[Math.min(p.length-1,5)].focus();
            if(!sub&&p.length===6){sub=true;form.submit();}
        });
    });
    form.addEventListener('submit',function(){
        var b=document.getElementById('verify-btn');
        b.querySelector('.btn-text').textContent='Verifying...';
        b.querySelector('.btn-spinner').style.display='inline-flex';
        b.querySelector('.btn-arrow').style.display='none';
        b.disabled=true;
    });
})();
(function(){
    var t=10*60,d=document.getElementById('countdown'),el=document.getElementById('otp-timer');
    var iv=setInterval(function(){t--;if(t<=0){clearInterval(iv);el.style.color='var(--danger)';el.textContent='Code expired — please request a new one.';return;}
    var m=Math.floor(t/60),s=t%60;d.textContent=m+':'+(s<10?'0':'')+s;if(t<=60)el.style.color='var(--danger)';},1000);
})();
(function(){
    var b=document.getElementById('resend-btn'),l=document.getElementById('resend-timer'),s=60;
    var iv=setInterval(function(){s--;if(s<=0){clearInterval(iv);b.disabled=false;l.textContent='';}else{l.textContent='('+s+'s)';}},1000);
})();
</script>
</body>
</html>
