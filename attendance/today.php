<?php
// ============================================================
// Today's Attendance Log
// Location: /erp/attendance/today.php
// ============================================================

require_once __DIR__ . '/../include/config.php';

if (!is_logged_in()) {
    header("Location: ../auth/admin_login.php");
    exit;
}

if (!in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    header("Location: ../auth/admin_login.php");
    exit;
}

$username = htmlspecialchars($_SESSION['username']);
$role     = $_SESSION['role'];
$today    = date('Y-m-d');

// ---------- Fetch today's attendance ----------
$sql = "
    SELECT
        a.id,
        a.user_id,
        a.attendance_date,
        a.time_in,
        a.time_out,
        a.status,
        u.username,
        u.first_name,
        u.last_name,
        u.employee_code,
        u.department,
        u.position
    FROM attendance a
    INNER JOIN users u ON u.id = a.user_id
    WHERE a.attendance_date = ?
    ORDER BY
        CASE WHEN a.time_in IS NULL THEN 1 ELSE 0 END,
        a.time_in DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$today]);
$rows = $stmt->fetchAll();

// ---------- Counters ----------
$totalTimedIn  = 0;
$totalTimedOut = 0;
foreach ($rows as $r) {
    if ($r['time_in'])  $totalTimedIn++;
    if ($r['time_out']) $totalTimedOut++;
}

// ---------- Active employees count ----------
$activeStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'Active'");
$totalActive = (int) $activeStmt->fetchColumn();
$notScanned  = max(0, $totalActive - $totalTimedIn);

