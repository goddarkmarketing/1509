<?php

declare(strict_types=1);

require_once __DIR__ . '/database.php';

/** @return list<string> */
function requiredDatabaseTables(): array
{
    return [
        'courses',
        'students',
        'enrollments',
        'payments',
        'payment_items',
        'course_sessions',
        'session_bookings',
        'site_settings',
    ];
}

function databaseTableExists(string $table): bool
{
    static $cache = [];
    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    try {
        $stmt = db()->prepare('
            SELECT COUNT(*) FROM information_schema.tables
            WHERE table_schema = DATABASE() AND table_name = ?
        ');
        $stmt->execute([$table]);
        $cache[$table] = (int) $stmt->fetchColumn() > 0;
    } catch (Throwable $e) {
        $cache[$table] = false;
    }

    return $cache[$table];
}

/** @return list<string> */
function missingDatabaseTables(): array
{
    $missing = [];
    foreach (requiredDatabaseTables() as $table) {
        if (!databaseTableExists($table)) {
            $missing[] = $table;
        }
    }

    return $missing;
}

/** @param list<string> $missing */
function migrationHintMessage(array $missing): string
{
    if (!$missing) {
        return '';
    }

    $list = implode(', ', $missing);
    return 'ฐานข้อมูลยังไม่ครบตาราง: ' . $list
        . ' — เปิด /database/run_all_migrations.php หรือ run_migration_phase9.php บนเซิร์ฟเวอร์';
}

function databaseSchemaReady(): bool
{
    return missingDatabaseTables() === [];
}
