<?php
require_once __DIR__ . '/../include/config.php';
require_login();

$stmt = $pdo->query("
    SELECT id, username, first_name, last_name, employee_code, department,
           email, contact_number, position, hire_date, birthdate,
           sss_no, philhealth_no, pagibig_no, basic_salary, status, role
    FROM users
    WHERE status = 'Active'
    ORDER BY department, username
");
$users = $stmt->fetchAll();

$page_title = 'Employee QR List';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> — ERP</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <!-- Bootstrap Icons (also loaded by admin_nav.php, safe to include twice) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        /* ---------- Page-level helpers ---------- */
        .page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 25px;
        }
        .page-head .lead { margin-bottom: 0; }
        .page-head .lead strong { color: var(--amber); }

        /* Header actions */
        .head-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid transparent;
            cursor: pointer;
            transition: background .18s ease, transform .1s ease, box-shadow .18s ease,
                        border-color .18s ease, color .18s ease;
            white-space: nowrap;
            font-family: inherit;
            line-height: 1;
        }
        .btn i { font-size: 14px; line-height: 1; }
        .btn:active  { transform: translateY(1px); }
        .btn:disabled{ opacity: .5; cursor: not-allowed; }

        .btn-primary {
            background: var(--amber);
            color: #2b0e0e;
        }
        .btn-primary:hover {
            background: var(--amber-bright);
            box-shadow: 0 6px 18px rgba(232,140,46,0.28);
        }

        .btn-ghost {
            background: transparent;
            color: var(--off-white);
            border: 1px solid rgba(232, 140, 46, 0.35);
        }
        .btn-ghost:hover {
            background: rgba(232, 140, 46, 0.12);
            border-color: var(--amber);
            color: var(--amber);
        }

        .btn-sm {
            padding: 8px 14px;
            font-size: 11px;
            letter-spacing: .8px;
            gap: 6px;
        }
        .btn-sm i { font-size: 13px; }

        /* Search bar */
        .toolbar {
            margin-bottom: 18px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .search {
            flex: 1;
            min-width: 260px;
            position: relative;
        }
        .search input {
            width: 100%;
            padding: 12px 16px 12px 42px;
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.18);
            border-radius: 8px;
            color: var(--off-white);
            font-size: 14px;
            font-weight: 600;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
            font-family: inherit;
        }
        .search input::placeholder {
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: .5px;
        }
        .search input:focus {
            border-color: var(--amber);
            box-shadow: 0 0 0 3px rgba(232,140,46,0.15);
        }
        .search i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 14px;
            pointer-events: none;
            transition: color .18s ease;
        }
        .search input:focus + i,
        .search:focus-within i { color: var(--amber); }

        /* Table wrapper */
        .table-card {
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        }
        .table-head {
            padding: 16px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 1px solid rgba(232, 140, 46, 0.12);
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            background: rgba(0,0,0,0.2);
        }
        .table-head strong { color: var(--amber); }
        .table-head .head-count i {
            color: var(--amber);
            margin-right: 6px;
            font-size: 13px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        thead th {
            background: rgba(0,0,0,0.25);
            color: var(--text-muted);
            text-align: left;
            padding: 12px 22px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(232, 140, 46, 0.15);
            white-space: nowrap;
        }
        tbody td {
            padding: 14px 22px;
            border-bottom: 1px solid rgba(232, 140, 46, 0.08);
            vertical-align: middle;
            color: var(--off-white);
        }
        tbody tr:last-child td { border-bottom: none; }
        tbody tr:hover { background: rgba(232, 140, 46, 0.06); }

        /* Employee cell */
        .emp-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber), var(--amber-bright));
            color: #2b0e0e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 13px;
            letter-spacing: .5px;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(232,140,46,0.25);
        }
        .emp-name {
            font-weight: 700;
            color: var(--off-white);
            line-height: 1.25;
        }
        .emp-user {
            font-size: 11px;
            color: var(--text-muted);
            letter-spacing: .3px;
            margin-top: 2px;
        }
        .emp-user i { font-size: 10px; margin-right: 2px; }

        /* Chips */
        .code-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 12px;
            font-weight: 700;
            background: rgba(232, 140, 46, 0.12);
            color: var(--amber);
            border-radius: 4px;
            border: 1px solid rgba(232, 140, 46, 0.3);
            letter-spacing: .5px;
        }
        .code-chip i { font-size: 11px; }

        .dept-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 12px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            background: rgba(122, 42, 40, 0.35);
            color: #ffb884;
            border-radius: 999px;
            border: 1px solid rgba(232, 140, 46, 0.25);
        }
        .dept-chip i { font-size: 11px; }

        .muted-dash {
            color: #8a6a6a;
            font-size: 13px;
            letter-spacing: 1px;
        }

        .row-actions {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .no-code-note {
            color: #8a6a6a;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .no-code-note i { font-size: 12px; }

        .empty {
            text-align: center;
            padding: 50px 20px;
            color: var(--text-muted);
            letter-spacing: 1px;
            font-weight: 600;
        }
        .empty i {
            display: block;
            font-size: 32px;
            color: rgba(232,140,46,0.4);
            margin-bottom: 12px;
        }

        /* ---------- Modal ---------- */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 4, 4, 0.78);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
            z-index: 2000;
            backdrop-filter: blur(3px);
        }
        .modal-backdrop.show { display: flex; }

        .modal {
            background: var(--maroon-card);
            border: 1px solid rgba(232, 140, 46, 0.25);
            border-top: 3px solid var(--amber);
            border-radius: 10px;
            width: 100%;
            max-width: 660px;
            max-height: 90vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 25px 70px rgba(0, 0, 0, 0.75);
            animation: modalIn .18s ease-out;
        }
        @keyframes modalIn {
            from { transform: translateY(10px) scale(.98); opacity: 0; }
            to   { transform: none; opacity: 1; }
        }

        .modal-head {
            padding: 18px 24px;
            background: var(--maroon-deep);
            border-bottom: 1px solid rgba(232, 140, 46, 0.15);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .modal-head h3 {
            margin: 0;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--amber);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .modal-head h3 i { font-size: 16px; }
        .modal-close {
            background: transparent;
            border: none;
            color: var(--off-white);
            font-size: 22px;
            line-height: 1;
            cursor: pointer;
            padding: 4px 8px;
            opacity: .65;
            transition: opacity .15s ease, color .15s ease;
            display: flex;
            align-items: center;
        }
        .modal-close:hover { opacity: 1; color: var(--amber); }

        .modal-body {
            padding: 24px 26px;
            overflow-y: auto;
        }

        .profile-top {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(232, 140, 46, 0.15);
        }
        .profile-avatar {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--amber), var(--amber-bright));
            color: #2b0e0e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            letter-spacing: 1px;
            flex-shrink: 0;
            box-shadow: 0 8px 22px rgba(232,140,46,0.35);
        }
        .profile-name {
            font-size: 18px;
            font-weight: 800;
            color: var(--off-white);
            margin: 0;
            letter-spacing: .5px;
        }
        .profile-sub {
            font-size: 12px;
            color: var(--text-muted);
            margin: 5px 0 0;
            letter-spacing: .5px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 14px 24px;
        }
        @media (max-width: 520px) {
            .detail-grid { grid-template-columns: 1fr; }
        }
        .detail-item {
            border-bottom: 1px dashed rgba(232, 140, 46, 0.15);
            padding-bottom: 8px;
        }
        .detail-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            font-weight: 800;
            margin-bottom: 4px;
        }
        .detail-value {
            font-size: 14px;
            color: var(--off-white);
            word-break: break-word;
            font-weight: 600;
        }
        .section-title {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--amber);
            font-weight: 800;
            margin: 24px 0 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid rgba(232, 140, 46, 0.2);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .section-title i { font-size: 13px; }

        .modal-foot {
            padding: 16px 24px;
            border-top: 1px solid rgba(232, 140, 46, 0.15);
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            background: rgba(0,0,0,0.2);
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            border-radius: 999px;
            text-transform: uppercase;
        }
        .badge i { font-size: 9px; }
        .badge-active   { background: rgba(40,167,69,0.2);  color: #7ee29c; border: 1px solid #28a745; }
        .badge-inactive { background: rgba(220,53,69,0.18); color: #ff8f9a; border: 1px solid #dc3545; }
        .badge-resigned { background: rgba(120,120,120,0.2); color: #ccc; border: 1px solid #555; }

        .role-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1px;
            border-radius: 4px;
            background: rgba(232,140,46,0.15);
            color: var(--amber);
            border: 1px solid rgba(232,140,46,0.35);
            text-transform: uppercase;
        }
        .role-chip i { font-size: 10px; }

        .loader {
            display: none;
            padding: 40px;
            text-align: center;
            color: var(--text-muted);
            letter-spacing: 1px;
        }
        .loader.show { display: block; }
        .spinner {
            width: 32px;
            height: 32px;
            border: 3px solid rgba(232,140,46,0.2);
            border-top-color: var(--amber);
            border-radius: 50%;
            margin: 0 auto 12px;
            animation: spin .8s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../component/admin_nav.php'; ?>

<div class="admin-content">

    <div class="page-head">
        <div>
            <h2>Employee <span>QR Codes</span></h2>
            <p class="lead">
                First scan = <strong>Time In</strong> · Second scan = <strong>Time Out</strong>.
                Click <em>Details</em> to view the full profile.
            </p>
        </div>

        <!-- ✅ Header actions: Scanner + Today's Attendance -->
        <div class="head-actions">
            <a class="btn btn-ghost" href="today.php">
                <i class="bi bi-list-check"></i>
                <span>Today's Attendance</span>
            </a>
            <a class="btn btn-primary" href="attendance.php">
                <i class="bi bi-camera-video-fill"></i>
                <span>Open Scanner</span>
            </a>
        </div>
    </div>

    <div class="toolbar">
        <div class="search">
            <input type="text" id="searchInput"
                   placeholder="Search by name, code, or department…"
                   autocomplete="off">
            <i class="bi bi-search"></i>
        </div>
    </div>

    <div class="table-card">
        <div class="table-head">
            <span class="head-count">
                <i class="bi bi-people-fill"></i>
                <strong id="empCount"><?= count($users) ?></strong>
                active employee<?= count($users) === 1 ? '' : 's' ?>
            </span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Code</th>
                    <th>Department</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody id="empTableBody">
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="4" class="empty">
                        <i class="bi bi-inbox"></i>
                        No active employees found.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $u):
                    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: $u['username'];
                    $init = strtoupper(mb_substr($u['first_name'] ?? $u['username'], 0, 1)
                                      . mb_substr($u['last_name'] ?? '', 0, 1));
                    if ($init === '') $init = strtoupper(mb_substr($u['username'], 0, 1));
                ?>
                    <tr data-search="<?= e(strtolower($name . ' ' . $u['username'] . ' ' . ($u['employee_code'] ?? '') . ' ' . ($u['department'] ?? ''))) ?>">
                        <td>
                            <div class="emp-cell">
                                <div class="avatar"><?= e($init) ?></div>
                                <div>
                                    <div class="emp-name"><?= e($name) ?></div>
                                    <div class="emp-user">
                                        <i class="bi bi-at"></i><?= e($u['username']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($u['employee_code'])): ?>
                                <span class="code-chip">
                                    <i class="bi bi-upc-scan"></i><?= e($u['employee_code']) ?>
                                </span>
                            <?php else: ?>
                                <span class="muted-dash">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($u['department'])): ?>
                                <span class="dept-chip">
                                    <i class="bi bi-diagram-3-fill"></i><?= e($u['department']) ?>
                                </span>
                            <?php else: ?>
                                <span class="muted-dash">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button class="btn btn-ghost btn-sm js-view-details" data-id="<?= (int)$u['id'] ?>">
                                    <i class="bi bi-person-lines-fill"></i>
                                    <span>Details</span>
                                </button>
                                <?php if (!empty($u['employee_code'])): ?>
                                    <a class="btn btn-primary btn-sm"
                                       href="generate_qr.php?id=<?= (int)$u['id'] ?>"
                                       target="_blank" rel="noopener">
                                        <i class="bi bi-qr-code"></i>
                                        <span>View QR</span>
                                    </a>
                                <?php else: ?>
                                    <span class="no-code-note">
                                        <i class="bi bi-exclamation-circle"></i>No code
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- ===================== Details Modal ===================== -->
<div class="modal-backdrop" id="detailModal">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
        <div class="modal-head">
            <h3 id="modalTitle">
                <i class="bi bi-person-vcard-fill"></i>
                Employee Details
            </h3>
            <button class="modal-close" id="modalClose" aria-label="Close">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="modal-body">
            <div class="loader" id="modalLoader">
                <div class="spinner"></div>
                <div>LOADING…</div>
            </div>

            <div id="modalContent" style="display:none;">
                <div class="profile-top">
                    <div class="profile-avatar" id="mAvatar">—</div>
                    <div>
                        <p class="profile-name" id="mName">—</p>
                        <p class="profile-sub"  id="mSub">—</p>
                        <div style="margin-top:8px; display:flex; gap:6px; flex-wrap:wrap;">
                            <span class="badge badge-active" id="mStatus">
                                <i class="bi bi-check-circle-fill"></i>Active
                            </span>
                            <span class="role-chip" id="mRole">
                                <i class="bi bi-shield-lock-fill"></i>user
                            </span>
                        </div>
                    </div>
                </div>

                <div class="section-title">
                    <i class="bi bi-telephone-fill"></i>Contact
                </div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Email</div><div class="detail-value" id="mEmail">—</div></div>
                    <div class="detail-item"><div class="detail-label">Contact Number</div><div class="detail-value" id="mContact">—</div></div>
                </div>

                <div class="section-title">
                    <i class="bi bi-briefcase-fill"></i>Employment
                </div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">Employee Code</div><div class="detail-value" id="mCode">—</div></div>
                    <div class="detail-item"><div class="detail-label">Position</div><div class="detail-value" id="mPosition">—</div></div>
                    <div class="detail-item"><div class="detail-label">Department</div><div class="detail-value" id="mDept">—</div></div>
                    <div class="detail-item"><div class="detail-label">Hire Date</div><div class="detail-value" id="mHire">—</div></div>
                    <div class="detail-item"><div class="detail-label">Basic Salary</div><div class="detail-value" id="mSalary">—</div></div>
                    <div class="detail-item"><div class="detail-label">Birthdate</div><div class="detail-value" id="mBirth">—</div></div>
                </div>

                <div class="section-title">
                    <i class="bi bi-card-checklist"></i>Government IDs
                </div>
                <div class="detail-grid">
                    <div class="detail-item"><div class="detail-label">SSS No.</div><div class="detail-value" id="mSss">—</div></div>
                    <div class="detail-item"><div class="detail-label">PhilHealth No.</div><div class="detail-value" id="mPhil">—</div></div>
                    <div class="detail-item"><div class="detail-label">Pag-IBIG No.</div><div class="detail-value" id="mPag">—</div></div>
                </div>
            </div>

            <div id="modalError" style="display:none; padding:20px; color:#ff8f9a; text-align:center; letter-spacing:1px;"></div>
        </div>

        <div class="modal-foot">
            <a class="btn btn-primary" id="mQrLink" href="#" target="_blank" rel="noopener" style="display:none;">
                <i class="bi bi-qr-code"></i>
                <span>View QR</span>
            </a>
            <button class="btn btn-ghost" id="modalCloseBtn">
                <i class="bi bi-x-lg"></i>
                <span>Close</span>
            </button>
        </div>
    </div>
</div>

<script>
// ---------- Live search ----------
const searchInput = document.getElementById('searchInput');
const rows        = Array.from(document.querySelectorAll('#empTableBody tr[data-search]'));
const empCount    = document.getElementById('empCount');

searchInput.addEventListener('input', () => {
    const q = searchInput.value.trim().toLowerCase();
    let visible = 0;
    rows.forEach(r => {
        const hit = !q || r.dataset.search.includes(q);
        r.style.display = hit ? '' : 'none';
        if (hit) visible++;
    });
    empCount.textContent = visible;
});

// ---------- Details modal ----------
const modal        = document.getElementById('detailModal');
const modalClose   = document.getElementById('modalClose');
const modalCloseBt = document.getElementById('modalCloseBtn');
const modalLoader  = document.getElementById('modalLoader');
const modalContent = document.getElementById('modalContent');
const modalError   = document.getElementById('modalError');
const mQrLink      = document.getElementById('mQrLink');

const STATUS_ICONS = {
    'Active':   'bi-check-circle-fill',
    'Inactive': 'bi-dash-circle-fill',
    'Resigned': 'bi-slash-circle-fill'
};

function openModal()  { modal.classList.add('show');  }
function closeModal() {
    modal.classList.remove('show');
    modalContent.style.display = 'none';
    modalLoader.classList.remove('show');
    modalError.style.display = 'none';
    mQrLink.style.display = 'none';
}

modalClose.addEventListener('click', closeModal);
modalCloseBt.addEventListener('click', closeModal);
modal.addEventListener('click', (e) => { if (e.target === modal) closeModal(); });
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

function fmt(v, fallback = '—') {
    return (v === null || v === undefined || v === '') ? fallback : String(v);
}
function fmtMoney(v) {
    if (v === null || v === undefined || v === '') return '—';
    return '₱ ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}
function fmtDate(v) {
    if (!v) return '—';
    const d = new Date(v);
    return isNaN(d) ? v : d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'2-digit' });
}

