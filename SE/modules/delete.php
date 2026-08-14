<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$modules = require __DIR__ . '/../config/modules.php';
$moduleKey = $_GET['module'] ?? '';
$restrictedModules = ['events','registrations','venues','invitations','attendance','budget','program','media','feedback'];
$user = currentUser();
if (in_array($moduleKey, $restrictedModules, true) && $user && $user['role'] === 'student') {
    http_response_code(403);
    echo '<h2>403 - You do not have permission to view this page.</h2>';
    exit;
}
$id = $_GET['id'] ?? '';

if (!isset($modules[$moduleKey]) || $id === '') {
    http_response_code(404);
    exit('Unknown module or record.');
}

$cfg = $modules[$moduleKey];
$pdo = getDB();
$modalMode = !empty($_GET['modal']) || !empty($_POST['modal']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("DELETE FROM {$cfg['table']} WHERE {$cfg['primary_key']} = :id");
    $stmt->execute(['id' => $id]);

    if ($modalMode) {
        echo '<div class="alert alert-success">Record deleted successfully.</div>';
        echo '<div class="modal-actions"><button type="button" class="btn btn-secondary js-close-modal">Close</button></div>';
        exit;
    }

    header('Location: /modules/list.php?module=' . urlencode($moduleKey) . '&deleted=1');
    exit;
}

if ($modalMode) {
    echo '<div class="alert alert-error">Delete this record? This cannot be undone.</div>';
    echo '<form method="post" action="/modules/delete.php?module=' . urlencode($moduleKey) . '&id=' . urlencode($id) . '&modal=1">';
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
    echo '<input type="hidden" name="module" value="' . htmlspecialchars($moduleKey) . '">';
    echo '<input type="hidden" name="id" value="' . htmlspecialchars($id) . '">';
    echo '<div class="modal-actions"><button type="submit" class="btn btn-danger">Delete</button><button type="button" class="btn btn-secondary js-close-modal">Cancel</button></div>';
    echo '</form>';
    exit;
}

header('Location: /modules/list.php?module=' . urlencode($moduleKey) . '&deleted=1');
exit;
