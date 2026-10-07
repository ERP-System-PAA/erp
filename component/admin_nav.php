<?php
// ============================================================
// ADMIN NAVIGATION (self-contained, role + department aware)
// Styled to match the maroon + amber admin dashboard theme.
//
// Usage:
//   require_once __DIR__ . '/config.php';
//   require_once __DIR__ . '/admin_nav.php';
//
// Requires from config.php:
//   - is_logged_in(), require_login(), is_super_admin()
//   - current_department(), allowed_modules()
//   - BASE_URL constant
// ============================================================

// Safety net — load config if it wasn't already.
if (!function_exists('is_logged_in')) {
    require_once __DIR__ . '/config.php';
}

// Enforce authentication.
require_login();

// ---------- CURRENT USER CONTEXT ----------
$currentRole       = $_SESSION['role']       ?? 'user';
$currentDepartment = current_department();

// ---------- CONFIG ----------
$adminModules = [
    'procurement' => [
        'name'     => 'Procurement',
        'icon'     => 'bi-cart3',
        'features' => [
            ['name' => 'Purchase Requests', 'file' => 'procurement/purchase_requests.php'],
            ['name' => 'Purchase Orders',   'file' => 'procurement/purchase_orders.php'],
            ['name' => 'Suppliers',         'file' => 'procurement/suppliers.php'],
            ['name' => 'Goods Received',    'file' => 'procurement/goods_received.php'],
            ['name' => 'Raw Materials',     'file' => 'procurement/raw_materials.php'],
            ['name' => 'Expenditure',       'file' => 'procurement/expenditure.php'],
            ['name' => 'Attendance',       'file' => 'attendance/attendance.php'],
        ],
    ],
    'inventory' => [
        'name'     => 'Inventory',
        'icon'     => 'bi-box-seam-fill',
        'features' => [
            ['name' => 'Stock List',  'file' => 'inventory/stock_list.php'],
            ['name' => 'Stock In',    'file' => 'inventory/stock_in.php'],
            ['name' => 'Stock Out',   'file' => 'inventory/stock_out.php'],
            ['name' => 'Adjustments', 'file' => 'inventory/adjustments.php'],
            ['name' => 'Attendance',       'file' => 'attendance/attendance.php'],
        ],
    ],
    'production' => [
        'name'     => 'Production',
        'icon'     => 'bi-gear-fill',
        'features' => [
            ['name' => 'Work Orders',       'file' => 'production/work_orders.php'],
            ['name' => 'Bill of Materials', 'file' => 'production/bom.php'],
            ['name' => 'Schedules',         'file' => 'production/schedules.php'],
            ['name' => 'Product Quantity',  'file' => 'production/product_quantity.php'],
            ['name' => 'Attendance',       'file' => 'attendance/attendance.php'],
        ],
    ],
    'sales' => [
        'name'     => 'Sales',
        'icon'     => 'bi-graph-up-arrow',
        'features' => [
            ['name' => 'Customers',    'file' => 'sales/customers.php'],
            ['name' => 'Quotations',   'file' => 'sales/quotations.php'],
            ['name' => 'Sales Orders', 'file' => 'sales/sales_orders.php'],
            ['name' => 'Invoices',     'file' => 'sales/invoices.php'],
            ['name' => 'Attendance',       'file' => 'attendance/attendance.php'],
        ],
    ],
    'finance_hr' => [
        'name'     => 'Finance / HR',
        'icon'     => 'bi-cash-coin',
        'features' => [
            ['name' => 'User Management', 'file' => 'finance/user_management.php'],
            ['name' => 'Expenses',        'file' => 'finance/expenses.php'],
            ['name' => 'Employees',       'file' => 'finance/employees.php'],
            ['name' => 'Attendance',      'file' => 'finance/attendance.php'],
            ['name' => 'Attendance',       'file' => 'attendance/attendance.php'],
        ],
    ],
];

// ---------- APPLY ROLE + DEPARTMENT FILTER ----------
$allowed         = allowed_modules(); // [] for 'user', all keys for super_admin
$adminModules    = array_intersect_key($adminModules, array_flip($allowed));
$navAccessDenied = (empty($adminModules) && !is_super_admin());

