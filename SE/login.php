<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    $user = currentUser();
    header('Location: ' . (($user['role'] ?? 'student') === 'student' ? '/student_portal.php' : '/dashboard.php'));
    exit;
}

$error = '';
$loading = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo = getDB();
    $loading = true;

    if (login($pdo, $email, $password)) {
        $user = currentUser();
        header('Location: ' . (($user['role'] ?? 'student') === 'student' ? '/student_portal.php' : '/dashboard.php'));
        exit;
    }
    $error = 'Unable to sign in. Please check your credentials and try again.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - St. Agnes Academy SMS</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-shell">
        <div class="login-brand-panel">
            <div class="login-brand-content">
                <div class="brand-mark login-brand-mark">
                    <img src="/Pics%20for%20info/logo.jpg" alt="St. Agnes Academy logo">
                </div>
                <h1>St. Agnes Academy</h1>
                <p class="login-brand-title">Student Management System</p>
                <p class="login-brand-copy">Secure access to the digital services and connected school community of St. Agnes Academy.</p>

                <div class="login-visual" aria-hidden="true">
                    <div class="login-visual-card">
                        <div class="login-visual-ring"></div>
                        <div class="login-visual-badge">One Platform</div>
                        <div class="login-visual-grid">
                            <span>Events</span>
                            <span>Attendance</span>
                            <span>Services</span>
                            <span>Reports</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-card">
                <a href="/" class="back-home-link">← Back to Homepage</a>
                <div class="login-form-header">
                    <span class="section-tag">Secure entry</span>
                    <h2>Welcome Back</h2>
                    <p>Sign in to access the St. Agnes Academy Student Management System.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-error" role="alert"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form" id="loginForm">
                    <div class="form-group login-form-group">
                        <label for="email">Username / School ID</label>
                        <div class="input-with-icon">
                            <span aria-hidden="true">👤</span>
                            <input type="email" id="email" name="email" placeholder="Enter your email address" required autofocus>
                        </div>
                    </div>

                    <div class="form-group login-form-group">
                        <label for="password">Password</label>
                        <div class="input-with-icon">
                            <span aria-hidden="true">🔒</span>
                            <input type="password" id="password" name="password" placeholder="Enter your password" required>
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Show password">👁</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary login-submit" id="loginButton">
                        <span class="btn-label">Sign In</span>
                        <span class="btn-loading" aria-hidden="true">Signing in...</span>
                    </button>
                </form>

                <div class="login-footer-links">
                    <a href="/">Back to Homepage</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            const form = document.getElementById('loginForm');
            const button = document.getElementById('loginButton');
            const label = button.querySelector('.btn-label');
            const loading = button.querySelector('.btn-loading');

            if (toggle && passwordInput) {
                toggle.addEventListener('click', function () {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    toggle.textContent = isPassword ? '🙈' : '👁';
                    toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                });
            }

            if (form && button) {
                form.addEventListener('submit', function () {
                    button.classList.add('is-loading');
                    label.style.display = 'none';
                    loading.style.display = 'inline-flex';
                    button.disabled = true;
                });
            }
        });
    </script>
</body>
</html>
