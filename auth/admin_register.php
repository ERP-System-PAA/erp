<?php
require_once __DIR__ . '/../include/config.php';

// ---------- Already logged in? Route by role ----------
if (is_logged_in()) {
    if (in_array($_SESSION['role'] ?? '', ['super_admin', 'admin'], true)) {
        header("Location: ../admin/admin_dashboard.php");
        exit;
    }
    header("Location: admin_login.php");
    exit;
}

// ---------- CSRF ----------
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---------- Helper: auto-generate employee code ----------
function generate_employee_code(PDO $pdo, string $prefix = 'EMP'): string
{
    $year = date('Y');

    $stmt = $pdo->prepare("
        SELECT MAX(CAST(SUBSTRING_INDEX(employee_code, '-', -1) AS UNSIGNED)) AS max_seq
        FROM users
        WHERE employee_code LIKE :pattern
    ");
    $stmt->execute([':pattern' => $prefix . '-' . $year . '-%']);
    $next = (int)($stmt->fetchColumn() ?: 0) + 1;

    while (true) {
        $code  = sprintf('%s-%s-%04d', $prefix, $year, $next);
        $check = $pdo->prepare('SELECT 1 FROM users WHERE employee_code = ? LIMIT 1');
        $check->execute([$code]);
        if (!$check->fetchColumn()) {
            return $code;
        }
        $next++;
    }
}

// ---------- State ----------
$errors  = [];
$success = '';
$old = [
    'username'   => '',
    'email'      => '',
    'department' => '',
];

$validDepts = ['Procurement','Inventory','Production','Sales','Finance','HR','Admin'];

// ---------- Handle POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors[] = 'Invalid session token. Please refresh the page.';
    }

    // ---------- Collect ----------
    $username   = trim($_POST['username'] ?? '');
    $email      = strtolower(trim($_POST['email'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $department = trim($_POST['department'] ?? '');

    $old = [
        'username'   => $username,
        'email'      => $email,
        'department' => $department,
    ];

    // ---------- Fixed backend values ----------
    // TESTING: every account created here is a SUPER ADMIN so it can
    // pass the role gate in admin_login.php.
    $role   = 'admin';
    $status = 'Active';

    // ---------- Validation ----------
    if ($username === '') {
        $errors[] = 'Username is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,50}$/', $username)) {
        $errors[] = 'Username must be 3–50 characters (letters, numbers, underscore only).';
    }

    if ($email === '') {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@gmail\.com$/', $email)) {
        $errors[] = 'Email must be a @gmail.com address.';
    } elseif (strlen($email) > 100) {
        $errors[] = 'Email is too long (max 100 characters).';
    }

    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if ($department === '') {
        $errors[] = 'Department is required.';
    } elseif (!in_array($department, $validDepts, true)) {
        $errors[] = 'Invalid department selected.';
    }

    // ---------- Uniqueness ----------
    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        if ($stmt->fetch()) $errors[] = 'Username already exists.';

        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'Email already exists.';
    }

    // ---------- Insert ----------
    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                (username, password, email, role, employee_code, department, status)
                VALUES
                (:username, :password, :email, :role, :employee_code, :department, :status)";
            $stmt = $pdo->prepare($sql);

            $employeeCode = '';
            $attempts     = 0;

            while (true) {
                $employeeCode = generate_employee_code($pdo, 'EMP');
                try {
                    $stmt->execute([
                        ':username'      => $username,
                        ':password'      => $hash,
                        ':email'         => $email,
                        ':role'          => $role,
                        ':employee_code' => $employeeCode,
                        ':department'    => $department,
                        ':status'        => $status,
                    ]);
                    break;
                } catch (PDOException $e) {
                    $isCodeClash = $e->getCode() === '23000'
                        && stripos($e->getMessage(), 'uniq_employee_code') !== false;

                    if ($isCodeClash && ++$attempts < 5) {
                        continue;
                    }
                    throw $e;
                }
            }

            // Success → redirect to login
            header("Location: admin_login.php?created=1&reason=account_created");
            exit;

        } catch (PDOException $e) {
            error_log('[admin_register] create: ' . $e->getMessage());
            $errors[] = 'Database error occurred while creating the account. Please try again.';
        }
    }
}

