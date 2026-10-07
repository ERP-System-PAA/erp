<?php
// ============================================================
// QR Attendance — Backend Handler
// Location: /erp/attendance/process_scan.php
// ============================================================

require_once __DIR__ . '/../include/config.php';
require_login();   // any logged-in user can scan their own badge

header('Content-Type: application/json');

$code = trim($_POST['employee_code'] ?? '');
if ($code === '') {
    echo json_encode(['status' => 'err', 'message' => '❌ Empty QR code.']);
    exit;
}

// 1. Look up employee by employee_code
$stmt = $pdo->prepare("SELECT id, username, first_name, last_name
                       FROM users
                       WHERE employee_code = ? AND status = 'Active'
                       LIMIT 1");
$stmt->execute([$code]);
$u = $stmt->fetch();

if (!$u) {
    echo json_encode(['status' => 'err', 'message' => '❌ Unknown/inactive employee: ' . e($code)]);
    exit;
}

$name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
if ($name === '') $name = $u['username'];

// ✅ Philippine date (uses config timezone)
$today = date('Y-m-d');

// 2. Check today's attendance record
$stmt = $pdo->prepare("SELECT id, time_in, time_out
                       FROM attendance
                       WHERE user_id = ? AND attendance_date = ?");
$stmt->execute([$u['id'], $today]);
$row = $stmt->fetch();

// 3a. No record yet → TIME IN
if (!$row) {
    $ins = $pdo->prepare("INSERT INTO attendance
        (user_id, attendance_date, time_in, status)
        VALUES (?, ?, CURTIME(), 'Present')");
    $ins->execute([$u['id'], $today]);

    echo json_encode([
        'status'  => 'ok',
        'message' => "✅ <b>" . e($name) . "</b> timed <b>IN</b> at " . date('h:i:s A'),
    ]);
    exit;
}

// 3b. Record exists, no time_out → TIME OUT
if ($row['time_in'] && !$row['time_out']) {
    $upd = $pdo->prepare("UPDATE attendance SET time_out = CURTIME() WHERE id = ?");
    $upd->execute([$row['id']]);

    echo json_encode([
        'status'  => 'ok',
        'message' => "✅ <b>" . e($name) . "</b> timed <b>OUT</b> at " . date('h:i:s A'),
    ]);
    exit;
}

// 3c. Already completed
echo json_encode([
    'status'  => 'warn',
    'message' => "⚠️ <b>" . e($name) . "</b> already timed IN and OUT today.",
]);