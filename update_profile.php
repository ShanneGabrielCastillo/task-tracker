<?php
// update_profile.php — Profile update endpoint
// action=info     → update first_name, last_name, username, email
// action=password → verify current password, update to new hash
// Returns JSON only — never redirects.

header('Content-Type: application/json');
header('ngrok-skip-browser-warning: true');

// Session auth — JSON response on failure, never a redirect
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_name('TASKTRACKER');
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
    exit;
}

require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];
$action  = trim($_POST['action'] ?? '');

// Safe migration
$conn->query(
    "ALTER TABLE users
     ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NOT NULL DEFAULT '',
     ADD COLUMN IF NOT EXISTS last_name  VARCHAR(100) NOT NULL DEFAULT '',
     ADD COLUMN IF NOT EXISTS email      VARCHAR(255) NOT NULL DEFAULT ''"
);

// ── MODE 1: Update account info ───────────────────────────────────────────
if ($action === 'info') {

    $first_name = trim($_POST['first_name'] ?? '');
    $last_name  = trim($_POST['last_name']  ?? '');
    $username   = trim($_POST['username']   ?? '');
    $email      = trim($_POST['email']      ?? '');

    // Validate username
    if ($username === '') {
        echo json_encode(['success' => false, 'error' => 'Username is required.']);
        exit;
    }
    if (mb_strlen($username) < 3) {
        echo json_encode(['success' => false, 'error' => 'Username must be at least 3 characters.']);
        exit;
    }
    if (mb_strlen($username) > 100) {
        echo json_encode(['success' => false, 'error' => 'Username must be 100 characters or fewer.']);
        exit;
    }

    // Validate email format if provided
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a valid email address.']);
        exit;
    }

    // Check username uniqueness (exclude current user)
    $chk = $conn->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
    if ($chk) {
        $chk->bind_param('si', $username, $user_id);
        $chk->execute();
        $chk->store_result();
        $taken = $chk->num_rows > 0;
        $chk->close();
        if ($taken) {
            echo json_encode(['success' => false, 'error' => 'That username is already taken.']);
            exit;
        }
    }

    // Check email uniqueness (exclude current user)
    if ($email !== '') {
        $chk2 = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        if ($chk2) {
            $chk2->bind_param('si', $email, $user_id);
            $chk2->execute();
            $chk2->store_result();
            $emailTaken = $chk2->num_rows > 0;
            $chk2->close();
            if ($emailTaken) {
                echo json_encode(['success' => false, 'error' => 'That email is already in use by another account.']);
                exit;
            }
        }
    }

    // Run the update
    $stmt = $conn->prepare(
        'UPDATE users SET first_name = ?, last_name = ?, username = ?, email = ? WHERE id = ?'
    );
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param('ssssi', $first_name, $last_name, $username, $email, $user_id);

    if ($stmt->execute()) {
        $stmt->close();

        // Update session cache so subsequent page loads show the new name immediately
        $_SESSION['username'] = $username;
        $_SESSION['_user_cache'] = [
            'id'         => $user_id,
            'username'   => $username,
            'first_name' => $first_name,
            'last_name'  => $last_name,
            'email'      => $email,
        ];

        $full_name = trim($first_name . ' ' . $last_name);
        echo json_encode([
            'success'      => true,
            'message'      => 'Profile updated successfully.',
            'username'     => $username,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'email'        => $email,
            'display_name' => $full_name ?: $username,
        ]);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to update profile: ' . $conn->error]);
    }
    exit;
}

// ── MODE 2: Change password ───────────────────────────────────────────────
if ($action === 'password') {

    $current_pw = $_POST['current_password'] ?? '';
    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if ($current_pw === '' || $new_pw === '' || $confirm_pw === '') {
        echo json_encode(['success' => false, 'error' => 'All password fields are required.']);
        exit;
    }
    if (strlen($new_pw) < 6) {
        echo json_encode(['success' => false, 'error' => 'New password must be at least 6 characters.']);
        exit;
    }
    if ($new_pw !== $confirm_pw) {
        echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
        exit;
    }

    // Verify current password
    $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row || !password_verify($current_pw, $row['password'])) {
        echo json_encode(['success' => false, 'error' => 'Current password is incorrect.']);
        exit;
    }

    // Update password
    $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
    $stmt   = $conn->prepare('UPDATE users SET password = ? WHERE id = ?');
    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        exit;
    }
    $stmt->bind_param('si', $hashed, $user_id);
    if ($stmt->execute()) {
        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
    } else {
        $stmt->close();
        echo json_encode(['success' => false, 'error' => 'Failed to update password.']);
    }
    exit;
}

// Unknown action
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
