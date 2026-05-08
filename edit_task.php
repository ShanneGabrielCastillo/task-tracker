<?php
// edit_task.php — Edit Task Page
// Fetches the task by id (scoped to the authenticated user) and renders
// a pre-populated HTML form. The form submits via JavaScript fetch() POST
// to update_task.php; on success the user is redirected to dashboard.php.

// Include authentication guard (starts session, redirects if unauthenticated)
require_once 'auth.php';

// Include database connection ($conn)
require_once 'db.php';

// Read and sanitise the task id from the query string
$task_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get the authenticated user's id from the session
$user_id = $_SESSION['user_id'];

$task  = null;
$error = '';

// Fetch the task only if it belongs to the current user
if ($task_id > 0) {
    $stmt = $conn->prepare(
        'SELECT id, title, deadline, category FROM tasks WHERE id = ? AND user_id = ?'
    );

    if ($stmt) {
        $stmt->bind_param('ii', $task_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $task   = $result->fetch_assoc(); // null if not found
        $stmt->close();
    } else {
        $error = 'Database error.';
    }
} else {
    $error = 'Invalid task ID.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">

        <!-- Brand logo mark — matches login / register pages -->
        <div class="auth-logo">
            <div class="auth-logo-icon">📋</div>
            <span class="auth-logo-text">Task Tracker</span>
        </div>

        <h1>Edit Task</h1>
        <div class="auth-divider"></div>
        <p class="auth-subtitle">Update the task details below</p>

        <?php if ($task === null): ?>
            <!-- Task not found or does not belong to this user -->
            <p class="error">
                <?php echo htmlspecialchars($error !== '' ? $error : 'Task not found.'); ?>
            </p>
            <a href="dashboard.php" class="btn-secondary">← Back to Dashboard</a>

        <?php else: ?>
            <!-- Error area populated by JavaScript on fetch failure -->
            <p class="error" id="edit-error" style="display:none;"></p>

            <!-- Edit form pre-populated with the task's current values -->
            <form id="edit-form">
                <!-- Hidden field carries the task id to update_task.php -->
                <input type="hidden" id="task-id" value="<?php echo (int) $task['id']; ?>">

                <div class="form-group">
                    <label for="title">Title</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        value="<?php echo htmlspecialchars($task['title']); ?>"
                        required
                        autofocus
                    >
                </div>

                <div class="form-group">
                    <label for="deadline">Deadline</label>
                    <input
                        type="date"
                        id="deadline"
                        name="deadline"
                        value="<?php echo htmlspecialchars($task['deadline']); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">Category</label>
                    <input
                        type="text"
                        id="category"
                        name="category"
                        value="<?php echo htmlspecialchars($task['category'] ?? ''); ?>"
                        placeholder="e.g. School, Personal, Project"
                        list="category-suggestions"
                    >
                    <datalist id="category-suggestions">
                        <!-- populated dynamically -->
                    </datalist>
                </div>

                <button type="submit" class="btn-primary">Save Changes</button>
            </form>

            <a href="dashboard.php" class="btn-secondary">← Back to Dashboard</a>

            <script>
                // Load categories into datalist
                (function() {
                    fetch('get_categories.php')
                        .then(function(r) { return r.json(); })
                        .then(function(cats) {
                            var dl = document.getElementById('category-suggestions');
                            if (dl) {
                                dl.innerHTML = cats.map(function(c) {
                                    return '<option value="' + c.name.replace(/"/g,'&quot;') + '">';
                                }).join('');
                            }
                        })
                        .catch(function() {});
                })();
                // Submit the edit form via fetch() POST to update_task.php
                (function () {
                    var form    = document.getElementById('edit-form');
                    var saveBtn = form.querySelector('button[type="submit"]');
                    var errorEl = document.getElementById('edit-error');

                    // Spinner helper — mirrors setLoading() in script.js
                    function setLoading(btn, isLoading) {
                        if (!btn) return;
                        if (isLoading) {
                            btn.disabled = true;
                            btn.classList.add('btn-loading');
                            btn.innerHTML = '<span class="spinner"></span> Saving...';
                        } else {
                            btn.disabled = false;
                            btn.classList.remove('btn-loading');
                            btn.textContent = 'Save Changes';
                        }
                    }

                    form.addEventListener('submit', function (e) {
                        e.preventDefault();

                        var id       = document.getElementById('task-id').value;
                        var title    = document.getElementById('title').value.trim();
                        var deadline = document.getElementById('deadline').value;
                        var category = document.getElementById('category').value.trim();

                        // Hide any previous error
                        errorEl.style.display = 'none';
                        errorEl.textContent   = '';

                        // Enter loading state — prevents duplicate submissions
                        setLoading(saveBtn, true);

                        // Build form-encoded body for update_task.php
                        var body = new URLSearchParams();
                        body.append('id',       id);
                        body.append('title',    title);
                        body.append('deadline', deadline);
                        body.append('category', category);

                        fetch('update_task.php', {
                            method:  'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body:    body.toString()
                        })
                        .then(function (response) {
                            return response.json();
                        })
                        .then(function (data) {
                            if (data.success) {
                                // Brief "Task Updated!" feedback before redirect
                                saveBtn.disabled = true;
                                saveBtn.classList.remove('btn-loading');
                                saveBtn.textContent = 'Task Updated!';
                                setTimeout(function () {
                                    window.location.href = 'dashboard.php';
                                }, 800);
                            } else {
                                // Show the error message returned by the server
                                errorEl.textContent   = data.error || 'Update failed.';
                                errorEl.style.display = 'block';
                                setLoading(saveBtn, false);
                            }
                        })
                        .catch(function () {
                            // Network or parse error
                            errorEl.textContent   = 'Request failed. Please try again.';
                            errorEl.style.display = 'block';
                            setLoading(saveBtn, false);
                        });
                    });
                }());
            </script>

        <?php endif; ?>
    </div>
</body>
</html>
