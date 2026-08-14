<?php
/**
 * Database connection
 * Smart School Event Management System
 *
 * Uses PostgreSQL when available and falls back to a local SQLite database
 * so the prototype can run even if PostgreSQL is not installed.
 */

const DB_HOST = 'localhost';
const DB_PORT = '5432';
const DB_NAME = 'sms_event';
const DB_USER = 'postgres';
const DB_PASS = 'postgres'; // change for your environment
const DB_SQLITE_PATH = __DIR__ . '/../database/sms_event.sqlite';

function initializeSqliteDatabase(string $path): void
{
    if (file_exists($path)) {
        return;
    }

    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException('Unable to create database directory: ' . $dir);
    }

    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $seedHash = password_hash('Password123!', PASSWORD_DEFAULT);

    $schema = <<<SQL
    CREATE TABLE users (
        user_id INTEGER PRIMARY KEY AUTOINCREMENT,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(150) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'student' CHECK (role IN ('student','organizer','admin')),
        student_id VARCHAR(30),
        course_program VARCHAR(100),
        is_active BOOLEAN NOT NULL DEFAULT 1,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE events (
        event_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_name VARCHAR(150) NOT NULL,
        description TEXT,
        event_type VARCHAR(50) NOT NULL DEFAULT 'Seminar' CHECK (event_type IN ('Orientation','Seminar','Cultural','Recognition','Organization-Led','Sports','Other')),
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        location VARCHAR(150),
        organizer VARCHAR(150),
        status VARCHAR(20) NOT NULL DEFAULT 'Planned' CHECK (status IN ('Planned','Ongoing','Completed','Cancelled')),
        created_by INTEGER REFERENCES users(user_id),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (end_date >= start_date)
    );

    CREATE TABLE registrations (
        registration_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        participant_name VARCHAR(150) NOT NULL,
        student_id VARCHAR(30),
        course_program VARCHAR(100),
        year_level VARCHAR(20),
        status VARCHAR(20) NOT NULL DEFAULT 'Registered' CHECK (status IN ('Registered','Attended','Cancelled')),
        registered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE (event_id, student_id)
    );

    CREATE TABLE venue_bookings (
        booking_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        venue_name VARCHAR(150) NOT NULL,
        resource_name VARCHAR(150),
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Reserved' CHECK (status IN ('Reserved','Confirmed','Conflict','Cancelled')),
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CHECK (end_time > start_time)
    );

    CREATE TABLE invitations (
        invitation_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        target_group VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        channel VARCHAR(20) NOT NULL DEFAULT 'Email' CHECK (channel IN ('Email','SMS','In-App')),
        status VARCHAR(20) NOT NULL DEFAULT 'Draft' CHECK (status IN ('Draft','Sent','Failed')),
        sent_at TIMESTAMP,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE attendance_records (
        attendance_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        registration_id INTEGER REFERENCES registrations(registration_id) ON DELETE SET NULL,
        check_in_time TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        method VARCHAR(20) NOT NULL DEFAULT 'QR Code' CHECK (method IN ('QR Code','Manual','System')),
        status VARCHAR(20) NOT NULL DEFAULT 'Present' CHECK (status IN ('Present','Absent','Late'))
    );

    CREATE TABLE budget_entries (
        entry_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        category VARCHAR(100) NOT NULL,
        description VARCHAR(255),
        entry_type VARCHAR(20) NOT NULL DEFAULT 'Budget' CHECK (entry_type IN ('Budget','Expense')),
        amount NUMERIC(12,2) NOT NULL DEFAULT 0,
        entry_date DATE NOT NULL DEFAULT CURRENT_DATE
    );

    CREATE TABLE program_segments (
        segment_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        segment_name VARCHAR(150) NOT NULL,
        scheduled_start TIME NOT NULL,
        scheduled_end TIME NOT NULL,
        actual_start TIME,
        actual_end TIME,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending' CHECK (status IN ('Pending','Ongoing','Completed','Delayed')),
        sort_order INTEGER NOT NULL DEFAULT 0
    );

    CREATE TABLE media_files (
        media_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        file_name VARCHAR(200) NOT NULL,
        file_type VARCHAR(20) NOT NULL DEFAULT 'Photo' CHECK (file_type IN ('Photo','Video','Document','Report')),
        file_path VARCHAR(255) NOT NULL,
        uploaded_by VARCHAR(150),
        uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE feedback_entries (
        feedback_id INTEGER PRIMARY KEY AUTOINCREMENT,
        event_id INTEGER NOT NULL REFERENCES events(event_id) ON DELETE CASCADE,
        participant_name VARCHAR(150) NOT NULL,
        rating SMALLINT CHECK (rating BETWEEN 1 AND 5),
        comments TEXT,
        sentiment VARCHAR(20) CHECK (sentiment IN ('Positive','Neutral','Negative')),
        submitted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    );

    INSERT INTO users (full_name, email, password_hash, role, student_id, course_program) VALUES
    ('System Administrator', 'admin@saac.edu.ph', '{$seedHash}', 'admin', NULL, NULL),
    ('Carl Ephraim Garcia', 'organizer@saac.edu.ph', '{$seedHash}', 'organizer', NULL, NULL),
    ('Maria Santos', 'maria.santos@saac.edu.ph', '{$seedHash}', 'student', 'BCP-2024-0002', 'BSED'),
    ('Juan Dela Cruz', 'juan.delacruz@saac.edu.ph', '{$seedHash}', 'student', 'BCP-2024-0001', 'BSIT');

    INSERT INTO events (event_name, description, event_type, start_date, end_date, location, organizer, status, created_by) VALUES
    ('Freshmen Orientation 2026', 'Welcome program for incoming first-year students.', 'Orientation', '2026-08-15', '2026-08-15', 'Main Gymnasium', 'Student Affairs Office', 'Planned', 2),
    ('IT Career Seminar', 'Industry talk on emerging careers in Information Technology.', 'Seminar', '2026-08-22', '2026-08-22', 'Auditorium', 'CCS Student Council', 'Planned', 2),
    ('Foundation Day Cultural Show', 'Annual cultural stage performances celebrating the school foundation.', 'Cultural', '2026-09-05', '2026-09-06', 'Open Grounds', 'Cultural Affairs Committee', 'Planned', 2);

    INSERT INTO registrations (event_id, participant_name, student_id, course_program, year_level, status) VALUES
    (1, 'Juan Dela Cruz', 'BCP-2024-0001', 'BSIT', '3rd Year', 'Registered'),
    (1, 'Maria Santos', 'BCP-2024-0002', 'BSED', '2nd Year', 'Registered'),
    (2, 'Juan Dela Cruz', 'BCP-2024-0001', 'BSIT', '3rd Year', 'Registered');

    INSERT INTO venue_bookings (event_id, venue_name, resource_name, booking_date, start_time, end_time, status) VALUES
    (1, 'Main Gymnasium', 'Sound System, Projector', '2026-08-15', '08:00', '12:00', 'Confirmed'),
    (2, 'Auditorium', 'Microphones', '2026-08-22', '13:00', '16:00', 'Reserved');

    INSERT INTO budget_entries (event_id, category, description, entry_type, amount, entry_date) VALUES
    (1, 'Venue', 'Gymnasium rental fee', 'Budget', 5000.00, '2026-07-01'),
    (1, 'Supplies', 'Tarpaulin and giveaways', 'Expense', 3200.00, '2026-08-01'),
    (2, 'Honorarium', 'Guest speaker fee', 'Budget', 8000.00, '2026-07-15');

    INSERT INTO program_segments (event_id, segment_name, scheduled_start, scheduled_end, status, sort_order) VALUES
    (1, 'Opening Remarks', '08:00', '08:15', 'Pending', 1),
    (1, 'Keynote Address', '08:15', '09:00', 'Pending', 2),
    (1, 'Icebreaker Activities', '09:00', '10:30', 'Pending', 3);

    INSERT INTO feedback_entries (event_id, participant_name, rating, comments, sentiment) VALUES
    (1, 'Juan Dela Cruz', 5, 'Well organized and engaging program.', 'Positive');
    SQL;

    $pdo->exec($schema);
}

function getDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        if (in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            try {
                $dsn = 'pgsql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
                    throw $e;
                }
                initializeSqliteDatabase(DB_SQLITE_PATH);
                $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
            }
        } else {
            initializeSqliteDatabase(DB_SQLITE_PATH);
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
        }
    }

    return $pdo;
}
