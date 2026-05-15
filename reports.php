<?php
// reports.php — Analytics and reports page
require_once 'auth.php';
require_once 'db.php';

$user_id = (int) $_SESSION['user_id'];

// Fetch display name, username, and initial from session cache / DB
require_once 'user_helper.php';

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
$total      = count($tasks);
$completed  = 0; $pending = 0; $overdue = 0;
$catCounts  = [];

foreach ($tasks as $t) {
    $cat = $t['category'] ?: 'Uncategorized';
    $catCounts[$cat] = ($catCounts[$cat] ?? 0) + 1;

    if ($t['status'] === 'completed') {
        $completed++;
    } else {
        $pending++;
        if (!empty($t['deadline']) && $t['deadline'] < $today) $overdue++;
    }
}

arsort($catCounts);
$maxCat = max(array_values($catCounts) ?: [1]);

// Donut chart values (SVG)
$donutTotal = $total ?: 1;
$r = 54; $cx = 70; $cy = 70; $circumference = 2 * M_PI * $r;
$completedPct = $completed / $donutTotal;
$pendingPct   = ($pending - $overdue) / $donutTotal;
$overduePct   = $overdue / $donutTotal;

function donutSegment($pct, $offset, $color, $r, $cx, $cy, $circ) {
    $dash = $pct * $circ;
    $gap  = $circ - $dash;
    return '<circle cx="'.$cx.'" cy="'.$cy.'" r="'.$r.'"
        fill="none" stroke="'.$color.'" stroke-width="16"
        stroke-dasharray="'.$dash.' '.$gap.'"
        stroke-dashoffset="'.(-$offset * $circ).'"
        transform="rotate(-90 '.$cx.' '.$cy.')" />';
}