// ---------- PAGE DETECTION ----------
$currentPage = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$navId       = 'adminSidebar'; // stable ID → reliable localStorage + caching
?>

<!-- ================= BOOTSTRAP ICONS CDN ================= -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<!-- ================= SIDEBAR CSS (scoped + matches dashboard theme) ================= -->
<style>
#<?= $navId ?> {
    --maroon-deep: #2b0e0e;
    --maroon-card: #3d1414;
    --maroon-dark: #5a1f1e;
    --maroon-mid:  #7a2a28;
    --amber: #E88C2E;
    --amber-bright: #ffa54a;
    --off-white: #f0e6e6;
    --text-muted: #9a8585;

    width: 260px;
    min-height: 100vh;
    background:
        radial-gradient(circle at 20% 20%, rgba(120, 51, 50, 0.25), transparent 45%),
        var(--maroon-deep);
    color: var(--off-white);
    padding: 20px 0;
    position: fixed;
    top: 0; left: 0;
    overflow-y: auto;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    border-right: 1px solid rgba(232, 140, 46, 0.15);
    z-index: 1030;
    display: flex;
    flex-direction: column;
    transition: background 0.3s ease, color 0.3s ease;
}

/* Light mode styles for the sidebar */
body.light-mode #<?= $navId ?> {
    --maroon-deep: #f5e6e6;
    --maroon-card: #e8d5d5;
    --maroon-dark: #d4b8b8;
    --maroon-mid:  #c9a8a8;
    --amber: #b35f0a;
    --amber-bright: #d97a1a;
    --off-white: #2b0e0e;
    --text-muted: #7a5a5a;

    background:
        radial-gradient(circle at 20% 20%, rgba(180, 130, 130, 0.2), transparent 45%),
        var(--maroon-deep);
    border-right: 1px solid rgba(179, 95, 10, 0.2);
}

body.light-mode #<?= $navId ?>::-webkit-scrollbar-thumb {
    background: rgba(179, 95, 10, 0.3);
}

#<?= $navId ?>::-webkit-scrollbar { width: 6px; }
#<?= $navId ?>::-webkit-scrollbar-track { background: transparent; }
#<?= $navId ?>::-webkit-scrollbar-thumb {
    background: rgba(232, 140, 46, 0.25);
    border-radius: 3px;
}

#<?= $navId ?> .sidebar-brand {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 0 22px 18px;
    font-size: 16px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--off-white);
    border-bottom: 1px solid rgba(232, 140, 46, 0.15);
    margin-bottom: 12px;
    transition: color 0.3s ease, border-color 0.3s ease;
}
#<?= $navId ?> .sidebar-brand i {
    color: var(--amber);
    font-size: 22px;
    filter: drop-shadow(0 0 6px rgba(232, 140, 46, 0.4));
    transition: transform 0.3s ease, filter 0.3s ease;
}
#<?= $navId ?> .sidebar-brand:hover i {
    transform: rotate(-6deg) scale(1.1);
    filter: drop-shadow(0 0 10px rgba(232, 140, 46, 0.7));
}
#<?= $navId ?> .sidebar-brand span::after {
    content: '';
    display: block;
    width: 30px;
    height: 3px;
    background: var(--amber);
    margin-top: 4px;
    border-radius: 2px;
}

#<?= $navId ?> .sidebar-heading {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 2px;
    text-transform: uppercase;
    color: var(--text-muted);
    padding: 14px 22px 8px;
    transition: color 0.3s ease;
}

#<?= $navId ?> .sidebar-menu {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1 1 auto;
}

