<?php
// update_task.php — Task update endpoint
// Accepts a POST request and supports two modes:
//   1. Status-only update (mark complete): provide 'id' and 'status' = 'completed'
//   2. Full update (edit title/deadline): provide 'id', 'title', and 'deadline'
// Always scopes the UPDATE to the authenticated user's tasks (id = ? AND user_id = ?).
// Returns a JSON response.

// Set JSON content type before any output
header('Content-Type: application/json');

// Include authentication guard (starts session, redirects if unauthenticated)
require_once 'auth.php';

// Include database connection ($conn)
require_once 'db.php';

// Read POST inputs
$id       = isset($_POST['id'])       ? (int) $_POST['id']           : 0;
$title    = isset($_POST['title'])    ? trim($_POST['title'])         : '';
$deadline = isset($_POST['deadline']) ? trim($_POST['deadline'])      : '';
$status   = isset($_POST['status'])   ? trim($_POST['status'])        : '';
$category = isset($_POST['category']) ? trim($_POST['category'])      : '';

// Get the authenticated user's ID from the session
$user_id = $_SESSION['user_id'];

// --- Mode 1: Status-only update (mark as completed) ---
if ($status === 'completed' && $id > 0) {
    // Update only the status column, scoped to the owning user
    $stmt = $conn->prepare(
        'UPDATE tasks SET status = ? WHERE id = ? AND user_id = ?'
    );

    if (!$stmt) {
        // Prepared statement creation failed
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        exit;
    }

    // Bind parameters: status (string), id (int), user_id (int)
    $stmt->bind_param('sii', $status, $id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }

    $stmt->close();
    exit;
}

// --- Mode 2: Full update (edit title, deadline, and category) ---
if ($title !== '' && $deadline !== '' && $id > 0) {
    // Update title, deadline, and category, scoped to the owning user
    $stmt = $conn->prepare(
        'UPDATE tasks SET title = ?, deadline = ?, category = ? WHERE id = ? AND user_id = ?'
    );

    if (!$stmt) {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
        exit;
    }

    // Bind parameters: title (string), deadline (string), category (string), id (int), user_id (int)
    $stmt->bind_param('sssii', $title, $deadline, $category, $id, $user_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Database error.']);
    }

    $stmt->close();
    exit;
}

// --- Neither valid mode matched ---
// Missing or invalid combination of inputs
echo json_encode(['success' => false, 'error' => 'Invalid request.']);
