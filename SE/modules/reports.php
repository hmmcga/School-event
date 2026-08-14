<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/form_fields.php';
requireLogin();

$pdo = getDB();
$events = $pdo->query('SELECT * FROM events ORDER BY start_date DESC')->fetchAll();

$eventId = $_GET['event_id'] ?? ($events[0]['event_id'] ?? null);
$selected = null;
$stats = [
    'registrations' => 0, 'attended' => 0,
    'budget_total' => 0, 'expense_total' => 0,
    'avg_rating' => null, 'feedback_count' => 0,
    'sentiment' => ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0],
];

if ($eventId) {
    $stmt = $pdo->prepare('SELECT * FROM events WHERE event_id = :id');
    $stmt->execute(['id' => $eventId]);
    $selected = $stmt->fetch();

    if ($selected) {
        $regStmt = $pdo->prepare('SELECT COUNT(*) c FROM registrations WHERE event_id = :id');
        $regStmt->execute(['id' => $eventId]);
        $stats['registrations'] = (int)$regStmt->fetch()['c'];

        $attStmt = $pdo->prepare("SELECT COUNT(*) c FROM registrations WHERE event_id = :id AND status = 'Attended'");
        $attStmt->execute(['id' => $eventId]);
        $stats['attended'] = (int)$attStmt->fetch()['c'];

        $budgetStmt = $pdo->prepare("SELECT
                COALESCE(SUM(CASE WHEN entry_type = 'Budget' THEN amount END), 0) AS budget_total,
                COALESCE(SUM(CASE WHEN entry_type = 'Expense' THEN amount END), 0) AS expense_total
            FROM budget_entries WHERE event_id = :id");
        $budgetStmt->execute(['id' => $eventId]);
        $budgetRow = $budgetStmt->fetch();
        $stats['budget_total'] = (float)$budgetRow['budget_total'];
        $stats['expense_total'] = (float)$budgetRow['expense_total'];

        $fbStmt = $pdo->prepare('SELECT COUNT(*) c, AVG(rating) avg_rating FROM feedback_entries WHERE event_id = :id');
        $fbStmt->execute(['id' => $eventId]);
        $fb = $fbStmt->fetch();
        $stats['feedback_count'] = (int)$fb['c'];
        $stats['avg_rating'] = $fb['avg_rating'] !== null ? round((float)$fb['avg_rating'], 2) : null;

        $sentStmt = $pdo->prepare("SELECT sentiment, COUNT(*) c FROM feedback_entries
                                  WHERE event_id = :id AND sentiment IS NOT NULL
                                  GROUP BY sentiment");
        $sentStmt->execute(['id' => $eventId]);
        $sentRows = $sentStmt->fetchAll();
        foreach ($sentRows as $r) {
            $stats['sentiment'][$r['sentiment']] = (int)$r['c'];
        }
    }
}

$pageTitle = 'Event Report & Analytics';
$breadcrumbParent = 'School Event';
$breadcrumbCurrent = 'Event Report & Analytics';
$activeModule = 'reports';
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="page-title-icon">&#128202;</div>
        <div>
            <h1>Event Report & Analytics</h1>
            <p>Attendance, budget utilization, and AI-based feedback sentiment per event</p>
        </div>
    </div>
    <div class="page-actions">
        <form method="get" class="search-box" style="min-width:260px;">
            <select name="event_id" onchange="this.form.submit()" style="border:none;background:transparent;width:100%;">
                <?php foreach ($events as $ev): ?>
                    <option value="<?= $ev['event_id'] ?>" <?= (string)$ev['event_id'] === (string)$eventId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($ev['event_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
</div>

<?php if (!$selected): ?>
    <div class="card"><div class="empty-state">No events found. Create an event first.</div></div>
<?php else: ?>

<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-label">Registrations</div>
        <div class="stat-value"><?= $stats['registrations'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Attended</div>
        <div class="stat-value"><?= $stats['attended'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Budget Allocated</div>
        <div class="stat-value">&#8369;<?= number_format($stats['budget_total'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Expenses Incurred</div>
        <div class="stat-value">&#8369;<?= number_format($stats['expense_total'], 2) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg. Feedback Rating</div>
        <div class="stat-value"><?= $stats['avg_rating'] ?? '—' ?> / 5</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Feedback Responses</div>
        <div class="stat-value"><?= $stats['feedback_count'] ?></div>
    </div>
</div>

<div class="card">
    <div class="card-meta">AI Sentiment Breakdown &mdash; <?= htmlspecialchars($selected['event_name']) ?></div>
    <table class="data-table">
        <thead><tr><th>Sentiment</th><th>Responses</th></tr></thead>
        <tbody>
            <tr><td><?= renderBadge('Positive') ?></td><td><?= $stats['sentiment']['Positive'] ?></td></tr>
            <tr><td><?= renderBadge('Neutral') ?></td><td><?= $stats['sentiment']['Neutral'] ?></td></tr>
            <tr><td><?= renderBadge('Negative') ?></td><td><?= $stats['sentiment']['Negative'] ?></td></tr>
        </tbody>
    </table>
</div>
<p style="font-size:12px;color:var(--text-muted);margin-top:10px;">
    Sentiment values are written by the NLP assistant service described in Chapter 3 of the manuscript;
    this page only aggregates and displays results already stored in <code>feedback_entries.sentiment</code>.
</p>

<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
