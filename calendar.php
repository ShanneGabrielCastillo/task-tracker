<?php
// calendar.php — Calendar view of tasks
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];

$username = '';
$stmt = $conn->prepare('SELECT username FROM users WHERE id = ?');
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) $username = $row['username'];
    $stmt->close();
}

// Determine displayed month/year from query string or default to current
$year  = isset($_GET['y']) ? (int)$_GET['y']  : (int)date('Y');
$month = isset($_GET['m']) ? (int)$_GET['m']  : (int)date('n');
if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$today     = date('Y-m-d');
$monthName = date('F Y', mktime(0,0,0,$month,1,$year));
$firstDay  = (int)date('w', mktime(0,0,0,$month,1,$year)); // 0=Sun
$daysInMonth = (int)date('t', mktime(0,0,0,$month,1,$year));

// Fetch tasks for this month
$monthStart = sprintf('%04d-%02d-01', $year, $month);
$monthEnd   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

$tasks = [];
$stmt = $conn->prepare(
    'SELECT id, title, deadline, status FROM tasks
     WHERE user_id = ? AND deadline BETWEEN ? AND ?
     ORDER BY deadline ASC'
);
if ($stmt) {
    $stmt->bind_param('iss', $user_id, $monthStart, $monthEnd);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) { $tasks[] = $row; }
    $stmt->close();
}

// Group tasks by day
$tasksByDay = [];
foreach ($tasks as $t) {
    $day = (int)date('j', strtotime($t['deadline']));
    $tasksByDay[$day][] = $t;
}

