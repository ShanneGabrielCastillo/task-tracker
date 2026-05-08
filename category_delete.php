<?php
// category_delete.php — Delete a category owned by the authenticated user.
// POST params: name (required)
// Behavior: sets category = '' on all tasks that used this category,
//           then deletes the category row.
// Returns JSON: { success, tasks_updated } | { success:false, error }

header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];
$name    = isset($_POST['name']) ? trim($_POST['name']) : '';

if ($name === '') {
    echo json_encode(['success' => false, 'error' => 'Category name is required.']);
    exit;
}

// Verify the category belongs to this user
$stmt = $conn->prepare(
    'SELECT id FROM categories WHERE user_id = ? AND name = ?'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('is', $user_id, $name);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Category not found.']);
    exit;
}
$stmt->close();

// Clear the category from all tasks that used it (set to empty string)
$stmt = $conn->prepare(
    'UPDATE tasks SET category = "" WHERE user_id = ? AND category = ?'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('is', $user_id, $name);
$stmt->execute();
$tasks_updated = $stmt->affected_rows;
$stmt->close();

// Delete the category row
$stmt = $conn->prepare(
    'DELETE FROM categories WHERE user_id = ? AND name = ?'
);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Database error.']);
    exit;
}
$stmt->bind_param('is', $user_id, $name);
if ($stmt->execute()) {
    $stmt->close();
    echo json_encode(['success' => true, 'tasks_updated' => $tasks_updated]);
} else {
    $stmt->close();
    echo json_encode(['success' => false, 'error' => 'Failed to delete category.']);
}
