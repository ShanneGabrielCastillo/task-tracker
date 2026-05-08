 <?php
// all_tasks.php — All Tasks page with table view, filters, sort, search, pagination
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];

// Fetch username
$username = '';
$stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $username = $row['username'];
    $stmt->close();
}

// Fetch all tasks
$tasks = [];
$stmt = $conn->prepare(
    'SELECT id, title, deadline, status, category FROM tasks WHERE user_id = ? ORDER BY deadline ASC'
);
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $tasks[] = $row; }
    $stmt->close();
}

$today      = date('Y-m-d');
$categories = array_values(array_unique(array_filter(array_column($tasks, 'category'))));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Tasks — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-layout">

    <!-- Sidebar -->
    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">📋</div>
            <span class="sidebar-brand-name">Task Tracker</span>
        </a>
        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Menu</span>
            <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="all_tasks.php"  class="nav-item active"><span class="nav-icon">✅</span> All Tasks</a>
            <a href="calendar.php"   class="nav-item"><span class="nav-icon">📅</span> Calendar</a>
            <a href="categories.php" class="nav-item"><span class="nav-icon">🏷️</span> Categories</a>
            <a href="reports.php"    class="nav-item"><span class="nav-icon">📊</span> Reports</a>
        </nav>
        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="sidebar-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <div class="sidebar-user-info">
                    <div class="sidebar-username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="sidebar-role">Student</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Header -->
    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">All Tasks</div>
            <div class="header-breadcrumb">Manage and track all your tasks</div>
        </div>
        <div class="header-right">
            <div class="header-user-pill">
                <div class="header-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <span class="header-username"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <a href="logout.php" class="btn-header-logout">⏻ Logout</a>
        </div>
    </header>

    <!-- Main -->
    <main class="app-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">All Tasks</h1>
                <p class="page-subtitle"><?php echo count($tasks); ?> tasks total</p>
            </div>
        </div>

        <!-- Controls -->
        <div class="page-controls">
            <!-- Filter tabs -->
            <div class="filter-tabs">
                <button class="filter-tab active" data-filter="all">All</button>
                <button class="filter-tab" data-filter="completed">Completed</button>
                <button class="filter-tab" data-filter="pending">Pending</button>
                <button class="filter-tab" data-filter="overdue">Overdue</button>
            </div>

            <!-- Category dropdown -->
            <select class="ctrl-select" id="cat-filter">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>

            <!-- Sort dropdown — custom styled -->
            <div class="sort-dropdown" id="sort-dropdown" role="combobox" aria-haspopup="listbox" aria-expanded="false" aria-label="Sort tasks">
                <button class="sort-trigger" id="sort-trigger" type="button" aria-haspopup="listbox">
                    <span class="sort-trigger-icon">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="3" y1="6"  x2="21" y2="6"/><line x1="6" y1="12" x2="18" y2="12"/><line x1="9" y1="18" x2="15" y2="18"/>
                        </svg>
                    </span>
                    <span class="sort-trigger-label" id="sort-label">Deadline ↑</span>
                    <span class="sort-trigger-chevron">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </span>
                </button>
                <ul class="sort-menu" id="sort-menu" role="listbox" aria-label="Sort options">
                    <li class="sort-option selected" role="option" aria-selected="true"  data-value="deadline-asc">
                        <span class="sort-option-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>
                        </span>
                        Deadline Ascending
                        <span class="sort-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </li>
                    <li class="sort-option" role="option" aria-selected="false" data-value="deadline-desc">
                        <span class="sort-option-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><polyline points="19 12 12 19 5 12"/></svg>
                        </span>
                        Deadline Descending
                        <span class="sort-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </li>
                    <li class="sort-divider" role="separator"></li>
                    <li class="sort-option" role="option" aria-selected="false" data-value="title-asc">
                        <span class="sort-option-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 6 8 2 12 6"/><line x1="8" y1="2" x2="8" y2="22"/><line x1="14" y1="6" x2="20" y2="6"/><line x1="14" y1="12" x2="18" y2="12"/><line x1="14" y1="18" x2="16" y2="18"/></svg>
                        </span>
                        A – Z
                        <span class="sort-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </li>
                    <li class="sort-option" role="option" aria-selected="false" data-value="title-desc">
                        <span class="sort-option-icon">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 18 8 22 12 18"/><line x1="8" y1="22" x2="8" y2="2"/><line x1="14" y1="6" x2="20" y2="6"/><line x1="14" y1="12" x2="18" y2="12"/><line x1="14" y1="18" x2="16" y2="18"/></svg>
                        </span>
                        Z – A
                        <span class="sort-check">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Search -->
            <div class="search-section">
                <input type="text" id="task-search" placeholder="Search tasks..." autocomplete="off">
            </div>
        </div>

        <!-- Table -->
        <div class="task-table-wrap">
            <table class="task-table" id="tasks-table">
                <thead>
                    <tr>
                        <th>Task Name</th>
                        <th>Category</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="tasks-tbody">
                <?php foreach ($tasks as $t):
                    $isCompleted = $t['status'] === 'completed';
                    $isOverdue   = !$isCompleted && !empty($t['deadline']) && $t['deadline'] < $today;
                    $statusKey   = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : 'pending');
                    $statusLabel = $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : 'Pending');
                    $catKey      = strtolower($t['category'] ?? '');
                    $catClass    = in_array($catKey, ['school','personal','work','project','health'])
                                   ? 'badge-'.$catKey : 'badge-default';
                    $deadlineFmt = !empty($t['deadline'])
                                   ? date('M j, Y', strtotime($t['deadline'])) : '—';
                ?>
                <tr data-status="<?php echo $statusKey; ?>"
                    data-category="<?php echo htmlspecialchars($t['category'] ?? ''); ?>"
                    data-title="<?php echo htmlspecialchars(strtolower($t['title'])); ?>"
                    data-deadline="<?php echo htmlspecialchars($t['deadline'] ?? ''); ?>">
                    <td>
                        <div class="task-name-cell">
                            <div class="task-name-icon">📌</div>
                            <span class="task-name-text"><?php echo htmlspecialchars($t['title']); ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($t['category'])): ?>
                        <span class="badge <?php echo $catClass; ?>"><?php echo htmlspecialchars($t['category']); ?></span>
                        <?php else: ?>
                        <span style="color:var(--text-muted);font-size:.8rem;">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="color:var(--text-secondary);font-size:.875rem;"><?php echo $deadlineFmt; ?></td>
                    <td>
                        <span class="badge badge-<?php echo $statusKey; ?>"><?php echo $statusLabel; ?></span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="edit_task.php?id=<?php echo (int)$t['id']; ?>" class="tbl-btn tbl-btn-edit">✏️ Edit</a>
                            <button class="tbl-btn tbl-btn-delete" onclick="deleteTblTask(<?php echo (int)$t['id']; ?>, this)">🗑️ Delete</button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($tasks)): ?>
                <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-muted);">No tasks yet. <a href="dashboard.php">Add your first task →</a></td></tr>
                <?php endif; ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <div class="pagination">
                <span class="pagination-info" id="pg-info">Showing all tasks</span>
                <div class="pagination-btns" id="pg-btns"></div>
            </div>
        </div>

    </main>

    <!-- Confirm modal -->
    <div id="confirm-modal" class="confirm-overlay" role="dialog" aria-modal="true">
        <div class="confirm-box">
            <div class="confirm-icon">🗑️</div>
            <p id="confirm-message" class="confirm-message"></p>
            <div class="confirm-actions">
                <button id="confirm-cancel" class="btn-secondary confirm-btn-cancel">Cancel</button>
                <button id="confirm-ok" class="btn-danger confirm-btn-ok">Delete</button>
            </div>
        </div>
    </div>

    <script>
    // ── Filter / search / sort ──────────────────────────────
    var rows = Array.from(document.querySelectorAll('#tasks-tbody tr[data-status]'));
    var currentFilter = 'all';
    var currentCat    = '';
    var currentSearch = '';
    var currentSort   = 'deadline-asc';
    var PAGE_SIZE     = 10;
    var currentPage   = 1;

    function applyAll() {
        var tbody = document.getElementById('tasks-tbody');

        // Step 1 — filter
        var filtered = rows.filter(function(r) {
            var matchF = currentFilter === 'all' || r.dataset.status === currentFilter;
            var matchC = currentCat === '' || r.dataset.category === currentCat;
            var matchS = currentSearch === '' || r.dataset.title.includes(currentSearch);
            return matchF && matchC && matchS;
        });

        // Step 2 — sort the filtered set
        filtered.sort(function(a, b) {
            var da = a.dataset.deadline || '';
            var db = b.dataset.deadline || '';
            var ta = a.dataset.title    || '';
            var tb = b.dataset.title    || '';

            if (currentSort === 'deadline-asc') {
                if (!da && !db) return 0;
                if (!da) return 1;
                if (!db) return -1;
                return da < db ? -1 : da > db ? 1 : 0;
            }
            if (currentSort === 'deadline-desc') {
                if (!da && !db) return 0;
                if (!da) return 1;
                if (!db) return -1;
                return db < da ? -1 : db > da ? 1 : 0;
            }
            if (currentSort === 'title-asc')  return ta.localeCompare(tb);
            if (currentSort === 'title-desc') return tb.localeCompare(ta);
            return 0;
        });

        // Step 3 — reorder DOM using the SORTED filtered array, then append
        //          excluded rows at the end (hidden). This is the critical fix:
        //          previously rows were re-appended in the original `rows` order,
        //          which overwrote the sort every time.
        var filteredSet = new Set(filtered);

        // Append sorted+filtered rows first (in correct sort order)
        filtered.forEach(function(r) {
            r.style.display = 'none'; // will be shown by pagination below
            tbody.appendChild(r);
        });

        // Append excluded rows after (they stay hidden)
        rows.forEach(function(r) {
            if (!filteredSet.has(r)) {
                r.style.display = 'none';
                tbody.appendChild(r);
            }
        });

        // Step 4 — pagination: show only the current page slice
        var total = filtered.length;
        var pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
        if (currentPage > pages) currentPage = pages;
        var start = (currentPage - 1) * PAGE_SIZE;
        var end   = start + PAGE_SIZE;

        filtered.slice(start, end).forEach(function(r) {
            r.style.display = '';
        });

        document.getElementById('pg-info').textContent =
            total === 0 ? 'No tasks found' :
            'Showing ' + (start + 1) + '–' + Math.min(end, total) + ' of ' + total + ' tasks';

        // Step 5 — pagination buttons
        var btns = document.getElementById('pg-btns');
        btns.innerHTML = '';

        var prevBtn = document.createElement('button');
        prevBtn.className = 'pg-btn';
        prevBtn.textContent = '‹';
        prevBtn.disabled = currentPage === 1;
        prevBtn.onclick = function() { currentPage--; applyAll(); };
        btns.appendChild(prevBtn);

        for (var i = 1; i <= pages; i++) {
            (function(p) {
                var b = document.createElement('button');
                b.className = 'pg-btn' + (p === currentPage ? ' active' : '');
                b.textContent = p;
                b.onclick = function() { currentPage = p; applyAll(); };
                btns.appendChild(b);
            })(i);
        }

        var nextBtn = document.createElement('button');
        nextBtn.className = 'pg-btn';
        nextBtn.textContent = '›';
        nextBtn.disabled = currentPage === pages;
        nextBtn.onclick = function() { currentPage++; applyAll(); };
        btns.appendChild(nextBtn);
    }

    document.querySelectorAll('.filter-tab').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.filter-tab').forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentFilter = btn.dataset.filter;
            currentPage = 1;
            applyAll();
        });
    });

    document.getElementById('cat-filter').addEventListener('change', function() {
        currentCat = this.value; currentPage = 1; applyAll();
    });

    // ── Custom sort dropdown ────────────────────────────────
    (function() {
        var dropdown = document.getElementById('sort-dropdown');
        var trigger  = document.getElementById('sort-trigger');
        var menu     = document.getElementById('sort-menu');
        var label    = document.getElementById('sort-label');
        var options  = Array.from(menu.querySelectorAll('.sort-option'));

        var labels = {
            'deadline-asc':  'Deadline ↑',
            'deadline-desc': 'Deadline ↓',
            'title-asc':     'A – Z',
            'title-desc':    'Z – A'
        };

        function openMenu() {
            dropdown.classList.add('open');
            dropdown.setAttribute('aria-expanded', 'true');
            menu.setAttribute('aria-hidden', 'false');
            // Focus first option for keyboard nav
            var sel = menu.querySelector('.sort-option.selected') || options[0];
            if (sel) sel.focus();
        }

        function closeMenu(returnFocus) {
            dropdown.classList.remove('open');
            dropdown.setAttribute('aria-expanded', 'false');
            menu.setAttribute('aria-hidden', 'true');
            // Only return focus to trigger when closed via keyboard (not outside click)
            if (returnFocus) trigger.focus();
        }

        function selectOption(opt) {
            var val = opt.dataset.value;
            options.forEach(function(o) {
                o.classList.remove('selected');
                o.setAttribute('aria-selected', 'false');
            });
            opt.classList.add('selected');
            opt.setAttribute('aria-selected', 'true');
            label.textContent = labels[val] || val;
            currentSort = val;
            currentPage = 1;
            applyAll();
            closeMenu(true);  // return focus to trigger after keyboard/click selection
        }

        trigger.addEventListener('click', function(e) {
            e.stopPropagation();
            dropdown.classList.contains('open') ? closeMenu(true) : openMenu();
        });

        options.forEach(function(opt) {
            opt.setAttribute('tabindex', '-1');

            opt.addEventListener('click', function(e) {
                e.stopPropagation();
                selectOption(opt);
            });

            opt.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    selectOption(opt);
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    var next = options[options.indexOf(opt) + 1];
                    if (next) next.focus();
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    var prev = options[options.indexOf(opt) - 1];
                    if (prev) prev.focus();
                } else if (e.key === 'Escape') {
                    closeMenu(true);  // Escape always returns focus to trigger
                }
            });
        });

        trigger.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openMenu();
            } else if (e.key === 'Escape') {
                closeMenu(true);
            }
        });

        // Close on outside click — do NOT steal focus from whatever was clicked
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target)) closeMenu(false);
        });

        // Close on Escape from anywhere — return focus to trigger
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && dropdown.classList.contains('open')) closeMenu(true);
        });
    })();

    document.getElementById('task-search').addEventListener('input', function() {
        currentSearch = this.value.trim().toLowerCase(); currentPage = 1; applyAll();
    });

    applyAll();

    // ── Delete ──────────────────────────────────────────────
    function showConfirm(msg) {
        return new Promise(function(resolve) {
            var modal = document.getElementById('confirm-modal');
            var msgEl = document.getElementById('confirm-message');
            var ok    = document.getElementById('confirm-ok');
            var cancel= document.getElementById('confirm-cancel');
            msgEl.textContent = msg;
            modal.classList.add('visible');
            cancel.focus();
            function cleanup(r) {
                modal.classList.remove('visible');
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                modal.removeEventListener('click', onBd);
                document.removeEventListener('keydown', onKey);
                resolve(r);
            }
            function onOk()    { cleanup(true);  }
            function onCancel(){ cleanup(false); }
            function onBd(e)   { if (e.target === modal) cleanup(false); }
            function onKey(e)  { if (e.key === 'Escape') cleanup(false); }
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
            modal.addEventListener('click', onBd);
            document.addEventListener('keydown', onKey);
        });
    }

    function deleteTblTask(id, btn) {
        showConfirm('Are you sure you want to delete this task?').then(function(confirmed) {
            if (!confirmed) return;
            btn.disabled = true;
            btn.textContent = '…';
            fetch('delete_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(id)
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    var row = btn.closest('tr');
                    rows = rows.filter(function(r) { return r !== row; });
                    row.remove();
                    applyAll();
                } else {
                    btn.disabled = false;
                    btn.textContent = '🗑️ Delete';
                    alert(data.error || 'Delete failed.');
                }
            })
            .catch(function() { btn.disabled = false; btn.textContent = '🗑️ Delete'; });
        });
    }
    </script>
</body>
</html>
