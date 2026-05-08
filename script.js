/**
 * script.js — Client-side task rendering and interaction logic
 * Handles task display, filtering, and CRUD operations via fetch().
 *
 * Expects the following to be injected by dashboard.php before this script:
 *   const TASKS = [...];  // array of { id, title, deadline, status, category }
 *
 * DOM requirements:
 *   #task-list        — container where task cards are rendered
 *   #add-task-form    — form with #task-title, #task-deadline, #task-category
 *   #form-message     — element for add-form feedback messages
 *   .btn-filter       — filter buttons with data-filter="all|completed|pending"
 *   #stat-total       — stat card value spans (updated after every mutation)
 *   #stat-completed
 *   #stat-pending
 *   #stat-overdue
 */

// In-memory task array, initialised from the server-injected TASKS constant.
/* global TASKS */
let tasks = (typeof TASKS !== 'undefined' && Array.isArray(TASKS)) ? TASKS : [];

// Active filter / search / category state — preserved across re-renders.
let currentFilter   = 'all';
let currentSearch   = '';
let currentCategory = '';

// ---------------------------------------------------------------------------
// Date helpers
// ---------------------------------------------------------------------------

/**
 * formatDeadline(dateStr)
 * Converts "YYYY-MM-DD" → "Jan 15, 2025" using UTC to avoid timezone shifts.
 */
function formatDeadline(dateStr) {
    if (!dateStr) return '';
    var parts = dateStr.split('-');
    var date = new Date(Date.UTC(
        parseInt(parts[0], 10),
        parseInt(parts[1], 10) - 1,
        parseInt(parts[2], 10)
    ));
    return date.toLocaleDateString('en-US', {
        year: 'numeric', month: 'short', day: 'numeric', timeZone: 'UTC'
    });
}

/**
 * todayStr()
 * Returns today's date as "YYYY-MM-DD" in local time.
 */
function todayStr() {
    var now = new Date();
    return now.getFullYear() + '-' +
        String(now.getMonth() + 1).padStart(2, '0') + '-' +
        String(now.getDate()).padStart(2, '0');
}

/**
 * isOverdue(dateStr)
 * True when deadline is strictly before today and task is still pending.
 */
function isOverdue(dateStr) {
    if (!dateStr) return false;
    return dateStr < todayStr();
}

// ---------------------------------------------------------------------------
// Sorting
// ---------------------------------------------------------------------------

/**
 * sortByDeadline(taskArray)
 * Returns a new array sorted by deadline ascending (nearest first).
 * Tasks with no deadline fall to the end.
 */
function sortByDeadline(taskArray) {
    return taskArray.slice().sort(function (a, b) {
        if (!a.deadline) return 1;
        if (!b.deadline) return -1;
        return a.deadline < b.deadline ? -1 : a.deadline > b.deadline ? 1 : 0;
    });
}

// ---------------------------------------------------------------------------
// Stats — recalculate from the in-memory tasks array and update the DOM
// ---------------------------------------------------------------------------

/**
 * refreshStats()
 * Recounts total / completed / pending / overdue from the current tasks[]
 * and writes the numbers into the stat card elements added in dashboard.php.
 * Called after every mutation so the stats are always in sync.
 */
function refreshStats() {
    var today = todayStr();
    var total     = tasks.length;
    var completed = 0;
    var pending   = 0;
    var overdue   = 0;

    tasks.forEach(function (t) {
        if (t.status === 'completed') {
            completed++;
        } else {
            pending++;
            if (t.deadline && t.deadline < today) {
                overdue++;
            }
        }
    });

    // Write to DOM — elements may not exist on non-dashboard pages, so guard.
    var elTotal     = document.getElementById('stat-total');
    var elCompleted = document.getElementById('stat-completed');
    var elPending   = document.getElementById('stat-pending');
    var elOverdue   = document.getElementById('stat-overdue');

    if (elTotal)     elTotal.textContent     = total;
    if (elCompleted) elCompleted.textContent = completed;
    if (elPending)   elPending.textContent   = pending;
    if (elOverdue)   elOverdue.textContent   = overdue;
}

