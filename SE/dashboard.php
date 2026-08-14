<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/form_fields.php';
requireLogin();

$user = currentUser();
if ($user && $user['role'] === 'student') {
    header('Location: /student_portal.php');
    exit;
}

$pdo = getDB();

function extractFeedbackThemes(array $feedbackRows): array
{
    $themes = [
        'organization' => ['organized', 'organization', 'arrangement', 'management', 'planning'],
        'timing' => ['time', 'timing', 'schedule', 'late', 'delay', 'punctual'],
        'venue' => ['venue', 'location', 'hall', 'room', 'gym', 'auditorium'],
        'communication' => ['communication', 'announcement', 'info', 'reminder', 'informed'],
        'facilities' => ['sound', 'projector', 'mic', 'microphone', 'equipment', 'wifi'],
        'food' => ['food', 'snack', 'meal', 'catering'],
        'staff' => ['staff', 'host', 'speaker', 'volunteer', 'facilitator'],
        'program' => ['program', 'activity', 'session', 'agenda', 'flow'],
    ];

    $counts = [];
    foreach ($feedbackRows as $row) {
        $comment = strtolower((string)($row['comments'] ?? ''));
        foreach ($themes as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($comment, $keyword)) {
                    $counts[$theme] = ($counts[$theme] ?? 0) + 1;
                    break;
                }
            }
        }
    }

    arsort($counts);
    return array_slice($counts, 0, 5, true);
}

$totalEvents      = (int)$pdo->query("SELECT COUNT(*) c FROM events")->fetch()['c'];
$upcomingEvents   = (int)$pdo->query("SELECT COUNT(*) c FROM events WHERE start_date >= CURRENT_DATE AND status != 'Cancelled'")->fetch()['c'];
$totalRegistrants = (int)$pdo->query("SELECT COUNT(*) c FROM registrations")->fetch()['c'];
$pendingFeedback  = (int)$pdo->query("SELECT COUNT(*) c FROM feedback_entries WHERE sentiment IS NULL")->fetch()['c'];
$venueBookings    = (int)$pdo->query("SELECT COUNT(*) c FROM venue_bookings")->fetch()['c'];
$attendanceRecords = (int)$pdo->query("SELECT COUNT(*) c FROM attendance_records")->fetch()['c'];
$budgetEntries    = (int)$pdo->query("SELECT COUNT(*) c FROM budget_entries")->fetch()['c'];
$mediaFiles       = (int)$pdo->query("SELECT COUNT(*) c FROM media_files")->fetch()['c'];
$invitations      = (int)$pdo->query("SELECT COUNT(*) c FROM invitations")->fetch()['c'];

$recentEvents = $pdo->query("SELECT * FROM events ORDER BY start_date DESC LIMIT 5")->fetchAll();
$eventRows = $pdo->query("SELECT event_id, event_name, start_date, status FROM events ORDER BY start_date ASC, event_id ASC")->fetchAll();
$registrationRows = $pdo->query("SELECT event_id, status FROM registrations")->fetchAll();
$attendanceRows = $pdo->query("SELECT event_id FROM attendance_records")->fetchAll();
$budgetRows = $pdo->query("SELECT event_id, entry_type, amount FROM budget_entries")->fetchAll();
$feedbackRows = $pdo->query("SELECT event_id, rating, comments, sentiment FROM feedback_entries")->fetchAll();

$eventStatusBreakdown = $pdo->query("SELECT status, COUNT(*) c FROM events GROUP BY status ORDER BY c DESC")->fetchAll();

$eventTrend = [];
foreach ($eventRows as $event) {
    $month = $event['start_date'] ? date('Y-m', strtotime($event['start_date'])) : 'Unknown';
    if (!isset($eventTrend[$month])) {
        $eventTrend[$month] = 0;
    }
    $eventTrend[$month]++;
}
ksort($eventTrend);
$eventTrendData = [];
foreach ($eventTrend as $month => $count) {
    $eventTrendData[] = ['label' => date('M Y', strtotime($month . '-01')), 'value' => $count];
}

