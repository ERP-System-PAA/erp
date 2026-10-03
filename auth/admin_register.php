<?php
require_once __DIR__ . '/../include/config.php';

// --- Bootstrap detection ---
$hasSuperAdmin = false;
try {
    $hasSuperAdmin = (bool) $pdo
        ->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1")
        ->fetch();
} catch (Throwable $e) {
    $hasSuperAdmin = false;
}
$isBootstrap = !$hasSuperAdmin;

// --- Access control ---
$isSuperAdminSession = !empty($_SESSION['role']) && $_SESSION['role'] === 'super_admin';

if ($hasSuperAdmin && !$isSuperAdminSession) {
    header("Location: admin_login.php?reason=login_required");
    exit;
}

$errors  = [];
$success = '';
$old = ['username' => '', 'email' => '', 'contact_number' => '', 'role' => 'admin'];

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errors[] = "Session expired. Please try again.";
    }

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $contact  = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = [
        'username'       => $username,
        'email'          => $email,
        'contact_number' => $contact,
        'role'           => 'admin',
    ];

    // --- Role ---
    if ($isBootstrap) {
        $role = 'super_admin';
    } else {
        $role = $_POST['role'] ?? 'admin';
        if (!in_array($role, ['admin', 'super_admin'], true)) {
            $role = 'admin';
        }
        $old['role'] = $role;
    }

    // --- Validation ---
    if ($username === '' || $email === '' || $password === '') {
        $errors[] = "Please fill in all required fields.";
    }
    if (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $username)) {
        $errors[] = "Username may only contain letters, numbers, dots, dashes, underscores.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email address.";
    }
    if ($contact !== '' && !preg_match('/^[0-9+\-\s()]{6,20}$/', $contact)) {
        $errors[] = "Contact number looks invalid.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // --- Uniqueness ---
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        }
    }

    // --- Insert ---
    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        try {
            $pdo->beginTransaction();

            // Detect if `id` column is auto-increment.
            // If it is, omit id from INSERT. If it isn't, use MAX(id)+1.
            $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'id'")->fetch(PDO::FETCH_ASSOC);
            $isAuto = $col && stripos($col['Extra'], 'auto_increment') !== false;

            if ($isAuto) {
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, password, email, contact_number, role)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $username,
                    $hash,
                    $email,
                    $contact !== '' ? $contact : null,
                    $role,
                ]);
            } else {
                $nextId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM users")->fetchColumn();
                $stmt = $pdo->prepare("
                    INSERT INTO users (id, username, password, email, contact_number, role)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $nextId,
                    $username,
                    $hash,
                    $email,
                    $contact !== '' ? $contact : null,
                    $role,
                ]);
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log("admin_register insert failed: " . $e->getMessage());
            $errors[] = "Could not create account. Please try again.";
        }

        if (empty($errors)) {
            if ($isBootstrap) {
                header("Location: admin_login.php?created=1");
                exit;
            }
            $success = "Account created successfully.";
            $old = ['username' => '', 'email' => '', 'contact_number' => '', 'role' => 'admin'];
        }
    }
}

function e(?string $v): string {
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isBootstrap ? 'Initial Setup' : 'Create Admin' ?> — ERP</title>
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
        .alert-info { background:rgba(232,140,46,.1); color:var(--amber-bright); border-left:3px solid var(--amber); }
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
    </style>
</head>
<body>

<div class="admin-card">
    <div class="admin-header">
        <h1><?= $isBootstrap ? 'INITIAL <span>SETUP</span>' : 'CREATE <span>ACCOUNT</span>' ?></h1>
        <p><?= $isBootstrap ? 'Create the first super admin' : 'Super Admin Access' ?></p>
    </div>

    <div class="admin-form">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= e($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <?= e($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($isBootstrap): ?>
            <div class="alert alert-info">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                <span>First-time setup. This account will be created as <strong>Super Admin</strong>.</span>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate autocomplete="off">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">

            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required value="<?= e($old['username']) ?>" placeholder="Choose username">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required value="<?= e($old['email']) ?>" placeholder="admin@example.com">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
            </div>

            <div class="form-group">
                <label>Contact Number</label>
                <input type="text" name="contact_number" value="<?= e($old['contact_number']) ?>" placeholder="Optional">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required placeholder="At least 6 characters">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
            </div>

            <div class="form-group">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" required placeholder="Re-enter password">
                <svg class="input-icon" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/></svg>
            </div>

            <?php if (!$isBootstrap): ?>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <option value="admin"       <?= $old['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="super_admin" <?= $old['role'] === 'super_admin' ? 'selected' : '' ?>>Super Admin</option>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" class="btn-admin">
                <?= $isBootstrap ? 'Create Super Admin' : 'Create Account' ?>
            </button>
        </form>

        <div class="admin-footer">
            <?php if ($isBootstrap): ?>
                Already have an account? <a href="admin_login.php">Sign In</a>
            <?php else: ?>
                <a href="../admin/admin_dashboard.php">← Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>