<?php
// register.php — User Registration
// Renders the registration form on GET.
// On POST: validates inputs, checks for duplicate username,
// hashes the password, inserts the new user, and redirects to index.php.

require_once 'db.php';

// Allow ngrok to serve pages without the browser-warning interstitial
header('ngrok-skip-browser-warning: true');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and trim form inputs
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate that both fields are non-empty
    if ($username === '' && $password === '') {
        $error = 'Username and password are required.';
    } elseif ($username === '') {
        $error = 'Username is required.';
    } elseif ($password === '') {
        $error = 'Password is required.';
    } else {
        // Check for duplicate username using a prepared statement
        $stmt = $conn->prepare('SELECT id FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Username already exists — show error
            $error = 'Username is already taken. Please choose a different one.';
        } else {
            // Hash the password securely before storing
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert the new user into the users table
            $insert = $conn->prepare('INSERT INTO users (username, password) VALUES (?, ?)');
            $insert->bind_param('ss', $username, $hashed);

            if ($insert->execute()) {
                // Registration successful — redirect to login page
                header('Location: index.php');
                exit;
            } else {
                // Database insert failed
                $error = 'Registration failed. Please try again.';
            }

            $insert->close();
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-split-body">

    <div class="auth-split">

        <!-- ── LEFT PANEL — Branding ── -->
        <div class="auth-panel-left">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="auth-panel-left-inner">
                <div class="auth-brand">
                    <div class="auth-brand-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <span class="auth-brand-name">Task Tracker</span>
                </div>

                <div class="auth-panel-copy">
                    <h2 class="auth-panel-headline">Your productivity<br>journey starts<br>right here.</h2>
                    <p class="auth-panel-sub">Join thousands of students managing tasks, deadlines, and goals in one clean workspace.</p>
                </div>

                <div class="auth-features">
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-green"></span>
                        Free to use, always
                    </div>
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-blue"></span>
                        Set up in under a minute
                    </div>
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-purple"></span>
                        No credit card required
                    </div>
                </div>

                <div class="auth-preview-card">
                    <div class="preview-card-header">
                        <span class="preview-dot dot-green"></span>
                        <span class="preview-label">Welcome to your workspace</span>
                    </div>
                    <div class="preview-progress-bar">
                        <div class="preview-progress-fill" style="width:0%;transition:width 1.4s 0.6s ease" id="reg-progress"></div>
                    </div>
                    <div class="preview-stats">
                        <span>Getting started</span>
                        <span class="preview-pct">Step 1 of 1</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── RIGHT PANEL — Register Form ── -->
        <div class="auth-panel-right">
            <div class="auth-form-card">

                <!-- Mobile brand -->
                <div class="auth-mobile-brand">
                    <div class="auth-brand-icon small">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <span>Task Tracker</span>
                </div>

                <div class="auth-form-header">
                    <h1 class="auth-form-title">Create your account</h1>
                    <p class="auth-form-subtitle">Start organizing your tasks in seconds</p>
                </div>

                <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="register.php" class="auth-form" novalidate>

                    <div class="auth-field">
                        <label for="username" class="auth-label">Username</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            </span>
                            <input
                                type="text"
                                id="username"
                                name="username"
                                class="auth-input"
                                placeholder="Choose a username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <label for="password" class="auth-label">Password</label>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="auth-input"
                                placeholder="Create a strong password"
                                required
                                autocomplete="new-password"
                            >
                            <button type="button" class="auth-pw-toggle" id="pw-toggle-reg" aria-label="Toggle password visibility">
                                <svg class="eye-show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-hide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                        <!-- Password strength indicator -->
                        <div class="auth-strength" id="strength-wrap" style="display:none">
                            <div class="auth-strength-bar">
                                <div class="strength-seg" id="seg1"></div>
                                <div class="strength-seg" id="seg2"></div>
                                <div class="strength-seg" id="seg3"></div>
                                <div class="strength-seg" id="seg4"></div>
                            </div>
                            <span class="auth-strength-label" id="strength-label"></span>
                        </div>
                    </div>

                    <button type="submit" class="auth-submit-btn" id="register-btn">
                        <span class="btn-text">Create Account</span>
                        <span class="btn-spinner" style="display:none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </span>
                        <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>

                </form>

                <p class="auth-switch">
                    Already have an account?
                    <a href="index.php" class="auth-switch-link">Sign in</a>
                </p>

            </div>
        </div>
    </div>

    <script>
    // Password toggle
    (function() {
        var btn = document.getElementById('pw-toggle-reg');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var input = document.getElementById('password');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-show').style.display = isHidden ? 'none' : '';
            btn.querySelector('.eye-hide').style.display = isHidden ? '' : 'none';
        });
    })();

    // Password strength meter
    (function() {
        var input   = document.getElementById('password');
        var wrap    = document.getElementById('strength-wrap');
        var label   = document.getElementById('strength-label');
        var segs    = [
            document.getElementById('seg1'),
            document.getElementById('seg2'),
            document.getElementById('seg3'),
            document.getElementById('seg4')
        ];
        var levels  = ['weak', 'fair', 'good', 'strong'];
        var labels  = ['Weak', 'Fair', 'Good', 'Strong'];
        var colors  = ['active-weak', 'active-fair', 'active-good', 'active-strong'];

        function score(pw) {
            var s = 0;
            if (pw.length >= 8)  s++;
            if (/[A-Z]/.test(pw)) s++;
            if (/[0-9]/.test(pw)) s++;
            if (/[^A-Za-z0-9]/.test(pw)) s++;
            return s;
        }

        input.addEventListener('input', function() {
            var pw = input.value;
            if (!pw) { wrap.style.display = 'none'; return; }
            wrap.style.display = 'block';
            var s = score(pw);
            segs.forEach(function(seg, i) {
                seg.className = 'strength-seg';
                if (i < s) seg.classList.add(colors[s - 1]);
            });
            label.textContent = labels[s - 1] || '';
        });
    })();

    // Animate preview progress bar on load
    (function() {
        var bar = document.getElementById('reg-progress');
        if (bar) setTimeout(function() { bar.style.width = '35%'; }, 100);
    })();

    // Submit loading state
    (function() {
        var form = document.querySelector('.auth-form');
        var btn  = document.getElementById('register-btn');
        if (!form || !btn) return;
        form.addEventListener('submit', function() {
            btn.querySelector('.btn-text').textContent = 'Creating account...';
            btn.querySelector('.btn-spinner').style.display = 'inline-flex';
            btn.querySelector('.btn-arrow').style.display = 'none';
            btn.disabled = true;
        });
    })();
    </script>

</body>
</html>