#<?= $navId ?> .sidebar-menu .nav-link {
    color: var(--off-white);
    padding: 11px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 3px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    letter-spacing: .5px;
    text-decoration: none;
    border-left: 3px solid transparent;
    transition: background .2s ease, color .2s ease, border-color .2s ease, transform .15s ease;
}
#<?= $navId ?> .sidebar-menu .nav-link i:first-child {
    font-size: 17px;
    color: var(--text-muted);
    transition: color .2s ease, transform .2s ease;
    width: 20px;
    text-align: center;
}
#<?= $navId ?> .sidebar-menu .nav-link:hover {
    background: var(--maroon-card);
    color: #fff;
    transform: translateX(2px);
}
body.light-mode #<?= $navId ?> .sidebar-menu .nav-link:hover {
    color: #2b0e0e;
}
#<?= $navId ?> .sidebar-menu .nav-link:hover i:first-child {
    color: var(--amber);
    transform: scale(1.15);
}

#<?= $navId ?> .sidebar-menu .nav-link.active {
    background: linear-gradient(90deg, var(--maroon-dark) 0%, var(--maroon-card) 100%);
    color: #fff;
    border-left-color: var(--amber);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .35);
}
body.light-mode #<?= $navId ?> .sidebar-menu .nav-link.active {
    color: #2b0e0e;
    box-shadow: 0 6px 18px rgba(0, 0, 0, .1);
}
#<?= $navId ?> .sidebar-menu .nav-link.active i:first-child {
    color: var(--amber);
    filter: drop-shadow(0 0 4px rgba(232, 140, 46, 0.5));
}

#<?= $navId ?> .module-toggle .caret {
    transition: transform .3s ease;
    font-size: 11px !important;
    color: var(--text-muted) !important;
}
#<?= $navId ?> .module-toggle[aria-expanded="true"] .caret {
    transform: rotate(180deg);
    color: var(--amber) !important;
}

#<?= $navId ?> .submenu {
    background: rgba(0, 0, 0, .25);
    border-left: 2px solid rgba(232, 140, 46, .25);
    border-radius: 6px;
    margin: 2px 18px 8px 22px;
    max-height: 0;
    overflow: hidden;
    transition: max-height .35s ease, padding .3s ease, background 0.3s ease;
}
body.light-mode #<?= $navId ?> .submenu {
    background: rgba(0, 0, 0, .08);
    border-left: 2px solid rgba(179, 95, 10, .3);
}
#<?= $navId ?> .submenu.open {
    max-height: 600px;
    padding: 6px 0;
}
#<?= $navId ?> .submenu ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
#<?= $navId ?> .submenu .sub-link {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px 8px 12px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: .5px;
    color: var(--text-muted);
    text-decoration: none;
    border-radius: 4px;
    transition: background .2s ease, color .2s ease, padding-left .2s ease;
}
#<?= $navId ?> .submenu .sub-link i {
    color: var(--text-muted);
    transition: color .2s ease, transform .2s ease;
    font-size: 15px;
}
#<?= $navId ?> .submenu .sub-link:hover {
    background: rgba(232, 140, 46, .08);
    color: var(--off-white);
    padding-left: 16px;
}
#<?= $navId ?> .submenu .sub-link:hover i {
    color: var(--amber);
    transform: translateX(2px);
}
#<?= $navId ?> .submenu .sub-link.active {
    color: var(--amber);
    background: rgba(232, 140, 46, .1);
    font-weight: 800;
}
body.light-mode #<?= $navId ?> .submenu .sub-link.active {
    background: rgba(179, 95, 10, .15);
}
#<?= $navId ?> .submenu .sub-link.active i {
    color: var(--amber);
}

/* ---------- Empty / denied notice ---------- */
#<?= $navId ?> .sidebar-notice {
    margin: 12px 18px;
    padding: 12px 14px;
    border-radius: 8px;
    font-size: 12px;
    line-height: 1.5;
    color: #ffb3b3;
    background: rgba(122, 42, 40, 0.35);
    border-left: 3px solid #ff5555;
    display: flex;
    align-items: flex-start;
    gap: 8px;
}
#<?= $navId ?> .sidebar-notice i {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 1px;
}
body.light-mode #<?= $navId ?> .sidebar-notice {
    color: #8b2020;
    background: rgba(200, 150, 150, 0.35);
    border-left-color: #8b2020;
}

