<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? 'student') === 'student' ? '/student_portal.php' : '/dashboard.php'));
    exit;
}

$pdo = getDB();
$stats = [
    'events' => (int) $pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(),
    'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'registrations' => (int) $pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn(),
];

$services = [
    [
        'title' => 'Library Management System',
        'description' => 'Manage library resources, borrowing, reservations, returns, and records in one organized workspace.',
        'icon' => '📚',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Clinic Management System',
        'description' => 'Support student health services with appointments, clinic visits, and wellness record coordination.',
        'icon' => '🩺',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Property Custodian Management System',
        'description' => 'Track assets, property issuance, supply requests, and maintenance workflows across campus operations.',
        'icon' => '🏛️',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'School Event Management System',
        'description' => 'Plan, organize, monitor, and evaluate school events with registration, attendance, budgets, and feedback.',
        'icon' => '🎓',
        'route' => '/modules/list.php?module=events',
        'note' => 'Live module',
    ],
    [
        'title' => 'OSAS',
        'description' => 'Support student services, safety programs, welfare operations, and student assistance coordination.',
        'icon' => '🤝',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Attendance Monitoring System',
        'description' => 'Monitor attendance, tardiness, absences, and related reports through a connected school workflow.',
        'icon' => '✅',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'PREFECT Disciplinary Action System',
        'description' => 'Organize disciplinary records, behavior monitoring, sanctions, and follow-up actions with clarity.',
        'icon' => '🛡️',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Academic Human Resource Management',
        'description' => 'Manage employee information, attendance, leave, training, and performance records in a secure portal.',
        'icon' => '👥',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Financial Management System',
        'description' => 'Coordinate budgets, expenses, revenues, procurement, and financial records across departments.',
        'icon' => '💰',
        'route' => null,
        'note' => 'Integrated service',
    ],
    [
        'title' => 'Alumni Management System',
        'description' => 'Maintain alumni records, engagement activities, graduate tracking, and alumni-focused communications.',
        'icon' => '🎉',
        'route' => null,
        'note' => 'Integrated service',
    ],
];

