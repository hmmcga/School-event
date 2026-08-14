<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/form_fields.php';
requireLogin();

$modules = require __DIR__ . '/../config/modules.php';
$moduleKey = $_GET['module'] ?? $_POST['module'] ?? '';
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
$pk  = $cfg['primary_key'];
$modalMode = !empty($_GET['modal']) || !empty($_POST['modal']);

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$isEdit = $id !== null && $id !== '';
$record = array_fill_keys(array_keys($cfg['fields']), '');
$errors = [];

if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM {$cfg['table']} WHERE $pk = :id");
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();
    if (!$found) {
        exit('Record not found.');
    }
    $record = array_merge($record, $found);
}

// ---------------------------------------------------------------
// Handle submit
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid session token. Please try again.';
    }

    // Field types whose empty string must become NULL (DB column is not text)
    $nullableTypes = ['date', 'time', 'datetime', 'number', 'event_select'];

    foreach ($cfg['fields'] as $name => $fieldCfg) {
        $val = trim($_POST[$name] ?? '');
        if (!empty($fieldCfg['required']) && $val === '') {
            $errors[] = "{$fieldCfg['label']} is required.";
        }
        if ($val === '' && in_array($fieldCfg['type'], $nullableTypes, true)) {
            $record[$name] = null;
        } else {
            $record[$name] = $val;
        }

        if ($fieldCfg['type'] === 'date' && in_array($name, ['start_date', 'end_date', 'booking_date'], true) && $record[$name] !== null && $record[$name] !== '') {
            $selectedDate = date('Y-m-d', strtotime($record[$name]));
            $today = date('Y-m-d');
            if ($selectedDate < $today) {
                $errors[] = ucfirst(str_replace('_', ' ', $name)) . ' cannot be set to a past date.';
            }
        }
    }

    // Venue & Resource Scheduling: overlap detection for the same venue/date
    if (empty($errors) && !empty($cfg['conflict_check'])) {
        $sql = "SELECT booking_id FROM venue_bookings
                WHERE venue_name = :venue AND booking_date = :date
                  AND status != 'Cancelled'
                  AND start_time < :end_time AND end_time > :start_time";
        $params = [
            'venue' => $record['venue_name'],
            'date' => $record['booking_date'],
            'start_time' => $record['start_time'],
            'end_time' => $record['end_time'],
        ];
        if ($isEdit) {
            $sql .= " AND booking_id != :id";
            $params['id'] = $id;
        }
        $conflictStmt = $pdo->prepare($sql);
        $conflictStmt->execute($params);
        if ($conflictStmt->fetch()) {
            $errors[] = 'Scheduling conflict: this venue is already booked for an overlapping time on that date.';
        }
    }

    if (empty($errors)) {
        $columns = array_keys($cfg['fields']);

        if ($isEdit) {
            $setSql = implode(', ', array_map(fn($c) => "$c = :$c", $columns));
            $stmt = $pdo->prepare("UPDATE {$cfg['table']} SET $setSql WHERE $pk = :id");
            $execParams = array_intersect_key($record, array_flip($columns));
            $execParams['id'] = $id;
            $stmt->execute($execParams);
        } else {
            $colSql = implode(', ', $columns);
            $placeholders = implode(', ', array_map(fn($c) => ":$c", $columns));
            $stmt = $pdo->prepare("INSERT INTO {$cfg['table']} ($colSql) VALUES ($placeholders)");
            $stmt->execute(array_intersect_key($record, array_flip($columns)));
        }

        if ($modalMode) {
            echo '<div class="alert alert-success">Record saved successfully.</div>';
            echo '<div class="modal-actions"><button type="button" class="btn btn-secondary js-close-modal">Close</button></div>';
            exit;
        }

        header('Location: /modules/list.php?module=' . urlencode($moduleKey) . '&saved=1');
        exit;
    }
}

if ($modalMode) {
    echo '<div class="modal-form-shell">';
} else {
    $pageTitle = ($isEdit ? 'Edit' : 'Add') . ' - ' . $cfg['label'];
    $breadcrumbParent = 'School Event';
    $breadcrumbCurrent = $cfg['label'];
    $activeModule = $moduleKey;
    include __DIR__ . '/../includes/header.php';
}
?>

<div class="page-header module-page-header">
    <div class="page-title">
        <div class="page-title-icon"><?= $isEdit ? '&#9998;' : '&#10133;' ?></div>
        <div>
            <h1><?= $isEdit ? 'Edit Record' : 'Add New Record' ?></h1>
            <p><?= htmlspecialchars($cfg['label']) ?></p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
    </div>
<?php endif; ?>

<div class="form-card">
    <form method="post" action="/modules/form.php?module=<?= urlencode($moduleKey) ?><?= $isEdit ? '&id=' . $id : '' ?><?= $modalMode ? '&modal=1' : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="module" value="<?= htmlspecialchars($moduleKey) ?>">
        <?php if ($isEdit): ?><input type="hidden" name="id" value="<?= htmlspecialchars($id) ?>"><?php endif; ?>

        <?php foreach ($cfg['fields'] as $name => $fieldCfg): ?>
            <?php renderField($name, $fieldCfg, $record[$name], $pdo); ?>
        <?php endforeach; ?>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Save Changes' : 'Create Record' ?></button>
            <?php if ($modalMode): ?>
                <button type="button" class="btn btn-secondary js-close-modal">Cancel</button>
            <?php else: ?>
                <a href="/modules/list.php?module=<?= urlencode($moduleKey) ?>" class="btn btn-secondary">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if ($modalMode) {
    echo '</div>';
} else {
    include __DIR__ . '/../includes/footer.php';
}
?>