/* ---------- Sidebar footer + Logout button ---------- */
#<?= $navId ?> .sidebar-footer {
    flex-shrink: 0;
    margin-top: 20px;
    padding: 16px 22px 8px;
    border-top: 1px solid rgba(232, 140, 46, 0.15);
    transition: border-color 0.3s ease;
}

#<?= $navId ?> .logout-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .5px;
    text-decoration: none;
    color: #ffb3b3;
    background: rgba(122, 42, 40, 0.35);
    border-left: 3px solid transparent;
    transition: background .2s ease, color .2s ease, border-color .2s ease, transform .15s ease;
}
body.light-mode #<?= $navId ?> .logout-btn {
    color: #8b2020;
    background: rgba(200, 150, 150, 0.4);
}
#<?= $navId ?> .logout-btn i {
    font-size: 17px;
    color: #ff8080;
    transition: color .2s ease, transform .2s ease;
}
body.light-mode #<?= $navId ?> .logout-btn i {
    color: #8b2020;
}
#<?= $navId ?> .logout-btn:hover {
    background: var(--maroon-mid);
    color: #fff;
    border-left-color: #ff5555;
}
body.light-mode #<?= $navId ?> .logout-btn:hover {
    background: #c9a8a8;
    color: #2b0e0e;
    border-left-color: #8b2020;
}
#<?= $navId ?> .logout-btn:hover i {
    color: #fff;
    transform: translateX(3px);
}
body.light-mode #<?= $navId ?> .logout-btn:hover i {
    color: #2b0e0e;
}
#<?= $navId ?> .logout-btn:active {
    transform: translateY(1px);
}

/* ---------- Theme toggle button ---------- */
#<?= $navId ?> .theme-toggle-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .5px;
    text-decoration: none;
    color: var(--off-white);
    background: rgba(232, 140, 46, 0.15);
    border-left: 3px solid transparent;
    transition: background .2s ease, color .2s ease, border-color .2s ease, transform .15s ease;
    cursor: pointer;
    border: none;
    width: 100%;
    text-align: left;
    font-family: inherit;
    margin-bottom: 8px;
}
body.light-mode #<?= $navId ?> .theme-toggle-btn {
    background: rgba(179, 95, 10, 0.15);
    color: #2b0e0e;
}
#<?= $navId ?> .theme-toggle-btn i {
    font-size: 17px;
    color: var(--amber);
    transition: color .2s ease, transform 0.3s ease;
}
#<?= $navId ?> .theme-toggle-btn:hover {
    background: var(--maroon-card);
    color: #fff;
    border-left-color: var(--amber);
}
body.light-mode #<?= $navId ?> .theme-toggle-btn:hover {
    background: var(--maroon-dark);
    color: #2b0e0e;
}
#<?= $navId ?> .theme-toggle-btn:hover i {
    transform: rotate(20deg) scale(1.1);
}
#<?= $navId ?> .theme-toggle-btn:active {
    transform: translateY(1px);
}
#<?= $navId ?> .theme-toggle-btn .theme-label {
    flex: 1;
}
#<?= $navId ?> .theme-toggle-btn .theme-icon-dark {
    display: inline-block;
}
#<?= $navId ?> .theme-toggle-btn .theme-icon-light {
    display: none;
}
body.light-mode #<?= $navId ?> .theme-toggle-btn .theme-icon-dark {
    display: none;
}
body.light-mode #<?= $navId ?> .theme-toggle-btn .theme-icon-light {
    display: inline-block;
}

/* ---------- Page content adjustments for light mode ---------- */
.admin-content {
    margin-left: 260px;
    padding: 40px 20px;
    max-width: 1100px;
    transition: background 0.3s ease, color 0.3s ease;
}

body.light-mode {
    background: #f5e6e6;
    color: #2b0e0e;
}

@media (max-width: 768px) {
    #<?= $navId ?> { width: 220px; }
    .admin-content  { margin-left: 220px; padding: 24px 14px; }
}
</style>