// ---------------------------------------------------------------------------
// Rendering
// ---------------------------------------------------------------------------

/**
 * renderTasks(taskArray)
 * Sorts by deadline, rebuilds #task-list, re-applies active filters,
 * and refreshes the stats strip.
 */
function renderTasks(taskArray) {
    taskArray = sortByDeadline(taskArray);
    var list = document.getElementById('task-list');
    if (!list) return;

    list.innerHTML = '';

    taskArray.forEach(function (task) {
        var isCompleted = task.status === 'completed';
        var overdue     = !isCompleted && isOverdue(task.deadline);

        var card = document.createElement('div');
        card.className = 'task-card' +
            (isCompleted ? ' completed' : '') +
            (overdue     ? ' overdue'   : '');
        card.setAttribute('data-status',   task.status);
        card.setAttribute('data-category', task.category || '');

        var badgeText   = isCompleted ? 'Completed' : 'Pending';
        var completeBtn = isCompleted
            ? ''
            : '<button class="btn-complete" onclick="completeTask(' + task.id + ')">Complete</button>';
        var categoryBadge = task.category
            ? '<span class="task-category badge">' + escapeHtml(task.category) + '</span>'
            : '';

        card.innerHTML =
            '<div class="task-info">' +
                '<span class="task-title">' + escapeHtml(task.title) + '</span>' +
                '<div class="task-meta">' +
                    '<span class="task-deadline">📅 ' + formatDeadline(task.deadline) + '</span>' +
                    '<span class="task-status badge">' + badgeText + '</span>' +
                    categoryBadge +
                '</div>' +
            '</div>' +
            '<div class="task-actions">' +
                '<a href="edit_task.php?id=' + task.id + '" class="btn-edit">Edit</a>' +
                completeBtn +
                '<button class="btn-delete" onclick="deleteTask(' + task.id + ')">Delete</button>' +
            '</div>';

        list.appendChild(card);
    });

    applyFilter(currentFilter);

    // Keep stats in sync after every render.
    refreshStats();
}

// ---------------------------------------------------------------------------
// Filtering
// ---------------------------------------------------------------------------

/**
 * applyFilter(filter)
 * Shows / hides cards based on status, search text, and category.
 */
function applyFilter(filter) {
    var query = currentSearch.toLowerCase();
    var cards = document.querySelectorAll('#task-list .task-card');
    cards.forEach(function (card) {
        var status       = card.getAttribute('data-status');
        var cardCategory = card.getAttribute('data-category') || '';
        var titleEl      = card.querySelector('.task-title');
        var titleText    = titleEl ? titleEl.textContent.toLowerCase() : '';

        var matchesFilter   = filter === 'all' || status === filter;
        var matchesSearch   = query === '' || titleText.includes(query);
        var matchesCategory = currentCategory === '' || cardCategory === currentCategory;

        card.style.display = (matchesFilter && matchesSearch && matchesCategory) ? '' : 'none';
    });
}

// ---------------------------------------------------------------------------
// Utilities
// ---------------------------------------------------------------------------

function escapeHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
}

/**
 * showFormMessage(message, isError, autoDismiss)
 * Displays a message in #form-message. Success messages auto-dismiss after 3 s.
 */
function showFormMessage(message, isError, autoDismiss) {
    var el = document.getElementById('form-message');
    if (!el) return;
    el.textContent = message;
    el.style.display = 'block';
    el.className = 'form-message ' + (isError ? 'error' : 'success');

    if (!isError && autoDismiss !== false) {
        clearTimeout(el._dismissTimer);
        el._dismissTimer = setTimeout(function () {
            el.style.display = 'none';
            el.textContent = '';
        }, 3000);
    }
}

function hideFormMessage() {
    var el = document.getElementById('form-message');
    if (!el) return;
    el.style.display = 'none';
    el.textContent = '';
}

