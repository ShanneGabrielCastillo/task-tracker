<?php
// logout.php — User Logout
// Starts the session, destroys it to terminate the authenticated session,
// and redirects the user back to the login page.

// Resume the existing session so it can be destroyed
session_start();

// Destroy all session data, effectively logging the user out
session_destroy();

// Redirect to the login page and stop execution
header('Location: index.php');
exit;
