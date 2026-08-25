<?php
declare(strict_types=1);

/**
 * Run once after pulling Phase 1 updates on an existing database.
 * CLI: C:\xampp\php\php.exe database\run_migration.php
 * Web (local only): http://localhost/LMS/database/run_migration.php
 */

require_once dirname(__DIR__) . '/includes/database.php';

header('Content-Type: text/plain; charset=utf-8');

$steps = [];

try {
    $cols = db()->query("SHOW COLUMNS FROM students LIKE 'password_hash'")->fetchAll();
    if (!$cols) {
        db()->exec('ALTER TABLE students ADD COLUMN password_hash VARCHAR(255) DEFAULT NULL AFTER line_id');
        $steps[] = 'Added students.password_hash';
    } else {
        $steps[] = 'students.password_hash already exists';
    }
} catch (Throwable $e) {
    $steps[] = 'password_hash: ' . $e->getMessage();
}

try {
    db()->exec('DELETE s1 FROM students s1 INNER JOIN students s2 ON s1.phone = s2.phone AND s1.phone IS NOT NULL AND s1.phone != \'\' AND s1.id > s2.id');
    db()->exec('ALTER TABLE students ADD UNIQUE KEY uk_students_phone (phone)');
    $steps[] = 'Added unique index on students.phone';
} catch (Throwable $e) {
    $steps[] = 'unique phone: ' . $e->getMessage();
}

try {
    db()->exec('ALTER TABLE enrollments ADD UNIQUE KEY uk_enrollment_student_course (student_id, course_id)');
    $steps[] = 'Added unique enrollment index';
} catch (Throwable $e) {
    $steps[] = 'enrollment unique: ' . $e->getMessage();
}

echo "Wenxin LMS migration complete\n\n";
foreach ($steps as $step) {
    echo "- {$step}\n";
}
