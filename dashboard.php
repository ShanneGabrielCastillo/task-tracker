<?php
// dashboard.php — Main Dashboard Page
// Displays the authenticated user's tasks, a task input form,
// filter buttons, and an empty task list populated by script.js.

// Include authentication guard (starts session, redirects if unauthenticated)
require_once 'auth.php';

// Include database connection ($conn)
require_once 'db.php';

// Get the authenticated user's id from the session
$user_id = (int) $_SESSION['user_id'];

// Fetch display name, username, and initial from session cache / DB
require_once 'user_helper.php';
// $username, $display_name, $display_initial are now available

// Query all tasks belonging to the authenticated user, ordered by deadline ascending
$tasks = [];
$stmt = $conn->prepare(
    'SELECT id, title, deadline, status, category FROM tasks WHERE user_id = ? ORDER BY deadline ASC'
);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    // Fetch all rows into the $tasks array
    while ($row = $result->fetch_assoc()) {
        $tasks[] = $row;
    }
    $stmt->close();
}

// JSON-encode the tasks array for injection into the page script block
$tasks_json = json_encode($tasks);

// Collect unique non-empty categories for the category filter row
$categories = array_values(array_unique(array_filter(array_column($tasks, 'category'))));

// -------------------------------------------------------------------------
// Dashboard statistics — derived from the already-fetched $tasks array.
// No extra database query needed.
// -------------------------------------------------------------------------
$today = date('Y-m-d');

$stat_total     = count($tasks);
$stat_completed = 0;
$stat_pending   = 0;
$stat_overdue   = 0;

