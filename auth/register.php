<?php
require_once __DIR__ . '/../include/config.php';

// If already logged in, go to index
if (is_logged_in()) {
    header("Location: ../index.php");
    exit;
}

$errors = [];
$old = ['username' => '', 'email' => '', 'contact_number' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $contact  = trim($_POST['contact_number'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    $old = [
        'username'       => htmlspecialchars($username),
        'email'          => htmlspecialchars($email),
        'contact_number' => htmlspecialchars($contact),
    ];

    // Validation
    if ($username === '' || $email === '' || $password === '') {
        $errors[] = "Please fill in all required fields.";
    }
    if ($username !== '' && strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters.";
    }
    if ($username !== '' && !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username may only contain letters, numbers, and underscores.";
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }
    if ($password !== '' && strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters.";
    }
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // Check duplicates
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or email already taken.";
        }
    }

    // Insert user
    if (empty($errors)) {
        $nextId = (int) $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM users")->fetchColumn();
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO users (id, username, password, email, contact_number, role)
            VALUES (?, ?, ?, ?, ?, 'user')
        ");
        $stmt->execute([$nextId, $username, $hashed, $email, $contact ?: null]);

        header("Location: login.php?registered=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - ERP System</title>
    <style>
        /* =========================================
           COLOR PALETTE VARIABLES (From Logo)
           ========================================= */
        :root {
            --maroon: #783332;
            --maroon-dark: #5a2524;
            --amber: #E88C2E;
            --amber-hover: #d17a22;
            --green: #2B8F38;
            --white: #FFFFFF;
            --black: #1A1A1A;
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

        /* =========================================
           CARD CONTAINER
           ========================================= */
        .login-container {
            background-color: var(--white);
            width: 100%;
            max-width: 480px;
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

        /* =========================================
           FORM STYLES
           ========================================= */
        .login-form {
            padding: 30px 35px 40px 35px;
        }

        .form-group {
            margin-bottom: 18px;
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

        .form-group label .optional {
            color: var(--text-muted);
            font-weight: 500;
            text-transform: none;
            font-style: italic;
            letter-spacing: 0;
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

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 38px;
            width: 18px;
            height: 18px;
            fill: var(--text-muted);
            cursor: pointer;
            transition: fill 0.3s ease;
        }

        .toggle-password:hover {
            fill: var(--maroon);
        }

        /* =========================================
           BUTTONS & LINKS
           ========================================= */
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

        /* =========================================
           FOOTER / SIGN IN PROMPT
           ========================================= */
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

        /* =========================================
           ALERT MESSAGES
           ========================================= */
        .alert {
            padding: 12px 15px;
            border-radius: 6px;
            font-size: 13px;
            margin-bottom: 20px;
            font-weight: 500;
            display: flex;
            align-items: flex-start;
            gap: 10px;
            animation: slideDown 0.3s ease;
        }

        .alert-danger {
            background-color: #fdecea;
            color: var(--error-red);
            border-left: 4px solid var(--error-red);
        }

        .alert svg {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .alert ul {
            margin: 0;
            padding-left: 16px;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* =========================================
           RESPONSIVE
           ========================================= */
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
        <h1>CREATE ACCOUNT</h1>
        <p>Sign up to get started</p>
    </div>

    <div class="login-form">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?= $old['username'] ?>"
                       placeholder="Choose a username">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= $old['email'] ?>"
                       placeholder="you@example.com">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
            </div>

            <!-- Contact (optional) -->
            <div class="form-group">
                <label for="contact_number">Contact Number <span class="optional">(optional)</span></label>
                <input type="text" id="contact_number" name="contact_number"
                       value="<?= $old['contact_number'] ?>"
                       placeholder="09171234567">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M6.62 10.79c1.44 2.83 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/>
                </svg>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="At least 6 characters">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                </svg>
                <svg class="toggle-password" data-target="password" viewBox="0 0 24 24">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" required
                       placeholder="Re-enter your password">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                </svg>
                <svg class="toggle-password" data-target="confirm_password" viewBox="0 0 24 24">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </div>

            <button type="submit" class="btn-primary">SIGN UP</button>
        </form>

        <div class="login-footer">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>
</div>

<script>
    // Password show / hide toggle (works for multiple fields)
    document.querySelectorAll('.toggle-password').forEach(function (icon) {
        icon.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const isHidden = input.getAttribute('type') === 'password';

            input.setAttribute('type', isHidden ? 'text' : 'password');
            this.style.fill = isHidden ? 'var(--maroon)' : 'var(--text-muted)';
        });
    });
</script>

</body>
</html>