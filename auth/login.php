<?php
require_once __DIR__ . '/../include/config.php';

// If already logged in, go to index
if (is_logged_in()) {
    header("Location: ../index.php");
    exit;
}

$error = '';
$username_value = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $username_value = htmlspecialchars($username);

    if ($username === '' || $password === '') {
        $error = "Please enter username and password.";
    } else {
        // Find user by username or email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email']    = $user['email'];
            $_SESSION['role']     = $user['role'];

            header("Location: ../index.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ERP System</title>
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

        /* =========================================
           RESET & BASE STYLES
           ========================================= */
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
           LOGIN CARD CONTAINER
           ========================================= */
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

        /* =========================================
           LOGO STYLES (TWO LOGOS)
           ========================================= */
        .logo-row {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .logo-placeholder {
            width: 90px;
            height: 90px;
            background-color: var(--white);
            border-radius: 50%;
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
            object-fit: contain;   /* keeps whole logo visible */
            border-radius: 50%;
            padding: 6px;
            background-color: var(--white);
        }

        /* =========================================
           FORM STYLES
           ========================================= */
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

        /* Input icon styling (SVG Icons) */
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

        /* Password toggle button */
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

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            color: var(--text-muted);
            cursor: pointer;
            font-weight: 500;
        }

        .remember-me input {
            margin-right: 8px;
            width: 16px;
            height: 16px;
            accent-color: var(--maroon);
            cursor: pointer;
        }

        .forgot-password {
            color: var(--amber);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .forgot-password:hover {
            color: var(--maroon);
            text-decoration: underline;
        }

        /* =========================================
           FOOTER / SIGN UP PROMPT
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
           ALERT / ERROR MESSAGES
           ========================================= */
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

        .alert-success {
            background-color: #eaf7ec;
            color: var(--green);
            border-left: 4px solid var(--green);
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

        /* =========================================
           RESPONSIVE DESIGN
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

            .login-header {
                border-radius: 0;
            }

            /* Smaller logos on mobile */
            .logo-placeholder {
                width: 75px;
                height: 75px;
            }

            .logo-row {
                gap: 15px;
            }
        }
    </style>
</head>
<body>

<div class="login-container">
    <!-- Header with TWO Logos -->
    <div class="login-header">
        <div class="logo-row">
            <div class="logo-placeholder">
                <img src="logo.jpg" alt="Company Logo">
            </div>
            <div class="logo-placeholder">
                <img src="logo_erp.jpg" alt="ERP Logo">
            </div>
        </div>
        <h1>WELCOME</h1>
        <p>Sign in to your account</p>
    </div>

    <!-- Login Form -->
    <div class="login-form">

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/>
                </svg>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                Registration successful! Please log in.
            </div>
        <?php endif; ?>

        <form method="POST" novalidate>
            <!-- Username -->
            <div class="form-group">
                <label for="username">Username or Email</label>
                <input type="text" id="username" name="username" required
                       value="<?= $username_value ?>"
                       placeholder="Enter your username or email">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required
                       placeholder="Enter your password">
                <svg class="input-icon" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6zm9 14H6V10h12v10zm-6-3c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2z"/>
                </svg>
                <svg class="toggle-password" id="togglePassword" viewBox="0 0 24 24">
                    <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                </svg>
            </div>

            <!-- Options -->
            <div class="form-options">
                <label class="remember-me">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="forget_pass.php" class="forgot-password">Forgot Password?</a>
            </div>

            <!-- Submit -->
            <button type="submit" class="btn-primary">LOGIN</button>
        </form>

        <!-- Footer -->
        <div class="login-footer">
            Don't have an account? <a href="register.php">Sign Up</a>
        </div>
    </div>
</div>

<script>
    // Password show / hide toggle
    const toggle = document.getElementById('togglePassword');
    const passwordInput = document.getElementById('password');

    toggle.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.style.fill = type === 'text' ? 'var(--maroon)' : 'var(--text-muted)';
    });
</script>

</body>
</html>