// ---------- Helper ----------
function display_name(array $u): string {
    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
    return $name !== '' ? $name : ($u['username'] ?? '—');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Attendance — ERP</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* =========================================================
           PAGE HEAD
           ========================================================= */
        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 22px;
        }
        .page-head h2 {
            margin: 0 0 6px;
            font-size: 22px;
            letter-spacing: 1px;
        }
        .page-head h2 span { color: var(--amber); }
        .page-head .lead {
            margin: 0;
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 700;
        }
        .page-head .lead strong {
            color: var(--off-white);
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }

        .head-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            border: 1px solid rgba(232, 140, 46, 0.35);
            background: transparent;
            color: var(--off-white);
            cursor: pointer;
            text-decoration: none;
            transition: all .18s ease;
            line-height: 1;
        }
        .action-btn:hover {
            background: rgba(232, 140, 46, 0.12);
            border-color: var(--amber);
            color: var(--amber);
        }
        .action-btn.primary {
            background: var(--amber);
            color: #2b0e0e;
            border-color: var(--amber);
        }
        .action-btn.primary:hover {
            background: var(--amber-bright);
            color: #2b0e0e;
        }

        /* =========================================================
           STATS ROW
           ========================================================= */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }
        .stat-card {
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-radius: 10px;
            padding: 16px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-card .stat-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: rgba(232, 140, 46, 0.15);
            color: var(--amber);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .stat-card .stat-icon.green {
            background: rgba(40, 167, 69, 0.18);
            color: #7ee29c;
        }
        .stat-card .stat-icon.blue {
            background: rgba(78, 158, 232, 0.18);
            color: #9ecbff;
        }
        .stat-card .stat-icon.yellow {
            background: rgba(255, 193, 7, 0.18);
            color: #ffdb6e;
        }
        .stat-card .stat-info { min-width: 0; }
        .stat-card .stat-value {
            font-size: 22px;
            font-weight: 800;
            color: var(--off-white);
            line-height: 1;
            font-family: ui-monospace, Menlo, Consolas, monospace;
        }
        .stat-card .stat-label {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--text-muted);
            font-weight: 800;
            margin-top: 4px;
        }

        /* =========================================================
           TABLE PANEL
           ========================================================= */
        .panel {
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-radius: 10px;
            overflow: hidden;
        }
        .panel-head {
            padding: 12px 16px;
            background: rgba(0,0,0,0.25);
            border-bottom: 1px solid rgba(232, 140, 46, 0.12);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            flex-wrap: wrap;
        }
        .panel-head .left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .panel-head i { color: var(--amber); font-size: 12px; }
        .panel-head .count-badge {
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 10px;
            background: rgba(232, 140, 46, 0.15);
            color: var(--amber);
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-weight: 700;
            letter-spacing: 0;
        }

        .table-wrap { overflow-x: auto; }

        table.att-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        table.att-table thead th {
            background: rgba(0,0,0,0.2);
            color: var(--text-muted);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-align: left;
            padding: 12px 16px;
            border-bottom: 1px solid rgba(232, 140, 46, 0.12);
            white-space: nowrap;
        }
        table.att-table tbody td {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(232, 140, 46, 0.08);
            color: var(--off-white);
            vertical-align: middle;
        }
        table.att-table tbody tr:last-child td {
            border-bottom: none;
        }
        table.att-table tbody tr:hover {
            background: rgba(232, 140, 46, 0.04);
        }

        .emp-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .emp-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(232, 140, 46, 0.15);
            color: var(--amber);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            flex-shrink: 0;
            text-transform: uppercase;
        }
        .emp-meta { min-width: 0; }
        .emp-meta .name {
            font-weight: 700;
            color: var(--off-white);
            line-height: 1.2;
            white-space: nowrap;
        }
        .emp-meta .sub {
            font-size: 11px;
            color: var(--text-muted);
            font-family: ui-monospace, Menlo, Consolas, monospace;
            letter-spacing: .5px;
            margin-top: 2px;
        }

        .code-cell {
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: .5px;
            white-space: nowrap;
        }

        .time-cell {
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 13px;
            font-weight: 700;
            color: var(--off-white);
            white-space: nowrap;
        }
        .time-cell.empty {
            color: #666;
            font-weight: 500;
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .pill i { font-size: 11px; }

        .pill.present { background: rgba(40, 167, 69, 0.15);  color: #7ee29c; }
        .pill.absent  { background: rgba(220, 53, 69, 0.15);  color: #ff8f9a; }
        .pill.late    { background: rgba(255, 193, 7, 0.15);  color: #ffdb6e; }
        .pill.leave   { background: rgba(78, 158, 232, 0.15); color: #9ecbff; }
        .pill.pending { background: rgba(255, 193, 7, 0.15);  color: #ffdb6e; }

        /* Empty state */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        .empty-state i {
            font-size: 54px;
            color: rgba(232, 140, 46, 0.35);
            margin-bottom: 16px;
            display: block;
        }
        .empty-state .title {
            font-size: 16px;
            font-weight: 800;
            color: var(--off-white);
            letter-spacing: 1px;
            margin-bottom: 6px;
        }
        .empty-state .sub {
            font-size: 12px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        @media (max-width: 700px) {
            table.att-table thead { display: none; }
            table.att-table tbody td {
                display: block;
                padding: 8px 16px;
                border-bottom: none;
            }
            table.att-table tbody tr {
                display: block;
                padding: 12px 0;
                border-bottom: 1px solid rgba(232, 140, 46, 0.08);
            }
            table.att-table tbody td::before {
                content: attr(data-label);
                display: inline-block;
                width: 100px;
                font-size: 10px;
                font-weight: 800;
                letter-spacing: 1.5px;
                text-transform: uppercase;
                color: var(--text-muted);
                margin-right: 8px;
            }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../component/admin_nav.php'; ?>

<div class="admin-content">

    <!-- ================= PAGE HEAD ================= -->
    <div class="page-head">
        <div>
            <h2>Today's <span>Attendance</span></h2>
            <p class="lead">
                <i class="bi bi-calendar3"></i>
                <?= date('l, F j, Y') ?>
                · <strong><?= date('h:i A') ?> PHT</strong>
            </p>
        </div>

        <div class="head-actions">
         <a href="list.php" class="action-btn">
    <i class="bi bi-people-fill"></i> List of Employee
</a>

<a href="attendance.php" class="action-btn">
    <i class="bi bi-camera-video-fill"></i> Scanner
</a>
        </div>
    </div>

    <!-- ================= STATS ROW ================= -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-icon green"><i class="bi bi-check-circle-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalTimedIn ?></div>
                <div class="stat-label">Timed In</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon blue"><i class="bi bi-box-arrow-right"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalTimedOut ?></div>
                <div class="stat-label">Timed Out</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon yellow"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $notScanned ?></div>
                <div class="stat-label">Not Yet In</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-info">
                <div class="stat-value"><?= $totalActive ?></div>
                <div class="stat-label">Active Staff</div>
            </div>
        </div>
    </div>

    <!-- ================= TABLE ================= -->
    <div class="panel">
        <div class="panel-head">
            <div class="left">
                <i class="bi bi-list-check"></i>
                Attendance Log
                <span class="count-badge"><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?></span>
            </div>
            <div class="left">
                <i class="bi bi-clock-history"></i>
                Live · <?= date('h:i:s A') ?> PHT
            </div>
        </div>

        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="bi bi-clipboard-x"></i>
                <div class="title">No attendance records yet</div>
                <div class="sub">Scans will appear here as employees badge in</div>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="att-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Code</th>
                            <th>Department</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $r):
                        $name    = display_name($r);
                        $initial = strtoupper(mb_substr($name, 0, 1));
                        $in      = $r['time_in']  ? date('h:i:s A', strtotime($r['time_in']))  : null;
                        $out     = $r['time_out'] ? date('h:i:s A', strtotime($r['time_out'])) : null;

                        $statusClass = strtolower($r['status']);
                        $statusIcon  = [
                            'Present' => 'bi-check-circle-fill',
                            'Absent'  => 'bi-x-circle-fill',
                            'Late'    => 'bi-exclamation-triangle-fill',
                            'Leave'   => 'bi-airplane-fill',
                        ][$r['status']] ?? 'bi-circle-fill';
                    ?>
                        <tr>
                            <td data-label="Employee">
                                <div class="emp-cell">
                                    <div class="emp-avatar"><?= e($initial) ?></div>
                                    <div class="emp-meta">
                                        <div class="name"><?= e($name) ?></div>
                                        <div class="sub">@<?= e($r['username']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td data-label="Code">
                                <span class="code-cell"><?= e($r['employee_code'] ?? '—') ?></span>
                            </td>
                            <td data-label="Department">
                                <span class="code-cell"><?= e($r['department'] ?? '—') ?></span>
                            </td>
                            <td data-label="Time In">
                                <?php if ($in): ?>
                                    <span class="time-cell"><?= e($in) ?></span>
                                <?php else: ?>
                                    <span class="time-cell empty">—</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Time Out">
                                <?php if ($out): ?>
                                    <span class="time-cell"><?= e($out) ?></span>
                                <?php else: ?>
                                    <?php if ($in): ?>
                                        <span class="pill pending"><i class="bi bi-hourglass-split"></i> Still In</span>
                                    <?php else: ?>
                                        <span class="time-cell empty">—</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td data-label="Status">
                                <span class="pill <?= e($statusClass) ?>">
                                    <i class="bi <?= e($statusIcon) ?>"></i>
                                    <?= e($r['status']) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>