<?php
/** Expects $activeModule to be set by the including page (string key or 'dashboard') */
$activeModule = $activeModule ?? '';
$user = currentUser();

function navLink(string $key, string $href, string $icon, string $label, string $active): void
{
    $cls = $active === $key ? 'active' : '';
    echo '<a href="' . htmlspecialchars($href) . '" class="' . $cls . '">'
       . '<span class="icon">' . $icon . '</span><span>' . htmlspecialchars($label) . '</span></a>';
}
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <img src="/Pics%20for%20info/logo.jpg" alt="School logo" class="brand-logo">
        <div>
            <div>School Event</div>
            <small>St. Agnes Academy of Caloocan, Inc.</small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php navLink('dashboard', '/dashboard.php', '&#9632;', 'Dashboard', $activeModule); ?>

        <?php if ($user && $user['role'] !== 'student'): ?>
            <div class="sidebar-section">Event Management</div>
            <?php
            navLink('events', '/modules/list.php?module=events', '&#128197;', 'Event Planning & Creation', $activeModule);
            navLink('registrations', '/modules/list.php?module=registrations', '&#128100;', 'Participant Registration', $activeModule);
            navLink('venues', '/modules/list.php?module=venues', '&#127968;', 'Venue & Resource Scheduling', $activeModule);
            navLink('invitations', '/modules/list.php?module=invitations', '&#128231;', 'Invitation & Communication', $activeModule);
            ?>
        <?php else: ?>
            <div class="sidebar-section">Student Workspace</div>
            <?php navLink('student', '/student_portal.php', '&#127891;', 'Student Portal', $activeModule); ?>
        <?php endif; ?>

        <div class="sidebar-section">Monitoring & Engagement</div>
        <?php
        if ($user && $user['role'] !== 'student') {
            navLink('attendance', '/modules/list.php?module=attendance', '&#9989;', 'Attendance Tracking', $activeModule);
            navLink('budget', '/modules/list.php?module=budget', '&#128176;', 'Budget & Expense Tracking', $activeModule);
            navLink('program', '/modules/list.php?module=program', '&#9201;', 'Program Flow Monitoring', $activeModule);
            navLink('media', '/modules/list.php?module=media', '&#127916;', 'Multimedia & Documentation', $activeModule);
            navLink('feedback', '/modules/list.php?module=feedback', '&#11088;', 'Feedback & Evaluation', $activeModule);
            navLink('assistant', '/ai_assistant.php', '&#129302;', 'NLP Scheduling Assistant', $activeModule);
        } else {
            navLink('student', '/student_portal.php', '&#127891;', 'Student Portal', $activeModule);
        }
        ?>

        <div class="sidebar-section">Administration</div>
        <?php
        navLink('reports', '/modules/reports.php', '&#128202;', 'Event Report & Analytics', $activeModule);
        if ($user && $user['role'] === 'admin') {
            navLink('users', '/modules/users.php', '&#128274;', 'User Access Control', $activeModule);
        }
        ?>
    </nav>
</aside>
