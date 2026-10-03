<?php
session_start();
session_unset();
session_destroy();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging out...</title>
    <meta http-equiv="refresh" content="2;url=login.php">
    <style>
        :root {
            --maroon: #783332;
            --maroon-dark: #5a2524;
            --amber: #E88C2E;
            --white: #FFFFFF;
            --text-muted: #666666;
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

        .logout-container {
            background-color: var(--white);
            width: 100%;
            max-width: 420px;
            border-radius: 12px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            padding: 50px 35px;
            text-align: center;
            border-top: 6px solid var(--amber);
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* =========================================
           SPINNER
           ========================================= */
        .spinner {
            width: 70px;
            height: 70px;
            margin: 0 auto 25px auto;
            position: relative;
        }

        .spinner::before,
        .spinner::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        /* Outer ring */
        .spinner::before {
            border: 4px solid rgba(120, 51, 50, 0.15);
        }

        /* Spinning arc */
        .spinner::after {
            border: 4px solid transparent;
            border-top-color: var(--maroon);
            border-right-color: var(--amber);
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Center dot pulse */
        .spinner .dot {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 14px;
            height: 14px;
            background-color: var(--amber);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            animation: pulse 1s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1);   opacity: 1; }
            50%      { transform: translate(-50%, -50%) scale(1.4); opacity: 0.6; }
        }

        /* =========================================
           TEXT
           ========================================= */
        .logout-container h2 {
            color: var(--maroon);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .logout-container p {
            color: var(--text-muted);
            font-size: 14px;
            margin-bottom: 25px;
        }

        /* =========================================
           PROGRESS BAR
           ========================================= */
        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(120, 51, 50, 0.1);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--maroon), var(--amber));
            border-radius: 2px;
            animation: fillBar 2s ease forwards;
        }

        @keyframes fillBar {
            from { width: 0; }
            to   { width: 100%; }
        }
    </style>
</head>
<body>

<div class="logout-container">
    <div class="spinner">
        <div class="dot"></div>
    </div>
    <h2>LOGGING OUT</h2>
    <p>You're being signed out securely…</p>
    <div class="progress-bar"></div>
</div>

<script>
    // Fallback redirect in case meta refresh fails
    setTimeout(function () {
        window.location.href = 'login.php';
    }, 2000);
</script>

</body>
</html>