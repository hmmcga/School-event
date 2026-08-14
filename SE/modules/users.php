<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/form_fields.php';
requireRole(['admin']);

$pdo = getDB();
$errors = [];

// Create / update a user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid session token.';
    } else {
        $id       = $_POST['user_id'] ?? '';
        $fullName = trim($_POST['full_name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? 'student';
        $password = $_POST['password'] ?? '';

        if ($fullName === '' || $email === '') {
            $errors[] = 'Name and email are required.';
        }

        if (empty($errors)) {
            if ($id !== '') {
                if ($password !== '') {
                    $stmt = $pdo->prepare('UPDATE users SET full_name=:n, email=:e, role=:r, password_hash=:p WHERE user_id=:id');
                    $stmt->execute(['n' => $fullName, 'e' => $email, 'r' => $role,
                                     'p' => password_hash($password, PASSWORD_DEFAULT), 'id' => $id]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET full_name=:n, email=:e, role=:r WHERE user_id=:id');
                    $stmt->execute(['n' => $fullName, 'e' => $email, 'r' => $role, 'id' => $id]);
                }
            } else {
                if ($password === '') {
                    $errors[] = 'Password is required for new users.';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO users (full_name, email, password_hash, role) VALUES (:n,:e,:p,:r)');
                    $stmt->execute(['n' => $fullName, 'e' => $email,
                                     'p' => password_hash($password, PASSWORD_DEFAULT), 'r' => $role]);
                }
            }
            if (empty($errors)) {
                header('Location: /modules/users.php?saved=1');
                exit;
            }
        }
    }
}

if (($_GET['action'] ?? '') === 'toggle' && !empty($_GET['id'])) {
    $stmt = $pdo->prepare('UPDATE users SET is_active = NOT is_active WHERE user_id = :id');
    $stmt->execute(['id' => $_GET['id']]);
    header('Location: /modules/users.php');
    exit;
}

$editUser = null;
if (!empty($_GET['edit'])) {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = :id');
    $stmt->execute(['id' => $_GET['edit']]);
    $editUser = $stmt->fetch();
}

$users = $pdo->query('SELECT * FROM users ORDER BY created_at DESC')->fetchAll();

$pageTitle = 'User Access Control';
$breadcrumbParent = 'Administration';
$breadcrumbCurrent = 'User Access Control';
$activeModule = 'users';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="page-title-icon">&#128274;</div>
        <div>
            <h1>User Access Control</h1>
            <p>Manage accounts and role-based permissions (Student, Organizer, Admin)</p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
<?php endif; ?>

<div class="form-card" style="margin-bottom:24px;">
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <?php if ($editUser): ?><input type="hidden" name="user_id" value="<?= $editUser['user_id'] ?>"><?php endif; ?>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($editUser['full_name'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select name="role">
                <?php foreach (['student','organizer','admin'] as $r): ?>
                    <option value="<?= $r ?>" <?= ($editUser['role'] ?? '') === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Password <?= $editUser ? '(leave blank to keep current)' : '' ?></label>
            <input type="password" name="password">
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editUser ? 'Save Changes' : 'Create User' ?></button>
            <?php if ($editUser): ?><a href="/modules/users.php" class="btn btn-secondary">Cancel</a><?php endif; ?>
        </div>
    </form>
</div>

<div class="card">
    <table class="data-table">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                <td><?= $u['is_active'] ? renderBadge('Confirmed') : renderBadge('Cancelled') ?></td>
                <td class="actions-cell">
                    <a href="/modules/users.php?edit=<?= $u['user_id'] ?>" title="Edit">&#9998;</a>
                    <a href="/modules/users.php?action=toggle&id=<?= $u['user_id'] ?>" title="Enable/Disable">&#128274;</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