/**
 * setLoading(btn, isLoading, originalLabel)
 * Swaps a button into a spinner/disabled state while a request is in flight.
 */
function setLoading(btn, isLoading, originalLabel) {
    if (!btn) return;
    if (isLoading) {
        btn.dataset.originalLabel = originalLabel || btn.textContent;
        btn.disabled = true;
        btn.classList.add('btn-loading');
        btn.innerHTML = '<span class="spinner"></span> Saving...';
    } else {
        btn.disabled = false;
        btn.classList.remove('btn-loading');
        btn.textContent = btn.dataset.originalLabel || originalLabel || btn.textContent;
        delete btn.dataset.originalLabel;
    }
}

/**
 * showToast(message, isError)
 * Floating toast that auto-dismisses after 3 s.
 */
function showToast(message, isError) {
    var toast = document.getElementById('ux-toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'ux-toast';
        document.body.appendChild(toast);
    }
    toast.textContent = message;
    toast.className = 'ux-toast ' + (isError ? 'error' : 'success') + ' visible';

    clearTimeout(toast._timer);
    toast._timer = setTimeout(function () {
        toast.classList.remove('visible');
    }, 3000);
}

// ---------------------------------------------------------------------------
// Custom confirmation modal
// Replaces the native browser confirm() with a styled dialog that matches
// the design system. Returns a Promise that resolves true (confirm) or
// false (cancel) so callers can await it cleanly.
// ---------------------------------------------------------------------------

/**
 * showConfirm(message)
 * Shows the #confirm-modal with the given message.
 * Resolves true when the user clicks Confirm, false on Cancel / backdrop click.
 *
 * @param {string} message
 * @returns {Promise<boolean>}
 */
function showConfirm(message) {
    return new Promise(function (resolve) {
        var modal   = document.getElementById('confirm-modal');
        var msgEl   = document.getElementById('confirm-message');
        var btnOk   = document.getElementById('confirm-ok');
        var btnCancel = document.getElementById('confirm-cancel');

        if (!modal) {
            // Fallback to native confirm if modal markup is missing.
            resolve(window.confirm(message));
            return;
        }

        msgEl.textContent = message;
        modal.classList.add('visible');

        // Focus the Cancel button by default — safer UX for destructive actions.
        btnCancel.focus();

        function cleanup(result) {
            modal.classList.remove('visible');
            // Remove listeners to prevent stacking on repeated calls.
            btnOk.removeEventListener('click', onOk);
            btnCancel.removeEventListener('click', onCancel);
            modal.removeEventListener('click', onBackdrop);
            document.removeEventListener('keydown', onKey);
            resolve(result);
        }

        function onOk()      { cleanup(true);  }
        function onCancel()  { cleanup(false); }
        function onBackdrop(e) {
            // Close when clicking the dark overlay (not the dialog box itself).
            if (e.target === modal) cleanup(false);
        }
        function onKey(e) {
            if (e.key === 'Escape') cleanup(false);
            if (e.key === 'Enter'  && document.activeElement === btnOk) cleanup(true);
        }

        btnOk.addEventListener('click', onOk);
        btnCancel.addEventListener('click', onCancel);
        modal.addEventListener('click', onBackdrop);
        document.addEventListener('keydown', onKey);
    });
}