// Preview next employee code (for display only)
$nextEmployeeCode = 'EMP-' . date('Y') . '-0001';
try {
    $nextEmployeeCode = generate_employee_code($pdo, 'EMP');
} catch (Throwable $e) {
    // ignore, use fallback
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account — ERP (Testing)</title>
    <style>
        :root {
            --maroon-deep:#2b0e0e; --maroon-card:#3d1414; --maroon-dark:#5a1f1e; --maroon-mid:#7a2a28;
            --amber:#E88C2E; --amber-bright:#ffa54a;
            --off-white:#f0e6e6; --text-muted:#9a8585;
            --error-red:#ff5252; --green:#4caf50;
        }
        * { margin:0; padding:0; box-sizing:border-box;
            font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
        body {
            background:
                radial-gradient(circle at 20% 20%, rgba(120,51,50,.25), transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(232,140,46,.08), transparent 45%),
                var(--maroon-deep);
            display:flex; justify-content:center; align-items:center;
            min-height:100vh; padding:20px;
        }
        .admin-card {
            background-color: var(--maroon-card);
            width:100%; max-width:480px;
            border-radius:14px;
            border:1px solid rgba(232,140,46,.15);
            border-top:4px solid var(--amber);
            box-shadow:0 20px 50px rgba(0,0,0,.65);
            overflow:hidden; animation:cardIn .5s ease;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .admin-header { padding:34px 24px 22px; text-align:center; position:relative; }
        .admin-header::after {
            content:''; position:absolute; bottom:0; left:12%; width:76%; height:1px;
            background:linear-gradient(90deg, transparent, var(--amber) 50%, transparent);
            opacity:.6;
        }
        .admin-header h1 {
            font-size:20px; font-weight:800; letter-spacing:3px;
            text-transform:uppercase; color:var(--off-white);
        }
        .admin-header h1 span { color:var(--amber); }
        .admin-header p {
            font-size:11px; font-weight:600; letter-spacing:2px;
            text-transform:uppercase; color:var(--text-muted); margin-top:6px;
        }
        .test-banner {
            background:rgba(232,140,46,.12);
            border-top:1px solid rgba(232,140,46,.25);
            border-bottom:1px solid rgba(232,140,46,.25);
            color:var(--amber);
            font-size:11px; font-weight:800; letter-spacing:2px;
            text-transform:uppercase; text-align:center;
            padding:8px 12px;
        }
        .admin-form { padding:28px 34px 36px; }
        .form-group { margin-bottom:18px; position:relative; }
        .form-group label {
            display:block; margin-bottom:8px; font-size:11px; font-weight:800;
            letter-spacing:1.5px; text-transform:uppercase; color:var(--amber);
        }
        .form-group input, .form-group select {
            width:100%; padding:13px 15px 13px 42px;
            background-color:rgba(0,0,0,.35);
            border:1px solid rgba(232,140,46,.2); border-radius:8px;
            font-size:15px; font-weight:600; color:var(--off-white);
            letter-spacing:.5px; transition:all .25s ease;
        }
        .form-group select {
            padding-left:15px; cursor:pointer;
            -webkit-appearance:none; -moz-appearance:none; appearance:none;
            background-image:url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%23E88C2E'%3e%3cpath d='M7 10l5 5 5-5z'/%3e%3c/svg%3e");
            background-repeat:no-repeat; background-position:right 12px center; background-size:20px;
        }
        .form-group select option { background:#3d1414; color:#f0e6e6; }
        .form-group input::placeholder { color:rgba(154,133,133,.6); font-weight:500; }
        .form-group input:focus, .form-group select:focus {
            outline:none; border-color:var(--amber);
            background-color:rgba(0,0,0,.55);
            box-shadow:0 0 0 3px rgba(232,140,46,.15);
        }
        .input-icon {
            position:absolute; left:14px; top:39px; width:18px; height:18px;
            fill:var(--text-muted); pointer-events:none; transition:fill .25s ease;
        }
        .form-group input:focus + .input-icon { fill:var(--amber); }
        .btn-admin {
            width:100%; padding:15px;
            background:linear-gradient(135deg, var(--maroon-dark) 0%, var(--maroon-deep) 100%);
            color:var(--off-white);
            border:1px solid var(--amber); border-radius:8px;
            font-size:14px; font-weight:800; letter-spacing:3px; text-transform:uppercase;
            cursor:pointer; transition:all .25s ease; margin-top:10px;
        }
        .btn-admin:hover {
            background:linear-gradient(135deg, var(--maroon-mid) 0%, var(--maroon-dark) 100%);
            color:var(--amber-bright);
        }
        .alert {
            padding:12px 15px; border-radius:8px; font-size:13px; font-weight:600;
            margin-bottom:20px; display:flex; align-items:flex-start; gap:10px;
            animation:slideDown .35s ease;
        }
        .alert-danger { background:rgba(255,82,82,.1); color:var(--error-red); border-left:3px solid var(--error-red); }
        .alert-success { background:rgba(76,175,80,.1); color:#6ddc71; border-left:3px solid var(--green); }
        .alert svg { width:18px; height:18px; flex-shrink:0; margin-top:1px; }
        .alert ul { margin:0; padding-left:16px; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }
        .admin-footer {
            text-align:center; padding-top:18px; margin-top:6px;
            border-top:1px solid rgba(232,140,46,.12);
            font-size:12px; font-weight:600; color:var(--text-muted);
        }
        .admin-footer a { color:var(--amber); text-decoration:none; font-weight:800; }
        .admin-footer a:hover { color:var(--amber-bright); text-decoration:underline; }
        .hint {
            font-size:11px; color:var(--text-muted); margin-top:6px;
            padding-left:4px; letter-spacing:.3px;
        }
    </style>
</head>
<body>

<div class="admin-card">
    <div class="admin-header">
        <h1>CREATE <span>ACCOUNT</span></h1>
        <p>Employee Registration</p>
    </div>

    <div class="admin-form">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <!-- Username -->
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required maxlength="50"
                       value="<?= e($old['username']) ?>"
                       placeholder="Choose username"
                       pattern="[a-zA-Z0-9_]{3,50}">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>

            <!-- Email (gmail only) -->
            <div class="form-group">
                <label>Email (Gmail)</label>
                <input type="email" name="email" required maxlength="100"
                       value="<?= e($old['email']) ?>"
                       placeholder="yourname@gmail.com"
                       pattern="[a-zA-Z0-9._%+-]+@gmail\.com"
                       title="Must be a valid @gmail.com address">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
                <div class="hint">Only @gmail.com addresses are accepted</div>
            </div>

            <!-- Department -->
            <div class="form-group">
                <label>Department</label>
                <select name="department" required>
                    <option value="" disabled <?= $old['department'] === '' ? 'selected' : '' ?>>— Select department —</option>
                    <?php foreach ($validDepts as $dept): ?>
                        <option value="<?= e($dept) ?>" <?= $old['department'] === $dept ? 'selected' : '' ?>>
                            <?= e($dept) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label>Password (min 8 chars)</label>
                <input type="password" name="password" required minlength="8" maxlength="72"
                       placeholder="At least 8 characters">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required minlength="8" maxlength="72"
                       placeholder="Re-enter password">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
            </div>

            <button type="submit" class="btn-admin">Register</button>
        </form>

        <div class="admin-footer">
            Already have an account? <a href="admin_login.php">Sign In</a>
        </div>
    </div>
</div>

</body>
</html>