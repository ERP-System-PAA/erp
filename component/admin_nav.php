<?php
// ============================================================
// ADMIN NAVIGATION (self-contained)
// Styled to match the maroon + amber admin dashboard theme.
// Usage:  require_once 'admin_nav.php';
// ============================================================

// ---------- CONFIG ----------
$adminModules = [
    'procurement' => [
        'name'     => 'Procurement',
        'icon'     => 'bi-cart',
        'features' => [
            ['name' => 'Purchase Requests', 'file' => 'procurement/purchase_requests.php'],
            ['name' => 'Purchase Orders',   'file' => 'procurement/purchase_orders.php'],
            ['name' => 'Suppliers',         'file' => 'procurement/suppliers.php'],
            ['name' => 'Goods Received',    'file' => 'procurement/goods_received.php'],
            ['name' => 'Raw Materials',    'file' => 'procurement/raw_materails.php'],
            ['name' => 'Expenditure',    'file' => 'procurement/expenditure.php'],
        ],
    ],
    'inventory' => [
        'name'     => 'Inventory',
        'icon'     => 'bi-box-seam',
        'features' => [
            ['name' => 'Stock List',  'file' => 'inventory/stock_list.php'],
            ['name' => 'Stock In',    'file' => 'inventory/stock_in.php'],
            ['name' => 'Stock Out',   'file' => 'inventory/stock_out.php'],
            ['name' => 'Adjustments', 'file' => 'inventory/adjustments.php'],
        ],
    ],
    'production' => [
        'name'     => 'Production',
        'icon'     => 'bi-gear',
        'features' => [
            ['name' => 'Work Orders',       'file' => 'production/work_orders.php'],
            ['name' => 'Bill of Materials', 'file' => 'production/bom.php'],
            ['name' => 'Schedules',         'file' => 'production/schedules.php'],
            ['name' => 'BOM',         'file' => 'production/bom.php'],
             ['name' => 'Prodcut Quantity',         'file' => 'production/bom.php'],
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
            ['name' => 'User Management',     'file' => 'sales/user_management.php'],
        ],
    ],
    'finance_hr' => [
        'name'     => 'Finance / HR',
        'icon'     => 'bi-cash-coin',
        'features' => [
            ['name' => 'Payroll',    'file' => 'finance/payroll.php'],
            ['name' => 'Expenses',   'file' => 'finance/expenses.php'],
            ['name' => 'Employees',  'file' => 'finance/employees.php'],
            ['name' => 'Attendance', 'file' => 'finance/attendance.php'],
        ],
    ],
];

// Detect current page (works from any folder depth)
$currentPage = str_replace('\\', '/', $_SERVER['PHP_SELF']);
$navId = 'adminSidebar_' . substr(md5($currentPage . microtime()), 0, 6);
?>

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
}
#<?= $navId ?> .sidebar-brand i {
    color: var(--amber);
    font-size: 18px;
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
    transition: background .2s ease, color .2s ease, border-color .2s ease;
}
#<?= $navId ?> .sidebar-menu .nav-link i:first-child {
    font-size: 15px;
    color: var(--text-muted);
    transition: color .2s ease;
}
#<?= $navId ?> .sidebar-menu .nav-link:hover {
    background: var(--maroon-card);
    color: #fff;
}
#<?= $navId ?> .sidebar-menu .nav-link:hover i:first-child {
    color: var(--amber);
}

#<?= $navId ?> .sidebar-menu .nav-link.active {
    background: linear-gradient(90deg, var(--maroon-dark) 0%, var(--maroon-card) 100%);
    color: #fff;
    border-left-color: var(--amber);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .35);
}
#<?= $navId ?> .sidebar-menu .nav-link.active i:first-child {
    color: var(--amber);
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
    transition: max-height .35s ease, padding .3s ease;
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
    transition: color .2s ease;
}
#<?= $navId ?> .submenu .sub-link:hover {
    background: rgba(232, 140, 46, .08);
    color: var(--off-white);
    padding-left: 16px;
}
#<?= $navId ?> .submenu .sub-link:hover i {
    color: var(--amber);
}
#<?= $navId ?> .submenu .sub-link.active {
    color: var(--amber);
    background: rgba(232, 140, 46, .1);
    font-weight: 800;
}
#<?= $navId ?> .submenu .sub-link.active i {
    color: var(--amber);
}

/* ---------- Sidebar footer + Logout button ---------- */
#<?= $navId ?> .sidebar-footer {
    flex-shrink: 0;
    margin-top: 20px;
    padding: 16px 22px 8px;
    border-top: 1px solid rgba(232, 140, 46, 0.15);
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
#<?= $navId ?> .logout-btn i {
    font-size: 15px;
    color: #ff8080;
    transition: color .2s ease;
}
#<?= $navId ?> .logout-btn:hover {
    background: var(--maroon-mid);
    color: #fff;
    border-left-color: #ff5555;
}
#<?= $navId ?> .logout-btn:hover i {
    color: #fff;
}
#<?= $navId ?> .logout-btn:active {
    transform: translateY(1px);
}

.admin-content {
    margin-left: 260px;
    padding: 40px 20px;
    max-width: 1100px;
}

@media (max-width: 768px) {
    #<?= $navId ?> { width: 220px; }
    .admin-content  { margin-left: 220px; padding: 24px 14px; }
}
</style>

<!-- ================= SIDEBAR HTML ================= -->
<aside id="<?= $navId ?>">
    <div class="sidebar-brand">
        <i class="bi bi-shield-lock"></i>
        <span>Admin Panel</span>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a class="nav-link <?= str_contains($currentPage, 'admin_dashboard') ? 'active' : '' ?>"
               href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <li class="sidebar-heading">Modules</li>

        <?php foreach ($adminModules as $slug => $mod): ?>
            <?php $isOpen = str_contains($currentPage, "/{$slug}/"); ?>
            <li>
                <a class="nav-link module-toggle <?= $isOpen ? 'active' : '' ?>"
                   href="#module-<?= $slug ?>-<?= $navId ?>"
                   data-target="module-<?= $slug ?>-<?= $navId ?>"
                   role="button"
                   aria-expanded="<?= $isOpen ? 'true' : 'false' ?>">
                    <i class="bi <?= $mod['icon'] ?>"></i>
                    <span><?= $mod['name'] ?></span>
                    <i class="bi bi-chevron-down ms-auto caret"></i>
                </a>

                <div class="submenu <?= $isOpen ? 'open' : '' ?>"
                     id="module-<?= $slug ?>-<?= $navId ?>">
                    <ul>
                        <?php foreach ($mod['features'] as $feature): ?>
                            <li>
                                <a class="sub-link <?= str_contains($currentPage, $feature['file']) ? 'active' : '' ?>"
                                   href="<?= $feature['file'] ?>">
                                    <i class="bi bi-dot"></i>
                                    <?= $feature['name'] ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- ---------- Sidebar footer: Logout ---------- -->
    <div class="sidebar-footer">
        <a class="logout-btn"
           href="/erp/auth/admin_logout.php"
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
})();
</script>