<?php
// Database config
$host = 'localhost';
$db   = 'erp_db';
$user = 'root';
$pass = '';

// Start session
session_start();

// Connect to database
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Base URL
define('BASE_URL', 'http://localhost/erp/');

// User is logged in?
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Require login
function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "auth/admin_login.php");
        exit;
    }
}

// Require a specific role
function require_role($roles) {
    require_login();
    if (!in_array($_SESSION['role'], (array) $roles, true)) {
        http_response_code(403);
        die("Access denied.");
    }
}

// Is this session a super admin?
function is_super_admin() {
    return is_logged_in() && $_SESSION['role'] === 'super_admin';
}