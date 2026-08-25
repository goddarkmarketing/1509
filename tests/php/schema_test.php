<?php

declare(strict_types=1);

/**
 * Schema preflight: ตรวจว่ารัน migration ครบก่อนรัน test อื่น
 *
 * รัน: php tests/php/schema_test.php
 */

require_once dirname(__DIR__, 2) . '/includes/schema.php';
require_once __DIR__ . '/test_helpers.php';

test_reset_checks();

foreach (requiredDatabaseTables() as $table) {
    test_check("ตาราง {$table} มีอยู่", databaseTableExists($table));
}

// Phase 9 columns
$phase9Columns = [
    'courses' => ['course_type', 'zoom_url'],
    'students' => ['line_user_id'],
    'payment_items' => ['session_id'],
];

foreach ($phase9Columns as $table => $columns) {
    if (!databaseTableExists($table)) {
        continue;
    }
    foreach ($columns as $column) {
        try {
            $stmt = db()->prepare('
                SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
            ');
            $stmt->execute([$table, $column]);
            test_check("คอลัมน์ {$table}.{$column}", (int) $stmt->fetchColumn() > 0);
        } catch (Throwable $e) {
            test_check("คอลัมน์ {$table}.{$column}", false, $e->getMessage());
        }
    }
}

if (databaseTableExists('course_sessions')) {
    try {
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM information_schema.columns
            WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
        ');
        $stmt->execute(['course_sessions', 'image_url']);
        test_check('คอลัมน์ course_sessions.image_url (phase 10)', (int) $stmt->fetchColumn() > 0);
    } catch (Throwable $e) {
        test_check('คอลัมน์ course_sessions.image_url (phase 10)', false, $e->getMessage());
    }
}

$missing = missingDatabaseTables();
if ($missing) {
    test_check('schema พร้อมใช้งาน', false, migrationHintMessage($missing));
} else {
    test_check('schema พร้อมใช้งาน', true);
}

$result = test_print_summary('LMS Schema Test');
exit($result['fail'] > 0 ? 1 : 0);