$attendanceByEvent = [];
foreach ($eventRows as $event) {
    $eventId = (int)$event['event_id'];
    $registered = 0;
    $attended = 0;
    foreach ($registrationRows as $reg) {
        if ((int)$reg['event_id'] === $eventId) {
            $registered++;
            if ((string)$reg['status'] === 'Attended') {
                $attended++;
            }
        }
    }
    if ($registered === 0 && $attended === 0) {
        $attendanceCount = 0;
        foreach ($attendanceRows as $att) {
            if ((int)$att['event_id'] === $eventId) {
                $attendanceCount++;
            }
        }
        $attended = $attendanceCount;
        $registered = max($registered, $attendanceCount);
    }
    $attendanceByEvent[] = [
        'label' => $event['event_name'],
        'registered' => $registered,
        'attended' => $attended,
    ];
}

$budgetByEvent = [];
foreach ($eventRows as $event) {
    $eventId = (int)$event['event_id'];
    $allocated = 0.0;
    $expense = 0.0;
    foreach ($budgetRows as $row) {
        if ((int)$row['event_id'] !== $eventId) {
            continue;
        }
        if ((string)$row['entry_type'] === 'Budget') {
            $allocated += (float)$row['amount'];
        } elseif ((string)$row['entry_type'] === 'Expense') {
            $expense += (float)$row['amount'];
        }
    }
    $budgetByEvent[] = [
        'label' => $event['event_name'],
        'allocated' => $allocated,
        'expense' => $expense,
    ];
}

$feedbackByEvent = [];
foreach ($eventRows as $event) {
    $eventId = (int)$event['event_id'];
    $ratings = [];
    $count = 0;
    foreach ($feedbackRows as $feedback) {
        if ((int)$feedback['event_id'] === $eventId) {
            $ratings[] = (float)$feedback['rating'];
            $count++;
        }
    }
    $avgRating = $count > 0 ? round(array_sum($ratings) / $count, 1) : 0;
    $feedbackByEvent[] = [
        'label' => $event['event_name'],
        'avg_rating' => $avgRating,
        'count' => $count,
    ];
}

$sentimentCounts = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
foreach ($feedbackRows as $feedback) {
    $sentiment = (string)($feedback['sentiment'] ?? '');
    if (isset($sentimentCounts[$sentiment])) {
        $sentimentCounts[$sentiment]++;
    }
}

$feedbackThemes = extractFeedbackThemes($feedbackRows);
$statusCounts = ['Planned' => 0, 'Ongoing' => 0, 'Completed' => 0, 'Cancelled' => 0];
foreach ($eventRows as $event) {
    $status = (string)($event['status'] ?? 'Planned');
    if (isset($statusCounts[$status])) {
        $statusCounts[$status]++;
    }
}

if (empty($eventStatusBreakdown)) {
    $eventStatusBreakdown = [];
}

$pageTitle = 'Dashboard';
$breadcrumbParent = 'School Event';
$breadcrumbCurrent = 'Dashboard';
$activeModule = 'dashboard';
include __DIR__ . '/includes/header.php';
?>

<div class="hero-panel">
    <div>
        <div class="hero-eyebrow">School Event Dashboard</div>
        <h1>Welcome back, <?= htmlspecialchars(explode(' ', currentUser()['full_name'])[0]) ?></h1>
        <p>Monitor school events, participant registrations, and progress from a single campus-ready dashboard.</p>
    </div>
    <a class="btn btn-primary" href="/modules/form.php?module=events">+ New Event</a>
</div>

