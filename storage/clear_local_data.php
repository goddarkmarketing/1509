<?php

declare(strict_types=1);

/**
 * เคลียร์ข้อมูลผู้เรียน การชำระเงิน และคอร์ส Live บน localhost เท่านั้น
 *
 * CLI:  php storage/clear_local_data.php
 * Web:  http://localhost/LMS/storage/clear_local_data.php?confirm=1
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/booking.php';

header('Content-Type: text/plain; charset=utf-8');

function localCleanupAllowed(): bool
{
    $blocked = ['wenxinchinese.online'];
    foreach ($blocked as $needle) {
        if (str_contains(strtolower(APP_URL), $needle)) {
            return false;
        }
        if (str_contains(strtolower((string) getSetting('site_url', '')), $needle)) {
            return false;
        }
    }

    $dbHost = strtolower(DB_HOST);
    if (!in_array($dbHost, ['localhost', '127.0.0.1'], true)) {
        return false;
    }

    if (php_sapi_name() === 'cli') {
        return true;
    }

    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return str_contains($host, 'localhost')
        || str_contains($host, '127.0.0.1')
        || str_contains($host, '.local');
}

function tableExists(string $table): bool
{
    try {
        $stmt = db()->prepare('SHOW TABLES LIKE ?');
        $stmt->execute([$table]);

        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function deleteAll(string $table): int
{
    if (!tableExists($table)) {
        return 0;
    }

    return (int) db()->exec('DELETE FROM `' . str_replace('`', '', $table) . '`');
}

function countRows(string $table): int
{
    if (!tableExists($table)) {
        return 0;
    }

    return (int) db()->query('SELECT COUNT(*) FROM `' . str_replace('`', '', $table) . '`')->fetchColumn();
}

if (!localCleanupAllowed()) {
    http_response_code(403);
    echo "Blocked: this script only runs on localhost.\n";
    exit(1);
}

$isCli = php_sapi_name() === 'cli';
if (!$isCli && (($_GET['confirm'] ?? '') !== '1')) {
    echo "Add ?confirm=1 to run cleanup on localhost.\n";
    exit(0);
}

$summary = [];

try {
    db()->beginTransaction();

    $summary['session_bookings'] = deleteAll('session_bookings');
    $summary['payment_items'] = deleteAll('payment_items');
    $summary['payments'] = deleteAll('payments');
    $summary['lesson_progress'] = deleteAll('lesson_progress');
    $summary['quiz_attempts'] = deleteAll('quiz_attempts');
    $summary['certificates'] = deleteAll('certificates');
    $summary['password_reset_tokens'] = deleteAll('password_reset_tokens');
    $summary['enrollments'] = deleteAll('enrollments');
    $summary['login_attempts'] = deleteAll('login_attempts');
    $summary['students'] = deleteAll('students');
    $summary['course_sessions'] = deleteAll('course_sessions');

    $summary['live_courses_deleted'] = 0;
    if (tableExists('courses')) {
        $summary['live_courses_deleted'] = (int) db()->exec("
            DELETE FROM courses WHERE course_type IN ('live', 'hybrid')
        ");
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

$slipsRemoved = 0;
$slipDir = UPLOAD_PATH;
if (is_dir($slipDir)) {
    foreach (glob($slipDir . '/*') ?: [] as $file) {
        if (is_file($file) && @unlink($file)) {
            $slipsRemoved++;
        }
    }
}
$summary['payment_slips_removed'] = $slipsRemoved;

echo "Local data cleanup complete\n\n";
foreach ($summary as $label => $count) {
    echo sprintf("- %-24s %s\n", $label . ':', (string) $count);
}

echo "\nRemaining:\n";
echo sprintf("- students:          %d\n", countRows('students'));
echo sprintf("- payments:          %d\n", countRows('payments'));
echo sprintf("- course_sessions:   %d\n", countRows('course_sessions'));
echo sprintf("- live/hybrid courses: %d\n", (int) db()->query("SELECT COUNT(*) FROM courses WHERE course_type IN ('live','hybrid')")->fetchColumn());

echo "\nRecorded courses and admin accounts were kept.\n";
