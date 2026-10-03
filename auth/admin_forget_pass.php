<?php
require_once __DIR__ . '/../include/config.php';

$message = '';
$error   = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $email_value = htmlspecialchars($email);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $message = "If that admin email exists, a reset link has been sent.";
        // TODO: generate token + send email
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Password Reset — ERP</title>
    <style>
        :root {
            --maroon-deep: #2b0e0e;
            --maroon-card: #3d1414;
            --maroon-dark: #5a1f1e;
            --maroon-mid:  #7a2a28;
            --amber: #E88C2E;
            --amber-bright: #ffa54a;
            --off-white: #f0e6e6;
            --text-muted: #9a8585;
            --error-red: #ff5252;
        }

        * { margin: 0; padding: 0; box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

        body {
            background:
                radial-gradient(circle at 20% 20%, rgba(120, 51, 50, 0.25), transparent 45%),
                radial-gradient(circle at 80% 80%, rgba(232, 140, 46, 0.08), transparent 45%),
                var(--maroon-deep);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .admin-card {
            background-color: var(--maroon-card);
            width: 100%;
            max-width: 430px;
            border-radius: 14px;
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-top: 4px solid var(--amber);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
            overflow: hidden;
            animation: cardIn 0.5s ease;
        }

        @keyframes cardIn {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .admin-header {
            padding: 34px 24px 22px 24px;
            text-align: center;
            position: relative;
        }

        .admin-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 12%;
            width: 76%;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--amber) 50%, transparent);
            opacity: 0.6;
        }

        .admin-header h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            color: var(--off-white);
        }

        .admin-header h1 span { color: var(--amber); }

        .admin-header p {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .admin-form { padding: 30px 34px 36px 34px; }

        .form-group { margin-bottom: 20px; position: relative; }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--amber);
        }

        .form-group input {
            width: 100%;
            padding: 13px 15px 13px 42px;
            background-color: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(232, 140, 46, 0.2);
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            color: var(--off-white);
            letter-spacing: 0.5px;
            transition: all 0.25s ease;
        }

        .form-group input::placeholder {
            color: rgba(154, 133, 133, 0.6);
            font-weight: 500;
            letter-spacing: 0;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--amber);
            background-color: rgba(0, 0, 0, 0.55);
            box-shadow: 0 0 0 3px rgba(232, 140, 46, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 39px;
            width: 18px;
            height: 18px;
            fill: var(--text-muted);
            pointer-events: none;
            transition: fill 0.25s ease;
        }

        .form-group input:focus + .input-icon { fill: var(--amber); }

        .btn-admin {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--maroon-dark) 0%, var(--maroon-deep) 100%);
            color: var(--off-white);
            border: 1px solid var(--amber);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 3px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 10px;
        }

        .btn-admin:hover {
            background: linear-gradient(135deg, var(--maroon-mid) 0%, var(--maroon-dark) 100%);
            color: var(--amber-bright);
        }

        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.35s ease;
        }

        .alert-danger {
            background-color: rgba(255, 82, 82, 0.1);
            color: var(--error-red);
            border-left: 3px solid var(--error-red);
        }

        .alert-info {
            background-color: rgba(232, 140, 46, 0.1);
            color: var(--amber-bright);
            border-left: 3px solid var(--amber);
        }

        .alert svg { width: 18px; height: 18px; flex-shrink: 0; }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .admin-footer {
            text-align: center;
            padding-top: 18px;
            margin-top: 6px;
            border-top: 1px solid rgba(232, 140, 46, 0.12);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .admin-footer a {
            color: var(--amber);
            text-decoration: none;
            font-weight: 800;
        }

        .admin-footer a:hover { color: var(--amber-bright); text-decoration: underline; }
    </style>
</head>
<body>

<div class="admin-card">
    <div class="admin-header">
        <h1>PASSWORD <span>RESET</span></h1>
        <p>Admin Recovery</p>
    </div>

    <div class="admin-form">

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert alert-info">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z"/>
                </svg>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= $email_value ?>"
                       placeholder="admin@example.com">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>

            <button type="submit" class="btn-admin">Send Reset Link</button>
        </form>

        <div class="admin-footer">
            <a href="admin_login.php">← Back to Admin Login</a>
        </div>
    </div>
</div>

</body>
</html>