<div class="summary-grid">
    <div class="stat-card"><div class="stat-label">Total Events</div><div class="stat-value"><?= $totalEvents ?></div><div class="stat-foot">Planned and active programs</div></div>
    <div class="stat-card"><div class="stat-label">Upcoming Events</div><div class="stat-value"><?= $upcomingEvents ?></div><div class="stat-foot">Scheduled this month</div></div>
    <div class="stat-card"><div class="stat-label">Total Registrants</div><div class="stat-value"><?= $totalRegistrants ?></div><div class="stat-foot">Participants on record</div></div>
    <div class="stat-card"><div class="stat-label">Feedback Awaiting AI Analysis</div><div class="stat-value"><?= $pendingFeedback ?></div><div class="stat-foot">Pending sentiment review</div></div>
</div>

<div class="module-grid">
    <div class="module-card"><div class="module-title">Event Planning</div><div class="module-value"><?= $totalEvents ?></div><div class="module-meta">Events created</div></div>
    <div class="module-card"><div class="module-title">Registrations</div><div class="module-value"><?= $totalRegistrants ?></div><div class="module-meta">Registered participants</div></div>
    <div class="module-card"><div class="module-title">Venue Booking</div><div class="module-value"><?= $venueBookings ?></div><div class="module-meta">Venue reservations</div></div>
    <div class="module-card"><div class="module-title">Invitations</div><div class="module-value"><?= $invitations ?></div><div class="module-meta">Outreach records</div></div>
    <div class="module-card"><div class="module-title">Attendance</div><div class="module-value"><?= $attendanceRecords ?></div><div class="module-meta">Check-in entries</div></div>
    <div class="module-card"><div class="module-title">Budget</div><div class="module-value"><?= $budgetEntries ?></div><div class="module-meta">Budget entries</div></div>
    <div class="module-card"><div class="module-title">Media</div><div class="module-value"><?= $mediaFiles ?></div><div class="module-meta">Uploaded files</div></div>
    <div class="module-card"><div class="module-title">Feedback</div><div class="module-value"><?= $pendingFeedback ?></div><div class="module-meta">Pending review items</div></div>
</div>