foreach ($tasks as $t) {
    if ($t['status'] === 'completed') {
        $stat_completed++;
    } else {
        // Pending = not completed
        $stat_pending++;
        // Overdue = not completed AND deadline is strictly before today
        if (!empty($t['deadline']) && $t['deadline'] < $today) {
            $stat_overdue++;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-layout">

    <!-- ── Sidebar ─────────────────────────────────────── -->
    <aside class="sidebar">
        <button class="sidebar-close-btn" id="sidebar-close" aria-label="Close menu" onclick="document.querySelector('.sidebar').classList.remove('open');document.getElementById('sidebar-overlay')&&document.getElementById('sidebar-overlay').classList.remove('visible');document.body.classList.remove('sidebar-open');">✕</button>
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">📋</div>
            <span class="sidebar-brand-name">Task Tracker</span>
        </a>

        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Menu</span>
            <a href="dashboard.php" class="nav-item active">
                <span class="nav-icon">🏠</span> Dashboard
            </a>
            <a href="all_tasks.php" class="nav-item">
                <span class="nav-icon">✅</span> All Tasks
            </a>
            <a href="calendar.php" class="nav-item">
                <span class="nav-icon">📅</span> Calendar
            </a>
            <a href="categories.php" class="nav-item">
                <span class="nav-icon">🏷️</span> Categories
            </a>
            <a href="reports.php" class="nav-item">
                <span class="nav-icon">📊</span> Reports
            </a>
        </nav>

        <div class="sidebar-footer">
            <a href="profile.php" class="sidebar-user sidebar-user-link">
                <div class="sidebar-avatar"><?php echo $display_initial; ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-username"><?php echo htmlspecialchars($display_name); ?></div>
                    <div class="sidebar-role">Student</div>
                </div>
            </a>
        </div>
    </aside>

    <!-- ── Top Header ──────────────────────────────────── -->
    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">Dashboard</div>
            <div class="header-breadcrumb">Welcome back, <?php echo htmlspecialchars($display_name); ?>!</div>
        </div>
        <div class="header-right">
            <a href="profile.php" class="header-user-pill">
                <div class="header-avatar"><?php echo $display_initial; ?></div>
                <span class="header-username"><?php echo htmlspecialchars($display_name); ?></span>
            </a>
            <a href="logout.php" class="btn-header-logout">⏻ Logout</a>
        </div>
    </header>

    <!-- ── Main Content ────────────────────────────────── -->
    <main class="app-main">

        <!-- Page header -->
        <div class="page-header">
            <div>
                <h1 class="page-title">Dashboard</h1>
                <p class="page-subtitle">Here's what's happening with your tasks today.</p>
            </div>
        </div>

        <!-- =====================================================
             Mini Dashboard Statistics
             ===================================================== -->
        <section class="stats-grid" aria-label="Task statistics">

            <div class="stat-card stat-total">
                <div class="stat-icon">📋</div>
                <div class="stat-body">
                    <span class="stat-value" id="stat-total"><?php echo $stat_total; ?></span>
                    <span class="stat-label">Total Tasks</span>
                </div>
            </div>

            <div class="stat-card stat-completed">
                <div class="stat-icon">✅</div>
                <div class="stat-body">
                    <span class="stat-value" id="stat-completed"><?php echo $stat_completed; ?></span>
                    <span class="stat-label">Completed</span>
                </div>
            </div>

            <div class="stat-card stat-pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-body">
                    <span class="stat-value" id="stat-pending"><?php echo $stat_pending; ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>

            <div class="stat-card stat-overdue">
                <div class="stat-icon">🚨</div>
                <div class="stat-body">
                    <span class="stat-value" id="stat-overdue"><?php echo $stat_overdue; ?></span>
                    <span class="stat-label">Overdue</span>
                </div>
            </div>

        </section>

        <!-- Task input form -->
        <section class="task-form-section">
            <div class="section-header">
                <div class="section-icon">✏️</div>
                <h2>Add New Task</h2>
            </div>

            <!-- Error / success message area for the add-task form -->
            <p id="form-message" class="form-message" style="display:none;"></p>

            <form id="add-task-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="task-title">Title</label>
                        <input
                            type="text"
                            id="task-title"
                            name="title"
                            placeholder="What do you need to do?"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="task-deadline">Deadline</label>
                        <input
                            type="date"
                            id="task-deadline"
                            name="deadline"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="task-category">Category</label>
                        <input
                            type="text"
                            id="task-category"
                            name="category"
                            placeholder="e.g. School"
                            list="category-suggestions"
                        >
                        <datalist id="category-suggestions">
                            <!-- populated dynamically by loadCategoryOptions() -->
                        </datalist>
                    </div>

                    <button type="submit" class="btn-primary">+ Add Task</button>
                </div>
            </form>
        </section>

        <!-- Controls: filter buttons + search input -->
        <div class="controls-bar">
            <section class="filter-section">
                <button class="btn-filter active" data-filter="all">All</button>
                <button class="btn-filter" data-filter="completed">Completed</button>
                <button class="btn-filter" data-filter="pending">Pending</button>
            </section>

            <!-- Search input — filters tasks by title in real-time -->
            <div class="search-section">
                <input
                    type="text"
                    id="task-search"
                    placeholder="Search tasks by title..."
                    autocomplete="off"
                >
            </div>
        </div>

        <!-- Category filter row — populated dynamically -->
        <div class="category-filter-bar" id="cat-filter-bar" style="display:none;">
            <button class="btn-cat active" data-category="">All Categories</button>
        </div>

        <!-- Empty task list container — populated by script.js -->
        <div id="task-list"></div>

    </main><!-- /.app-main -->

    <!-- Custom confirmation modal — used by deleteTask() in script.js -->
    <div id="confirm-modal" class="confirm-overlay" role="dialog" aria-modal="true" aria-labelledby="confirm-message">
        <div class="confirm-box">
            <div class="confirm-icon">🗑️</div>
            <p id="confirm-message" class="confirm-message"></p>
            <div class="confirm-actions">
                <button id="confirm-cancel" class="btn-secondary confirm-btn-cancel">Cancel</button>
                <button id="confirm-ok"     class="btn-danger  confirm-btn-ok">Delete</button>
            </div>
        </div>
    </div>

    <!-- Inject the server-side task array into the page for script.js -->
    <script>
        const TASKS = <?php echo $tasks_json; ?>;
    </script>

    <!-- Load categories dynamically and populate datalist + filter bar -->
    <script>
    (function() {
        function escHtml(s) {
            return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }

        fetch('get_categories.php')
            .then(function(r) { return r.json(); })
            .then(function(cats) {
                // Populate datalist for the add-task form
                var dl = document.getElementById('category-suggestions');
                if (dl) {
                    dl.innerHTML = cats.map(function(c) {
                        return '<option value="' + escHtml(c.name) + '">';
                    }).join('');
                }

                // Populate category filter bar
                var bar = document.getElementById('cat-filter-bar');
                if (bar && cats.length > 0) {
                    // Keep the "All Categories" button, add one per category
                    var extra = cats.map(function(c) {
                        return '<button class="btn-cat" data-category="' + escHtml(c.name) + '">' +
                               escHtml(c.name) + '</button>';
                    }).join('');
                    bar.innerHTML = '<button class="btn-cat active" data-category="">All Categories</button>' + extra;
                    bar.style.display = '';

                    // Re-attach category filter handlers (script.js runs after this)
                    bar.querySelectorAll('.btn-cat').forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            bar.querySelectorAll('.btn-cat').forEach(function(b) { b.classList.remove('active'); });
                            btn.classList.add('active');
                            if (typeof window.setCategoryFilter === 'function') {
                                window.setCategoryFilter(btn.dataset.category || '');
                            }
                        });
                    });
                }
            })
            .catch(function() { /* silently ignore — datalist is optional */ });
    })();
    </script>

    <!-- Client-side task rendering and interaction logic -->
    <script src="script.js"></script>
    <script src="sidebar.js"></script>
</body>
</html>
