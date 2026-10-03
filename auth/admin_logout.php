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
    <title>Signing out…</title>
    <meta http-equiv="refresh" content="2;url=admin_login.php">
    <style>
        :root {
            --maroon-deep: #2b0e0e;
            --maroon-card: #3d1414;
            --amber: #E88C2E;
            --amber-bright: #ffa54a;
            --off-white: #f0e6e6;
            --text-muted: #9a8585;
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

        .logout-card {
            background-color: var(--maroon-card);
            width: 100%;
            max-width: 420px;
            border-radius: 14px;
            border: 1px solid rgba(232, 140, 46, 0.15);
            border-top: 4px solid var(--amber);
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
            padding: 55px 35px;
            text-align: center;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .spinner {
            width: 80px;
            height: 80px;
            margin: 0 auto 28px auto;
            position: relative;
        }

        .spinner::before,
        .spinner::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            top: 0; left: 0;
            width: 100%; height: 100%;
        }

        .spinner::before {
            border: 4px solid rgba(232, 140, 46, 0.1);
        }

        .spinner::after {
            border: 4px solid transparent;
            border-top-color: var(--amber);
            border-right-color: var(--amber-bright);
            animation: spin 0.9s linear infinite;
        }

        @keyframes spin {
            0%   { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .spinner .dot {
            position: absolute;
            top: 50%; left: 50%;
            width: 16px; height: 16px;
            background: var(--amber);
            border-radius: 50%;
            transform: translate(-50%, -50%);
            box-shadow: 0 0 20px rgba(232, 140, 46, 0.8);
            animation: pulse 1s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1);   opacity: 1; }
            50%      { transform: translate(-50%, -50%) scale(1.4); opacity: 0.6; }
        }

        .logout-card h2 {
            color: var(--off-white);
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 4px;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .logout-card h2 span { color: var(--amber); }

        .logout-card p {
            color: var(--text-muted);
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 28px;
        }

        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: rgba(232, 140, 46, 0.1);
            border-radius: 2px;
            overflow: hidden;
            position: relative;
        }

        .progress-bar::after {
            content: '';
            position: absolute;
            left: 0; top: 0;
            height: 100%;
            width: 0;
            background: linear-gradient(90deg, var(--amber), var(--amber-bright));
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

<div class="logout-card">
    <div class="spinner">
        <div class="dot"></div>
    </div>
    <h2>SIGNING <span>OUT</span></h2>
    <p>Securing your session…</p>
    <div class="progress-bar"></div>
</div>

<script>
    setTimeout(function () {
        window.location.href = 'admin_login.php';
    }, 2000);
</script>

</body>
</html>