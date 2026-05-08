<?php
// Copy this file to db.php and fill in your database credentials.

$host = 'localhost';
$user = 'your_db_username';
$pass = 'your_db_password';
$db   = 'task_tracker3';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo '<!DOCTYPE html><html><body>';
    echo '<h1>Database Connection Error</h1>';
    echo '<p>Could not connect to the database. Please try again later.</p>';
    echo '</body></html>';
    exit;
}