document.querySelectorAll('.js-view-details').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        if (!id) return;

        modalError.style.display = 'none';
        modalContent.style.display = 'none';
        modalLoader.classList.add('show');
        openModal();

        fetch('get_employee.php?id=' + encodeURIComponent(id), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            modalLoader.classList.remove('show');
            if (!data || data.status !== 'ok' || !data.user) {
                modalError.textContent = (data && data.message) || 'Failed to load employee details.';
                modalError.style.display = 'block';
                return;
            }

            const u = data.user;
            const name = [u.first_name, u.last_name].filter(Boolean).join(' ') || u.username;
            const initial = ((u.first_name || u.username || '?').charAt(0) + (u.last_name || '').charAt(0)).toUpperCase() || '?';

            document.getElementById('mAvatar').textContent = initial;
            document.getElementById('mName').textContent   = name;
            document.getElementById('mSub').textContent    = '@' + fmt(u.username) +
                (u.position ? ' · ' + u.position : '');

            const statusEl  = document.getElementById('mStatus');
            const statusTxt = fmt(u.status, 'Active');
            const statusIco = STATUS_ICONS[statusTxt] || 'bi-circle-fill';
            statusEl.innerHTML = '<i class="bi ' + statusIco + '"></i>' + statusTxt;
            statusEl.className = 'badge ' +
                (statusTxt === 'Active'   ? 'badge-active'
                : statusTxt === 'Inactive'? 'badge-inactive'
                : 'badge-resigned');

            document.getElementById('mRole').innerHTML =
                '<i class="bi bi-shield-lock-fill"></i>' + fmt(u.role);

            document.getElementById('mEmail').textContent    = fmt(u.email);
            document.getElementById('mContact').textContent  = fmt(u.contact_number);
            document.getElementById('mCode').textContent     = fmt(u.employee_code);
            document.getElementById('mPosition').textContent = fmt(u.position);
            document.getElementById('mDept').textContent     = fmt(u.department);
            document.getElementById('mHire').textContent     = fmtDate(u.hire_date);
            document.getElementById('mSalary').textContent   = fmtMoney(u.basic_salary);
            document.getElementById('mBirth').textContent    = fmtDate(u.birthdate);
            document.getElementById('mSss').textContent      = fmt(u.sss_no);
            document.getElementById('mPhil').textContent     = fmt(u.philhealth_no);
            document.getElementById('mPag').textContent      = fmt(u.pagibig_no);

            if (u.employee_code) {
                mQrLink.href = 'generate_qr.php?id=' + encodeURIComponent(u.id);
                mQrLink.style.display = '';
            } else {
                mQrLink.style.display = 'none';
            }

            modalContent.style.display = '';
        })
        .catch(err => {
            modalLoader.classList.remove('show');
            modalError.textContent = 'Network error: ' + err;
            modalError.style.display = 'block';
        });
    });
});
</script>

</body>
</html>