/* ============================================================
   ADMIN NAVIGATION — mobile toggle + active link highlighting
   ============================================================ */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var toggle = document.getElementById('navToggle');
        var nav    = document.getElementById('topbarNav');

        // ---- Mobile hamburger toggle ----
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('open');
                toggle.classList.toggle('active');
            });
        }

        // ---- Auto-highlight current page ----
        var current = window.location.pathname.split('/').pop() || 'admin_dashboard.php';
        var links   = document.querySelectorAll('.topbar-nav .nav-link');

        links.forEach(function (link) {
            var href = link.getAttribute('href');
            if (!href) return;

            var target = href.split('/').pop().split('?')[0];

            if (target === current) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });

        // ---- Close mobile nav when clicking outside ----
        document.addEventListener('click', function (e) {
            if (!nav || !toggle) return;
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('open');
                toggle.classList.remove('active');
            }
        });
    });
})();