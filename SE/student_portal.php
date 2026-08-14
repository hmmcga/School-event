<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['student', 'organizer', 'admin']);

$pdo = getDB();
$user = currentUser();
$errors = [];
$success = [];

$events = $pdo->query("SELECT * FROM events WHERE status != 'Cancelled' ORDER BY start_date ASC")->fetchAll();

$studentId = $user['student_id'] ?? '';
$participantName = $user['full_name'] ?? '';
$courseProgram = '';

if ($studentId !== '') {
    $stmt = $pdo->prepare('SELECT course_program FROM users WHERE user_id = :id');
    $stmt->execute(['id' => $user['user_id']]);
    $courseRow = $stmt->fetch();
    if ($courseRow) {
        $courseProgram = (string)($courseRow['course_program'] ?? '');
    }
}

$registrations = [];
if ($studentId !== '') {
    $stmt = $pdo->prepare(
        'SELECT r.*, e.event_name, e.start_date, e.location, e.status AS event_status
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         WHERE r.student_id = :student_id
         ORDER BY e.start_date DESC'
    );
    $stmt->execute(['student_id' => $studentId]);
    $registrations = $stmt->fetchAll();
} elseif ($participantName !== '') {
    $stmt = $pdo->prepare(
        'SELECT r.*, e.event_name, e.start_date, e.location, e.status AS event_status
         FROM registrations r
         JOIN events e ON e.event_id = r.event_id
         WHERE r.participant_name = :name
         ORDER BY e.start_date DESC'
    );
    $stmt->execute(['name' => $participantName]);
    $registrations = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid session token. Please try again.';
    } else {
        $action = $_POST['action'];
        if ($action === 'register') {
            $eventId = (int)($_POST['event_id'] ?? 0);
            $yearLevel = trim($_POST['year_level'] ?? '');
            if ($eventId <= 0) {
                $errors[] = 'Please select an event.';
            } else {
                try {
                    $stmt = $pdo->prepare(
                        'INSERT INTO registrations (event_id, participant_name, student_id, course_program, year_level, status) VALUES (:event_id, :participant_name, :student_id, :course_program, :year_level, :status)'
                    );
                    $stmt->execute([
                        'event_id' => $eventId,
                        'participant_name' => $participantName,
                        'student_id' => $studentId,
                        'course_program' => $courseProgram,
                        'year_level' => $yearLevel,
                        'status' => 'Registered',
                    ]);
                    $success[] = 'Registration submitted successfully.';
                } catch (PDOException $e) {
                    if (str_contains($e->getMessage(), 'UNIQUE') || str_contains($e->getMessage(), 'unique')) {
                        $errors[] = 'You are already registered for that event.';
                    } else {
                        $errors[] = 'Registration failed. Please try again.';
                    }
                }
            }
        } elseif ($action === 'feedback') {
            $eventId = (int)($_POST['feedback_event_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $comments = trim($_POST['comments'] ?? '');
            if ($eventId <= 0 || $rating < 1 || $rating > 5 || $comments === '') {
                $errors[] = 'Please provide a rating and comments for your feedback.';
            } else {
                $sentiment = 'Neutral';
                $commentLower = strtolower($comments);
                if (str_contains($commentLower, 'good') || str_contains($commentLower, 'excellent') || str_contains($commentLower, 'great') || str_contains($commentLower, 'well')) {
                    $sentiment = 'Positive';
                } elseif (str_contains($commentLower, 'bad') || str_contains($commentLower, 'poor') || str_contains($commentLower, 'late') || str_contains($commentLower, 'disorganized')) {
                    $sentiment = 'Negative';
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO feedback_entries (event_id, participant_name, rating, comments, sentiment) VALUES (:event_id, :participant_name, :rating, :comments, :sentiment)'
                );
                $stmt->execute([
                    'event_id' => $eventId,
                    'participant_name' => $participantName,
                    'rating' => $rating,
                    'comments' => $comments,
                    'sentiment' => $sentiment,
                ]);
                $success[] = 'Feedback submitted and sent to the analytics module.';
            }
        }
    }

    if (empty($errors) && empty($success) === false) {
        $registrations = [];
        if ($studentId !== '') {
            $stmt = $pdo->prepare(
                'SELECT r.*, e.event_name, e.start_date, e.location, e.status AS event_status
                 FROM registrations r
                 JOIN events e ON e.event_id = r.event_id
                 WHERE r.student_id = :student_id
                 ORDER BY e.start_date DESC'
            );
            $stmt->execute(['student_id' => $studentId]);
            $registrations = $stmt->fetchAll();
        }
    }
}

$pageTitle = 'Student Portal';
$breadcrumbParent = 'School Event';
$breadcrumbCurrent = 'Student Portal';
$activeModule = 'student';
include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-title">
        <div class="page-title-icon">🎓</div>
        <div>
            <h1>Student Portal</h1>
            <p>Register for events, check attendance, and submit feedback.</p>
        </div>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <?php foreach ($errors as $err): ?><div><?= htmlspecialchars($err) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?php foreach ($success as $msg): ?><div><?= htmlspecialchars($msg) ?></div><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="summary-grid">
    <div class="stat-card">
        <div class="stat-label">My Registrations</div>
        <div class="stat-value"><?= count($registrations) ?></div>
        <div class="stat-foot">Events you are currently registered for</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Available Events</div>
        <div class="stat-value"><?= count($events) ?></div>
        <div class="stat-foot">Open for student enrollment</div>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-meta">Register for an Event</div>
    <div style="padding:20px;">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="register">
            <div class="form-group">
                <label for="event_id">Event</label>
                <select name="event_id" id="event_id" required>
                    <option value="">-- Select an event --</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?= (int)$event['event_id'] ?>"><?= htmlspecialchars($event['event_name']) ?> — <?= htmlspecialchars($event['location'] ?? 'TBA') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="year_level">Year Level</label>
                <select name="year_level" id="year_level">
                    <option value="1st Year">1st Year</option>
                    <option value="2nd Year">2nd Year</option>
                    <option value="3rd Year">3rd Year</option>
                    <option value="4th Year">4th Year</option>
                </select>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Register</button>
            </div>
        </form>
    </div>
</div>

<div class="card" style="margin-bottom:24px;">
    <div class="card-meta">My Current Registrations</div>
    <?php if (empty($registrations)): ?>
        <div class="empty-state">You have not registered for any events yet.</div>
    <?php else: ?>
    <table class="data-table">
        <thead>
            <tr><th>Event</th><th>Start Date</th><th>Location</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php foreach ($registrations as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['event_name']) ?></td>
                <td><?= htmlspecialchars($row['start_date']) ?></td>
                <td><?= htmlspecialchars($row['location'] ?? 'TBA') ?></td>
                <td><?= renderBadge($row['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-meta">Submit Feedback After the Event</div>
    <div style="padding:20px;">
        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="feedback">
            <div class="form-group">
                <label for="feedback_event_id">Event</label>
                <select name="feedback_event_id" id="feedback_event_id" required>
                    <option value="">-- Select event --</option>
                    <?php foreach ($registrations as $reg): ?>
                        <option value="<?= (int)$reg['event_id'] ?>"><?= htmlspecialchars($reg['event_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="rating">Rating (1-5)</label>
                <input type="number" min="1" max="5" name="rating" id="rating" required>
            </div>
            <div class="form-group">
                <label for="comments">Comments</label>
                <textarea name="comments" id="comments" required></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Submit Feedback</button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
