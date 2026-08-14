<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/form_fields.php';
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

if (!isset($modules[$moduleKey])) {
    http_response_code(404);
    exit('Unknown module.');
}
$cfg = $modules[$moduleKey];
$pdo = getDB();

// Optional search across list columns
$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $likeParts = [];
    foreach (array_keys($cfg['list_columns']) as $col) {
        $likeParts[] = "LOWER(COALESCE($col, '')) LIKE LOWER(:q)";
    }
    $where = 'WHERE ' . implode(' OR ', $likeParts);
    $params['q'] = '%' . $search . '%';
}

$sql = "SELECT * FROM {$cfg['table']} $where ORDER BY {$cfg['order_by']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$pageTitle = $cfg['label'];
$breadcrumbParent = 'School Event';
$breadcrumbCurrent = $cfg['label'];
$activeModule = $moduleKey;
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header module-page-header">
    <div class="page-title">
        <div class="page-title-icon">&#128203;</div>
        <div>
            <h1><?= htmlspecialchars($cfg['label']) ?></h1>
            <p><?= count($rows) ?> record<?= count($rows) === 1 ? '' : 's' ?> on file</p>
        </div>
    </div>
    <div class="page-actions">
        <form class="search-box" method="get">
            <input type="hidden" name="module" value="<?= htmlspecialchars($moduleKey) ?>">
            <span>&#128269;</span>
            <input type="text" name="q" placeholder="Search records..." value="<?= htmlspecialchars($search) ?>">
        </form>
        <a class="btn btn-primary open-modal-link" href="#" data-modal-url="/modules/form.php?module=<?= urlencode($moduleKey) ?>&modal=1">+ Add New</a>
    </div>
</div>

<div class="modal-overlay" id="modalOverlay" aria-hidden="true">
    <div class="modal-card">
        <button type="button" class="modal-close js-close-modal" aria-label="Close">&times;</button>
        <div class="modal-body" id="modalBody"></div>
    </div>
</div>

<div class="card">
    <?php if (empty($rows)): ?>
        <div class="empty-state">No records found. Click "+ Add New" to create one.</div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr>
                <?php foreach ($cfg['list_columns'] as $label): ?>
                    <th><?= htmlspecialchars($label) ?></th>
                <?php endforeach; ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <?php foreach (array_keys($cfg['list_columns']) as $col): ?>
                    <td>
                        <?php
                        $val = $row[$col] ?? '';
                        $fieldCfg = $cfg['fields'][$col] ?? null;
                        if ($fieldCfg && $fieldCfg['type'] === 'select') {
                            echo renderBadge($val);
                        } else {
                            echo htmlspecialchars((string)$val);
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                <td class="actions-cell">
                    <a href="#" class="open-modal-link" data-modal-url="/modules/form.php?module=<?= urlencode($moduleKey) ?>&id=<?= $row[$cfg['primary_key']] ?>&modal=1" title="Edit">&#9998;</a>
                    <a href="#" class="danger open-delete-link" data-delete-url="/modules/delete.php?module=<?= urlencode($moduleKey) ?>&id=<?= $row[$cfg['primary_key']] ?>&modal=1" title="Delete">&#128465;</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('modalOverlay');
        const modalBody = document.getElementById('modalBody');
        const closeModal = () => {
            overlay.classList.remove('is-open');
            modalBody.innerHTML = '';
        };

        document.addEventListener('click', function (event) {
            const openLink = event.target.closest('.open-modal-link');
            if (openLink) {
                event.preventDefault();
                const url = openLink.getAttribute('data-modal-url');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.text())
                    .then((html) => {
                        modalBody.innerHTML = html;
                        overlay.classList.add('is-open');
                    });
                return;
            }

            const deleteLink = event.target.closest('.open-delete-link');
            if (deleteLink) {
                event.preventDefault();
                const url = deleteLink.getAttribute('data-delete-url');
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then((response) => response.text())
                    .then((html) => {
                        modalBody.innerHTML = html;
                        overlay.classList.add('is-open');
                    });
            }
        });

        document.addEventListener('click', function (event) {
            if (event.target.classList.contains('js-close-modal') || event.target === overlay) {
                closeModal();
            }
        });

        document.addEventListener('submit', function (event) {
            const form = event.target.closest('#modalBody form');
            if (!form) return;

            event.preventDefault();
            const formData = new FormData(form);
            formData.append('modal', '1');
            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then((response) => response.text())
                .then((html) => {
                    modalBody.innerHTML = html;
                    if (html.includes('alert-success')) {
                        setTimeout(() => window.location.reload(), 500);
                    }
                });
        });
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
