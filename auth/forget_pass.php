<?php
require_once __DIR__ . '/../include/config.php';

// If already logged in, go to index
if (is_logged_in()) {
    header("Location: ../index.php");
    exit;
}

$message = '';
$error   = '';
$email_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $email_value = htmlspecialchars($email);

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Always show the same message (avoid user enumeration)
        $message = "If that email exists, a reset link has been sent.";

        // TODO: Generate token + send email in the future.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ERP System</title>
    <style>
        :root {
            --maroon: #783332;
            --maroon-dark: #5a2524;
            --amber: #E88C2E;
            --green: #2B8F38;
            --white: #FFFFFF;
            --gray-light: #f4f4f4;
            --gray-border: #ccc;
            --text-dark: #333333;
            --text-muted: #666666;
            --error-red: #d32f2f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--maroon);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .login-container {
            background-color: var(--white);
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            overflow: hidden;
            border-top: 6px solid var(--amber);
            position: relative;
            z-index: 10;
        }

        .login-header {
            background-color: var(--white);
            padding: 30px 20px 20px 20px;
            text-align: center;
            color: var(--maroon);
            position: relative;
        }

        .login-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 10%;
            width: 80%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--amber) 50%, transparent 100%);
        }

        .login-header h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 5px;
            color: var(--maroon);
        }

        .login-header p {
            font-size: 13px;
            color: var(--text-muted);
            font-style: italic;
        }

        .logo-placeholder {
            width: 80px;
            height: 80px;
            background-color: var(--white);
            border-radius: 50%;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 3px solid var(--maroon);
            box-shadow: 0 4px 10px rgba(120, 51, 50, 0.2);
            overflow: hidden;
        }

        .logo-placeholder img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .login-form {
            padding: 30px 35px 40px 35px;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 700;
            color: var(--maroon);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px 12px 40px;
            border: 2px solid var(--gray-border);
            border-radius: 6px;
            font-size: 15px;
            color: var(--text-dark);
            transition: all 0.3s ease;
            background-color: var(--gray-light);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--amber);
            background-color: var(--white);
            box-shadow: 0 0 0 3px rgba(232, 140, 46, 0.2);
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 38px;
            width: 18px;
            height: 18px;
            fill: var(--text-muted);
            pointer-events: none;
            transition: fill 0.3s ease;
        }

        .form-group input:focus + .input-icon {
            fill: var(--amber);
        }

        .btn-primary {
            width: 100%;
            padding: 14px;
            background-color: var(--maroon);
            color: var(--white);
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.1s ease;
            margin-top: 10px;
            letter-spacing: 0.5px;
        }

        .btn-primary:hover {
            background-color: var(--maroon-dark);
        }

        .btn-primary:active {
            transform: scale(0.98);
        }

        .login-footer {
            text-align: center;
            padding-top: 20px;
            border-top: 1px solid #eee;
            margin-top: 10px;
            font-size: 13px;
            color: var(--text-muted);
        }

        .login-footer a {
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .login-footer a:hover {
            color: var(--maroon);
            text-decoration: underline;
        }

        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .alert-danger {
            background-color: #fdecea;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        .alert-info {
            background-color: #fff5e6;
            color: var(--amber-hover);
            border-left: 4px solid var(--amber);
        }

        .alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 480px) {
            .login-container {
                border-radius: 0;
                box-shadow: none;
                max-width: 100%;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                justify-content: center;
                border-top: none;
            }

            body {
                padding: 0;
                background-color: var(--white);
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <div class="logo-placeholder">
            <img src="logo.jpg" alt="Logo">
        </div>
        <h1>FORGOT PASSWORD</h1>
        <p>Enter your email to reset</p>
    </div>

    <div class="login-form">

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
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required
                       value="<?= $email_value ?>"
                       placeholder="you@example.com">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>

            <button type="submit" class="btn-primary">SEND RESET LINK</button>
        </form>

        <div class="login-footer">
            Remember your password? <a href="login.php">Back to Login</a>
        </div>
    </div>
</div>

</body>
</html>