<?php
// category_add.php — Create a new category for the authenticated user.
// POST params: name (required), description (optional)
// Returns JSON: { success, id, name, description } | { success:false, error }

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

require_once 'db.php';

$user_id     = (int) $_SESSION['user_id'];
$name        = isset($_POST['name'])        ? trim($_POST['name'])        : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate
if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Category name is required.']);
    exit;
}
if (mb_strlen($name) > 100) {
    echo json_encode(['success' => false, 'error' => 'Category name must be 100 characters or fewer.']);
    exit;
}

// Duplicate check (case-insensitive, per user)
$stmt = $conn->prepare(
    'SELECT id FROM categories WHERE user_id = ? AND LOWER(name) = LOWER(?)'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('is', $user_id, $name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'A category with that name already exists.']);
    exit;
}
$stmt->close();

// Insert
$stmt = $conn->prepare(
    'INSERT INTO categories (user_id, name, description) VALUES (?, ?, ?)'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('iss', $user_id, $name, $description);
if ($stmt->execute()) {
    $new_id = $conn->insert_id;
    $stmt->close();
    echo json_encode([
        'success'     => true,
        'id'          => $new_id,
        'name'        => $name,
        'description' => $description,
    ]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Failed to create category.']);
}
