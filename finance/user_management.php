<?php
// Session is started by config.php — no need to call session_start() here.

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../component/admin_nav.php';

// ============ AUTH GUARD ============
if (!isset($_SESSION['user_id'])) {
    header('Location: /erp/auth/admin_login.php');
    exit;
}

$currentRole       = $_SESSION['role']       ?? 'user';
$currentDepartment = $_SESSION['department'] ?? null;

function canManageUsers(string $role, ?string $department): bool {
    if ($role === 'super_admin') return true;
    if ($role === 'admin' && $department === 'HR') return true;
    return false;
}

if (!canManageUsers($currentRole, $currentDepartment)) {
    http_response_code(403);
    die('Access denied. Only HR admins and super admins can manage users.');
}

/**
 * Generate the next unique employee code in the format EMP-YYYY-NNNN.
 */
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
        $code = sprintf('%s-%s-%04d', $prefix, $year, $next);
        $check = $pdo->prepare('SELECT 1 FROM users WHERE employee_code = ? LIMIT 1');
        $check->execute([$code]);
        if (!$check->fetchColumn()) {
            return $code;
        }
        $next++;
    }
}

// ============ HANDLE POST ============
$errors  = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---------- CREATE ----------
    if ($action === 'create') {
        $username      = trim($_POST['username'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $password      = $_POST['password'] ?? '';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $role          = $_POST['role'] ?? 'user';

        $employeeCode  = '';

        $firstName     = trim($_POST['first_name'] ?? '');
        $lastName      = trim($_POST['last_name'] ?? '');
        $position      = trim($_POST['position'] ?? '');
        $department    = $_POST['department'] ?? null;
        $hireDate      = $_POST['hire_date'] ?? null;
        $birthdate     = $_POST['birthdate'] ?? null;
        $sssNo         = trim($_POST['sss_no'] ?? '');
        $philhealthNo  = trim($_POST['philhealth_no'] ?? '');
        $pagibigNo     = trim($_POST['pagibig_no'] ?? '');
        $basicSalary   = $_POST['basic_salary'] ?? 0;
        $status        = $_POST['status'] ?? 'Active';

        if ($username === '')  $errors[] = 'Username is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!in_array($role, ['super_admin','admin','user'], true)) $errors[] = 'Invalid role.';

        $validDepts = ['Procurement','Inventory','Production','Sales','Finance','HR','Admin'];
        if ($department !== null && $department !== '' && !in_array($department, $validDepts, true)) {
            $errors[] = 'Invalid department.';
        }
        if (!in_array($status, ['Active','Inactive','Resigned'], true)) $errors[] = 'Invalid status.';

        if ($currentRole !== 'super_admin' && $role === 'super_admin') {
            $errors[] = 'Only a super admin can create another super admin.';
        }

        if (!$errors) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) $errors[] = 'Username already exists.';

            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) $errors[] = 'Email already exists.';
        }

        if (!$errors) {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);

                $sql = "INSERT INTO users
                    (username, password, email, contact_number, role,
                     employee_code, first_name, last_name, position, department,
                     hire_date, birthdate, sss_no, philhealth_no, pagibig_no,
                     basic_salary, status)
                    VALUES
                    (:username, :password, :email, :contact_number, :role,
                     :employee_code, :first_name, :last_name, :position, :department,
                     :hire_date, :birthdate, :sss_no, :philhealth_no, :pagibig_no,
                     :basic_salary, :status)";
                $stmt = $pdo->prepare($sql);

                $attempts = 0;
                while (true) {
                    $employeeCode = generate_employee_code($pdo, 'EMP');
                    try {
                        $stmt->execute([
                            ':username'       => $username,
                            ':password'       => $hash,
                            ':email'          => $email,
                            ':contact_number' => $contactNumber ?: null,
                            ':role'           => $role,
                            ':employee_code'  => $employeeCode,
                            ':first_name'     => $firstName ?: null,
                            ':last_name'      => $lastName ?: null,
                            ':position'       => $position ?: null,
                            ':department'     => $department ?: null,
                            ':hire_date'      => $hireDate ?: null,
                            ':birthdate'      => $birthdate ?: null,
                            ':sss_no'         => $sssNo ?: null,
                            ':philhealth_no'  => $philhealthNo ?: null,
                            ':pagibig_no'     => $pagibigNo ?: null,
                            ':basic_salary'   => $basicSalary ?: 0,
                            ':status'         => $status,
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

                $success = "Account created successfully for {$username} "
                         . "(Employee Code: {$employeeCode}).";
            } catch (PDOException $e) {
                error_log('[user_manager] create: ' . $e->getMessage());
                $errors[] = 'Database error occurred while creating the account. Please try again.';
            }
        }
    }

    // ---------- UPDATE ----------
    if ($action === 'update') {
        $id            = (int)($_POST['id'] ?? 0);
        $username      = trim($_POST['username'] ?? '');
        $email         = trim($_POST['email'] ?? '');
        $password      = $_POST['password'] ?? '';
        $contactNumber = trim($_POST['contact_number'] ?? '');
        $role          = $_POST['role'] ?? 'user';

        $firstName     = trim($_POST['first_name'] ?? '');
        $lastName      = trim($_POST['last_name'] ?? '');
        $position      = trim($_POST['position'] ?? '');
        $department    = $_POST['department'] ?? null;
        $hireDate      = $_POST['hire_date'] ?? null;
        $birthdate     = $_POST['birthdate'] ?? null;
        $sssNo         = trim($_POST['sss_no'] ?? '');
        $philhealthNo  = trim($_POST['philhealth_no'] ?? '');
        $pagibigNo     = trim($_POST['pagibig_no'] ?? '');
        $basicSalary   = $_POST['basic_salary'] ?? 0;
        $status        = $_POST['status'] ?? 'Active';

        // Load target user
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target) {
            $errors[] = 'User not found.';
        } else {
            // Permission checks
            if ($currentRole !== 'super_admin' && $target['role'] === 'super_admin') {
                $errors[] = 'Cannot edit a super admin.';
            }
            if ($currentRole !== 'super_admin' && $role === 'super_admin') {
                $errors[] = 'Only a super admin can assign the super admin role.';
            }

            if ($username === '')  $errors[] = 'Username is required.';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
            if ($password !== '' && strlen($password) < 8) {
                $errors[] = 'Password must be at least 8 characters (leave blank to keep current).';
            }
            if (!in_array($role, ['super_admin','admin','user'], true)) $errors[] = 'Invalid role.';

            $validDepts = ['Procurement','Inventory','Production','Sales','Finance','HR','Admin'];
            if ($department !== null && $department !== '' && !in_array($department, $validDepts, true)) {
                $errors[] = 'Invalid department.';
            }
            if (!in_array($status, ['Active','Inactive','Resigned'], true)) $errors[] = 'Invalid status.';

            // Uniqueness (excluding self)
            if (!$errors) {
                $stmt = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id <> ?');
                $stmt->execute([$username, $id]);
                if ($stmt->fetch()) $errors[] = 'Username already exists.';

                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
                $stmt->execute([$email, $id]);
                if ($stmt->fetch()) $errors[] = 'Email already exists.';
            }

            if (!$errors) {
                try {
                    $sql = "UPDATE users SET
                                username = :username,
                                email = :email,
                                contact_number = :contact_number,
                                role = :role,
                                first_name = :first_name,
                                last_name = :last_name,
                                position = :position,
                                department = :department,
                                hire_date = :hire_date,
                                birthdate = :birthdate,
                                sss_no = :sss_no,
                                philhealth_no = :philhealth_no,
                                pagibig_no = :pagibig_no,
                                basic_salary = :basic_salary,
                                status = :status";
                    $params = [
                        ':username'       => $username,
                        ':email'          => $email,
                        ':contact_number' => $contactNumber ?: null,
                        ':role'           => $role,
                        ':first_name'     => $firstName ?: null,
                        ':last_name'      => $lastName ?: null,
                        ':position'       => $position ?: null,
                        ':department'     => $department ?: null,
                        ':hire_date'      => $hireDate ?: null,
                        ':birthdate'      => $birthdate ?: null,
                        ':sss_no'         => $sssNo ?: null,
                        ':philhealth_no'  => $philhealthNo ?: null,
                        ':pagibig_no'     => $pagibigNo ?: null,
                        ':basic_salary'   => $basicSalary ?: 0,
                        ':status'         => $status,
                    ];

                    if ($password !== '') {
                        $sql .= ", password = :password";
                        $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    $sql .= " WHERE id = :id";
                    $params[':id'] = $id;

                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);

                    $success = "User '{$username}' updated successfully.";
                } catch (PDOException $e) {
                    error_log('[user_manager] update: ' . $e->getMessage());
                    $errors[] = 'Database error occurred while updating the account. Please try again.';
                }
            }
        }
    }

    // ---------- TOGGLE STATUS ----------
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== (int)$_SESSION['user_id']) {
            $chk = $pdo->prepare('SELECT role FROM users WHERE id = ?');
            $chk->execute([$id]);
            $targetRole = $chk->fetchColumn();

            if ($targetRole === false) {
                $errors[] = 'User not found.';
            } elseif ($currentRole !== 'super_admin' && $targetRole === 'super_admin') {
                $errors[] = 'Cannot modify a super admin.';
            } else {
                $stmt = $pdo->prepare("UPDATE users SET status = IF(status='Active','Inactive','Active') WHERE id = ?");
                $stmt->execute([$id]);
                $success = 'Status updated.';
            }
        } else {
            $errors[] = 'Cannot change your own status.';
        }
    }

    // ---------- DELETE ----------
    if ($action === 'delete' && $currentRole === 'super_admin') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0 && $id !== (int)$_SESSION['user_id']) {
            $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
            $stmt->execute([$id]);
            $success = 'User deleted.';
        } else {
            $errors[] = 'Cannot delete your own account.';
        }
    }
}

