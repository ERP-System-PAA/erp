<?php
// ============================================================
// ERP CONFIGURATION + AUTH + MODULE HELPERS
// Location: /erp/include/config.php
// ============================================================

// Database config
$host = 'localhost';
$db   = 'erp_db';
$user = 'root';
$pass = '';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// ---------- PATHS / URLS ----------
define('BASE_URL',  'http://localhost/erp/');
define('ROOT_PATH', dirname(__DIR__));   // → C:\xampp\htdocs\erp

// ---------- HTML ESCAPE HELPER ----------
if (!function_exists('e')) {
    /**
     * Escape a value for safe output in HTML.
     * Shortcut for htmlspecialchars() with safe defaults.
     */
    function e($value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

// ---------- AUTH HELPERS ----------
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: " . BASE_URL . "auth/admin_login.php");
        exit;
    }
}

function require_role($roles) {
    require_login();
    if (!in_array($_SESSION['role'], (array) $roles, true)) {
        http_response_code(403);
        die("Access denied.");
    }
}

function is_super_admin() {
    return is_logged_in() && ($_SESSION['role'] ?? '') === 'super_admin';
}

// ---------- DEPARTMENT / MODULE HELPERS ----------

/**
 * Department → allowed nav module keys.
 * Finance and HR both unlock the combined `finance_hr` module.
 */
function department_module_map(): array {
    return [
        'Procurement' => ['procurement'],
        'Inventory'   => ['inventory'],
        'Production'  => ['production'],
        'Sales'       => ['sales'],
        'Finance'     => ['finance_hr'],
        'HR'          => ['finance_hr'],
        'Admin'       => ['procurement', 'inventory', 'production', 'sales', 'finance_hr'],
    ];
}

/**
 * Get the current user's department (from session).
 */
function current_department(): ?string {
    return $_SESSION['department'] ?? null;
}

/**
 * Get the list of module keys the current user can access.
 *   - super_admin → ALL module keys
 *   - admin       → modules mapped to their department
 *   - user        → nothing
 */
function allowed_modules(): array {
    if (!is_logged_in()) {
        return [];
    }

    // Super admin: full access
    if (is_super_admin()) {
        return department_module_map()['Admin'];   // ← return VALUES, not array_keys()
    }

    // Department admin
    if (($_SESSION['role'] ?? '') === 'admin') {
        $dept = current_department();
        return department_module_map()[$dept] ?? [];
    }

    // Everyone else
    return [];
}

/**
 * Guard a page by module key.
 * Usage at top of a restricted page: require_module('inventory');
 */
function require_module(string $moduleKey): void {
    require_login();

    if (is_super_admin()) {
        return; // super admin bypasses all gates
    }

    $allowed = allowed_modules();
    if (!in_array($moduleKey, $allowed, true)) {
        http_response_code(403);
        die("Access denied. You do not have permission to view this module.");
    }
}