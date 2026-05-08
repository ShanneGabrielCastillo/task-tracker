<?php
// get_categories.php — Returns all categories for the authenticated user as JSON.
// Creates the categories table if it does not exist yet (safe first-run).
// Auto-seeds five built-in categories on first call per user.
// Returns JSON: [ { id, name, description, task_count }, ... ]

header('Content-Type: application/json');
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];

// ── Step 1: ensure the categories table exists ───────────
$conn->query(
    "CREATE TABLE IF NOT EXISTS categories (
        id          INT          AUTO_INCREMENT PRIMARY KEY,
        user_id     INT          NOT NULL,
        name        VARCHAR(100) NOT NULL,
        description VARCHAR(255) NOT NULL DEFAULT '',
        created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_user_category (user_id, name),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )"
);

// ── Step 2: auto-seed built-in categories if user has none ─
$check = $conn->prepare('SELECT COUNT(*) AS cnt FROM categories WHERE user_id = ?');
if ($check) {
    $check->bind_param('i', $user_id);
    $check->execute();
    $cnt = (int) $check->get_result()->fetch_assoc()['cnt'];
    $check->close();

    if ($cnt === 0) {
        $seeds = [
            ['School',   'Academic assignments and study tasks'],
            ['Personal', 'Personal goals and daily habits'],
            ['Work',     'Professional and work-related tasks'],
            ['Project',  'Project milestones and deliverables'],
            ['Health',   'Health, fitness, and wellness tasks'],
        ];
        $ins = $conn->prepare(
            'INSERT IGNORE INTO categories (user_id, name, description) VALUES (?, ?, ?)'
        );
        if ($ins) {
            foreach ($seeds as $s) {
                $ins->bind_param('iss', $user_id, $s[0], $s[1]);
                $ins->execute();
            }
            $ins->close();
        }
    }
}

// ── Step 3: fetch categories with live task counts ────────
$stmt = $conn->prepare(
    'SELECT c.id, c.name, c.description,
            COUNT(t.id) AS task_count
     FROM   categories c
     LEFT JOIN tasks t
            ON t.user_id = c.user_id AND t.category = c.name
     WHERE  c.user_id = ?
     GROUP  BY c.id
     ORDER  BY c.name ASC'
);

if (!$stmt) {
    echo json_encode([]);
    exit;
}

$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$cats = [];
while ($row = $result->fetch_assoc()) {
    $cats[] = [
        'id'          => (int) $row['id'],
        'name'        => $row['name'],
        'description' => $row['description'],
        'task_count'  => (int) $row['task_count'],
    ];
}
$stmt->close();

echo json_encode($cats);
