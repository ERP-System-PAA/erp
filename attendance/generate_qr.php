<?php
// ============================================================
// Employee QR Code Generator
// Location: /erp/attendance/generate_qr.php
// Usage:    generate_qr.php?id=3
// ============================================================

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Must be logged in
if (!is_logged_in()) {
    header("Location: ../auth/admin_login.php");
    exit;
}

// Only admin or super_admin can view
if (!in_array($_SESSION['role'], ['admin', 'super_admin'], true)) {
    header("Location: ../auth/admin_login.php");
    exit;
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// ---------- Load the employee ----------
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT id, username, first_name, last_name,
                              employee_code, department, position
                       FROM users
                       WHERE id = ?");
$stmt->execute([$id]);
$u = $stmt->fetch();

if (!$u) {
    http_response_code(404);
    die('Employee not found.');
}

if (empty($u['employee_code'])) {
    die('This employee has no employee_code assigned. Please set one in the user record first.');
}

$name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
if ($name === '') {
    $name = $u['username'];
}

// ---------- Generate the QR ----------
$options = new QROptions([
    'outputType' => QRCode::OUTPUT_MARKUP_SVG,
    'eccLevel'   => QRCode::ECC_L,
    'scale'      => 8,
]);

$qrSvg = (new QRCode($options))->render($u['employee_code']);

$username = htmlspecialchars($_SESSION['username']);
$role     = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee QR — <?= e($name) ?> — ERP</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <style>
        /* =========================================================
           PAGE HEADER
           ========================================================= */
        .qr-page-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 14px;
            margin-bottom: 25px;
        }
        .qr-page-head .lead { margin: 0; }

        /* =========================================================
           BADGE CARD
           ========================================================= */
        .badge-stage {
            display: flex;
            justify-content: center;
            padding: 10px 0 20px;
        }

        .badge {
            width: 360px;
            background: linear-gradient(180deg, #ffffff 0%, #faf5f0 100%);
            border-radius: 16px;
            overflow: hidden;
            box-shadow:
                0 20px 50px rgba(0, 0, 0, 0.45),
                0 0 0 1px rgba(232, 140, 46, 0.25);
            color: #2b0e0e;
            position: relative;
        }

        /* Top brand bar */
        .badge-header {
            background: linear-gradient(135deg, #2b0e0e 0%, #5a1f1e 100%);
            color: #fff;
            padding: 14px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 3px solid #E88C2E;
        }
        .badge-header .brand {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }
        .badge-header .brand::before {
            content: "";
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #E88C2E;
            box-shadow: 0 0 10px rgba(232, 140, 46, 0.8);
        }
        .badge-header .badge-type {
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
            color: #E88C2E;
            text-transform: uppercase;
        }

        /* Employee identity block */
        .badge-identity {
            padding: 22px 22px 10px;
            text-align: center;
        }
        .badge-avatar {
            width: 66px;
            height: 66px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E88C2E, #ffa54a);
            color: #2b0e0e;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            margin: 0 auto 14px;
            box-shadow: 0 8px 20px rgba(232, 140, 46, 0.4);
            letter-spacing: 1px;
        }
        .badge-name {
            font-size: 20px;
            font-weight: 800;
            margin: 0 0 4px;
            color: #2b0e0e;
            letter-spacing: .3px;
            line-height: 1.2;
        }
        .badge-meta {
            font-size: 12px;
            color: #7a5a5a;
            letter-spacing: .5px;
            margin: 0;
            text-transform: uppercase;
            font-weight: 600;
        }
        .badge-code {
            display: inline-block;
            margin-top: 10px;
            padding: 5px 14px;
            font-family: ui-monospace, Menlo, Consolas, monospace;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            background: rgba(232, 140, 46, 0.14);
            color: #b35f0a;
            border: 1px solid rgba(232, 140, 46, 0.35);
            border-radius: 999px;
        }

        /* QR block */
        .badge-qr {
            padding: 18px 22px 8px;
            display: flex;
            justify-content: center;
        }
        .badge-qr-inner {
            background: #fff;
            padding: 14px;
            border-radius: 12px;
            box-shadow: 0 4px 14px rgba(0,0,0,0.08);
            border: 1px solid rgba(232, 140, 46, 0.2);
        }
        .badge-qr img {
            display: block;
            width: 220px;
            height: 220px;
        }

        /* Footer */
        .badge-footer {
            padding: 10px 22px 18px;
            text-align: center;
        }
        .badge-footer .hint {
            font-size: 10px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: #a08484;
            font-weight: 700;
        }
        .badge-footer .hint::before,
        .badge-footer .hint::after {
            content: "·";
            margin: 0 8px;
            color: #E88C2E;
        }

        /* =========================================================
           ACTION BUTTONS
           ========================================================= */
        .qr-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 26px;
            flex-wrap: wrap;
        }
        .qr-actions .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            border-radius: 6px;
            border: 1px solid transparent;
            cursor: pointer;
            text-decoration: none;
            font-family: inherit;
            transition: background .18s ease, transform .1s ease, box-shadow .18s ease,
                        border-color .18s ease, color .18s ease;
        }
        .qr-actions .btn:active { transform: translateY(1px); }

        .qr-actions .btn-primary {
            background: #E88C2E;
            color: #2b0e0e;
        }
        .qr-actions .btn-primary:hover {
            background: #ffa54a;
            box-shadow: 0 6px 18px rgba(232,140,46,0.28);
        }

        .qr-actions .btn-ghost {
            background: transparent;
            color: #f0e6e6;
            border: 1px solid rgba(232, 140, 46, 0.35);
        }
        .qr-actions .btn-ghost:hover {
            background: rgba(232, 140, 46, 0.12);
            border-color: #E88C2E;
            color: #E88C2E;
        }

        /* =========================================================
           PRINT STYLES
           ========================================================= */
        @media print {
            /* Reset page */
            @page { margin: 12mm; }

            html, body {
                background: #fff !important;
                color: #000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Hide chrome */
            #adminSidebar,
            .qr-page-head,
            .qr-actions {
                display: none !important;
            }

            /* Remove sidebar offset */
            .admin-content {
                margin-left: 0 !important;
                padding: 0 !important;
                max-width: none !important;
            }

            /* Badge takes full attention */
            .badge-stage { padding: 0; }

            .badge {
                width: 100%;
                max-width: 360px;
                margin: 0 auto;
                box-shadow: none !important;
                border: 1px solid #ccc;
                border-radius: 10px;
                page-break-inside: avoid;
                background: #fff !important;
            }

            .badge-header {
                background: #2b0e0e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .badge-avatar {
                background: #E88C2E !important;
                color: #2b0e0e !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

<?php require_once __DIR__ . '/../component/admin_nav.php'; ?>

<div class="admin-content">

    <div class="qr-page-head">
        <div>
            <h2>Employee <span>QR Badge</span></h2>
            <p class="lead">Printable badge for attendance scanning</p>
        </div>
    </div>

    <?php
        // Build initials for the badge avatar
        $init = strtoupper(mb_substr($u['first_name'] ?? $u['username'], 0, 1)
                         . mb_substr($u['last_name']  ?? '', 0, 1));
        if ($init === '') $init = strtoupper(mb_substr($u['username'], 0, 1));
    ?>

    <div class="badge-stage">
        <div class="badge">

            <!-- Brand bar -->
            <div class="badge-header">
                <span class="brand">ERP Attendance</span>
                <span class="badge-type">Employee Badge</span>
            </div>

            <!-- Identity -->
            <div class="badge-identity">
                <div class="badge-avatar"><?= e($init) ?></div>
                <h1 class="badge-name"><?= e($name) ?></h1>
                <?php if (!empty($u['department']) || !empty($u['position'])): ?>
                    <p class="badge-meta">
                        <?= e($u['department'] ?? '') ?>
                        <?php if (!empty($u['position'])): ?>
                            <?= !empty($u['department']) ? ' · ' : '' ?><?= e($u['position']) ?>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <span class="badge-code"><?= e($u['employee_code']) ?></span>
            </div>

            <!-- QR -->
            <div class="badge-qr">
                <div class="badge-qr-inner">
                    <img src="<?= $qrSvg ?>" alt="QR Code for <?= e($u['employee_code']) ?>">
                </div>
            </div>

            <!-- Footer -->
            <div class="badge-footer">
                <div class="hint">PAA Employee</div>
            </div>
        </div>
    </div>

    <div class="qr-actions">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Badge</button>
        <a class="btn btn-ghost" href="list.php">← Back to list</a>
    </div>

</div>

</body>
</html>