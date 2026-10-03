<?php
require_once __DIR__ . '/../include/config.php';

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

$username  = htmlspecialchars($_SESSION['username']);
$role      = $_SESSION['role'];
$roleLabel = $role === 'super_admin' ? 'Super Admin' : 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — ERP</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
</head>
<body>

<?php require_once __DIR__ . '/../component/admin_nav.php'; ?>

<div class="container">
    <h2>Welcome, <span><?= $username ?></span></h2>
    <p class="lead">You are signed in as <strong style="color:var(--amber)"><?= $roleLabel ?></strong></p>

    <div class="grid">
        <div class="card">
            <h3>Users</h3>
            <p>Manage all user accounts.</p>
        </div>
        <div class="card">
            <h3>Reports</h3>
            <p>View system reports and analytics.</p>
        </div>
        <div class="card">
            <h3>Settings</h3>
            <p>Configure application preferences.</p>
        </div>
        <div class="card">
            <h3>Logs</h3>
            <p>Audit trail and activity monitor.</p>
        </div>

        <?php if ($role === 'super_admin'): ?>
            <div class="card super">
                <h3>Create Admin</h3>
                <p>Add new admin or super admin accounts.</p>
                <a href="../auth/admin_register.php" class="card-link">→ Go to Register</a>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>