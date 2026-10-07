<?php
require_once __DIR__ . '/../include/config.php';
require_login();

header('Content-Type: application/json');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo json_encode(['status' => 'err', 'message' => 'Invalid employee id.']);
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, email, contact_number, role,
           employee_code, first_name, last_name, position, department,
           hire_date, birthdate, sss_no, philhealth_no, pagibig_no,
           basic_salary, status
    FROM users
    WHERE id = ?
    LIMIT 1
");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(['status' => 'err', 'message' => 'Employee not found.']);
    exit;
}

echo json_encode(['status' => 'ok', 'user' => $user]);