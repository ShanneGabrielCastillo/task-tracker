<?php
// categories.php — Category management page
require_once 'auth.php';
require_once 'db.php';

$user_id  = (int) $_SESSION['user_id'];
$username = '';
$stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $username = $row['username'];
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-layout">

    <aside class="sidebar">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">📋</div>
            <span class="sidebar-brand-name">Task Tracker</span>
        </a>
        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Menu</span>
            <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="all_tasks.php"  class="nav-item"><span class="nav-icon">✅</span> All Tasks</a>
            <a href="calendar.php"   class="nav-item"><span class="nav-icon">📅</span> Calendar</a>
            <a href="categories.php" class="nav-item active"><span class="nav-icon">🏷️</span> Categories</a>
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

    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">Categories</div>
            <div class="header-breadcrumb">Organize your tasks by category</div>
        </div>
        <div class="header-right">
            <div class="header-user-pill">
                <div class="header-avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <span class="header-username"><?php echo htmlspecialchars($username); ?></span>
            </div>
            <a href="logout.php" class="btn-header-logout">⏻ Logout</a>
        </div>
    </header>

    <main class="app-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Categories</h1>
                <p class="page-subtitle" id="cat-subtitle">Loading…</p>
            </div>
            <button class="btn-primary" id="btn-add-cat"
                    style="width:auto;margin-top:0;padding:10px 20px;font-size:.875rem;">
                + Add Category
            </button>
        </div>

        <!-- Loading / error state -->
        <div id="cat-loading" style="text-align:center;padding:40px;color:var(--text-muted);">
            Loading categories…
        </div>

        <!-- Error state (hidden by default) -->
        <div id="cat-error" style="display:none;">
            <div class="auth-alert auth-alert-error" style="margin-bottom:16px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span id="cat-error-msg">Failed to load categories.</span>
            </div>
            <button class="btn-secondary" id="cat-retry-btn"
                    style="width:auto;padding:9px 20px;margin-top:0;">
                Retry
            </button>
        </div>

        <!-- Category table (hidden until loaded) -->
        <div id="cat-content" style="display:none;">
            <div class="cat-table-wrap">
                <table class="cat-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Tasks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cat-tbody"></tbody>
                </table>
                <div id="cat-empty" class="task-empty" style="display:none;">
                    <span class="empty-icon" style="font-size:2rem;display:block;margin-bottom:10px;opacity:.4">🏷️</span>
                    <p>No categories yet. Click <strong>+ Add Category</strong> to create one.</p>
                </div>
            </div>
        </div>

    </main>

    <!-- ── Add Category Modal ─────────────────────────────── -->
    <div class="cat-modal-overlay" id="add-modal" role="dialog" aria-modal="true" aria-labelledby="add-modal-title">
        <div class="cat-modal-box">
            <div class="cat-modal-header">
                <h2 class="cat-modal-title" id="add-modal-title">Add Category</h2>
                <button class="cat-modal-close" id="add-modal-close" aria-label="Close">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div id="add-modal-error" class="auth-alert auth-alert-error" style="display:none;margin-bottom:16px;"></div>

            <form id="add-cat-form" novalidate>

                <div class="form-group">
                    <label for="cat-name" class="auth-label">
                        Category Name <span style="color:var(--danger)">*</span>
                    </label>
                    <input type="text" id="cat-name" class="auth-input"
                           placeholder="e.g. Research, Fitness, Study…"
                           maxlength="100" required autocomplete="off"
                           style="padding-left:14px;">
                </div>

                <div class="form-group" style="margin-bottom:0;">
                    <label for="cat-desc" class="auth-label">
                        Description
                        <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">
                            (optional)
                        </span>
                    </label>
                    <input type="text" id="cat-desc" class="auth-input"
                           placeholder="Short description of this category"
                           maxlength="255" autocomplete="off"
                           style="padding-left:14px;">
                </div>

                <div class="cat-modal-footer">
                    <button type="button" class="btn-secondary" id="add-modal-cancel"
                            style="width:auto;margin-top:0;padding:10px 20px;">
                        Cancel
                    </button>
                    <button type="submit" class="auth-submit-btn" id="add-submit-btn"
                            style="width:auto;padding:10px 24px;min-height:42px;font-size:.9rem;">
                        <span class="btn-text">Create Category</span>
                        <span class="btn-spinner" style="display:none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5" stroke-linecap="round">
                                <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83
                                         M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                            </svg>
                        </span>
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- ── Delete Confirm Modal ───────────────────────────── -->
    <div class="confirm-overlay" id="del-modal" role="dialog" aria-modal="true">
        <div class="confirm-box">
            <div class="confirm-icon">🗑️</div>
            <p class="confirm-message" id="del-modal-msg"></p>
            <p id="del-modal-sub"
               style="font-size:.8rem;color:var(--text-muted);margin-top:-8px;
                      margin-bottom:16px;text-align:center;"></p>
            <div class="confirm-actions">
                <button class="btn-secondary confirm-btn-cancel" id="del-cancel"
                        style="width:auto;margin-top:0;padding:10px 20px;">Cancel</button>
                <button class="btn-danger confirm-btn-ok" id="del-confirm">Delete</button>
            </div>
        </div>
    </div>

    <!-- Toast -->
    <div id="ux-toast" class="ux-toast"></div>

    <script>
    // ── State ────────────────────────────────────────────────
    var categories = [];

    // ── Helpers ──────────────────────────────────────────────
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showToast(msg, isError) {
        var t = document.getElementById('ux-toast');
        t.textContent = msg;
        t.className = 'ux-toast ' + (isError ? 'error' : 'success') + ' visible';
        clearTimeout(t._t);
        t._t = setTimeout(function () { t.classList.remove('visible'); }, 3000);
    }

    // ── Render table ─────────────────────────────────────────
    function render() {
        var tbody   = document.getElementById('cat-tbody');
        var empty   = document.getElementById('cat-empty');
        var content = document.getElementById('cat-content');
        var sub     = document.getElementById('cat-subtitle');

        sub.textContent = categories.length +
            ' categor' + (categories.length === 1 ? 'y' : 'ies');

        content.style.display = '';

        if (categories.length === 0) {
            tbody.innerHTML     = '';
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';

        tbody.innerHTML = categories.map(function (c) {
            var taskLabel = c.task_count + ' task' + (c.task_count !== 1 ? 's' : '');
            return '<tr>' +
                '<td><span class="cat-row-name">' + escHtml(c.name) + '</span></td>' +
                '<td class="cat-row-desc">' + escHtml(c.description || '—') + '</td>' +
                '<td><span class="badge badge-default">' + taskLabel + '</span></td>' +
                '<td>' +
                    '<div style="display:flex;gap:6px;">' +
                        '<a href="all_tasks.php" class="tbl-btn tbl-btn-edit">View Tasks</a>' +
                        '<button class="tbl-btn tbl-btn-delete cat-del-btn" ' +
                                'data-name="' + escHtml(c.name) + '" ' +
                                'data-count="' + c.task_count + '">Delete</button>' +
                    '</div>' +
                '</td>' +
            '</tr>';
        }).join('');

        // Attach delete handlers
        document.querySelectorAll('.cat-del-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openDeleteModal(btn.dataset.name, parseInt(btn.dataset.count, 10));
            });
        });
    }

    // ── Load categories ──────────────────────────────────────
    function loadCategories() {
        var loading = document.getElementById('cat-loading');
        var errBox  = document.getElementById('cat-error');
        var content = document.getElementById('cat-content');

        loading.style.display = '';
        errBox.style.display  = 'none';
        content.style.display = 'none';

        fetch('get_categories.php')
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (data) {
                if (!Array.isArray(data)) throw new Error('Unexpected response');
                categories = data;
                loading.style.display = 'none';
                render();
            })
            .catch(function (err) {
                loading.style.display = 'none';
                document.getElementById('cat-error-msg').textContent =
                    'Failed to load categories. ' + (err.message || '');
                errBox.style.display = '';
                document.getElementById('cat-subtitle').textContent = 'Error loading';
            });
    }

    document.getElementById('cat-retry-btn').addEventListener('click', loadCategories);

    // ── Add Modal ────────────────────────────────────────────
    var addModal  = document.getElementById('add-modal');
    var addForm   = document.getElementById('add-cat-form');
    var addErrEl  = document.getElementById('add-modal-error');
    var addSubmit = document.getElementById('add-submit-btn');

    function openAddModal() {
        addForm.reset();
        addErrEl.style.display = 'none';
        addModal.classList.add('visible');
        document.getElementById('cat-name').focus();
    }

    function closeAddModal() {
        addModal.classList.remove('visible');
    }

    document.getElementById('btn-add-cat').addEventListener('click', openAddModal);
    document.getElementById('add-modal-close').addEventListener('click', closeAddModal);
    document.getElementById('add-modal-cancel').addEventListener('click', closeAddModal);
    addModal.addEventListener('click', function (e) {
        if (e.target === addModal) closeAddModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && addModal.classList.contains('visible')) closeAddModal();
    });

    addForm.addEventListener('submit', function (e) {
        e.preventDefault();

        var name = document.getElementById('cat-name').value.trim();
        var desc = document.getElementById('cat-desc').value.trim();

        addErrEl.style.display = 'none';

        if (!name) {
            addErrEl.textContent   = 'Category name is required.';
            addErrEl.style.display = 'flex';
            document.getElementById('cat-name').focus();
            return;
        }

        // Client-side duplicate check
        var dup = categories.some(function (c) {
            return c.name.toLowerCase() === name.toLowerCase();
        });
        if (dup) {
            addErrEl.textContent   = 'A category with that name already exists.';
            addErrEl.style.display = 'flex';
            return;
        }

        // Loading state
        addSubmit.querySelector('.btn-text').textContent = 'Creating…';
        addSubmit.querySelector('.btn-spinner').style.display = 'inline-flex';
        addSubmit.disabled = true;

        var body = new URLSearchParams();
        body.append('name',        name);
        body.append('description', desc);

        fetch('category_add.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                categories.push({
                    id:          data.id,
                    name:        data.name,
                    description: data.description,
                    task_count:  0
                });
                categories.sort(function (a, b) { return a.name.localeCompare(b.name); });
                render();
                closeAddModal();
                showToast('Category "' + data.name + '" created!', false);
            } else {
                addErrEl.textContent   = data.error || 'Failed to create category.';
                addErrEl.style.display = 'flex';
            }
        })
        .catch(function () {
            addErrEl.textContent   = 'Request failed. Please try again.';
            addErrEl.style.display = 'flex';
        })
        .finally(function () {
            addSubmit.querySelector('.btn-text').textContent = 'Create Category';
            addSubmit.querySelector('.btn-spinner').style.display = 'none';
            addSubmit.disabled = false;
        });
    });

    // ── Delete Modal ─────────────────────────────────────────
    var delModal       = document.getElementById('del-modal');
    var delMsg         = document.getElementById('del-modal-msg');
    var delSub         = document.getElementById('del-modal-sub');
    var delConfirmBtn  = document.getElementById('del-confirm');
    var delCancelBtn   = document.getElementById('del-cancel');
    var pendingDelName = null;

    function openDeleteModal(name, count) {
        pendingDelName     = name;
        delMsg.textContent = 'Delete "' + name + '"?';
        delSub.textContent = count > 0
            ? count + ' task' + (count !== 1 ? 's' : '') + ' will have their category cleared.'
            : 'This category has no tasks assigned.';
        delModal.classList.add('visible');
        delCancelBtn.focus();
    }

    function closeDeleteModal() {
        delModal.classList.remove('visible');
        pendingDelName = null;
    }

    delCancelBtn.addEventListener('click', closeDeleteModal);
    delModal.addEventListener('click', function (e) {
        if (e.target === delModal) closeDeleteModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && delModal.classList.contains('visible')) closeDeleteModal();
    });

    delConfirmBtn.addEventListener('click', function () {
        if (!pendingDelName) return;
        var name = pendingDelName;

        delConfirmBtn.disabled    = true;
        delConfirmBtn.textContent = '…';

        var body = new URLSearchParams();
        body.append('name', name);

        fetch('category_delete.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString()
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                categories = categories.filter(function (c) { return c.name !== name; });
                render();
                closeDeleteModal();
                showToast('Category "' + name + '" deleted.', false);
            } else {
                closeDeleteModal();
                showToast(data.error || 'Failed to delete category.', true);
            }
        })
        .catch(function () {
            closeDeleteModal();
            showToast('Request failed. Please try again.', true);
        })
        .finally(function () {
            delConfirmBtn.disabled    = false;
            delConfirmBtn.textContent = 'Delete';
        });
    });

    // ── Initial load ─────────────────────────────────────────
    loadCategories();
    </script>
</body>
</html>
