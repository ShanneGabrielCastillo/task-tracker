<?php
// reset_password.php — Step 3: Set new password
// Access controlled by rid+tok URL params + verified=1 in DB. No sessions needed.

require_once 'db.php';

header('ngrok-skip-browser-warning: true');

$reset_id    = isset($_GET['rid']) ? (int) $_GET['rid']   : 0;
$reset_token = isset($_GET['tok']) ? trim($_GET['tok'])    : '';

// Validate params
if ($reset_id <= 0 || $reset_token === '') {
    header('Location: forgot_password.php');
    exit;
}

// Fetch record — must be verified=1, used=0, not expired, token matches
$stmt = $conn->prepare(
    'SELECT id, user_id, expires_at, used, verified
     FROM password_resets
     WHERE id = ? AND reset_token = ?'
);
$stmt->bind_param('is', $reset_id, $reset_token);
$stmt->execute();
$rec = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$rec) {
    header('Location: forgot_password.php');
    exit;
}

if ((int)$rec['used'] === 1) {
    header('Location: forgot_password.php?msg=used');
    exit;
}

if ((int)$rec['verified'] !== 1) {
    // OTP not yet verified — send back to verify page
    header('Location: verify_otp.php?rid=' . $reset_id . '&tok=' . urlencode($reset_token));
    exit;
}

if (strtotime($rec['expires_at']) < time()) {
    $upd = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
    $upd->bind_param('i', $reset_id); $upd->execute(); $upd->close();
    header('Location: forgot_password.php?msg=expired');
    exit;
}

$user_id = (int) $rec['user_id'];
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_pw  = $_POST['new_password']     ?? '';
    $conf_pw = $_POST['confirm_password'] ?? '';

    if ($new_pw === '' || $conf_pw === '') {
        $error = 'Both password fields are required.';
    } elseif (strlen($new_pw) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_pw !== $conf_pw) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($new_pw, PASSWORD_DEFAULT);

        $upd = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
        $upd->bind_param('si', $hashed, $user_id);
        $ok = $upd->execute();
        $upd->close();

        if ($ok) {
            // Mark OTP as used — prevents replay
            $del = $conn->prepare('UPDATE password_resets SET used = 1 WHERE id = ?');
            $del->bind_param('i', $reset_id); $del->execute(); $del->close();

            // Redirect to login with success message
            header('Location: index.php?reset=success');
            exit;
        } else {
            $error = 'Failed to update password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password — Task Tracker</title>
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
                <h2 class="auth-panel-headline">Almost there.<br>Set your new<br>password.</h2>
                <p class="auth-panel-sub">Choose a strong password. You'll use it to sign in from now on.</p>
            </div>
            <div class="auth-features">
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-green"></span> Minimum 6 characters</div>
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-blue"></span> Securely hashed</div>
                <div class="auth-feature-pill"><span class="auth-feature-dot dot-purple"></span> Old password invalidated</div>
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
                <h1 class="auth-form-title">New Password</h1>
                <p class="auth-form-subtitle">Enter and confirm your new password below</p>
            </div>
            <?php if ($error !== ''): ?>
            <div class="auth-alert auth-alert-error" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="reset_password.php?rid=<?php echo $reset_id; ?>&tok=<?php echo urlencode($reset_token); ?>" class="auth-form" novalidate>
                <div class="auth-field">
                    <label for="new_password" class="auth-label">New Password <span style="color:var(--danger)">*</span></label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                        <input type="password" id="new_password" name="new_password" class="auth-input" placeholder="Min. 6 characters" required autocomplete="new-password">
                        <button type="button" class="auth-pw-toggle" id="toggle-new" aria-label="Toggle">
                            <svg class="eye-show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-hide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <div class="auth-strength" id="strength-wrap" style="display:none;">
                        <div class="auth-strength-bar">
                            <div class="strength-seg" id="seg1"></div><div class="strength-seg" id="seg2"></div>
                            <div class="strength-seg" id="seg3"></div><div class="strength-seg" id="seg4"></div>
                        </div>
                        <span class="auth-strength-label" id="strength-label"></span>
                    </div>
                </div>
                <div class="auth-field">
                    <label for="confirm_password" class="auth-label">Confirm Password <span style="color:var(--danger)">*</span></label>
                    <div class="auth-input-wrap">
                        <span class="auth-input-icon"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg></span>
                        <input type="password" id="confirm_password" name="confirm_password" class="auth-input" placeholder="Repeat new password" required autocomplete="new-password">
                        <button type="button" class="auth-pw-toggle" id="toggle-confirm" aria-label="Toggle">
                            <svg class="eye-show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-hide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                    <span class="auth-strength-label" id="match-label" style="margin-top:4px;display:block;"></span>
                </div>
                <button type="submit" class="auth-submit-btn" id="reset-btn">
                    <span class="btn-text">Reset Password</span>
                    <span class="btn-spinner" style="display:none;"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg></span>
                    <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </button>
            </form>
            <p class="auth-switch" style="margin-top:16px;"><a href="index.php" class="auth-switch-link">← Back to Sign In</a></p>
        </div>
    </div>
</div>
<script>
['toggle-new','toggle-confirm'].forEach(function(id){
    var btn=document.getElementById(id);if(!btn)return;
    var tid=id==='toggle-new'?'new_password':'confirm_password';
    btn.addEventListener('click',function(){var inp=document.getElementById(tid),h=inp.type==='password';inp.type=h?'text':'password';btn.querySelector('.eye-show').style.display=h?'none':'';btn.querySelector('.eye-hide').style.display=h?'':'none';});
});
(function(){
    var inp=document.getElementById('new_password'),wrap=document.getElementById('strength-wrap'),lbl=document.getElementById('strength-label');
    var segs=[1,2,3,4].map(function(n){return document.getElementById('seg'+n);});
    var colors=['active-weak','active-fair','active-good','active-strong'],labels=['Weak','Fair','Good','Strong'];
    inp.addEventListener('input',function(){var pw=inp.value;if(!pw){wrap.style.display='none';return;}wrap.style.display='block';
    var s=0;if(pw.length>=6)s++;if(pw.length>=10)s++;if(/[A-Z]/.test(pw))s++;if(/[0-9!@#$%^&*]/.test(pw))s++;s=Math.max(1,s);
    segs.forEach(function(seg,i){seg.className='strength-seg'+(i<s?' '+colors[s-1]:'');});lbl.textContent=labels[s-1];});
})();
(function(){
    var p1=document.getElementById('new_password'),p2=document.getElementById('confirm_password'),lbl=document.getElementById('match-label');
    function chk(){if(!p2.value){lbl.textContent='';return;}if(p1.value===p2.value){lbl.textContent='✓ Passwords match';lbl.style.color='var(--success)';}else{lbl.textContent='✗ Passwords do not match';lbl.style.color='var(--danger)';}}
    p1.addEventListener('input',chk);p2.addEventListener('input',chk);
})();
(function(){
    var f=document.querySelector('.auth-form'),b=document.getElementById('reset-btn');if(!f||!b)return;
    f.addEventListener('submit',function(){b.querySelector('.btn-text').textContent='Resetting...';b.querySelector('.btn-spinner').style.display='inline-flex';b.querySelector('.btn-arrow').style.display='none';b.disabled=true;});
})();
</script>
</body>
</html>
