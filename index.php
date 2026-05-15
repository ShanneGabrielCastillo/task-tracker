<?php
// index.php — User Login
// Renders the login form on GET.
// On POST: validates inputs, queries the users table, verifies the password,
// starts a session with the user's id, and redirects to dashboard.php.
// Re-renders with an inline error on any failure.

require_once 'db.php';

// Allow ngrok to serve pages without the browser-warning interstitial
header('ngrok-skip-browser-warning: true');

ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_name('TASKTRACKER');

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
        // Query the users table for the submitted username using a prepared statement
        $stmt = $conn->prepare('SELECT id, password FROM users WHERE username = ?');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            // Username not found — use a generic message to avoid username enumeration
            $error = 'Invalid username or password.';
        } elseif (!password_verify($password, $row['password'])) {
            // Password does not match the stored hash — same generic message
            $error = 'Invalid username or password.';
        } else {
            // Credentials are valid — start a session and store the user's id
            ini_set('session.cookie_samesite', 'Lax');
            ini_set('session.cookie_httponly', '1');
            session_name('TASKTRACKER');
            session_start();
            $_SESSION['user_id'] = $row['id'];

            // Redirect to the dashboard
            header('Location: dashboard.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-split-body">

    <!-- ===================== SPLIT SCREEN WRAPPER ===================== -->
    <div class="auth-split">

        <!-- ── LEFT PANEL — Branding ── -->
        <div class="auth-panel-left">
            <!-- Floating blobs -->
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>

            <div class="auth-panel-left-inner">
                <!-- Logo -->
                <div class="auth-brand">
                    <div class="auth-brand-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <span class="auth-brand-name">Task Tracker</span>
                </div>

                <!-- Headline -->
                <div class="auth-panel-copy">
                    <h2 class="auth-panel-headline">Stay on top of<br>everything that<br>matters.</h2>
                    <p class="auth-panel-sub">Organize tasks, track deadlines, and hit your goals — all in one clean workspace.</p>
                </div>

                <!-- Feature pills -->
                <div class="auth-features">
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-green"></span>
                        Smart deadline tracking
                    </div>
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-blue"></span>
                        Category organization
                    </div>
                    <div class="auth-feature-pill">
                        <span class="auth-feature-dot dot-purple"></span>
                        Progress at a glance
                    </div>
                </div>

            </div>
        </div>

        <!-- ── RIGHT PANEL — Login Form ── -->
        <div class="auth-panel-right">
            <div class="auth-form-card" id="auth-card">

                <!-- Top logo (mobile only) -->
                <div class="auth-mobile-brand">
                    <div class="auth-brand-icon small">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                        </svg>
                    </div>
                    <span>Task Tracker</span>
                </div>

                <div class="auth-form-header">
                    <h1 class="auth-form-title">Welcome back</h1>
                    <p class="auth-form-subtitle">Sign in to your account to continue</p>
                </div>

                <?php if ($error !== ''): ?>
                <div class="auth-alert auth-alert-error" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <?php if (isset($_GET['reset']) && $_GET['reset'] === 'success'): ?>
                <div class="auth-alert auth-alert-success" role="alert">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                    Password reset successfully! You can now sign in with your new password.
                </div>
                <?php endif; ?>

                <form method="POST" action="index.php" class="auth-form" novalidate>

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
                                placeholder="Enter your username"
                                value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                required
                                autofocus
                                autocomplete="username"
                            >
                        </div>
                    </div>

                    <div class="auth-field">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:7px;">
                            <label for="password" class="auth-label" style="margin-bottom:0;">Password</label>
                            <a href="forgot_password.php" class="auth-forgot">Forgot password?</a>
                        </div>
                        <div class="auth-input-wrap">
                            <span class="auth-input-icon">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            </span>
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="auth-input"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" class="auth-pw-toggle" id="pw-toggle-login" aria-label="Toggle password visibility">
                                <svg class="eye-show" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="eye-hide" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="auth-remember">
                        <label class="auth-checkbox-label">
                            <input type="checkbox" name="remember" class="auth-checkbox">
                            <span class="auth-checkbox-custom"></span>
                            Remember me for 30 days
                        </label>
                    </div>

                    <button type="submit" class="auth-submit-btn" id="login-btn">
                        <span class="btn-text">Sign In</span>
                        <span class="btn-spinner" style="display:none">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                        </span>
                        <svg class="btn-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>

                </form>

                <p class="auth-switch">
                    Don't have an account?
                    <a href="register.php" class="auth-switch-link">Create one free</a>
                </p>

            </div>
        </div>
    </div>

    <script>
    // Password toggle
    (function() {
        var btn = document.getElementById('pw-toggle-login');
        if (!btn) return;
        btn.addEventListener('click', function() {
            var input = document.getElementById('password');
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-show').style.display = isHidden ? 'none' : '';
            btn.querySelector('.eye-hide').style.display = isHidden ? '' : 'none';
        });
    })();

    // Submit loading state
    (function() {
        var form = document.querySelector('.auth-form');
        var btn  = document.getElementById('login-btn');
        if (!form || !btn) return;
        form.addEventListener('submit', function() {
            btn.querySelector('.btn-text').textContent = 'Signing in...';
            btn.querySelector('.btn-spinner').style.display = 'inline-flex';
            btn.querySelector('.btn-arrow').style.display = 'none';
            btn.disabled = true;
        });
    })();
    </script>

</body>
</html>