// ============ FETCH USERS ============
$search = trim($_GET['q'] ?? '');
$sql = "SELECT id, username, email, role, employee_code, first_name, last_name,
               position, department, status, created_at
        FROM users";
$params = [];
if ($search !== '') {
    $sql .= " WHERE username LIKE :q OR email LIKE :q OR employee_code LIKE :q
              OR first_name LIKE :q OR last_name LIKE :q";
    $params[':q'] = '%' . $search . '%';
}
$sql .= " ORDER BY id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// ============ EDIT MODE ============
$editUser = null;
$editId   = (int)($_GET['edit'] ?? 0);

// Don't re-enter edit mode after a successful update
$justUpdated = ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'update'
    && empty($errors)
    && $success !== '');

if ($editId > 0 && !$justUpdated) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$editId]);
    $editUser = $stmt->fetch();

    if (!$editUser) {
        $errors[] = 'User to edit was not found.';
    } elseif ($currentRole !== 'super_admin' && $editUser['role'] === 'super_admin') {
        $errors[] = 'You do not have permission to edit a super admin.';
        $editUser = null;
    }
}

// Preview of the next code (informational only)
$nextEmployeeCode = generate_employee_code($pdo, 'EMP');

// When editing, keep the submitted values (on validation error) or the DB row
if ($editUser && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $old = array_merge($editUser, $_POST);
} elseif ($editUser) {
    $old = $editUser;
} else {
    $old = $_POST;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>User Management — Admin Panel</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="../assets/css/user_management.css">
</head>
<body>

<!-- Sidebar already rendered by admin_nav.php above -->

<main class="admin-content">

    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="bi bi-people-fill"></i> User Management</h1>
            <div class="page-subtitle">
                Logged in as <strong><?= htmlspecialchars($_SESSION['username'] ?? 'user') ?></strong>
                (<?= htmlspecialchars($currentRole) ?><?= $currentDepartment ? ' · ' . htmlspecialchars($currentDepartment) : '' ?>)
            </div>
        </div>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <div class="alert-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
            <div class="alert-body">
                <?php if (count($errors) === 1): ?>
                    <div class="alert-line"><?= htmlspecialchars($errors[0]) ?></div>
                <?php else: ?>
                    <div class="alert-heading">Please fix the following:</div>
                    <?php foreach ($errors as $e): ?>
                        <div class="alert-line"><?= htmlspecialchars($e) ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <div class="alert-icon"><i class="bi bi-check-circle-fill"></i></div>
            <div class="alert-body">
                <div class="alert-line"><?= htmlspecialchars($success) ?></div>
            </div>
        </div>
    <?php endif; ?>

    <section class="panel<?= $editUser ? ' editing' : '' ?>">
        <h2 class="panel-title">
            <?php if ($editUser): ?>
                <i class="bi bi-pencil-square"></i> Edit User — <?= htmlspecialchars($editUser['username']) ?>
                <span style="color:var(--text-muted);font-weight:400;text-transform:none;letter-spacing:0;">
                    (ID #<?= (int)$editUser['id'] ?>)
                </span>
            <?php else: ?>
                <i class="bi bi-person-plus-fill"></i> Add Employee &amp; Create Account
            <?php endif; ?>
        </h2>

        <form method="post" autocomplete="off">
            <?php if ($editUser): ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
            <?php else: ?>
                <input type="hidden" name="action" value="create">
            <?php endif; ?>

            <fieldset>
                <legend>Account</legend>
                <div class="form-grid">
                    <div class="field">
                        <label>Username *</label>
                        <input name="username" required value="<?= htmlspecialchars($old['username'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Email *</label>
                        <input type="email" name="email" required value="<?= htmlspecialchars($old['email'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>
                            Password <?= $editUser ? '(leave blank to keep current)' : '* (min 8 chars)' ?>
                        </label>
                        <input type="password" name="password"
                               <?= $editUser ? '' : 'required' ?> minlength="8"
                               placeholder="<?= $editUser ? '•••••••• (unchanged)' : '' ?>">
                    </div>
                    <div class="field">
                        <label>Contact Number</label>
                        <input name="contact_number" value="<?= htmlspecialchars($old['contact_number'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Role *</label>
                        <select name="role" required>
                            <option value="user"  <?= (($old['role'] ?? '')==='user')?'selected':'' ?>>User</option>
                            <option value="admin" <?= (($old['role'] ?? '')==='admin')?'selected':'' ?>>Admin</option>
                            <?php if ($currentRole === 'super_admin'): ?>
                                <option value="super_admin" <?= (($old['role'] ?? '')==='super_admin')?'selected':'' ?>>Super Admin</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend>Employee Details</legend>
                <div class="form-grid">
                    <div class="field">
                        <label>Employee Code <?= $editUser ? '' : '<span style="color:var(--amber)">(auto)</span>' ?></label>
                        <?php if ($editUser): ?>
                            <input type="text" readonly
                                   value="<?= htmlspecialchars($editUser['employee_code'] ?? '—') ?>"
                                   title="Employee code cannot be changed.">
                        <?php else: ?>
                            <input type="text" readonly
                                   value="<?= htmlspecialchars($nextEmployeeCode) ?>"
                                   title="Auto-generated on save. Preview only.">
                        <?php endif; ?>
                    </div>
                    <div class="field">
                        <label>First Name</label>
                        <input name="first_name" value="<?= htmlspecialchars($old['first_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Last Name</label>
                        <input name="last_name" value="<?= htmlspecialchars($old['last_name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Position</label>
                        <input name="position" value="<?= htmlspecialchars($old['position'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Department</label>
                        <select name="department">
                            <option value="">— none —</option>
                            <?php foreach (['Procurement','Inventory','Production','Sales','Finance','HR','Admin'] as $d): ?>
                                <option value="<?= $d ?>" <?= (($old['department'] ?? '')===$d)?'selected':'' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>Hire Date</label>
                        <input type="date" name="hire_date" value="<?= htmlspecialchars($old['hire_date'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Birthdate</label>
                        <input type="date" name="birthdate" value="<?= htmlspecialchars($old['birthdate'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Status</label>
                        <select name="status">
                            <?php foreach (['Active','Inactive','Resigned'] as $s): ?>
                                <option value="<?= $s ?>" <?= (($old['status'] ?? 'Active')===$s)?'selected':'' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field">
                        <label>SSS No</label>
                        <input name="sss_no" value="<?= htmlspecialchars($old['sss_no'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>PhilHealth No</label>
                        <input name="philhealth_no" value="<?= htmlspecialchars($old['philhealth_no'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Pag-IBIG No</label>
                        <input name="pagibig_no" value="<?= htmlspecialchars($old['pagibig_no'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label>Basic Salary</label>
                        <input type="number" step="0.01" min="0" name="basic_salary"
                               value="<?= htmlspecialchars($old['basic_salary'] ?? '0.00') ?>">
                    </div>
                </div>
            </fieldset>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <button type="submit" class="btn btn-primary">
                    <?php if ($editUser): ?>
                        <i class="bi bi-save2"></i> Save Changes
                    <?php else: ?>
                        <i class="bi bi-check2-circle"></i> Create Account
                    <?php endif; ?>
                </button>
                <?php if ($editUser): ?>
                    <a href="?" class="btn btn-ghost">
                        <i class="bi bi-x-circle"></i> Cancel Edit
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="panel">
        <h2 class="panel-title"><i class="bi bi-list-ul"></i> All Users</h2>

        <form method="get" class="toolbar">
            <input name="q" placeholder="Search username, email, code, name…"
                   value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-ghost btn-sm">
                <i class="bi bi-search"></i> Search
            </button>
            <?php if ($search !== ''): ?>
                <a href="?" class="btn btn-ghost btn-sm"><i class="bi bi-x-lg"></i> Clear</a>
            <?php endif; ?>
        </form>

        <div class="table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Employee Code</th>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Dept</th>
                        <th>Position</th>
                        <th>Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!$users): ?>
                    <tr><td colspan="10" class="empty-row">No users found.</td></tr>
                <?php endif; ?>
                <?php foreach ($users as $u): ?>
                    <?php $isEditingRow = $editUser && (int)$editUser['id'] === (int)$u['id']; ?>
                    <tr class="<?= $isEditingRow ? 'editing-row' : '' ?>">
                        <td class="text-muted"><?= (int)$u['id'] ?></td>
                        <td>
                            <?php if (!empty($u['employee_code'])): ?>
                                <span class="emp-code"><?= htmlspecialchars($u['employee_code']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                        <td><?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?: '—' ?></td>
                        <td class="text-muted"><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge-soft badge-<?= htmlspecialchars($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                        <td><?= htmlspecialchars($u['department'] ?? '—') ?></td>
                        <td><?= htmlspecialchars($u['position'] ?? '—') ?></td>
                        <td><span class="badge-soft badge-<?= htmlspecialchars($u['status'] ?? 'Inactive') ?>"><?= htmlspecialchars($u['status'] ?? '—') ?></span></td>
                        <td>
                            <div class="actions-cell" style="justify-content:flex-end;">
                                <!-- EDIT -->
                                <a class="icon-btn icon-btn-edit"
                                   href="?edit=<?= (int)$u['id'] ?><?= $search !== '' ? '&q=' . urlencode($search) : '' ?>"
                                   title="Edit <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                <form method="post"
                                      onsubmit="return confirm('Toggle status for <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>?');">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="icon-btn icon-btn-toggle" type="submit"
                                            title="Toggle status for <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </form>
                                <?php if ($currentRole === 'super_admin'): ?>
                                    <form method="post"
                                          onsubmit="return confirm('Delete <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>? This cannot be undone.');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="icon-btn icon-btn-delete" type="submit"
                                                title="Delete <?= htmlspecialchars($u['username'], ENT_QUOTES) ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

</main>

<?php if ($editUser): ?>
<script>
// Scroll to the edit form when in edit mode so the user sees it immediately
(function () {
    var panel = document.querySelector('.panel.editing');
    if (panel) {
        panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
})();
</script>
<?php endif; ?>

</body>
</html>