$pageTitle = 'St. Agnes Academy Student Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="public-home">
    <header class="site-nav">
        <div class="site-nav-inner">
            <a href="/" class="brand-group">
                <span class="brand-mark"><img src="/Pics%20for%20info/logo.jpg" alt="St. Agnes Academy logo"></span>
                <span class="brand-copy">
                    <strong>St. Agnes Academy</strong>
                    <small>Student Management System</small>
                </span>
            </a>

            <button type="button" class="menu-toggle" id="navToggle" aria-label="Toggle navigation">☰</button>

            <nav class="site-nav-links" id="navLinks">
                <a href="#overview">Overview</a>
                <a href="#services">Services</a>
                <a href="#events">School Events</a>
                <a href="#connect">How It Connects</a>
                <a href="#access">Access</a>
                <a href="/login.php" class="nav-login">Login</a>
            </nav>
        </div>
    </header>

    <main class="home-main">
        <section class="hero-panel-home">
            <div class="hero-content">
                <span class="hero-eyebrow">Centralized school operations</span>
                <h1>Welcome to St. Agnes Academy</h1>
                <p class="hero-subtitle">Student Management System</p>
                <p class="hero-description">Access and manage essential school services through one secure and connected platform designed for the St. Agnes Academy community.</p>
                <div class="hero-actions">
                    <a href="/login.php" class="btn btn-primary">Login to SMS</a>
                    <a href="#services" class="btn btn-secondary">Explore Services</a>
                </div>
            </div>

            <div class="hero-visual" aria-hidden="true">
                <div class="preview-shell">
                    <div class="preview-top">
                        <div class="preview-pill">Live school platform</div>
                        <div class="preview-dot"></div>
                    </div>
                    <div class="preview-grid">
                        <div class="preview-card preview-card-large">
                            <strong>Student Services</strong>
                            <span>Connected operations</span>
                        </div>
                        <div class="preview-card">
                            <strong>Events</strong>
                            <span>Planning</span>
                        </div>
                        <div class="preview-card">
                            <strong>Attendance</strong>
                            <span>Tracking</span>
                        </div>
                        <div class="preview-card preview-card-wide">
                            <strong>Reports</strong>
                            <span>Analytics</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section-shell" id="overview">
            <div class="section-heading">
                <span class="section-tag">System overview</span>
                <h2>One Platform for a Connected School</h2>
                <p>The Student Management System brings multiple school services together into one centralized ecosystem for students, staff, organizers, and administrators.</p>
            </div>

            <div class="overview-grid">
                <article class="overview-card">
                    <div class="overview-icon">🔗</div>
                    <h3>Centralized Services</h3>
                    <p>One secure place for school workflows, communication, and operations.</p>
                </article>
                <article class="overview-card">
                    <div class="overview-icon">🔐</div>
                    <h3>Secure Access</h3>
                    <p>Role-based access helps keep every service aligned with authorized users.</p>
                </article>
                <article class="overview-card">
                    <div class="overview-icon">⚡</div>
                    <h3>Real-Time Information</h3>
                    <p>Keep school programs, events, and records connected and up to date.</p>
                </article>
                <article class="overview-card">
                    <div class="overview-icon">🏫</div>
                    <h3>Connected Operations</h3>
                    <p>Support a more responsive and coordinated academic environment.</p>
                </article>
            </div>
        </section>

        <section class="section-shell" id="services">
            <div class="section-heading">
                <span class="section-tag">School services</span>
                <h2>Explore School Services</h2>
                <p>The SMS ecosystem connects major school functions into one professional digital environment.</p>
            </div>

            <div class="service-grid">
                <?php foreach ($services as $service): ?>
                    <article class="service-card">
                        <div class="service-icon"><?= htmlspecialchars($service['icon']) ?></div>
                        <div class="service-title-row">
                            <h3><?= htmlspecialchars($service['title']) ?></h3>
                            <span class="service-pill"><?= htmlspecialchars($service['note']) ?></span>
                        </div>
                        <p><?= htmlspecialchars($service['description']) ?></p>
                        <?php if (!empty($service['route'])): ?>
                            <a href="<?= htmlspecialchars($service['route']) ?>" class="service-link">Explore</a>
                        <?php else: ?>
                            <span class="service-link is-disabled">Integrated service</span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="section-shell feature-shell" id="events">
            <div class="feature-grid">
                <div>
                    <span class="section-tag">School event system</span>
                    <h2>School Events, Simplified</h2>
                    <p>The School Event Management System supports event planning and creation, participant registration, venue and resource scheduling, invitations, attendance tracking, budgets, program flow monitoring, documentation, feedback, and reports.</p>
                    <a href="/modules/list.php?module=events" class="btn btn-primary">Explore School Events</a>
                </div>
                <div class="feature-list">
                    <div class="feature-item">Event Planning &amp; Creation</div>
                    <div class="feature-item">Participant Registration</div>
                    <div class="feature-item">Venue &amp; Resource Scheduling</div>
                    <div class="feature-item">Invitation &amp; Communication</div>
                    <div class="feature-item">Attendance Tracking</div>
                    <div class="feature-item">Budget &amp; Expense Tracking</div>
                </div>
            </div>
        </section>

        <section class="section-shell" id="connect">
            <div class="section-heading">
                <span class="section-tag">Connected ecosystem</span>
                <h2>One School. One Connected Platform.</h2>
                <p>The system links student services, school operations, events, and administrative reporting into one dependable experience.</p>
            </div>

            <div class="flow-visual">
                <div class="flow-node">Students</div>
                <div class="flow-arrow">↓</div>
                <div class="flow-node">Student Management System</div>
                <div class="flow-arrow">↓</div>
                <div class="flow-node">School Services</div>
                <div class="flow-arrow">↓</div>
                <div class="flow-node">Events &amp; Attendance</div>
                <div class="flow-arrow">↓</div>
                <div class="flow-node">Library &amp; Clinic</div>
                <div class="flow-arrow">↓</div>
                <div class="flow-node">Reports &amp; Analytics</div>
            </div>
        </section>

        <section class="section-shell" id="access">
            <div class="section-heading">
                <span class="section-tag">Secure access</span>
                <h2>Secure Access for Every Role</h2>
                <p>System access is guided by authorized roles and permissions, creating a secure and organized experience for the school community.</p>
            </div>

            <div class="role-grid">
                <article class="role-card">
                    <h3>Student</h3>
                    <p>Access student-facing services and event participation tools.</p>
                </article>
                <article class="role-card">
                    <h3>Faculty / Staff</h3>
                    <p>Coordinate school services and school-wide operations.</p>
                </article>
                <article class="role-card">
                    <h3>Event Organizer</h3>
                    <p>Manage events, schedules, communications, and logistics.</p>
                </article>
                <article class="role-card">
                    <h3>Administrator</h3>
                    <p>Oversee system-wide dashboards, access, and reporting.</p>
                </article>
            </div>
        </section>

        <section class="section-shell">
            <div class="section-heading">
                <span class="section-tag">Reliable system activity</span>
                <h2>Connected School Operations at a Glance</h2>
            </div>
            <div class="stats-grid">
                <article class="stat-card-home">
                    <strong><?= number_format($stats['events']) ?></strong>
                    <span>School events on record</span>
                </article>
                <article class="stat-card-home">
                    <strong><?= number_format($stats['users']) ?></strong>
                    <span>Connected system users</span>
                </article>
                <article class="stat-card-home">
                    <strong><?= number_format($stats['registrations']) ?></strong>
                    <span>Registered participants</span>
                </article>
            </div>
        </section>

        <section class="cta-banner">
            <h2>Ready to access the St. Agnes Academy Student Management System?</h2>
            <p>Sign in to explore the services and resources available to you.</p>
            <a href="/login.php" class="btn btn-primary">Login to SMS</a>
        </section>
    </main>

    <footer class="footer-shell">
        <div>
            <strong>St. Agnes Academy</strong>
            <p>Student Management System</p>
        </div>
        <div class="footer-links">
            <a href="#">Home</a>
            <a href="#services">Services</a>
            <a href="#events">School Events</a>
            <a href="/login.php">Login</a>
        </div>
        <div>
            <p>© St. Agnes Academy. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const navToggle = document.getElementById('navToggle');
            const navLinks = document.getElementById('navLinks');
            if (!navToggle || !navLinks) return;

            navToggle.addEventListener('click', function () {
                navLinks.classList.toggle('is-open');
            });
        });
    </script>
</body>
</html>
