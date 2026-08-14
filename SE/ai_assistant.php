<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/form_fields.php';
requireRole(['organizer', 'admin']);

$pdo = getDB();
$errors = [];
$results = [];
$assistantInput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid session token.';
    } else {
        $assistantInput = trim($_POST['assistant_input'] ?? '');
        if ($assistantInput === '') {
            $errors[] = 'Please enter a scheduling request.';
        } else {
            $lower = strtolower($assistantInput);
            $capacity = 0;
            $date = null;
            $timeOfDay = null;
            $resourceKeyword = 'venue';

            if (preg_match('/(\d+)\s+students?/i', $assistantInput, $m)) {
                $capacity = (int)$m[1];
            }
            if (preg_match('/(monday|tuesday|wednesday|thursday|friday|saturday|sunday)/i', $assistantInput, $m)) {
                $date = ucfirst($m[1]);
            }
            if (preg_match('/(morning|afternoon|evening)/i', $assistantInput, $m)) {
                $timeOfDay = ucfirst($m[1]);
            }
            if (preg_match('/(sound|projector|microphone|speaker|transportation|seating|venue)/i', $assistantInput, $m)) {
                $resourceKeyword = strtolower($m[1]);
            }

            $sql = 'SELECT * FROM venue_bookings WHERE status != :cancelled ORDER BY booking_date ASC, start_time ASC';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['cancelled' => 'Cancelled']);
            $bookings = $stmt->fetchAll();

            $results = [];
            foreach ($bookings as $booking) {
                $bookingDate = (string)($booking['booking_date'] ?? '');
                $bookingTime = (string)($booking['start_time'] ?? '');
                if ($date !== null && strtolower($bookingDate) !== strtolower($date)) {
                    continue;
                }
                if ($resourceKeyword !== '' && stripos((string)$booking['resource_name'], $resourceKeyword) === false && stripos((string)$booking['venue_name'], $resourceKeyword) === false) {
                    continue;
                }
                $results[] = [
                    'venue' => $booking['venue_name'],
                    'resource' => $booking['resource_name'],
                    'date' => $booking['booking_date'],
                    'time' => $booking['start_time'] . ' - ' . $booking['end_time'],
                    'status' => $booking['status'],
                ];
            }

            if (empty($results)) {
                $results[] = [
                    'venue' => 'No match',
                    'resource' => 'No suitable resource found',
                    'date' => '—',
                    'time' => '—',
                    'status' => 'Conflict',
                ];
            }
        }
    }
}

$pageTitle = 'NLP Scheduling Assistant';
$breadcrumbParent = 'School Event';
$breadcrumbCurrent = 'NLP Scheduling Assistant';
$activeModule = 'assistant';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="page-title-icon">🤖</div>
        <div>
            <h1>NLP Scheduling Assistant</h1>
            <p>Parse natural-language requests into scheduling suggestions and conflict checks.</p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-meta">Natural Language Request</div>
    <div style="padding:20px;">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <div class="form-group">
                <label for="assistant_input">Example: “I need a venue for 100 students on Friday afternoon.”</label>
                <textarea name="assistant_input" id="assistant_input" required><?= htmlspecialchars($assistantInput) ?></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Analyze Request</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-meta">Assistant Output</div>
    <?php if (empty($results)): ?>
        <div class="empty-state">Submit a scheduling request to see available options or conflicts.</div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Venue</th><th>Resource</th><th>Date</th><th>Time Slot</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($results as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['venue']) ?></td>
                <td><?= htmlspecialchars($row['resource']) ?></td>
                <td><?= htmlspecialchars($row['date']) ?></td>
                <td><?= htmlspecialchars($row['time']) ?></td>
                <td><?= renderBadge($row['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
