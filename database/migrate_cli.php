<?php

declare(strict_types=1);

/**
 * รัน migration ผ่าน SSH/CLI (ไม่ต้องเปิดเบราว์เซอร์)
 *
 *   cd /path/to/LMS
 *   php database/migrate_cli.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "ใช้ผ่าน CLI เท่านั้น: php database/migrate_cli.php\n";
    echo "หรือเปิดในเบราว์เซอร์: /database/run_all_migrations.php\n";
    exit(1);
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/schema.php';

echo "Wenxin LMS — migration (CLI)\n";
echo str_repeat('=', 40) . "\n\n";

$missingBefore = missingDatabaseTables();
if ($missingBefore) {
    echo 'ก่อนรัน ขาดตาราง: ' . implode(', ', $missingBefore) . "\n\n";
} else {
    echo "ก่อนรัน: schema ครบแล้ว (จะรัน migration ซ้ำได้อย่างปลอดภัย)\n\n";
}

$files = [
    __DIR__ . '/run_migration_phase3.php',
    __DIR__ . '/run_migration_phase4.php',
    __DIR__ . '/run_migration_phase5.php',
    __DIR__ . '/run_migration_phase6.php',
    __DIR__ . '/run_migration_phase7.php',
    __DIR__ . '/run_migration_phase8.php',
    __DIR__ . '/run_migration_phase9.php',
    __DIR__ . '/run_migration_phase10.php',
    __DIR__ . '/run_migration_phase11.php',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        echo "SKIP " . basename($file) . " (not found)\n\n";
        continue;
    }
    echo '=== ' . basename($file) . " ===\n";
    ob_start();
    include $file;
    echo ob_get_clean();
    echo "\n";
}

$missingAfter = missingDatabaseTables();
echo str_repeat('=', 40) . "\n";
if ($missingAfter) {
    echo "ยังขาดตาราง: " . implode(', ', $missingAfter) . "\n";
    exit(1);
}

echo "สำเร็จ — schema พร้อมใช้งาน (course_sessions, session_bookings)\n";
exit(0);