// ---------------------------------------------------------------------------
// DOMContentLoaded — wire up all event handlers
// ---------------------------------------------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    // Status filter buttons
    var filterButtons = document.querySelectorAll('.btn-filter');
    filterButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            filterButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentFilter = btn.getAttribute('data-filter') || 'all';
            applyFilter(currentFilter);
        });
    });

    // Category filter buttons
    var catButtons = document.querySelectorAll('.btn-cat');
    catButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            catButtons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            currentCategory = btn.getAttribute('data-category') || '';
            applyFilter(currentFilter);
        });
    });

    // Search input
    var searchInput = document.getElementById('task-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            currentSearch = searchInput.value.trim();
            applyFilter(currentFilter);
        });
    }

    // Add Task form
    var addForm = document.getElementById('add-task-form');
    if (addForm) {
        addForm.addEventListener('submit', function (e) {
            e.preventDefault();
            hideFormMessage();

            var titleInput    = document.getElementById('task-title');
            var deadlineInput = document.getElementById('task-deadline');
            var categoryInput = document.getElementById('task-category');
            var title    = titleInput    ? titleInput.value.trim()    : '';
            var deadline = deadlineInput ? deadlineInput.value.trim() : '';
            var category = categoryInput ? categoryInput.value.trim() : '';

            if (!title || !deadline) {
                showFormMessage('Title and deadline are required.', true);
                return;
            }

            var submitBtn = addForm.querySelector('button[type="submit"]');
            setLoading(submitBtn, true, '+ Add Task');

            fetch('add_task.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'title='    + encodeURIComponent(title)    +
                      '&deadline=' + encodeURIComponent(deadline) +
                      '&category=' + encodeURIComponent(category)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    tasks.push({
                        id:       data.id || Date.now(),
                        title:    title,
                        deadline: deadline,
                        status:   'pending',
                        category: category
                    });
                    // renderTasks calls refreshStats internally.
                    renderTasks(tasks);
                    if (titleInput)    titleInput.value    = '';
                    if (deadlineInput) deadlineInput.value = '';
                    if (categoryInput) categoryInput.value = '';
                    showFormMessage('Task Saved!', false);
                } else {
                    showFormMessage(data.error || 'Failed to add task.', true);
                }
            })
            .catch(function () {
                showFormMessage('Request failed. Please check your connection.', true);
            })
            .finally(function () {
                setLoading(submitBtn, false, '+ Add Task');
            });
        });
    }

    // Initial render — also sets stats to match the server-rendered values.
    renderTasks(tasks);
});

// ---------------------------------------------------------------------------
// completeTask(id)
// ---------------------------------------------------------------------------
function completeTask(id) {
    var btn = document.querySelector('.task-card [onclick="completeTask(' + id + ')"]');
    setLoading(btn, true, 'Complete');

    fetch('update_task.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=' + encodeURIComponent(id) + '&status=completed'
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            tasks = tasks.map(function (t) {
                return t.id === id ? Object.assign({}, t, { status: 'completed' }) : t;
            });
            // renderTasks calls refreshStats internally.
            renderTasks(tasks);
            showToast('Task Completed!', false);
        } else {
            setLoading(btn, false, 'Complete');
            showToast(data.error || 'Failed to complete task.', true);
        }
    })
    .catch(function () {
        setLoading(btn, false, 'Complete');
        showToast('Request failed. Please check your connection.', true);
    });
}

// ---------------------------------------------------------------------------
// deleteTask(id)
// Uses the custom modal instead of the native confirm() dialog.
// ---------------------------------------------------------------------------
function deleteTask(id) {
    showConfirm('Are you sure you want to delete this task?').then(function (confirmed) {
        if (!confirmed) return;

        var btn = document.querySelector('.task-card [onclick="deleteTask(' + id + ')"]');
        setLoading(btn, true, 'Delete');

        fetch('delete_task.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + encodeURIComponent(id)
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                tasks = tasks.filter(function (t) { return t.id !== id; });
                // renderTasks calls refreshStats internally.
                renderTasks(tasks);
                showToast('Task Deleted!', false);
            } else {
                setLoading(btn, false, 'Delete');
                showToast(data.error || 'Failed to delete task.', true);
            }
        })
        .catch(function () {
            setLoading(btn, false, 'Delete');
            showToast('Request failed. Please check your connection.', true);
        });
    });
}

// ---------------------------------------------------------------------------
// Expose functions on window for testability
// ---------------------------------------------------------------------------
window.renderTasks       = renderTasks;
window.applyFilter       = applyFilter;
window.completeTask      = completeTask;
window.deleteTask        = deleteTask;
window.refreshStats      = refreshStats;
window.setCategoryFilter = function(cat) {
    currentCategory = cat;
    applyFilter(currentFilter);
};
