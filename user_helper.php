<?php
// user_helper.php — Shared helper for fetching and caching the current user's display info.
// Include AFTER auth.php and db.php.
// Populates: $username, $display_name, $display_initial
//
// Display name priority:
//   1. First Name + Last Name  (if either is set)
//   2. Username                (fallback)
//
// Values are cached in $_SESSION so subsequent page loads within the same
// session do not need an extra DB query.

$user_id = (int) $_SESSION['user_id'];

// Refresh session cache if it is missing or stale (e.g. after profile update)
if (
    empty($_SESSION['_user_cache']) ||
    (int)($_SESSION['_user_cache']['id'] ?? 0) !== $user_id
) {
    // Ensure columns exist (safe migration — runs once per session at most)
    $conn->query(
        "ALTER TABLE users
         ADD COLUMN IF NOT EXISTS first_name VARCHAR(100) NOT NULL DEFAULT '',
         ADD COLUMN IF NOT EXISTS last_name  VARCHAR(100) NOT NULL DEFAULT ''"
    );

    $stmt = $conn->prepare(
        'SELECT username, first_name, last_name FROM users WHERE id = ?'
    );
    if ($stmt) {
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row) {
            $_SESSION['_user_cache'] = [
                'id'         => $user_id,
                'username'   => $row['username'],
                'first_name' => $row['first_name'],
                'last_name'  => $row['last_name'],
            ];
        }
    }
}

$_uc          = $_SESSION['_user_cache'] ?? [];
$username     = $_uc['username']   ?? '';
$_first       = trim($_uc['first_name'] ?? '');
$_last        = trim($_uc['last_name']  ?? '');
$_full        = trim($_first . ' ' . $_last);
$display_name = $_full ?: $username;          // "John Doe" or "johndoe"
$display_initial = strtoupper(substr($display_name, 0, 1));
