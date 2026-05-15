<?php
// delete_task.php — Task deletion endpoint
// Accepts a POST request with 'id' and deletes the matching task row,
// scoped to the authenticated user (id = ? AND user_id = ?).
// Returns a JSON response.

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

// Read and cast the task ID from POST input
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

// Validate that the ID is a positive integer
if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'Invalid task ID.']);
    exit;
}

// Get the authenticated user's ID from the session
$user_id = $_SESSION['user_id'];

// Prepare the DELETE statement scoped to the owning user
$stmt = $conn->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');

if (!$stmt) {
    // Prepared statement creation failed
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}

// Bind parameters: id (int), user_id (int)
$stmt->bind_param('ii', $id, $user_id);

// Execute and return the appropriate JSON response
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}

$stmt->close();
