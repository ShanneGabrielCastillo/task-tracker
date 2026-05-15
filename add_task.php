<?php
// add_task.php — Task creation endpoint
// Accepts a POST request with 'title' and 'deadline', inserts a new task
// for the authenticated user, and returns a JSON response.

// Set JSON content type before any output
header('Content-Type: application/json');
header('ngrok-skip-browser-warning: true');

// Inline session auth — returns JSON on failure instead of redirecting
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_httponly', '1');
session_name('TASKTRACKER');
session_start();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Session expired. Please log in again.']);
    exit;
}

// Include database connection ($conn)
require_once 'db.php';

// Read and trim POST inputs
$title    = isset($_POST['title'])    ? trim($_POST['title'])    : '';
$deadline = isset($_POST['deadline']) ? trim($_POST['deadline']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';

// Validate that required fields are non-empty
if ($title === '' || $deadline === '') {
    echo json_encode(['success' => false, 'error' => 'Title and deadline are required.']);
    exit;
}

// Get the authenticated user's ID from the session
$user_id = $_SESSION['user_id'];

// Insert the new task using a prepared statement
$stmt = $conn->prepare(
    'INSERT INTO tasks (user_id, title, deadline, status, category) VALUES (?, ?, ?, \'pending\', ?)'
);

if (!$stmt) {
    // Prepared statement creation failed
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}

// Bind parameters: user_id (int), title (string), deadline (string), category (string)
$stmt->bind_param('isss', $user_id, $title, $deadline, $category);

if ($stmt->execute()) {
    // Task inserted successfully — return the new row's id so the client
    // can reference it for complete/delete without a page reload
    echo json_encode(['success' => true, 'id' => $conn->insert_id]);
} else {
    // Execution failed
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}

$stmt->close();
