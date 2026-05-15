<?php
// auth.php — Authentication guard
// Include this file at the top of every protected page.
// Starts the session and redirects unauthenticated users to the login page.

ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_name('TASKTRACKER');
session_start();

// Allow ngrok to serve pages without the browser-warning interstitial
header('ngrok-skip-browser-warning: true');

// If no valid session exists, redirect to login and stop execution
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
