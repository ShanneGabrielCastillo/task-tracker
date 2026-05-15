<?php
// profile.php — Edit Profile page
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];

// Fetch display name, username, and initial from session cache / DB
// Also ensures first_name/last_name columns exist
require_once 'user_helper.php';

// Expose raw first/last/email for pre-filling the form fields
$user = [
    'username'   => $username,
    'first_name' => $_SESSION['_user_cache']['first_name'] ?? '',
    'last_name'  => $_SESSION['_user_cache']['last_name']  ?? '',
    'email'      => $_SESSION['_user_cache']['email']      ?? '',
];

// If email not in cache yet, fetch it directly
if ($user['email'] === '') {
    $conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS email VARCHAR(255) NOT NULL DEFAULT ''");
    $stmt = $conn->prepare('SELECT email FROM users WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $user['email'] = $row['email'];
            $_SESSION['_user_cache']['email'] = $row['email'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-layout">

    <!-- ── Sidebar ──────────────────────────────────────── -->
    <aside class="sidebar">
        <button class="sidebar-close-btn" id="sidebar-close" aria-label="Close menu" onclick="document.querySelector('.sidebar').classList.remove('open');document.getElementById('sidebar-overlay')&&document.getElementById('sidebar-overlay').classList.remove('visible');document.body.classList.remove('sidebar-open');">✕</button>
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">📋</div>
            <span class="sidebar-brand-name">Task Tracker</span>
        </a>
        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Menu</span>
            <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="all_tasks.php"  class="nav-item"><span class="nav-icon">✅</span> All Tasks</a>
            <a href="calendar.php"   class="nav-item"><span class="nav-icon">📅</span> Calendar</a>
            <a href="categories.php" class="nav-item"><span class="nav-icon">🏷️</span> Categories</a>
            <a href="reports.php"    class="nav-item"><span class="nav-icon">📊</span> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <a href="profile.php" class="sidebar-user sidebar-user-link active-profile">
                <div class="sidebar-avatar" id="sidebar-avatar"><?php echo $display_initial; ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-username" id="sidebar-display-name"><?php echo htmlspecialchars($display_name); ?></div>
                    <div class="sidebar-role">Edit Profile</div>
                </div>
            </a>
        </div>
    </aside>

    <!-- ── Header ───────────────────────────────────────── -->
    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">Profile</div>
            <div class="header-breadcrumb">Manage your account information</div>
        </div>
        <div class="header-right">
            <a href="profile.php" class="header-user-pill">
                <div class="header-avatar" id="header-avatar"><?php echo $display_initial; ?></div>
                <span class="header-username" id="header-display-name"><?php echo htmlspecialchars($display_name); ?></span>
            </a>
            <a href="logout.php" class="btn-header-logout">⏻ Logout</a>
        </div>
    </header>

    <!-- ── Main ─────────────────────────────────────────── -->
    <main class="app-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Profile Settings</h1>
                <p class="page-subtitle">Update your personal information and password</p>
            </div>
        </div>

        <div class="profile-layout">

            <!-- ── Avatar card ──────────────────────────── -->
            <div class="profile-avatar-card">
                <div class="profile-avatar-circle" id="avatar-circle">
                    <?php echo $display_initial; ?>
                </div>
                <div class="profile-avatar-name" id="avatar-name">
                    <?php
                    $display = trim($user['first_name'] . ' ' . $user['last_name']);
                    echo htmlspecialchars($display ?: $username);
                    ?>
                </div>
                <div class="profile-avatar-username" id="avatar-username">
                    @<?php echo htmlspecialchars($username); ?>
                </div>
                <div class="profile-avatar-badge">Student</div>
            </div>

            <!-- ── Forms column ─────────────────────────── -->
            <div class="profile-forms">

                <!-- Account Information -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        </div>
                        <div>
                            <h2 class="profile-card-title">Account Information</h2>
                            <p class="profile-card-sub">Update your name and username</p>
                        </div>
                    </div>

                    <div id="info-alert" class="profile-alert" style="display:none;"></div>

                    <form id="info-form" novalidate>
                        <div class="profile-field-row">
                            <div class="profile-field">
                                <label for="first_name" class="profile-label">First Name</label>
                                <input type="text" id="first_name" name="first_name"
                                       class="profile-input"
                                       placeholder="Enter first name"
                                       value="<?php echo htmlspecialchars($user['first_name']); ?>"
                                       maxlength="100" autocomplete="given-name">
                            </div>
                            <div class="profile-field">
                                <label for="last_name" class="profile-label">Last Name</label>
                                <input type="text" id="last_name" name="last_name"
                                       class="profile-input"
                                       placeholder="Enter last name"
                                       value="<?php echo htmlspecialchars($user['last_name']); ?>"
                                       maxlength="100" autocomplete="family-name">
                            </div>
                        </div>
                        <div class="profile-field">
                            <label for="username" class="profile-label">
                                Username <span class="profile-required">*</span>
                            </label>
                            <input type="text" id="username" name="username"
                                   class="profile-input"
                                   placeholder="Enter username"
                                   value="<?php echo htmlspecialchars($user['username']); ?>"
                                   minlength="3" maxlength="100" required
                                   autocomplete="username">
                            <span class="profile-hint">Minimum 3 characters. Must be unique.</span>
                        </div>
                        <div class="profile-field">
                            <label for="email" class="profile-label">Email Address</label>
                            <input type="email" id="email" name="email"
                                   class="profile-input"
                                   placeholder="your@email.com"
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                   maxlength="255"
                                   autocomplete="email">
                            <span class="profile-hint">Used for password recovery. Keep it up to date.</span>
                        </div>
                        <div class="profile-card-footer">
                            <button type="submit" class="profile-save-btn" id="info-btn">
                                <span class="btn-text">Save Changes</span>
                                <span class="btn-spinner" style="display:none;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security / Password -->
                <div class="profile-card">
                    <div class="profile-card-header">
                        <div class="profile-card-icon">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        </div>
                        <div>
                            <h2 class="profile-card-title">Change Password</h2>
                            <p class="profile-card-sub">Keep your account secure with a strong password</p>
                        </div>
                    </div>

                    <div id="pw-alert" class="profile-alert" style="display:none;"></div>

                    <form id="pw-form" novalidate>
                        <div class="profile-field">
                            <label for="current_password" class="profile-label">
                                Current Password <span class="profile-required">*</span>
                            </label>
                            <div class="profile-pw-wrap">
                                <input type="password" id="current_password" name="current_password"
                                       class="profile-input" placeholder="Enter current password"
                                       autocomplete="current-password">
                                <button type="button" class="profile-pw-toggle" data-target="current_password" aria-label="Toggle visibility">
                                    <svg class="eye-show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    <svg class="eye-hide" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                </button>
                            </div>
                        </div>
                        <div class="profile-field-row">
                            <div class="profile-field">
                                <label for="new_password" class="profile-label">
                                    New Password <span class="profile-required">*</span>
                                </label>
                                <div class="profile-pw-wrap">
                                    <input type="password" id="new_password" name="new_password"
                                           class="profile-input" placeholder="Min. 6 characters"
                                           autocomplete="new-password">
                                    <button type="button" class="profile-pw-toggle" data-target="new_password" aria-label="Toggle visibility">
                                        <svg class="eye-show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="eye-hide" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                                <!-- Password strength bar -->
                                <div class="pw-strength" id="pw-strength" style="display:none;">
                                    <div class="pw-strength-bar">
                                        <div class="pw-seg" id="pw-seg1"></div>
                                        <div class="pw-seg" id="pw-seg2"></div>
                                        <div class="pw-seg" id="pw-seg3"></div>
                                        <div class="pw-seg" id="pw-seg4"></div>
                                    </div>
                                    <span class="pw-strength-label" id="pw-strength-label"></span>
                                </div>
                            </div>
                            <div class="profile-field">
                                <label for="confirm_password" class="profile-label">
                                    Confirm Password <span class="profile-required">*</span>
                                </label>
                                <div class="profile-pw-wrap">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                           class="profile-input" placeholder="Repeat new password"
                                           autocomplete="new-password">
                                    <button type="button" class="profile-pw-toggle" data-target="confirm_password" aria-label="Toggle visibility">
                                        <svg class="eye-show" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        <svg class="eye-hide" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="display:none"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="profile-card-footer">
                            <button type="submit" class="profile-save-btn" id="pw-btn">
                                <span class="btn-text">Update Password</span>
                                <span class="btn-spinner" style="display:none;">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>
                                </span>
                            </button>
                        </div>
                    </form>
                </div>

            </div><!-- /.profile-forms -->
        </div><!-- /.profile-layout -->

    </main>

    <!-- Toast -->
    <div id="ux-toast" class="ux-toast"></div>

    <script>
    // ── Helpers ──────────────────────────────────────────────
    function showToast(msg, isError) {
        var t = document.getElementById('ux-toast');
        t.textContent = msg;
        t.className = 'ux-toast ' + (isError ? 'error' : 'success') + ' visible';
        clearTimeout(t._t);
        t._t = setTimeout(function() { t.classList.remove('visible'); }, 3500);
    }

    function setAlert(el, msg, isError) {
        el.textContent = msg;
        el.className = 'profile-alert ' + (isError ? 'profile-alert-error' : 'profile-alert-success');
        el.style.display = 'flex';
        if (!isError) {
            clearTimeout(el._t);
            el._t = setTimeout(function() { el.style.display = 'none'; }, 4000);
        }
    }

    function setBtnLoading(btn, isLoading, originalText) {
        if (isLoading) {
            btn.dataset.orig = originalText || btn.querySelector('.btn-text').textContent;
            btn.disabled = true;
            btn.querySelector('.btn-text').textContent = 'Saving…';
            btn.querySelector('.btn-spinner').style.display = 'inline-flex';
        } else {
            btn.disabled = false;
            btn.querySelector('.btn-text').textContent = btn.dataset.orig || 'Save';
            btn.querySelector('.btn-spinner').style.display = 'none';
        }
    }

    // ── Password toggles ─────────────────────────────────────
    document.querySelectorAll('.profile-pw-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var input = document.getElementById(btn.dataset.target);
            var isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            btn.querySelector('.eye-show').style.display = isHidden ? 'none' : '';
            btn.querySelector('.eye-hide').style.display = isHidden ? '' : 'none';
        });
    });

    // ── Password strength meter ───────────────────────────────
    document.getElementById('new_password').addEventListener('input', function() {
        var pw    = this.value;
        var wrap  = document.getElementById('pw-strength');
        var label = document.getElementById('pw-strength-label');
        var segs  = ['pw-seg1','pw-seg2','pw-seg3','pw-seg4'].map(function(id) {
            return document.getElementById(id);
        });
        var colors = ['active-weak','active-fair','active-good','active-strong'];
        var labels = ['Weak','Fair','Good','Strong'];

        if (!pw) { wrap.style.display = 'none'; return; }
        wrap.style.display = 'block';

        var score = 0;
        if (pw.length >= 6)          score++;
        if (pw.length >= 10)         score++;
        if (/[A-Z]/.test(pw))        score++;
        if (/[0-9!@#$%^&*]/.test(pw)) score++;
        score = Math.max(1, score);

        segs.forEach(function(seg, i) {
            seg.className = 'pw-seg' + (i < score ? ' ' + colors[score - 1] : '');
        });
        label.textContent = labels[score - 1];
    });

    // ── Account info form ─────────────────────────────────────
    document.getElementById('info-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var alertEl = document.getElementById('info-alert');
        var btn     = document.getElementById('info-btn');
        alertEl.style.display = 'none';

        var username   = document.getElementById('username').value.trim();
        var first_name = document.getElementById('first_name').value.trim();
        var last_name  = document.getElementById('last_name').value.trim();

        if (!username) {
            setAlert(alertEl, 'Username is required.', true); return;
        }
        if (username.length < 3) {
            setAlert(alertEl, 'Username must be at least 3 characters.', true); return;
        }

        setBtnLoading(btn, true);

        var body = new URLSearchParams();
        body.append('action',     'info');
        body.append('first_name', first_name);
        body.append('last_name',  last_name);
        body.append('username',   username);
        body.append('email',      document.getElementById('email') ? document.getElementById('email').value.trim() : '');

        fetch('update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                // ── Update avatar card ──────────────────────────────
                var initials = (data.display_name || data.username).charAt(0).toUpperCase();
                document.getElementById('avatar-circle').textContent = initials;
                document.getElementById('avatar-username').textContent = '@' + (data.username || username);
                document.getElementById('avatar-name').textContent = data.display_name || data.username;

                // ── Update header pill (same page, live) ────────────
                var headerAvatar = document.getElementById('header-avatar');
                var headerName   = document.getElementById('header-display-name');
                if (headerAvatar) headerAvatar.textContent = initials;
                if (headerName)   headerName.textContent   = data.display_name || data.username;

                // ── Update sidebar user block (same page, live) ─────
                var sidebarAvatar = document.getElementById('sidebar-avatar');
                var sidebarName   = document.getElementById('sidebar-display-name');
                if (sidebarAvatar) sidebarAvatar.textContent = initials;
                if (sidebarName)   sidebarName.textContent   = data.display_name || data.username;

                setAlert(alertEl, data.message || 'Profile updated!', false);
                showToast('Profile updated!', false);
            } else {
                setAlert(alertEl, data.error || 'Update failed.', true);
            }
        })
        .catch(function() {
            setAlert(alertEl, 'Request failed. Please try again.', true);
        })
        .finally(function() {
            setBtnLoading(btn, false);
        });
    });

    // ── Password form ─────────────────────────────────────────
    document.getElementById('pw-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var alertEl = document.getElementById('pw-alert');
        var btn     = document.getElementById('pw-btn');
        alertEl.style.display = 'none';

        var current = document.getElementById('current_password').value;
        var newPw   = document.getElementById('new_password').value;
        var confirm = document.getElementById('confirm_password').value;

        if (!current || !newPw || !confirm) {
            setAlert(alertEl, 'All password fields are required.', true); return;
        }
        if (newPw.length < 6) {
            setAlert(alertEl, 'New password must be at least 6 characters.', true); return;
        }
        if (newPw !== confirm) {
            setAlert(alertEl, 'New passwords do not match.', true); return;
        }

        setBtnLoading(btn, true);

        var body = new URLSearchParams();
        body.append('action',           'password');
        body.append('current_password', current);
        body.append('new_password',     newPw);
        body.append('confirm_password', confirm);

        fetch('update_profile.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('pw-form').reset();
                document.getElementById('pw-strength').style.display = 'none';
                setAlert(alertEl, data.message || 'Password changed!', false);
                showToast('Password changed successfully!', false);
            } else {
                setAlert(alertEl, data.error || 'Update failed.', true);
            }
        })
        .catch(function() {
            setAlert(alertEl, 'Request failed. Please try again.', true);
        })
        .finally(function() {
            setBtnLoading(btn, false);
        });
    });
    </script>
    <script src="sidebar.js"></script>
</body>
</html>