$prevM = $month - 1; $prevY = $year;
if ($prevM < 1) { $prevM = 12; $prevY--; }
$nextM = $month + 1; $nextY = $year;
if ($nextM > 12) { $nextM = 1; $nextY++; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar — Task Tracker</title>
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
            <a href="calendar.php"   class="nav-item active"><span class="nav-icon">📅</span> Calendar</a>
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

    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">Calendar</div>
            <div class="header-breadcrumb">View tasks by date</div>
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
                <h1 class="page-title">Calendar</h1>
                <p class="page-subtitle"><?php echo count($tasks); ?> tasks this month</p>
            </div>
        </div>

        <!-- Calendar controls -->
        <div class="calendar-controls">
            <div class="cal-nav" id="cal-nav-month">
                <a href="calendar.php?y=<?php echo $prevY; ?>&m=<?php echo $prevM; ?>" class="cal-nav-btn">‹</a>
                <span class="cal-month-label" id="cal-period-label"><?php echo $monthName; ?></span>
                <a href="calendar.php?y=<?php echo $nextY; ?>&m=<?php echo $nextM; ?>" class="cal-nav-btn">›</a>
            </div>

            <!-- Week / Day nav — hidden until those views are active -->
            <div class="cal-nav" id="cal-nav-js" style="display:none;">
                <button class="cal-nav-btn" id="cal-prev-btn" type="button">‹</button>
                <span class="cal-month-label" id="cal-js-label"></span>
                <button class="cal-nav-btn" id="cal-next-btn" type="button">›</button>
            </div>

            <div style="display:flex;align-items:center;gap:10px;">
                <div class="view-toggle">
                    <button class="view-btn active" data-view="month"  type="button">Month</button>
                    <button class="view-btn"         data-view="week"   type="button">Week</button>
                    <button class="view-btn"         data-view="day"    type="button">Day</button>
                </div>
            </div>
        </div>

        <!-- Month view (PHP-rendered, shown by default) -->
        <div class="calendar-wrap" id="view-month">
            <!-- Day name headers -->
            <div class="cal-header-row">
                <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $d): ?>
                <div class="cal-day-name"><?php echo $d; ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Grid cells -->
            <div class="cal-grid">
                <?php
                // Leading empty cells
                for ($i = 0; $i < $firstDay; $i++):
                ?>
                <div class="cal-cell other-month">
                    <div class="cal-date-num" style="opacity:.3"><?php echo $daysInMonth - $firstDay + $i + 1; ?></div>
                </div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++):
                    $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $day);
                    $isToday  = $dateStr === $today;
                    $dayTasks = $tasksByDay[$day] ?? [];
                ?>
                <div class="cal-cell<?php echo $isToday ? ' today' : ''; ?>">
                    <div class="cal-date-num"><?php echo $day; ?></div>
                    <?php foreach (array_slice($dayTasks, 0, 3) as $t):
                        $isCompleted = $t['status'] === 'completed';
                        $isOverdue   = !$isCompleted && $t['deadline'] < $today;
                        $cls = $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : 'pending');
                    ?>
                    <div class="cal-task-dot <?php echo $cls; ?>">
                        <span class="cal-dot <?php echo $cls; ?>"></span>
                        <?php echo htmlspecialchars(mb_strimwidth($t['title'], 0, 18, '…')); ?>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count($dayTasks) > 3): ?>
                    <div style="font-size:.65rem;color:var(--text-muted);padding:1px 4px;">+<?php echo count($dayTasks)-3; ?> more</div>
                    <?php endif; ?>
                </div>
                <?php endfor; ?>

                <?php
                // Trailing empty cells to complete the last row
                $total = $firstDay + $daysInMonth;
                $trailing = (7 - ($total % 7)) % 7;
                for ($i = 1; $i <= $trailing; $i++):
                ?>
                <div class="cal-cell other-month">
                    <div class="cal-date-num" style="opacity:.3"><?php echo $i; ?></div>
                </div>
                <?php endfor; ?>
            </div>

            <!-- Legend -->
            <div class="cal-legend">
                <div class="legend-item"><span class="legend-dot" style="background:var(--success)"></span> Completed</div>
                <div class="legend-item"><span class="legend-dot" style="background:var(--warning)"></span> Pending</div>
                <div class="legend-item"><span class="legend-dot" style="background:var(--danger)"></span> Overdue</div>
            </div>
        </div>

        <!-- Week view (JS-rendered) -->
        <div class="calendar-wrap cal-view-panel" id="view-week" style="display:none;"></div>

        <!-- Day view (JS-rendered) -->
        <div class="calendar-wrap cal-view-panel" id="view-day"   style="display:none;"></div>

    </main>

    <script>
    // ── Task data injected from PHP ─────────────────────────
    // All tasks for this month, keyed by YYYY-MM-DD string
    var ALL_TASKS = <?php
        $taskMap = [];
        foreach ($tasks as $t) {
            $taskMap[$t['deadline']][] = [
                'id'     => (int)$t['id'],
                'title'  => $t['title'],
                'status' => $t['status'],
            ];
        }
        echo json_encode($taskMap);
    ?>;

    var TODAY_STR = '<?php echo $today; ?>';

    // ── Helpers ─────────────────────────────────────────────
    function pad(n) { return String(n).padStart(2, '0'); }

    function dateStr(y, m, d) {
        return y + '-' + pad(m) + '-' + pad(d);
    }

    function taskStatus(t, todayStr) {
        if (t.status === 'completed') return 'completed';
        return t.deadline < todayStr ? 'overdue' : 'pending';
    }

    function statusLabel(s) {
        return s.charAt(0).toUpperCase() + s.slice(1);
    }

    // Returns tasks for a given YYYY-MM-DD string
    function tasksForDate(ds) {
        return ALL_TASKS[ds] || [];
    }

    // Truncate title
    function trunc(str, n) {
        return str.length > n ? str.slice(0, n) + '…' : str;
    }

    // Build a task pill HTML string
    function taskPill(t, ds) {
        var s = taskStatus(t, TODAY_STR);
        return '<div class="cal-task-dot ' + s + '">' +
               '<span class="cal-dot ' + s + '"></span>' +
               '<span>' + escHtml(trunc(t.title, 22)) + '</span>' +
               '</div>';
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── State ───────────────────────────────────────────────
    var currentView = 'month';

    // Cursor date for week/day navigation — start at today if in current month,
    // otherwise start at the 1st of the PHP-rendered month
    var cursorDate = (function() {
        var phpYear  = <?php echo $year; ?>;
        var phpMonth = <?php echo $month; ?>;
        var now = new Date();
        if (now.getFullYear() === phpYear && (now.getMonth() + 1) === phpMonth) {
            return new Date(now.getFullYear(), now.getMonth(), now.getDate());
        }
        return new Date(phpYear, phpMonth - 1, 1);
    }());

    // ── View panels ─────────────────────────────────────────
    var panels = {
        month : document.getElementById('view-month'),
        week  : document.getElementById('view-week'),
        day   : document.getElementById('view-day')
    };

    var navMonth = document.getElementById('cal-nav-month');
    var navJs    = document.getElementById('cal-nav-js');
    var jsLabel  = document.getElementById('cal-js-label');

    // ── Fade helper ─────────────────────────────────────────
    function showPanel(id) {
        Object.keys(panels).forEach(function(k) {
            var p = panels[k];
            p.style.display = 'none';
            p.classList.remove('cal-fade-in');
        });
        var target = panels[id];
        target.style.display = '';
        // Trigger reflow then add class for CSS transition
        void target.offsetWidth;
        target.classList.add('cal-fade-in');
    }

    // ── WEEK VIEW renderer ───────────────────────────────────
    function renderWeek() {
        var panel = panels.week;

        // Find Sunday of the week containing cursorDate
        var dow   = cursorDate.getDay(); // 0=Sun
        var sun   = new Date(cursorDate);
        sun.setDate(sun.getDate() - dow);

        // Build 7 dates
        var days = [];
        for (var i = 0; i < 7; i++) {
            var d = new Date(sun);
            d.setDate(sun.getDate() + i);
            days.push(d);
        }

        // Update nav label
        var startLabel = days[0].toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        var endLabel   = days[6].toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        jsLabel.textContent = startLabel + ' – ' + endLabel;

        // Build HTML
        var html = '<div class="cal-week-grid">';

        // Header row
        var dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        days.forEach(function(d, i) {
            var ds      = dateStr(d.getFullYear(), d.getMonth()+1, d.getDate());
            var isToday = ds === TODAY_STR;
            html += '<div class="cal-week-col-head' + (isToday ? ' today' : '') + '">' +
                    '<span class="cal-week-dayname">' + dayNames[i] + '</span>' +
                    '<span class="cal-week-daynum' + (isToday ? ' today-num' : '') + '">' + d.getDate() + '</span>' +
                    '</div>';
        });

        // Task rows
        days.forEach(function(d) {
            var ds    = dateStr(d.getFullYear(), d.getMonth()+1, d.getDate());
            var tlist = tasksForDate(ds);
            var isToday = ds === TODAY_STR;
            html += '<div class="cal-week-col' + (isToday ? ' today' : '') + '">';
            if (tlist.length === 0) {
                html += '<div class="cal-week-empty">—</div>';
            } else {
                tlist.forEach(function(t) {
                    html += taskPill(t, ds);
                });
            }
            html += '</div>';
        });

        html += '</div>';

        // Legend
        html += '<div class="cal-legend">' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--success)"></span> Completed</div>' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--warning)"></span> Pending</div>' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--danger)"></span> Overdue</div>' +
            '</div>';

        panel.innerHTML = html;
    }

    // ── DAY VIEW renderer ────────────────────────────────────
    function renderDay() {
        var panel = panels.day;
        var d  = cursorDate;
        var ds = dateStr(d.getFullYear(), d.getMonth()+1, d.getDate());

        // Update nav label
        jsLabel.textContent = d.toLocaleDateString('en-US', {
            weekday: 'long', month: 'long', day: 'numeric', year: 'numeric'
        });

        var tlist   = tasksForDate(ds);
        var isToday = ds === TODAY_STR;

        var html = '<div class="cal-day-view">';

        // Date header
        html += '<div class="cal-day-header' + (isToday ? ' today' : '') + '">' +
                '<span class="cal-day-header-num">' + d.getDate() + '</span>' +
                '<div class="cal-day-header-info">' +
                '<span class="cal-day-header-name">' +
                d.toLocaleDateString('en-US', { weekday: 'long' }) + '</span>' +
                '<span class="cal-day-header-month">' +
                d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' }) + '</span>' +
                '</div>' +
                (isToday ? '<span class="cal-today-badge">Today</span>' : '') +
                '</div>';

        // Task list
        if (tlist.length === 0) {
            html += '<div class="cal-day-empty">' +
                    '<span style="font-size:2rem;opacity:.35">📭</span>' +
                    '<p>No tasks due on this day</p>' +
                    '</div>';
        } else {
            html += '<div class="cal-day-tasks">';
            tlist.forEach(function(t) {
                var s = taskStatus(t, TODAY_STR);
                html += '<div class="cal-day-task-row ' + s + '">' +
                        '<span class="cal-dot ' + s + '" style="flex-shrink:0"></span>' +
                        '<div class="cal-day-task-info">' +
                        '<span class="cal-day-task-title">' + escHtml(t.title) + '</span>' +
                        '<span class="cal-day-task-status badge-' + s + '">' + statusLabel(s) + '</span>' +
                        '</div>' +
                        '<a href="edit_task.php?id=' + t.id + '" class="cal-day-task-edit">Edit</a>' +
                        '</div>';
            });
            html += '</div>';
        }

        html += '</div>';

        // Legend
        html += '<div class="cal-legend">' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--success)"></span> Completed</div>' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--warning)"></span> Pending</div>' +
            '<div class="legend-item"><span class="legend-dot" style="background:var(--danger)"></span> Overdue</div>' +
            '</div>';

        panel.innerHTML = html;
    }

    // ── Switch view ──────────────────────────────────────────
    function switchView(view) {
        currentView = view;

        // Update toggle buttons
        document.querySelectorAll('.view-btn').forEach(function(b) {
            b.classList.toggle('active', b.dataset.view === view);
        });

        if (view === 'month') {
            navMonth.style.display = '';
            navJs.style.display    = 'none';
            showPanel('month');
        } else {
            navMonth.style.display = 'none';
            navJs.style.display    = '';
            if (view === 'week') { renderWeek(); showPanel('week'); }
            if (view === 'day')  { renderDay();  showPanel('day');  }
        }
    }

    // ── Navigation (prev / next) for week & day ──────────────
    document.getElementById('cal-prev-btn').addEventListener('click', function() {
        if (currentView === 'week') {
            cursorDate.setDate(cursorDate.getDate() - 7);
            renderWeek(); showPanel('week');
        } else if (currentView === 'day') {
            cursorDate.setDate(cursorDate.getDate() - 1);
            renderDay(); showPanel('day');
        }
    });

    document.getElementById('cal-next-btn').addEventListener('click', function() {
        if (currentView === 'week') {
            cursorDate.setDate(cursorDate.getDate() + 7);
            renderWeek(); showPanel('week');
        } else if (currentView === 'day') {
            cursorDate.setDate(cursorDate.getDate() + 1);
            renderDay(); showPanel('day');
        }
    });

    // ── View toggle buttons ──────────────────────────────────
    document.querySelectorAll('.view-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            switchView(btn.dataset.view);
        });
    });
    </script>
</body>
</html>