<!-- ================= SIDEBAR HTML ================= -->
<aside id="<?= $navId ?>">
    <div class="sidebar-brand">
        <i class="bi bi-shield-lock-fill"></i>
        <span>Admin Panel</span>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a class="nav-link <?= str_contains($currentPage, 'admin/admin_dashboard') ? 'active' : '' ?>"
               href="<?= BASE_URL ?>admin/admin_dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <?php if (!empty($adminModules)): ?>
            <li class="sidebar-heading">Modules</li>

            <?php foreach ($adminModules as $slug => $mod): ?>
                <?php $isOpen = str_contains($currentPage, "/{$slug}/"); ?>
                <li>
                    <a class="nav-link module-toggle <?= $isOpen ? 'active' : '' ?>"
                       href="#module-<?= $slug ?>-<?= $navId ?>"
                       data-target="module-<?= $slug ?>-<?= $navId ?>"
                       role="button"
                       aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                        <i class="bi <?= htmlspecialchars($mod['icon'], ENT_QUOTES, 'UTF-8') ?>"></i>
                        <span><?= htmlspecialchars($mod['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="bi bi-chevron-down ms-auto caret"></i>
                    </a>

                    <div class="submenu <?= $isOpen ? 'open' : '' ?>"
                         id="module-<?= $slug ?>-<?= $navId ?>">
                        <ul>
                            <?php foreach ($mod['features'] as $feature): ?>
                                <li>
                                    <a class="sub-link <?= str_contains($currentPage, $feature['file']) ? 'active' : '' ?>"
                                       href="<?= BASE_URL . htmlspecialchars($feature['file'], ENT_QUOTES, 'UTF-8') ?>">
                                        <i class="bi bi-chevron-right"></i>
                                        <?= htmlspecialchars($feature['name'], ENT_QUOTES, 'UTF-8') ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </li>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($navAccessDenied): ?>
            <li>
                <div class="sidebar-notice">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>No modules are assigned to your account. Please contact your administrator.</span>
                </div>
            </li>
        <?php endif; ?>
    </ul>

    <!-- ---------- Sidebar footer: Theme toggle + Logout ---------- -->
    <div class="sidebar-footer">
        <button class="theme-toggle-btn" id="themeToggle_<?= $navId ?>" type="button" aria-label="Toggle theme">
            <i class="bi bi-moon-stars-fill theme-icon-dark"></i>
            <i class="bi bi-sun-fill theme-icon-light"></i>
            <span class="theme-label">Light Mode</span>
        </button>
        <a class="logout-btn"
           href="<?= BASE_URL ?>auth/admin_logout.php"
           onclick="return confirm('Are you sure you want to log out?');">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<!-- ================= SIDEBAR JS ================= -->
<script>
(function () {
    var sidebar = document.getElementById('<?= $navId ?>');
    if (!sidebar) return;

    // --- Module toggle ---
    sidebar.querySelectorAll('.module-toggle').forEach(function (toggle) {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            var targetId = toggle.getAttribute('data-target');
            var submenu  = document.getElementById(targetId);
            if (!submenu) return;

            var isOpen = submenu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    });

    // --- Theme toggle ---
    var themeToggle = document.getElementById('themeToggle_<?= $navId ?>');
    if (themeToggle) {
        var themeLabel  = themeToggle.querySelector('.theme-label');
        var STORAGE_KEY = 'admin_theme_preference';

        function applyTheme(theme) {
            if (theme === 'light') {
                document.body.classList.add('light-mode');
                themeLabel.textContent = 'Dark Mode';
            } else {
                document.body.classList.remove('light-mode');
                themeLabel.textContent = 'Light Mode';
            }
        }

        // Load saved preference (default: dark)
        var savedTheme = localStorage.getItem(STORAGE_KEY);
        applyTheme(savedTheme ? savedTheme : 'dark');

        themeToggle.addEventListener('click', function () {
            var isLight  = document.body.classList.contains('light-mode');
            var newTheme = isLight ? 'dark' : 'light';
            applyTheme(newTheme);
            localStorage.setItem(STORAGE_KEY, newTheme);
        });
    }
})();
</script>