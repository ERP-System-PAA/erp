<?php
require_once __DIR__ . '/include/config.php';

// If not logged in, go to login
if (!is_logged_in()) {
    header("Location: auth/login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PAA ERP — Philippines Alcohol Association</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/index.css">
</head>
<body>

<!-- =========================================
     NAVBAR
     ========================================= -->
<nav class="navbar navbar-expand-lg paa-navbar">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <img src="logo.jpg" alt="PAA Logo" class="brand-logo">
            <div>
                PAA ERP
                <small>Philippines Alcohol Association</small>
            </div>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMenu">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
                <li class="nav-item"><a class="nav-link" href="#mission">Mission & Vision</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center mt-3 mt-lg-0">
                <a href="auth/log_out.php" class="btn btn-outline-brand">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- =========================================
     HERO SECTION
     ========================================= -->
<section class="hero" id="home">
    <div class="container hero-content">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="hero-badge">Official ERP Portal</span>
                <h1>Welcome to <span>PAA ERP</span>.</h1>
                <p class="lead">
                    The Philippines Alcohol Association's unified ERP platform — connecting members,
                    streamlining operations, and driving excellence across the Philippine alcohol industry.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="#services" class="btn btn-amber">
                        <i class="bi bi-grid-1x2 me-2"></i>Explore Modules
                    </a>
                    <a href="#about" class="btn btn-ghost">
                        <i class="bi bi-info-circle me-2"></i>Learn About PAA
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     STATS BAR
     ========================================= -->
<section class="stats-bar">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <i class="bi bi-people-fill stat-icon"></i>
                    <h3>250+</h3>
                    <p>Member Companies</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <i class="bi bi-building stat-icon"></i>
                    <h3>17</h3>
                    <p>Regions Covered</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <i class="bi bi-award-fill stat-icon"></i>
                    <h3>30+</h3>
                    <p>Years of Service</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <i class="bi bi-graph-up-arrow stat-icon"></i>
                    <h3>98%</h3>
                    <p>Member Satisfaction</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     ABOUT SECTION
     ========================================= -->
<section class="section" id="about">
    <div class="container">
        <div class="text-center mb-5">
            <div class="divider-accent"></div>
            <h2 class="section-title">About PAA</h2>
            <p class="section-subtitle">
                The Philippines Alcohol Association is the nation's leading organization representing
                the interests of the alcohol and beverage industry.
            </p>
        </div>
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <img src="https://images.unsplash.com/photo-1514933651103-005eec06c04b?auto=format&fit=crop&w=900&q=80"
                     alt="PAA Industry" class="about-img">
            </div>
            <div class="col-lg-6">
                <div class="about-text">
                    <h3>Who We Are</h3>
                    <p>
                        Founded to unite stakeholders across the Philippine alcohol industry, the
                        Philippines Alcohol Association (PAA) serves as the voice of producers,
                        distributors, retailers, and allied businesses. We champion responsible
                        consumption, regulatory excellence, and sustainable industry growth.
                    </p>
                    <p>
                        Through our ERP platform, we empower members with the digital tools
                        they need to manage operations, ensure compliance, and collaborate
                        with partners nationwide.
                    </p>
                    <ul class="about-list">
                        <li><i class="bi bi-check-circle-fill"></i> Unified membership & compliance management</li>
                        <li><i class="bi bi-check-circle-fill"></i> Real-time inventory & distribution tracking</li>
                        <li><i class="bi bi-check-circle-fill"></i> Regulatory reporting made simple</li>
                        <li><i class="bi bi-check-circle-fill"></i> Nationwide member collaboration network</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     SERVICES / FEATURES
     ========================================= -->
<section class="section" id="services" style="background:#fafafa;">
    <div class="container">
        <div class="text-center mb-5">
            <div class="divider-accent"></div>
            <h2 class="section-title">What Our ERP Offers</h2>
            <p class="section-subtitle">
                A comprehensive suite of modules built specifically for the alcohol industry's
                unique operational and regulatory needs.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-people"></i></div>
                    <h5>Membership Management</h5>
                    <p>Centralized member database with profiles, dues tracking, and renewal automation.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                    <h5>Inventory & Distribution</h5>
                    <p>Track products from production to point-of-sale with real-time stock visibility.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-file-earmark-text"></i></div>
                    <h5>Compliance & Reporting</h5>
                    <p>Automated regulatory reports for BIR, FDA, and local government requirements.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-cash-coin"></i></div>
                    <h5>Finance & Billing</h5>
                    <p>Integrated invoicing, payments, and financial analytics for member transactions.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-bar-chart-line"></i></div>
                    <h5>Analytics Dashboard</h5>
                    <p>Actionable insights on industry trends, sales performance, and member activity.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                    <h5>Secure & Auditable</h5>
                    <p>Role-based access, audit trails, and encrypted data — built for enterprise trust.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     MISSION & VISION
     ========================================= -->
<section class="section mv-section" id="mission">
    <div class="container">
        <div class="text-center mb-5">
            <div class="divider-accent"></div>
            <h2 class="section-title">Our Mission & Vision</h2>
            <p class="section-subtitle">
                Guided by purpose, driven by progress — the values that shape everything we do.
            </p>
        </div>
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="mv-card">
                    <h4><i class="bi bi-bullseye"></i> Our Mission</h4>
                    <p>
                        To advance the Philippine alcohol industry by fostering responsible practices,
                        supporting sound regulation, and providing members with world-class tools and
                        resources that enable sustainable growth and operational excellence.
                    </p>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="mv-card vision">
                    <h4><i class="bi bi-eye"></i> Our Vision</h4>
                    <p>
                        A thriving, globally competitive Philippine alcohol industry — recognized for
                        its integrity, innovation, and contribution to national economic development
                        and community well-being.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================
     CTA SECTION
     ========================================= -->
<section class="cta-section" id="contact">
    <div class="container">
        <h2>Ready to explore the ERP?</h2>
        <p>
            Access your dashboard, manage your operations, and connect with the PAA network —
            all in one place.
        </p>
        <a href="#" class="btn btn-amber">
            <i class="bi bi-speedometer2 me-2"></i>Go to Dashboard
        </a>
    </div>
</section>

<!-- =========================================
     FOOTER
     ========================================= -->
<footer class="paa-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <h6><i class="bi bi-building me-2"></i>PAA ERP</h6>
                <p class="small" style="line-height:1.7;">
                    The official enterprise resource planning platform of the Philippines Alcohol
                    Association — empowering the industry through digital transformation.
                </p>
                <div class="social-icons mt-3">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                    <a href="#"><i class="bi bi-envelope"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-6">
                <h6>Quick Links</h6>
                <a href="#home">Home</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
                <a href="#mission">Mission & Vision</a>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6>Member Resources</h6>
                <a href="#">Member Directory</a>
                <a href="#">Industry Reports</a>
                <a href="#">Compliance Guides</a>
                <a href="#">Events & Trainings</a>
            </div>
            <div class="col-lg-3 col-md-6">
                <h6>Contact Us</h6>
                <p class="small mb-2"><i class="bi bi-geo-alt me-2"></i>Metro Manila, Philippines</p>
                <p class="small mb-2"><i class="bi bi-telephone me-2"></i>+63 (2) 8123-4567</p>
                <p class="small mb-2"><i class="bi bi-envelope me-2"></i>info@paa.org.ph</p>
            </div>
        </div>
        <div class="footer-bottom text-center">
            &copy; <?= date('Y') ?> Philippines Alcohol Association. All rights reserved.
            <span class="mx-2">|</span> ERP System v1.0
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Smooth scroll for internal anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
</script>

</body>
</html>