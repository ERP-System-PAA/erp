<?php
require_once __DIR__ . '/../include/config.php';

// Already logged in? Route by role
if (is_logged_in()) {
    if ($_SESSION['role'] === 'super_admin' || $_SESSION['role'] === 'admin') {
        header("Location: ../admin/admin_dashboard.php");
        exit;
    }
}

// --- Detect bootstrap (no super admin yet) ---
$hasSuperAdmin = false;
try {
    $hasSuperAdmin = (bool) $pdo
        ->query("SELECT id FROM users WHERE role = 'super_admin' LIMIT 1")
        ->fetch();
} catch (Throwable $e) {
    $hasSuperAdmin = false;
}
$showRegister = !$hasSuperAdmin;

// --- CSRF token ---
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$username_value = '';

// Success message after bootstrap registration
if (isset($_GET['created']) && $_GET['created'] === '1') {
    $success = "Super Admin account created. Please sign in.";
}

// Info message when redirected from admin_register.php
if (isset($_GET['reason']) && $_GET['reason'] === 'login_required') {
    $error = "Please sign in with a Super Admin account to register new admins.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Session expired. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $username_value = $username; // keep raw, escape on output

        if ($username === '' || $password === '') {
            $error = "Please enter both credentials.";
        } else {
            $stmt = $pdo->prepare(
                "SELECT * FROM users 
                 WHERE (username = ? OR email = ?) 
                   AND role IN ('super_admin','admin') 
                 LIMIT 1"
            );
            $stmt->execute([$username, $username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);

                $_SESSION['user_id']  = $admin['id'];
                $_SESSION['username'] = $admin['username'];
                $_SESSION['email']    = $admin['email'];
                $_SESSION['role']     = $admin['role'];

                header("Location: ../admin/admin_dashboard.php");
                exit;
            } else {
                $error = "Invalid credentials or unauthorized access.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — ERP</title>
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
            width:100%; max-width:430px;
            border-radius:14px;
            border:1px solid rgba(232,140,46,.15);
            border-top:4px solid var(--amber);
            box-shadow:0 20px 50px rgba(0,0,0,.65);
            overflow:hidden; position:relative;
            animation:cardIn .5s ease;
        }
        @keyframes cardIn { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
        .admin-card::before {
            content:''; position:absolute; top:0; right:0; width:120px; height:120px;
            background:linear-gradient(135deg, transparent 50%, rgba(232,140,46,.08) 50%);
            pointer-events:none;
        }
        .admin-header { padding:36px 24px 24px; text-align:center; position:relative; }
        .admin-header::after {
            content:''; position:absolute; bottom:0; left:12%; width:76%; height:1px;
            background:linear-gradient(90deg, transparent, var(--amber) 50%, transparent);
            opacity:.6;
        }
        .crest {
            width:84px; height:84px; border-radius:50%;
            margin:0 auto 18px; display:flex; justify-content:center; align-items:center;
            border:2px solid var(--amber);
            box-shadow:0 0 0 6px rgba(232,140,46,.06), 0 8px 24px rgba(0,0,0,.5);
            overflow:hidden; background:var(--maroon-deep);
        }
        .crest img { width:100%; height:100%; object-fit:cover; }
        .admin-header h1 {
            font-size:22px; font-weight:800; letter-spacing:3px;
            text-transform:uppercase; color:var(--off-white); margin-bottom:6px;
        }
        .admin-header h1 span { color:var(--amber); }
        .admin-header p {
            font-size:11px; font-weight:600; letter-spacing:2px;
            text-transform:uppercase; color:var(--text-muted);
        }
        .admin-form { padding:30px 34px 36px; }
        .form-group { margin-bottom:20px; position:relative; }
        .form-group label {
            display:block; margin-bottom:8px; font-size:11px; font-weight:800;
            letter-spacing:1.5px; text-transform:uppercase; color:var(--amber);
        }
        .form-group input {
            width:100%; padding:13px 15px 13px 42px;
            background-color:rgba(0,0,0,.35);
            border:1px solid rgba(232,140,46,.2); border-radius:8px;
            font-size:15px; font-weight:600; color:var(--off-white);
            letter-spacing:.5px; transition:all .25s ease;
        }
        .form-group input::placeholder { color:rgba(154,133,133,.6); font-weight:500; }
        .form-group input:focus {
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
            transform:translateY(-1px);
        }
        .alert {
            padding:12px 15px; border-radius:8px; font-size:13px; font-weight:600;
            margin-bottom:20px; display:flex; align-items:center; gap:10px;
            animation:slideDown .35s ease;
        }
        .alert-danger { background:rgba(255,82,82,.1); color:var(--error-red); border-left:3px solid var(--error-red); }
        .alert-success { background:rgba(76,175,80,.1); color:#6ddc71; border-left:3px solid var(--green); }
        .alert svg { width:18px; height:18px; flex-shrink:0; }
        @keyframes slideDown { from{opacity:0;transform:translateY(-8px)} to{opacity:1;transform:translateY(0)} }

        .divider {
            display:flex; align-items:center; text-align:center;
            margin:22px 0 18px; color:var(--text-muted); font-size:10px;
            font-weight:800; letter-spacing:3px; text-transform:uppercase;
        }
        .divider::before, .divider::after {
            content:''; flex:1; height:1px;
            background:linear-gradient(90deg, transparent, rgba(232,140,46,.35), transparent);
        }
        .divider span { padding:0 14px; color:var(--amber); opacity:.75; }

        .btn-register {
            display:flex; justify-content:center; align-items:center; gap:8px;
            width:100%; padding:13px 15px;
            background:transparent; color:var(--amber);
            border:1px solid var(--amber); border-radius:8px;
            font-size:12px; font-weight:800; letter-spacing:3px; text-transform:uppercase;
            text-decoration:none; cursor:pointer; transition:all .25s ease;
        }
        .btn-register svg { width:16px; height:16px; fill:currentColor; transition:transform .25s ease; }
        .btn-register:hover {
            background:var(--amber); color:var(--maroon-deep);
            transform:translateY(-1px);
        }
        .btn-register:hover svg { transform:translateX(3px); }
        .bootstrap-note {
            text-align:center; font-size:11px; font-weight:600;
            color:var(--text-muted); margin-top:12px; line-height:1.5;
        }
        .bootstrap-note strong { color:var(--amber); }

        .admin-footer {
            text-align:center; padding-top:20px; margin-top:6px;
            border-top:1px solid rgba(232,140,46,.12);
            font-size:12px; font-weight:600; color:var(--text-muted);
        }
        .admin-footer a {
            color:var(--amber); text-decoration:none; font-weight:800; transition:color .2s;
        }
        .admin-footer a:hover { color:var(--amber-bright); text-decoration:underline; }

        .access-badge {
            display:inline-block; padding:4px 12px;
            background:rgba(232,140,46,.1);
            border:1px solid rgba(232,140,46,.35);
            border-radius:20px; font-size:10px; font-weight:800;
            letter-spacing:2px; text-transform:uppercase;
            color:var(--amber); margin-top:8px;
        }
        @media (max-width:480px) {
            body { padding:0; }
            .admin-card {
                border-radius:0; min-height:100vh; max-width:100%;
                display:flex; flex-direction:column; justify-content:center;
                border-top-width:6px;
            }
        }
    </style>
</head>
<body>

<div class="admin-card">
    <div class="admin-header">
        <div class="crest"><img src="logo.jpg" alt="Logo"></div>
        <h1>SUPER <span>ADMIN</span></h1>
        <p>Restricted Access</p>
        <div class="access-badge">Secure Portal</div>
    </div>

    <div class="admin-form">

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required
                       value="<?= htmlspecialchars($username_value, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="Enter credentials">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required placeholder="Enter password">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                </svg>
            </div>

            <button type="submit" class="btn-admin">Sign In</button>
        </form>

        <?php if ($showRegister): ?>
            <div class="divider"><span>OR</span></div>

            <a href="admin_register.php" class="btn-register">
                <svg viewBox="0 0 24 24">
                    <path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                Create Account
            </a>

            <p class="bootstrap-note">
                No <strong>Super Admin</strong> exists yet.<br>
                First account created will be the Super Admin.
            </p>
        <?php endif; ?>

        <div class="admin-footer">
            <a href="admin_forget_pass.php">Forgot Password?</a>
        </div>
    </div>
</div>

</body>
</html>