// Recent activity (last 8 tasks by deadline proximity)
$recent = array_slice($tasks, 0, 8);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — Task Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="app-layout">

    <aside class="sidebar">
        <button class="sidebar-close-btn" id="sidebar-close" aria-label="Close menu" onclick="document.querySelector('.sidebar').classList.remove('open');document.getElementById('sidebar-overlay')&&document.getElementById('sidebar-overlay').classList.remove('visible');document.body.classList.remove('sidebar-open');">✕</button>
        <a href="dashboard.php" class="sidebar-brand">
            <div class="sidebar-brand-icon">📋</div>
            <span class="sidebar-brand-name">Task Tracker</span>
        </a>
        <nav class="sidebar-nav">
            <span class="sidebar-section-label">Menu</span>
            <a href="dashboard.php"  class="nav-item"><span class="nav-icon">🏠</span> Dashboard</a>
            <a href="all_tasks.php"  class="nav-item"><span class="nav-icon">✅</span> All Tasks</a>
            <a href="calendar.php"   class="nav-item"><span class="nav-icon">📅</span> Calendar</a>
            <a href="categories.php" class="nav-item"><span class="nav-icon">🏷️</span> Categories</a>
            <a href="reports.php"    class="nav-item active"><span class="nav-icon">📊</span> Reports</a>
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

    <header class="app-header">
        <div class="header-left">
            <div class="header-page-title">Reports</div>
            <div class="header-breadcrumb">Analytics and task insights</div>
        </div>
        <div class="header-right">
            <a href="profile.php" class="header-user-pill">
                <div class="header-avatar"><?php echo $display_initial; ?></div>
                <span class="header-username"><?php echo htmlspecialchars($display_name); ?></span>
            </a>
            <a href="logout.php" class="btn-header-logout">⏻ Logout</a>
        </div>
    </header>

    <main class="app-main">

        <div class="page-header">
            <div>
                <h1 class="page-title">Reports</h1>
                <p class="page-subtitle">Overview of your task performance</p>
            </div>
        </div>

        <!-- Summary stat cards -->
        <section class="stats-grid" style="margin-bottom:24px;" aria-label="Task statistics">
            <div class="stat-card stat-total">
                <div class="stat-icon">📋</div>
                <div class="stat-body">
                    <span class="stat-value"><?php echo $total; ?></span>
                    <span class="stat-label">Total Tasks</span>
                </div>
            </div>
            <div class="stat-card stat-completed">
                <div class="stat-icon">✅</div>
                <div class="stat-body">
                    <span class="stat-value"><?php echo $completed; ?></span>
                    <span class="stat-label">Completed</span>
                </div>
            </div>
            <div class="stat-card stat-pending">
                <div class="stat-icon">⏳</div>
                <div class="stat-body">
                    <span class="stat-value"><?php echo $pending; ?></span>
                    <span class="stat-label">Pending</span>
                </div>
            </div>
            <div class="stat-card stat-overdue">
                <div class="stat-icon">🚨</div>
                <div class="stat-body">
                    <span class="stat-value"><?php echo $overdue; ?></span>
                    <span class="stat-label">Overdue</span>
                </div>
            </div>
        </section>

        <!-- Charts row -->
        <div class="charts-row">

            <!-- Donut chart -->
            <div class="chart-card">
                <div class="chart-card-title">📊 Task Status Distribution</div>
                <?php if ($total > 0): ?>
                <div class="donut-wrap">
                    <svg class="donut-svg" viewBox="0 0 140 140">
                        <!-- Background ring -->
                        <circle cx="70" cy="70" r="54" fill="none" stroke="#f1f5f9" stroke-width="16"/>
                        <?php
                        $offset = 0;
                        if ($completed > 0) {
                            echo donutSegment($completedPct, $offset, '#22c55e', $r, $cx, $cy, $circumference);
                            $offset += $completedPct;
                        }
                        if ($overdue > 0) {
                            echo donutSegment($overduePct, $offset, '#ef4444', $r, $cx, $cy, $circumference);
                            $offset += $overduePct;
                        }
                        if (($pending - $overdue) > 0) {
                            echo donutSegment($pendingPct, $offset, '#f59e0b', $r, $cx, $cy, $circumference);
                        }
                        ?>
                        <text x="70" y="66" text-anchor="middle" font-size="20" font-weight="700" fill="#0f172a"><?php echo $total; ?></text>
                        <text x="70" y="80" text-anchor="middle" font-size="9" fill="#94a3b8">TOTAL</text>
                    </svg>
                    <div class="donut-legend">
                        <div class="donut-legend-item">
                            <span class="donut-legend-dot" style="background:#22c55e"></span>
                            <span class="donut-legend-label">Completed</span>
                            <span class="donut-legend-val"><?php echo $completed; ?></span>
                        </div>
                        <div class="donut-legend-item">
                            <span class="donut-legend-dot" style="background:#f59e0b"></span>
                            <span class="donut-legend-label">Pending</span>
                            <span class="donut-legend-val"><?php echo $pending - $overdue; ?></span>
                        </div>
                        <div class="donut-legend-item">
                            <span class="donut-legend-dot" style="background:#ef4444"></span>
                            <span class="donut-legend-label">Overdue</span>
                            <span class="donut-legend-val"><?php echo $overdue; ?></span>
                        </div>
                        <?php if ($total > 0): ?>
                        <div class="donut-legend-item" style="margin-top:8px;padding-top:8px;border-top:1px solid var(--border);">
                            <span class="donut-legend-label" style="color:var(--text-muted);font-size:.75rem;">Completion rate</span>
                            <span class="donut-legend-val" style="color:var(--success);"><?php echo round($completed/$total*100); ?>%</span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:40px;color:var(--text-muted);">No tasks yet</div>
                <?php endif; ?>
            </div>

            <!-- Bar chart -->
            <div class="chart-card">
                <div class="chart-card-title">📈 Tasks by Category</div>
                <?php if (!empty($catCounts)): ?>
                <div style="display:flex;flex-direction:column;gap:10px;">
                    <?php foreach ($catCounts as $cat => $cnt):
                        $pct = round($cnt / $maxCat * 100);
                        $colors = [
                            'School' => '#6366f1', 'Personal' => '#ec4899',
                            'Work' => '#22c55e', 'Project' => '#8b5cf6',
                            'Health' => '#f59e0b', 'Uncategorized' => '#94a3b8'
                        ];
                        $color = $colors[$cat] ?? '#6366f1';
                    ?>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <span style="font-size:.8rem;font-weight:600;color:var(--text-secondary);width:90px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($cat); ?></span>
                        <div style="flex:1;background:var(--surface-2);border-radius:4px;height:10px;overflow:hidden;">
                            <div style="width:<?php echo $pct; ?>%;height:100%;background:<?php echo $color; ?>;border-radius:4px;transition:width .4s ease;"></div>
                        </div>
                        <span style="font-size:.8rem;font-weight:700;color:var(--text-primary);width:24px;text-align:right;"><?php echo $cnt; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div style="text-align:center;padding:40px;color:var(--text-muted);">No category data yet</div>
                <?php endif; ?>
            </div>

        </div>

        <!-- Recent activity -->
        <div class="activity-card">
            <div class="chart-card-title" style="margin-bottom:16px;">🕐 Recent Tasks</div>
            <?php if (!empty($recent)): ?>
            <div class="activity-list">
                <?php foreach ($recent as $t):
                    $isCompleted = $t['status'] === 'completed';
                    $isOverdue   = !$isCompleted && !empty($t['deadline']) && $t['deadline'] < $today;
                    $iconClass   = $isCompleted ? 'completed' : ($isOverdue ? 'deleted' : 'created');
                    $icon        = $isCompleted ? '✅' : ($isOverdue ? '🚨' : '📌');
                    $statusText  = $isCompleted ? 'Completed' : ($isOverdue ? 'Overdue' : 'Pending');
                    $deadlineFmt = !empty($t['deadline']) ? date('M j, Y', strtotime($t['deadline'])) : 'No deadline';
                ?>
                <div class="activity-item">
                    <div class="activity-icon <?php echo $iconClass; ?>"><?php echo $icon; ?></div>
                    <div class="activity-body">
                        <div class="activity-text">
                            <strong><?php echo htmlspecialchars($t['title']); ?></strong>
                            <?php if (!empty($t['category'])): ?>
                            — <span style="color:var(--text-muted)"><?php echo htmlspecialchars($t['category']); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="activity-time">
                            <?php echo $statusText; ?> · Deadline: <?php echo $deadlineFmt; ?>
                        </div>
                    </div>
                    <span class="badge badge-<?php echo $isCompleted ? 'completed' : ($isOverdue ? 'overdue' : 'pending'); ?>">
                        <?php echo $statusText; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align:center;padding:40px;color:var(--text-muted);">No tasks yet. <a href="dashboard.php">Add your first task →</a></div>
            <?php endif; ?>
        </div>

    </main>
    <script src="sidebar.js"></script>
</body>
</html>