<div class="summary-section">
    <div class="summary-panel">
        <div class="card-meta">Event Overview</div>
        <div class="mini-chart">
            <?php
            $maxValue = max(1, max($totalEvents, $upcomingEvents, $totalRegistrants));
            $bars = [
                ['label' => 'Events', 'value' => $totalEvents],
                ['label' => 'Upcoming', 'value' => $upcomingEvents],
                ['label' => 'Registrants', 'value' => $totalRegistrants],
            ];
            foreach ($bars as $bar):
                $height = max(24, (int)round(($bar['value'] / $maxValue) * 100));
            ?>
            <div class="chart-row">
                <span><?= htmlspecialchars($bar['label']) ?></span>
                <div class="chart-track">
                    <div class="chart-fill" style="height: <?= $height ?>%"></div>
                </div>
                <strong><?= (int)$bar['value'] ?></strong>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="card-meta">Status Breakdown</div>
        <div class="mini-chart compact">
            <?php foreach ($eventStatusBreakdown as $item): ?>
                <div class="chart-row">
                    <span><?= htmlspecialchars($item['status']) ?></span>
                    <div class="chart-track">
                        <div class="chart-fill alt" style="height: <?= max(18, (int)round(($item['c'] / max(1, count($eventStatusBreakdown) > 0 ? max(array_column($eventStatusBreakdown, 'c')) : 1)) * 100)) ?>%"></div>
                    </div>
                    <strong><?= (int)$item['c'] ?></strong>
                </div>
            <?php endforeach; ?>
            <?php if (empty($eventStatusBreakdown)): ?>
                <div class="empty-state compact">No event status data yet.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="summary-panel">
        <div class="card-meta">Recent Events</div>
    <table class="data-table">
        <thead>
            <tr><th>Event Name</th><th>Type</th><th>Start Date</th><th>Location</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($recentEvents as $ev): ?>
            <tr>
                <td><?= htmlspecialchars($ev['event_name']) ?></td>
                <td><?= htmlspecialchars($ev['event_type']) ?></td>
                <td><?= htmlspecialchars($ev['start_date']) ?></td>
                <td><?= htmlspecialchars($ev['location']) ?></td>
                <td><?= renderBadge($ev['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($recentEvents)): ?>
            <tr><td colspan="5" class="empty-state">No events yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<div class="analytics-section">
    <div class="analytics-header">
        <h2>Analytics</h2>
        <p>Data-driven insights for future school events.</p>
    </div>

    <div class="analytics-grid">
        <div class="analytics-card">
            <div class="card-meta">Event Activity Trends</div>
            <div class="chart-shell">
                <svg viewBox="0 0 320 140" class="chart-svg" role="img" aria-label="Event activity trends">
                    <line x1="20" y1="120" x2="300" y2="120" class="chart-axis" />
                    <line x1="20" y1="20" x2="20" y2="120" class="chart-axis" />
                    <?php
                    $trendValues = array_values(array_map(fn($item) => (int)$item['value'], $eventTrendData));
                    $trendMax = max(1, max($trendValues));
                    $trendPoints = [];
                    foreach ($eventTrendData as $index => $item) {
                        $x = 30 + ($index * 48);
                        $y = 120 - (($item['value'] / $trendMax) * 90);
                        $trendPoints[] = [$x, $y];
                        echo '<circle cx="' . $x . '" cy="' . $y . '" r="4" class="chart-dot" />';
                    }
                    for ($i = 0; $i < count($trendPoints) - 1; $i++) {
                        $from = $trendPoints[$i];
                        $to = $trendPoints[$i + 1];
                        echo '<line x1="' . $from[0] . '" y1="' . $from[1] . '" x2="' . $to[0] . '" y2="' . $to[1] . '" class="chart-line" />';
                    }
                    ?>
                </svg>
                <div class="chart-labels">
                    <?php foreach ($eventTrendData as $item): ?><span><?= htmlspecialchars($item['label']) ?></span><?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Attendance Analytics</div>
            <div class="chart-shell bars">
                <?php foreach ($attendanceByEvent as $item): ?>
                    <div class="bar-group">
                        <div class="bar-label"><?= htmlspecialchars(substr($item['label'], 0, 18)) ?></div>
                        <div class="bar-row">
                            <div class="bar registered" style="height: <?= max(12, (int)round(($item['registered'] / max(1, max(array_column($attendanceByEvent, 'registered')))) * 70)) ?>px"></div>
                            <div class="bar attended" style="height: <?= max(12, (int)round(($item['attended'] / max(1, max(array_column($attendanceByEvent, 'attended')))) * 70)) ?>px"></div>
                        </div>
                        <div class="bar-values"><span><?= (int)$item['registered'] ?></span><span><?= (int)$item['attended'] ?></span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Budget Utilization</div>
            <div class="chart-shell bars budget">
                <?php foreach ($budgetByEvent as $item): ?>
                    <div class="bar-group">
                        <div class="bar-label"><?= htmlspecialchars(substr($item['label'], 0, 18)) ?></div>
                        <div class="bar-row">
                            <div class="bar registered" style="height: <?= max(12, (int)round(($item['allocated'] / max(1, max(array_column($budgetByEvent, 'allocated')))) * 70)) ?>px"></div>
                            <div class="bar attended" style="height: <?= max(12, (int)round(($item['expense'] / max(1, max(array_column($budgetByEvent, 'allocated')))) * 70)) ?>px"></div>
                        </div>
                        <div class="bar-values"><span>Budget</span><span>Expense</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Feedback Ratings</div>
            <div class="chart-shell bars">
                <?php foreach ($feedbackByEvent as $item): ?>
                    <div class="bar-group">
                        <div class="bar-label"><?= htmlspecialchars(substr($item['label'], 0, 18)) ?></div>
                        <div class="bar-row single">
                            <div class="bar attended" style="height: <?= max(10, (int)round(($item['avg_rating'] / 5) * 70)) ?>px"></div>
                        </div>
                        <div class="bar-values"><span><?= number_format($item['avg_rating'], 1) ?>/5</span><span><?= (int)$item['count'] ?> FB</span></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Sentiment Analysis</div>
            <div class="chart-shell donut-shell">
                <svg viewBox="0 0 120 120" class="donut-chart" role="img" aria-label="Sentiment distribution">
                    <circle cx="60" cy="60" r="40" class="donut-base" />
                    <?php
                    $totalSentiment = max(1, array_sum($sentimentCounts));
                    $offset = 0;
                    $segments = [
                        ['label' => 'Positive', 'value' => (int)$sentimentCounts['Positive'], 'class' => 'sentiment-positive'],
                        ['label' => 'Neutral', 'value' => (int)$sentimentCounts['Neutral'], 'class' => 'sentiment-neutral'],
                        ['label' => 'Negative', 'value' => (int)$sentimentCounts['Negative'], 'class' => 'sentiment-negative'],
                    ];
                    foreach ($segments as $segment) {
                        $length = ($segment['value'] / $totalSentiment) * 251;
                        $strokeDasharray = $length . ' 251';
                        echo '<circle cx="60" cy="60" r="40" class="donut-segment ' . $segment['class'] . '" style="stroke-dasharray: ' . $strokeDasharray . '; stroke-dashoffset: -' . $offset . ';" />';
                        $offset += $length;
                    }
                    ?>
                </svg>
                <div class="legend-list">
                    <?php foreach ($segments as $segment): ?>
                        <div class="legend-item"><span class="legend-dot <?= $segment['class'] ?>"></span><?= htmlspecialchars($segment['label']) ?>: <?= (int)$segment['value'] ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Recurring Feedback Themes</div>
            <div class="chart-shell list-shell">
                <?php foreach ($feedbackThemes as $theme => $count): ?>
                    <div class="theme-item"><span><?= htmlspecialchars(ucwords(str_replace('_', ' ', $theme))) ?></span><strong><?= (int)$count ?></strong></div>
                <?php endforeach; ?>
                <?php if (empty($feedbackThemes)): ?>
                    <div class="empty-state compact">No feedback themes detected yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="analytics-card">
            <div class="card-meta">Event Status Summary</div>
            <div class="chart-shell donut-shell">
                <svg viewBox="0 0 120 120" class="donut-chart" role="img" aria-label="Event status summary">
                    <circle cx="60" cy="60" r="40" class="donut-base" />
                    <?php
                    $statusTotal = max(1, array_sum($statusCounts));
                    $statusOffset = 0;
                    $statusSegments = [
                        ['label' => 'Planned', 'value' => (int)$statusCounts['Planned'], 'class' => 'sentiment-positive'],
                        ['label' => 'Ongoing', 'value' => (int)$statusCounts['Ongoing'], 'class' => 'sentiment-neutral'],
                        ['label' => 'Completed', 'value' => (int)$statusCounts['Completed'], 'class' => 'sentiment-negative'],
                        ['label' => 'Cancelled', 'value' => (int)$statusCounts['Cancelled'], 'class' => 'sentiment-cancelled'],
                    ];
                    foreach ($statusSegments as $segment) {
                        $length = ($segment['value'] / $statusTotal) * 251;
                        echo '<circle cx="60" cy="60" r="40" class="donut-segment ' . $segment['class'] . '" style="stroke-dasharray: ' . $length . ' 251; stroke-dashoffset: -' . $statusOffset . ';" />';
                        $statusOffset += $length;
                    }
                    ?>
                </svg>
                <div class="legend-list">
                    <?php foreach ($statusSegments as $segment): ?>
                        <div class="legend-item"><span class="legend-dot <?= $segment['class'] ?>"></span><?= htmlspecialchars($segment['label']) ?>: <?= (int)$segment['value'] ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
